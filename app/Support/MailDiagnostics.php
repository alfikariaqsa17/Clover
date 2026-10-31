<?php

namespace App\Support;

/** Mengelompokkan galat pengiriman email menjadi kategori yang mudah dipahami pemula. */
class MailDiagnostics
{
    public static function classify(\Throwable $e): string
    {
        $m = strtolower($e->getMessage());

        if ($e instanceof \Illuminate\View\ViewException || str_contains($m, 'view [') || str_contains($m, 'undefined variable')) {
            return 'template';
        }
        if (str_contains($m, 'failed to authenticate') || str_contains($m, 'username and password not accepted')
            || str_contains($m, '5.7.8') || str_contains($m, 'got code "535"') || str_contains($m, 'badcredentials')) {
            return 'auth';
        }
        if (str_contains($m, 'got code "530"') || str_contains($m, 'authentication required')) {
            return 'auth_missing';
        }
        if (str_contains($m, 'certificate verify failed') || str_contains($m, 'starttls')
            || str_contains($m, 'ssl operation failed') || str_contains($m, 'stream_socket_enable_crypto')) {
            return 'tls';
        }
        if (str_contains($m, 'connection could not be established') || str_contains($m, 'unable to connect')
            || str_contains($m, 'connection refused') || str_contains($m, 'timed out') || str_contains($m, 'getaddrinfo')
            || str_contains($m, 'php_network_getaddresses') || str_contains($m, 'name or service not known')
            || str_contains($m, 'connection attempt failed')) {
            return 'connection';
        }
        if (str_contains($m, 'sending limit') || str_contains($m, '5.4.5') || str_contains($m, 'got code "421"')) {
            return 'limit';
        }
        if (str_contains($m, 'got code "550"') || str_contains($m, 'got code "553"') || str_contains($m, 'got code "554"')
            || str_contains($m, 'mailbox unavailable') || str_contains($m, 'recipient')) {
            return 'recipient';
        }

        return 'unknown';
    }

    public static function label(string $category): string
    {
        return [
            'template' => 'Templat email bermasalah',
            'auth' => 'Login SMTP ditolak (username / App Password salah)',
            'auth_missing' => 'Kredensial SMTP kosong',
            'tls' => 'Sambungan aman (TLS/sertifikat) gagal',
            'connection' => 'Tidak dapat tersambung ke server SMTP',
            'limit' => 'Batas kirim email tercapai',
            'recipient' => 'Alamat tujuan ditolak server email',
            'unknown' => 'Penyebab belum dikenali',
        ][$category] ?? 'Penyebab belum dikenali';
    }

    /** Saran perbaikan untuk pengembang/pemilik (Bahasa Indonesia). */
    public static function hint(string $category): string
    {
        return [
            'template' => 'Jalankan "php artisan view:clear", lalu periksa folder resources/views/emails.',
            'auth' => 'Google menolak login. Pastikan Verifikasi 2 Langkah aktif, buat App Password baru di myaccount.google.com/apppasswords, tulis TANPA spasi di MAIL_PASSWORD, dan MAIL_USERNAME harus akun Gmail yang sama. Lalu jalankan "php artisan config:clear" dan restart "php artisan serve".',
            'auth_missing' => 'Server meminta login tetapi MAIL_USERNAME / MAIL_PASSWORD kosong. Isi keduanya di .env, lalu "php artisan config:clear".',
            'tls' => 'Sertifikat/TLS gagal. Di Windows biasanya CA bundle PHP belum diatur: pastikan baris openssl.cafile dan curl.cainfo di php.ini menunjuk ke file cacert.pem yang ada. Alternatif cepat: MAIL_PORT=465 dan MAIL_SCHEME=smtps.',
            'connection' => 'Tidak dapat tersambung. Periksa internet, matikan sementara firewall/antivirus yang memblokir email (mis. Avast, Kaspersky), coba MAIL_PORT=465 dengan MAIL_SCHEME=smtps, atau pindah jaringan (hotspot HP).',
            'limit' => 'Batas kirim Gmail tercapai. Coba lagi nanti atau pakai layanan email transaksional.',
            'recipient' => 'Alamat tujuan ditolak. Periksa penulisan email penerima.',
            'unknown' => 'Buka storage/logs/laravel.log dan cari baris "Pengiriman OTP gagal", lalu jalankan "php artisan clover:mail-doctor".',
        ][$category] ?? '';
    }

    /** Hapus kredensial dari teks galat sebelum ditulis ke log. */
    public static function redact(string $text): string
    {
        foreach ([(string) config('mail.mailers.smtp.password'), (string) env('MAIL_PASSWORD')] as $secret) {
            if ($secret !== '' && strlen($secret) >= 4) {
                $text = str_replace([$secret, str_replace(' ', '', $secret)], '[disamarkan]', $text);
            }
        }

        return preg_replace('/\s+/', ' ', $text);
    }
}
