<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ApproveUser extends Command
{
    protected $signature = 'clover:approve-user {email : Email akun yang ingin disetujui}';

    protected $description = 'Setujui akun pengguna (bantuan darurat jika admin tidak bisa masuk)';

    public function handle(): int
    {
        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Pengguna dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }
        $user->forceFill(['account_status' => 'approved', 'approved_at' => now(), 'status_note' => null])->save();
        $this->info("Akun {$user->email} sekarang disetujui.");

        return self::SUCCESS;
    }
}
