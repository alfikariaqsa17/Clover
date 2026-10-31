<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Support\MailDiagnostics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Penerbitan dan verifikasi OTP email.
 * - OTP numerik dibuat dengan random_int (CSPRNG) dan HANYA disimpan sebagai HMAC-SHA256.
 * - Hanya kode terbaru yang berhasil terkirim yang berlaku; kode lama otomatis tidak berlaku.
 * - Kode kedaluwarsa dalam ttl menit, maksimal N percobaan salah, tidak bisa dipakai ulang.
 */
class EmailOtpService
{
    public function digits(): int
    {
        return (int) config('clover.otp.digits', 8);
    }

    public function ttlMinutes(): int
    {
        return (int) config('clover.otp.ttl_minutes', 10);
    }

    public function maxAttempts(): int
    {
        return (int) config('clover.otp.max_attempts', 5);
    }

    public function cooldownSeconds(): int
    {
        return (int) config('clover.otp.cooldown_seconds', 60);
    }

    public function hash(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }

    public function generate(): string
    {
        $max = (10 ** $this->digits()) - 1;

        return str_pad((string) random_int(0, $max), $this->digits(), '0', STR_PAD_LEFT);
    }

    /** Kode aktif terbaru: terkirim, belum dipakai, belum kedaluwarsa, percobaan belum habis. */
    public function activeCode(User $user): ?EmailVerificationCode
    {
        $code = EmailVerificationCode::where('user_id', $user->id)
            ->whereNotNull('sent_at')->whereNull('consumed_at')
            ->orderByDesc('id')->first();

        if (! $code || $code->expires_at->isPast() || $code->attempts >= $this->maxAttempts()) {
            return null;
        }

        return $code;
    }

    /** Status kode untuk tampilan: active | none (belum dikirim) | expired | locked (percobaan habis). */
    public function state(User $user): string
    {
        if ($this->activeCode($user)) {
            return 'active';
        }
        $last = EmailVerificationCode::where('user_id', $user->id)->whereNotNull('sent_at')->orderByDesc('id')->first();
        if (! $last) {
            return 'none';
        }

        return $last->attempts >= $this->maxAttempts() ? 'locked' : 'expired';
    }

    /** Petunjuk teknis untuk pengembang, HANYA tampil bila APP_DEBUG=true dan APP_ENV=local. */
    public static function devHint(array $res): ?string
    {
        if (! config('app.debug') || ! app()->environment('local') || empty($res['category'])) {
            return null;
        }

        return MailDiagnostics::label($res['category']) . '. ' . MailDiagnostics::hint($res['category']);
    }

    /** Sisa detik sebelum boleh kirim ulang (0 = boleh). */
    public function cooldownRemaining(User $user): int
    {
        $last = EmailVerificationCode::where('user_id', $user->id)->whereNotNull('sent_at')->orderByDesc('id')->first();
        if (! $last) {
            return 0;
        }

        return max(0, $this->cooldownSeconds() - (int) $last->sent_at->diffInSeconds(now(), true));
    }

    /** Email belum diatur sungguhan (mode log/array) -> OTP tidak boleh ditulis ke log. */
    public function mailNotConfigured(): bool
    {
        return in_array(config('mail.default'), ['log', 'array'], true) && ! app()->environment('testing');
    }

    /**
     * Terbitkan dan kirim OTP baru.
     *
     * @return array{ok: bool, error?: string, retry_after?: int, expires_at?: \Carbon\CarbonInterface}
     */
    public function issue(User $user): array
    {
        if ($user->account_status === 'rejected') {
            return ['ok' => false, 'error' => 'rejected'];
        }
        if ($user->account_status === 'approved' && $user->email_verified_at) {
            return ['ok' => false, 'error' => 'already'];
        }
        if ($this->mailNotConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        $otp = $this->generate();

        // 1) Pemeriksaan batas + simpan baris kode (terkunci agar permintaan bersamaan aman)
        $pre = DB::transaction(function () use ($user, $otp) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return ['ok' => false, 'error' => 'missing'];
            }

            $inProgress = EmailVerificationCode::where('user_id', $locked->id)->whereNull('sent_at')
                ->where('created_at', '>', now()->subSeconds(30))->exists();
            if ($inProgress) {
                return ['ok' => false, 'error' => 'cooldown', 'retry_after' => 5];
            }

            $wait = $this->cooldownRemaining($locked);
            if ($wait > 0) {
                return ['ok' => false, 'error' => 'cooldown', 'retry_after' => $wait];
            }

            $perHour = EmailVerificationCode::where('user_id', $locked->id)->whereNotNull('sent_at')
                ->where('sent_at', '>', now()->subHour())->count();
            if ($perHour >= (int) config('clover.otp.max_per_hour', 5)) {
                return ['ok' => false, 'error' => 'hour_limit'];
            }

            $row = EmailVerificationCode::create([
                'user_id' => $locked->id,
                'code_hash' => $this->hash($otp),
                'expires_at' => now()->addMinutes($this->ttlMinutes()),
            ]);

            return ['ok' => true, 'row' => $row];
        });

        if (! $pre['ok']) {
            return $pre;
        }
        $row = $pre['row'];

        // 2) Kirim email. Jika gagal: hapus baris (kode tidak pernah sampai) dan laporkan jujur.
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $otp, $this->ttlMinutes()));
        } catch (\Throwable $e) {
            $row->delete();
            $category = MailDiagnostics::classify($e);
            // Log aman: tanpa OTP, tanpa kata sandi, tanpa trace. Cukup untuk mendiagnosis penyebab.
            Log::warning('Pengiriman OTP gagal', [
                'user_id' => $user->id,
                'kategori' => $category,
                'galat' => class_basename($e),
                'pesan' => Str::limit(MailDiagnostics::redact($e->getMessage()), 400),
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
            ]);

            return ['ok' => false, 'error' => 'mail_failed', 'category' => $category];
        }

        // 3) Tandai terkirim, dan matikan semua kode lama
        DB::transaction(function () use ($row, $user) {
            $row->update(['sent_at' => now()]);
            EmailVerificationCode::where('user_id', $user->id)->where('id', '!=', $row->id)
                ->whereNull('consumed_at')->update(['consumed_at' => now()]);
        });

        return ['ok' => true, 'expires_at' => $row->expires_at];
    }

    /**
     * Verifikasi OTP.
     *
     * @return array{ok: bool, error?: string, attempts_left?: int}
     */
    public function verify(User $user, string $input): array
    {
        return DB::transaction(function () use ($user, $input) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return ['ok' => false, 'error' => 'missing'];
            }
            if ($locked->account_status === 'rejected') {
                return ['ok' => false, 'error' => 'rejected'];
            }
            if ($locked->account_status === 'approved' && $locked->email_verified_at) {
                return ['ok' => false, 'error' => 'already'];
            }

            $code = EmailVerificationCode::where('user_id', $locked->id)
                ->whereNotNull('sent_at')->whereNull('consumed_at')
                ->orderByDesc('id')->lockForUpdate()->first();

            if (! $code) {
                return ['ok' => false, 'error' => 'no_code'];
            }
            if ($code->expires_at->isPast()) {
                $code->update(['consumed_at' => now()]);

                return ['ok' => false, 'error' => 'expired'];
            }
            if ($code->attempts >= $this->maxAttempts()) {
                $code->update(['consumed_at' => now()]);

                return ['ok' => false, 'error' => 'too_many'];
            }

            $code->increment('attempts');

            if (! hash_equals($code->code_hash, $this->hash($input))) {
                $left = max(0, $this->maxAttempts() - (int) $code->attempts);
                if ($left === 0) {
                    $code->update(['consumed_at' => now()]);
                }

                return ['ok' => false, 'error' => $left === 0 ? 'too_many' : 'invalid', 'attempts_left' => $left];
            }

            // Benar: nonaktifkan semua kode dan tandai akun terverifikasi
            EmailVerificationCode::where('user_id', $locked->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            $locked->forceFill([
                'email_verified_at' => $locked->email_verified_at ?: now(),
                'account_status' => 'approved',
                'approved_at' => $locked->approved_at ?: now(),
                'status_note' => null,
            ])->save();

            return ['ok' => true];
        });
    }

    /** Pesan untuk pengguna berdasarkan kode galat. */
    public static function describe(string $error, array $res = []): string
    {
        return match ($error) {
            'invalid' => 'Kode salah. Sisa percobaan: ' . ($res['attempts_left'] ?? 0) . '.',
            'expired' => 'Kode sudah kedaluwarsa. Silakan minta kode baru.',
            'too_many' => 'Terlalu banyak percobaan salah. Silakan minta kode baru.',
            'no_code' => 'Belum ada kode aktif. Silakan kirim kode baru.',
            'cooldown' => 'Tunggu ' . ($res['retry_after'] ?? 60) . ' detik sebelum meminta kode baru.',
            'hour_limit' => 'Batas pengiriman kode per jam tercapai. Coba lagi nanti.',
            'mail_failed' => 'Email belum berhasil terkirim. Periksa koneksi atau pengaturan email server, lalu coba kirim ulang.',
            'not_configured' => 'Pengiriman email belum dikonfigurasi di server (MAIL_MAILER). Hubungi admin.',
            'rejected' => 'Pendaftaran akun Anda ditolak. Hubungi admin untuk bantuan.',
            default => 'Terjadi kesalahan. Silakan coba lagi.',
        };
    }

    /** Tampilan email tersamar, contoh: ra***@g***.com */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $dot = strrpos($domain, '.');
        $name = $dot === false ? $domain : substr($domain, 0, $dot);
        $tld = $dot === false ? '' : substr($domain, $dot);

        return substr($local, 0, 2) . str_repeat('*', max(3, strlen($local) - 2))
            . '@' . substr($name, 0, 1) . str_repeat('*', max(2, strlen($name) - 1)) . $tld;
    }
}
