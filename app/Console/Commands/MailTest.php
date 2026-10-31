<?php

namespace App\Console\Commands;

use App\Support\MailDiagnostics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'clover:mail-test {email : Alamat tujuan email uji}';

    protected $description = 'Kirim email uji (untuk diagnosis lengkap pakai clover:mail-doctor)';

    public function handle(): int
    {
        $this->line('Mailer aktif : ' . config('mail.default'));
        $this->line('Host SMTP    : ' . (config('mail.mailers.smtp.host') ?: '-'));

        try {
            Mail::raw('Halo! Ini email uji dari Clover.', fn ($m) => $m->to($this->argument('email'))->subject('Tes email Clover'));
        } catch (\Throwable $e) {
            $cat = MailDiagnostics::classify($e);
            $this->error('Gagal: ' . MailDiagnostics::label($cat));
            $this->line(MailDiagnostics::redact($e->getMessage()));
            $this->warn('Saran: ' . MailDiagnostics::hint($cat));

            return self::FAILURE;
        }

        $this->info(in_array(config('mail.default'), ['log', 'array'], true)
            ? 'Mailer = ' . config('mail.default') . ': pesan tidak dikirim sungguhan.'
            : 'Email uji terkirim. Periksa kotak masuk dan folder Spam.');

        return self::SUCCESS;
    }
}
