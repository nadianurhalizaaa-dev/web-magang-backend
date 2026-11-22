<?php

namespace Database\Seeders;

use App\Models\AktivitasMagang;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LandingPageActivitySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Data aktivitas contoh hanya boleh ditambahkan pada environment local atau testing.');
        }

        $admin = User::query()->where('role', 'admin')->orderBy('id')->first();
        if (! $admin) {
            throw new RuntimeException('Akun admin belum tersedia. Buat akun admin terlebih dahulu.');
        }

        $activities = [
            [
                'judul' => 'Orientasi Peserta Magang',
                'deskripsi' => 'Pengenalan lingkungan kerja, alur komunikasi, dan tujuan pembelajaran peserta magang.',
                'tanggal' => '2026-01-12',
                'kategori' => 'Orientasi',
                'lokasi' => 'PAM TECHNO',
            ],
            [
                'judul' => 'Pelatihan Dasar Pengembangan Web',
                'deskripsi' => 'Sesi pengenalan alur pengembangan web dan praktik kolaborasi menggunakan version control.',
                'tanggal' => '2026-02-09',
                'kategori' => 'Pelatihan',
                'lokasi' => 'PAM TECHNO',
            ],
            [
                'judul' => 'Presentasi Hasil Proyek Magang',
                'deskripsi' => 'Peserta mempresentasikan hasil pembelajaran dan proyek yang dikerjakan selama periode magang.',
                'tanggal' => '2026-03-16',
                'kategori' => 'Presentasi',
                'lokasi' => 'PAM TECHNO',
            ],
        ];

        foreach ($activities as $activity) {
            AktivitasMagang::firstOrCreate(
                ['judul' => $activity['judul'], 'tanggal' => $activity['tanggal']],
                [...$activity, 'user_id' => $admin->id, 'status' => 'active'],
            );
        }
    }
}
