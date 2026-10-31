<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUser extends Command
{
    protected $signature = 'clover:verify-user {email : Email akun yang ingin diverifikasi}';

    protected $description = 'Tandai email pengguna sebagai terverifikasi (bantuan darurat)';

    public function handle(): int
    {
        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Pengguna dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }
        $user->markEmailAsVerified();
        $this->info("Email {$user->email} sekarang terverifikasi.");

        return self::SUCCESS;
    }
}
