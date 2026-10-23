<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['nama' => 'Hendra Wijaya', 'email' => 'pemilik@tokokasir.test', 'role' => User::ROLE_PEMILIK],
            ['nama' => 'Dewi Lestari', 'email' => 'admin@tokokasir.test', 'role' => User::ROLE_ADMIN],
            ['nama' => 'Siti Aminah', 'email' => 'kasir@tokokasir.test', 'role' => User::ROLE_KASIR],
            ['nama' => 'Budi Santoso', 'email' => 'kasir2@tokokasir.test', 'role' => User::ROLE_KASIR],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user + ['password' => 'password', 'status' => true]);
        }
    }
}
