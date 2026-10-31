<?php

namespace App\Console\Commands;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Support\MailDiagnostics;
use App\Support\SmtpProbe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailDoctor extends Command
{
    protected $signature = 'clover:mail-doctor {email? : Alamat tujuan untuk uji kirim nyata (disarankan)}';

    protected $description = 'Periksa pengaturan email Clover dan temukan penyebab OTP gagal terkirim';

    private int $problems = 0;

    private function bad(string $msg): void
    {
        $this->problems++;
        $this->error('  [MASALAH] ' . $msg);
    }

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $cfg = (array) config('mail.mailers.' . $mailer, []);
        $host = (string) ($cfg['host'] ?? '');
        $port = (int) ($cfg['port'] ?? 0);
        $user = (string) ($cfg['username'] ?? '');
        $pass = (string) ($cfg['password'] ?? '');

        $this->info('1. Konfigurasi yang terbaca Laravel');
        $this->line("   Mailer        : {$mailer}");
        $this->line('   Host / Port   : ' . ($host ?: '-') . ' / ' . ($port ?: '-'));
        $this->line('   Scheme        : ' . (($cfg['scheme'] ?? null) ?: '(kosong = otomatis)'));
        $this->line('   Username      : ' . ($user ? substr($user, 0, 3) . '***' . substr($user, -10) : '(KOSONG)'));
        $this->line('   Password      : ' . ($pass !== '' ? strlen($pass) . ' karakter (isi disembunyikan)' : '(KOSONG)'));
        $this->line('   Pengirim      : ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>');
        $this->line('   Konfigurasi di-cache: ' . (app()->configurationIsCached() ? 'YA' : 'tidak'));

        if (app()->configurationIsCached()) {
            $this->bad('Konfigurasi ter-cache, perubahan .env diabaikan. Jalankan: php artisan config:clear');
        }
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->bad("MAIL_MAILER={$mailer}: email TIDAK dikirim sungguhan. Ubah ke smtp di .env lalu php artisan config:clear.");
        }
        if ($mailer === 'smtp') {
            if ($host === '' || $port === 0) {
                $this->bad('MAIL_HOST atau MAIL_PORT kosong.');
            }
            if ($user === '') {
                $this->bad('MAIL_USERNAME kosong.');
            } elseif (! str_contains($user, '@')) {
                $this->bad('MAIL_USERNAME untuk Gmail harus alamat lengkap, contoh nama@gmail.com.');
            }
            if ($pass === '') {
                $this->bad('MAIL_PASSWORD kosong. Isi dengan App Password Google.');
            } else {
                if (preg_match('/\s/', $pass)) {
                    $this->bad('MAIL_PASSWORD mengandung spasi. Tulis App Password TANPA spasi.');
                }
                if (preg_match('/isi_|google_app|password|sandi/i', $pass)) {
                    $this->bad('MAIL_PASSWORD masih berisi teks contoh. Ganti dengan App Password asli.');
                }
                if (str_contains($host, 'gmail') && strlen(str_replace(' ', '', $pass)) !== 16) {
                    $this->bad('App Password Google berjumlah 16 huruf, sedangkan yang terbaca ' . strlen($pass) . ' karakter. Kemungkinan Anda memakai sandi Gmail biasa.');
                }
            }
            if ($user && strcasecmp($user, (string) config('mail.from.address')) !== 0 && str_contains($host, 'gmail')) {
                $this->warn('  [INFO] MAIL_FROM_ADDRESS berbeda dari MAIL_USERNAME. Gmail akan memakai alamat akun pengirim.');
            }
        }

        $this->newLine();
        $this->info('2. Lingkungan PHP');
        $this->line('   Ekstensi openssl : ' . (extension_loaded('openssl') ? 'aktif' : 'TIDAK AKTIF'));
        $caf = (string) ini_get('openssl.cafile');
        $cai = (string) ini_get('curl.cainfo');
        $this->line('   openssl.cafile   : ' . ($caf ?: '(kosong)') . ($caf && ! is_file($caf) ? '  <- FILE TIDAK ADA' : ''));
        $this->line('   curl.cainfo      : ' . ($cai ?: '(kosong)') . ($cai && ! is_file($cai) ? '  <- FILE TIDAK ADA' : ''));
        if (! extension_loaded('openssl')) {
            $this->bad('Ekstensi openssl tidak aktif. Aktifkan extension=openssl di php.ini.');
        }

        if ($mailer === 'smtp' && $host !== '' && $port > 0) {
            $this->newLine();
            $this->info("3. Uji sambungan ke {$host}:{$port} (tanpa login)");
            foreach (SmtpProbe::run($host, $port) as $s) {
                $this->line(($s['ok'] ? '   [OK]    ' : '   [GAGAL] ') . $s['step'] . ': ' . $s['detail']);
                if (! $s['ok']) {
                    $this->problems++;
                    $cat = str_contains(strtolower($s['detail']), 'certificate') || $s['step'] === 'STARTTLS' ? 'tls' : 'connection';
                    $this->warn('           Saran: ' . MailDiagnostics::hint($cat));
                    break;
                }
            }
        }

        $this->newLine();
        $this->info('4. Templat email');
        try {
            $html = (new VerificationCodeMail(new User(['name' => 'Uji']), '01234567', 10))->render();
            $this->line('   [OK]    Templat dapat dirender' . (str_contains($html, '01234567') ? ' dan memuat kode.' : ' (kode tidak ditemukan di isi!).'));
        } catch (\Throwable $e) {
            $this->bad('Templat email error: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('5. Uji kirim nyata');
        $to = $this->argument('email');
        if (! $to) {
            $this->line('   Dilewati. Jalankan lagi dengan email tujuan Anda:  php artisan clover:mail-doctor emailanda@gmail.com');
        } else {
            try {
                Mail::raw('Ini email uji dari Clover. Jika Anda membacanya, pengiriman SMTP bekerja.', fn ($m) => $m->to($to)->subject('Tes email Clover'));
                $this->line("   [OK]    Perintah kirim ke {$to} berhasil" . (in_array($mailer, ['log', 'array'], true) ? ' (tetapi mailer ' . $mailer . ' tidak mengirim sungguhan).' : '. Periksa Kotak Masuk dan folder Spam.'));
            } catch (\Throwable $e) {
                $cat = MailDiagnostics::classify($e);
                $this->bad('Gagal mengirim: ' . MailDiagnostics::label($cat));
                $this->line('   Pesan server : ' . MailDiagnostics::redact($e->getMessage()));
                $this->warn('   Saran        : ' . MailDiagnostics::hint($cat));
            }
        }

        $this->newLine();
        $this->problems === 0
            ? $this->info('Tidak ditemukan masalah konfigurasi.')
            : $this->error("Ditemukan {$this->problems} masalah. Perbaiki dari yang paling atas, lalu jalankan perintah ini lagi.");

        return $this->problems === 0 ? self::SUCCESS : self::FAILURE;
    }
}
