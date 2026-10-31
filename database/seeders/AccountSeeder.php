<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin@clover.id', 'Admin Clover', 'admin12345', 'admin', '081234567890'],
            ['user@clover.id', 'Pelanggan Demo', 'user12345', 'user', '081298765432'],
        ];
        foreach ($accounts as [$email, $name, $pass, $role, $phone]) {
            // firstOrCreate: sandi yang sudah Anda ganti tidak ikut tertimpa
            $u = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make($pass), 'role' => $role, 'phone' => $phone,
            ]);
            // Akun contoh langsung disetujui (hanya saat dibuat pertama kali).
            if ($u->wasRecentlyCreated) {
                $u->forceFill(['email_verified_at' => now(), 'account_status' => 'approved', 'approved_at' => now()])->save();
            }
        }
    }
}
