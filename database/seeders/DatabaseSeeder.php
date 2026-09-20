<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tugas;
use App\Models\SubmisiTugas;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Bersihkan data lama
        SubmisiTugas::query()->delete();
        Tugas::query()->delete();
        User::query()->delete();

        // 1. Akun Admin
        $admin = User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        // 2. Akun Peserta
        $peserta1 = User::create([
            'name'     => 'Peserta Magang',
            'email'    => 'peserta@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'peserta',
        ]);

        $peserta2 = User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'budi@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'peserta',
        ]);

        // 3. Akun Pembimbing
        $pembimbing = User::create([
            'name'     => 'Pembimbing Magang',
            'email'    => 'pembimbing@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'pembimbing',
        ]);

        // Tugas dan Submisi dikosongkan agar Anda bisa mencobanya dari awal
    }
}