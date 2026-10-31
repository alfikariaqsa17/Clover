<?php

namespace Tests\Unit;

use App\Support\MailDiagnostics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Pengelompokan galat SMTP memakai pesan asli Symfony Mailer. */
class MailDiagnosticsTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'login ditolak' => ['Failed to authenticate on SMTP server with username "x@gmail.com" using the following authenticators: "LOGIN", "PLAIN". Authenticator "LOGIN" returned "Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted."', 'auth'],
            'kredensial kosong' => ['Expected response code "250" but got code "530", with message "530 5.7.0 Authentication Required."', 'auth_missing'],
            'sertifikat' => ['Unable to connect with STARTTLS: stream_socket_enable_crypto(): SSL operation failed with code 1. certificate verify failed', 'tls'],
            'tidak tersambung' => ['Connection could not be established with host "smtp.gmail.com:587": stream_socket_client(): Unable to connect to smtp.gmail.com:587 (A connection attempt failed)', 'connection'],
            'port tertutup' => ['Connection could not be established with host "127.0.0.1:1": stream_socket_client(): Unable to connect to 127.0.0.1:1 (Connection refused)', 'connection'],
            'batas kirim' => ['Expected response code "250" but got code "550", with message "550-5.4.5 Daily user sending limit exceeded."', 'limit'],
            'penerima ditolak' => ['Expected response code "250/251/252" but got code "550", with message "550 5.1.1 The email account does not exist."', 'recipient'],
            'templat' => ['View [emails.verification-code] not found.', 'template'],
            'tidak dikenal' => ['sesuatu yang aneh', 'unknown'],
        ];
    }

    #[DataProvider('cases')]
    public function test_error_is_classified(string $message, string $expected): void
    {
        $this->assertSame($expected, MailDiagnostics::classify(new \RuntimeException($message)));
    }

    public function test_every_category_has_label_and_hint(): void
    {
        foreach (['template', 'auth', 'auth_missing', 'tls', 'connection', 'limit', 'recipient', 'unknown'] as $c) {
            $this->assertNotSame('', MailDiagnostics::label($c));
            $this->assertNotSame('', MailDiagnostics::hint($c));
        }
    }
}
