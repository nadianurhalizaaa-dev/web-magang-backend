<?php

namespace App\Http\Controllers;

use App\Models\Magang;
use App\Models\Pembimbing;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MagangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (strtolower((string) $request->user()?->role) === 'pembimbing') {
            $pesertaBimbingan = Pendaftaran::with('user')
                ->where('pembimbing_id', $request->user()->id)
                ->where('status', 'Pemagang Aktif')
                ->get()
                ->filter(fn (Pendaftaran $pendaftaran) => $pendaftaran->user !== null)
                ->map(fn (Pendaftaran $pendaftaran) => [
                    'id' => $pendaftaran->id,
                    'participant_code' => 'USER-' . $pendaftaran->user_id,
                    'participant_name' => $pendaftaran->user->name,
                    'institution' => $pendaftaran->asal_instansi,
                    'status' => 'Aktif',
                ])
                ->values();

            return response()->json($pesertaBimbingan);
        }

        return response()->json(
            Magang::with('pembimbing')->orderBy('participant_name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'participant_code' => ['required', 'string', 'max:30', 'unique:magangs,participant_code'],
            'participant_name' => ['required', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:120'],
        ]);

        if (strtolower((string) $request->user()?->role) === 'pembimbing') {
            $legacyPembimbing = Pembimbing::firstOrCreate(
                ['email' => $request->user()->email],
                ['nama' => $request->user()->name],
            );
            $validated['pembimbing_id'] = $legacyPembimbing->id;
        }

        $magang = Magang::create($validated);

        return response()->json([
            'message' => 'Data magang berhasil ditambahkan.',
            'data' => $magang,
        ], 201);
    }

    public function updateParticipantIdentity(Request $request, int $id): JsonResponse
    {
        $assignment = $this->findEditableMagangForPembimbing($request, $id);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }
        $magang = $assignment['magang'];
        $assignedRegistration = $assignment['registration'];

        $request->merge([
            'participant_code' => trim((string) $request->input('participant_code', '')),
            'participant_name' => trim((string) $request->input('participant_name', '')),
        ]);

        $rules = [
            'participant_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('magangs', 'participant_code')->ignore($magang->id),
            ],
            'participant_name' => ['required', 'string', 'max:100'],
        ];
        if ($assignedRegistration?->user) {
            $rules['participant_code'][] = Rule::unique('users', 'nim')->ignore($assignedRegistration->user->id);
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($magang, $validated, $assignedRegistration) {
            $magang->absensis()->update([
                'participant_code' => $validated['participant_code'],
                'participant_name' => $validated['participant_name'],
            ]);

            $magang->update([
                'participant_code' => $validated['participant_code'],
                'participant_name' => $validated['participant_name'],
            ]);

            if ($assignedRegistration?->user) {
                $assignedRegistration->user->update([
                    'nim' => $validated['participant_code'],
                    'name' => $validated['participant_name'],
                ]);
            }
        });

        return response()->json([
            'data' => $magang->fresh(),
            'message' => 'Identitas peserta berhasil diperbarui.',
        ]);
    }

    public function removeParticipantFromPeriod(Request $request, int $id): JsonResponse
    {
        $assignment = $this->findEditableMagangForPembimbing($request, $id);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }

        $magang = $assignment['magang'];
        $validated = $request->validate([
            'registration_id' => ['nullable', 'integer'],
        ]);
        $registration = null;

        if (! empty($validated['registration_id'])) {
            $registration = Pendaftaran::with('user')
                ->where('pembimbing_id', $request->user()->id)
                ->where('periode_id', $magang->periode_id)
                ->find($validated['registration_id']);
            $legacyName = strtolower(trim((string) $magang->participant_name));
            $registrationName = strtolower(trim((string) $registration?->user?->name));
            $namesMatch = $legacyName !== '' && $registrationName !== ''
                && ($legacyName === $registrationName
                    || str_contains($legacyName, $registrationName)
                    || str_contains($registrationName, $legacyName));

            if (! $registration || ! $registration->user
                || (trim((string) $registration->user->nim) !== trim((string) $magang->participant_code)
                    && ! $namesMatch)) {
                return response()->json([
                    'message' => 'Pendaftaran peserta tidak cocok dengan data periode.',
                ], 422);
            }
        } else {
            $registration = $assignment['registration'];
        }

        if (! $magang->periode_id) {
            return response()->json(['message' => 'Peserta ini tidak terdaftar pada periode.'], 422);
        }

        DB::transaction(function () use ($magang, $registration) {
            $magang->update(['periode_id' => null]);
            $registration?->update(['periode_id' => null]);
        });

        return response()->json([
            'message' => 'Peserta berhasil dikeluarkan dari periode. Akun dan riwayat absensi tetap tersimpan.',
        ]);
    }

    private function findEditableMagangForPembimbing(Request $request, int $id): array|JsonResponse
    {
        $magang = Magang::with('pembimbing')->findOrFail($id);
        $legacyPembimbingId = Pembimbing::whereRaw('LOWER(email) = ?', [
            strtolower(trim((string) $request->user()->email)),
        ])->value('id');
        $assignedRegistration = Pendaftaran::with('user')
            ->where('periode_id', $magang->periode_id)
            ->where('pembimbing_id', $request->user()->id)
            ->whereHas('user', fn ($query) => $query->where('nim', $magang->participant_code))
            ->first();
        $periodPembimbingIds = Pendaftaran::query()
            ->where('periode_id', $magang->periode_id)
            ->whereNotIn('status', ['Dibatalkan', 'Ditolak'])
            ->whereNotNull('pembimbing_id')
            ->distinct()
            ->pluck('pembimbing_id')
            ->map(fn ($pembimbingId) => (int) $pembimbingId);
        $isAssignedPeriod = $magang->periode_id !== null
            && $periodPembimbingIds->count() === 1
            && $periodPembimbingIds->contains((int) $request->user()->id);
        $isAssignedLegacyMagang = $legacyPembimbingId
            && (int) $magang->pembimbing_id === (int) $legacyPembimbingId;

        if (! $isAssignedLegacyMagang && ! $assignedRegistration && ! $isAssignedPeriod) {
            return response()->json([
                'message' => 'Anda hanya dapat mengelola peserta bimbingan Anda.',
            ], 403);
        }

        return [
            'magang' => $magang,
            'registration' => $assignedRegistration,
        ];
    }
}
