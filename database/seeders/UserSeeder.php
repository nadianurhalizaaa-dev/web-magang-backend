<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 1. Akun Admin
        User::create([
            'name'     => 'Admin Magang',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        // 2. Akun Peserta
        User::create([
            'name'     => 'Peserta Magang',
            'email'    => 'peserta@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'peserta',
        ]);

        // 3. Akun Pembimbing
        User::create([
            'name'     => 'Pembimbing Magang',
            'email'    => 'pembimbing@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'pembimbing',
        ]);
    }
}