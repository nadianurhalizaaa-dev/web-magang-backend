<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Pendaftaran;
use App\Models\Tugas;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoTimelineSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo timeline data cannot be seeded in production.');
        }

        $adminId = User::query()->where('role', 'admin')->value('id');
        if (!$adminId) {
            throw new RuntimeException('An admin user is required to create demo tasks.');
        }

        $registrations = Pendaftaran::query()
            ->with(['user', 'periode'])
            ->where('status', 'Pemagang Aktif')
            ->whereNotNull('periode_id')
            ->orderBy('periode_id')
            ->get();

        $validRegistrations = $registrations
            ->filter(fn (Pendaftaran $registration) => $registration->user && $registration->periode)
            ->values();

        if ($validRegistrations->count() !== $registrations->count()) {
            throw new RuntimeException('An active registration is missing its linked user or period.');
        }

        if ($validRegistrations->isEmpty()) {
            throw new RuntimeException('No active registrations with a linked user and period were found.');
        }

        $attendanceCreated = 0;
        $tasksCreated = 0;

        DB::transaction(function () use ($validRegistrations, $adminId, &$attendanceCreated, &$tasksCreated): void {
            foreach ($validRegistrations as $registration) {
                $user = $registration->user;
                $period = $registration->periode;
                $participantCode = 'USER-' . $user->id;
                $start = CarbonImmutable::parse($period->tanggal_mulai)->startOfDay();
                $end = CarbonImmutable::parse($period->tanggal_selesai)->startOfDay();

                for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                    if ($date->isWeekend()) {
                        continue;
                    }

                    $dateValue = $date->toDateString();
                    $attendanceExists = Absensi::query()
                        ->whereDate('tanggal', $dateValue)
                        ->where(function ($query) use ($user, $participantCode): void {
                            $query->where('user_id', $user->id)
                                ->orWhere('participant_code', $participantCode);
                        })
                        ->exists();

                    if ($attendanceExists) {
                        continue;
                    }

                    $seed = hexdec(substr(hash('sha256', $user->id . '|' . $dateValue), 0, 7));
                    $statusRoll = $seed % 100;
                    $status = $statusRoll < 86 ? 'Hadir' : ($statusRoll < 93 ? 'Izin' : 'Sakit');
                    $checkInMinutes = 450 + ($seed % 91);
                    $checkOutMinutes = 960 + (($seed >> 8) % 91);

                    Absensi::query()->create([
                        'user_id' => $user->id,
                        'magang_id' => $user->magang_id,
                        'participant_code' => $participantCode,
                        'participant_name' => $user->name,
                        'institution' => $registration->asal_instansi,
                        'tanggal' => $dateValue,
                        'jam_masuk' => $status === 'Hadir'
                            ? sprintf('%02d:%02d:00', intdiv($checkInMinutes, 60), $checkInMinutes % 60)
                            : null,
                        'jam_pulang' => $status === 'Hadir'
                            ? sprintf('%02d:%02d:00', intdiv($checkOutMinutes, 60), $checkOutMinutes % 60)
                            : null,
                        'status' => $status,
                        'approval_status' => $status === 'Izin' ? 'approved' : null,
                        'keterangan' => 'Data demo dashboard' . ($status === 'Hadir' ? '' : ' - ' . $status),
                    ]);

                    $attendanceCreated++;
                }

                for ($month = $start->startOfMonth(); $month->lte($end); $month = $month->addMonth()) {
                    $deadline = $month->endOfMonth();
                    if ($deadline->gt($end)) {
                        $deadline = $end;
                    }

                    while ($deadline->gte($start) && $deadline->isWeekend()) {
                        $deadline = $deadline->subDay();
                    }

                    if ($deadline->lt($start)) {
                        $deadline = $start;
                        while ($deadline->lte($end) && $deadline->isWeekend()) {
                            $deadline = $deadline->addDay();
                        }
                    }

                    if ($deadline->lt($start) || $deadline->gt($end)) {
                        continue;
                    }

                    $monthLabel = $month->format('Y-m');
                    $title = sprintf('[DEMO][P%s] %s - Tugas %s', $period->id, $period->nama_periode, $monthLabel);
                    $task = Tugas::query()->firstOrCreate(
                        ['judul' => $title],
                        [
                            'deskripsi' => sprintf(
                                'Tugas contoh untuk pratinjau periode %s. Data ini dibuat otomatis dan tidak ditujukan untuk penilaian nyata.',
                                $period->nama_periode
                            ),
                            'kategori' => 'Individu',
                            'deadline' => $deadline->setTime(23, 59),
                            'created_by' => $adminId,
                        ]
                    );

                    if ($task->wasRecentlyCreated) {
                        $tasksCreated++;
                    }
                }
            }
        });

        $this->command?->info('Peserta aktif diproses: ' . $validRegistrations->count());
        $this->command?->info('Absensi demo baru: ' . $attendanceCreated);
        $this->command?->info('Tugas demo baru: ' . $tasksCreated);
    }
}
