<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Test verifikasi email OTP. Memakai Mail::fake() HANYA untuk test otomatis.
 * Pengujian pengiriman nyata lewat SMTP Gmail harus dilakukan manual (lihat panduan).
 */
class EmailOtpTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $extra = [])
    {
        return $this->post('/daftar', $extra + [
            'name' => 'Rani Putri', 'email' => 'rani@test.id', 'phone' => '081234567890',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ]);
    }

    private function lastOtp(): string
    {
        $otp = null;
        Mail::assertSent(VerificationCodeMail::class, function ($m) use (&$otp) {
            $otp = $m->code;

            return true;
        });

        return (string) $otp;
    }

    private function wrongCode(string $otp): string
    {
        return $otp === '99999999' ? '11111111' : '99999999';
    }

    private function pendingUser(): User
    {
        $u = User::create(['name' => 'Budi', 'email' => 'budi@test.id', 'password' => Hash::make('password123'), 'role' => 'user', 'phone' => '081234567890']);

        return $u->fresh();
    }

    public function test_registration_sends_8_digit_otp_and_stores_only_a_hash(): void
    {
        Mail::fake();
        $this->register()->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'rani@test.id')->first();
        $this->assertSame('pending', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest();

        Mail::assertSent(VerificationCodeMail::class, 1);
        $otp = $this->lastOtp();
        $this->assertMatchesRegularExpression('/^\d{8}$/', $otp);

        $row = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertNotSame($otp, $row->code_hash);
        $this->assertSame(hash_hmac('sha256', $otp, config('app.key')), $row->code_hash);
        $this->assertNotNull($row->sent_at);
        $this->assertTrue($row->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)->addSecond()));
        $this->assertDatabaseMissing('email_verification_codes', ['code_hash' => $otp]);
    }

    public function test_correct_code_verifies_logs_in_and_cannot_be_reused(): void
    {
        Mail::fake();
        $this->register();
        $otp = $this->lastOtp();

        $this->post('/verifikasi-email', ['code' => $otp])->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'rani@test.id')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('approved', $user->account_status);
        $this->assertSame(0, EmailVerificationCode::where('user_id', $user->id)->whereNull('consumed_at')->count());

        // kode yang sama tidak bisa dipakai lagi
        $res = app(EmailOtpService::class)->verify($user, $otp);
        $this->assertFalse($res['ok']);
        $this->post('/keluar');
        $this->post('/verifikasi-email', ['code' => $otp])->assertRedirect(route('login'));
    }

    public function test_wrong_code_counts_attempts_and_locks_after_five(): void
    {
        Mail::fake();
        $this->register();
        $otp = $this->lastOtp();
        $bad = $this->wrongCode($otp);

        $this->post('/verifikasi-email', ['code' => $bad])->assertSessionHasErrors(['code' => 'Kode salah. Sisa percobaan: 4.']);
        for ($i = 0; $i < 3; $i++) {
            $this->post('/verifikasi-email', ['code' => $bad]);
        }
        $this->post('/verifikasi-email', ['code' => $bad])->assertSessionHasErrors(['code' => 'Terlalu banyak percobaan salah. Silakan minta kode baru.']);

        // setelah terkunci, kode yang benar pun ditolak
        $this->post('/verifikasi-email', ['code' => $otp])->assertSessionHasErrors('code');
        $this->assertNull(User::where('email', 'rani@test.id')->first()->email_verified_at);
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        Mail::fake();
        $this->register();
        $otp = $this->lastOtp();

        $this->travel(11)->minutes();
        $this->post('/verifikasi-email', ['code' => $otp])->assertSessionHasErrors(['code' => 'Kode sudah kedaluwarsa. Silakan minta kode baru.']);
        $this->assertNull(User::where('email', 'rani@test.id')->first()->email_verified_at);
    }

    public function test_resend_has_cooldown_and_invalidates_the_old_code(): void
    {
        Mail::fake();
        $this->register();
        $old = $this->lastOtp();

        $this->postJson('/verifikasi-email/kirim-ulang')->assertStatus(429);   // cooldown 60 detik
        Mail::assertSent(VerificationCodeMail::class, 1);

        $this->travel(61)->seconds();
        $this->postJson('/verifikasi-email/kirim-ulang')->assertOk()->assertJson(['ok' => true]);
        Mail::assertSent(VerificationCodeMail::class, 2);
        $new = $this->lastOtp();
        $this->assertNotSame($old, $new);

        $this->post('/verifikasi-email', ['code' => $old])->assertSessionHasErrors('code');   // kode lama mati
        $this->assertGuest();
        $this->post('/verifikasi-email', ['code' => $new])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_hourly_issue_limit_is_enforced(): void
    {
        Mail::fake();
        config(['clover.otp.cooldown_seconds' => 0]);
        $svc = app(EmailOtpService::class);
        $user = $this->pendingUser();

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($svc->issue($user)['ok']);
        }
        $this->assertSame('hour_limit', $svc->issue($user)['error']);
    }

    public function test_smtp_failure_is_reported_honestly_and_does_not_lose_the_account(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
        app('mail.manager')->forgetMailers();

        $this->register()->assertRedirect(route('verification.notice'))->assertSessionHas('otp_error');

        $user = User::where('email', 'rani@test.id')->first();
        $this->assertNotNull($user);                                   // akun tidak hilang
        $this->assertSame(1, User::where('email', 'rani@test.id')->count());
        $this->assertSame(0, EmailVerificationCode::count());          // tidak ada kode "palsu" tersimpan

        // Setelah SMTP pulih, kirim ulang langsung berhasil (tidak terkena cooldown)
        config(['mail.default' => 'array']);
        app('mail.manager')->forgetMailers();
        Mail::fake();
        $this->postJson('/verifikasi-email/kirim-ulang')->assertOk();
        Mail::assertSent(VerificationCodeMail::class, 1);
    }

    public function test_login_of_unverified_account_redirects_to_verification_without_logging_in(): void
    {
        Mail::fake();
        $user = $this->pendingUser();

        $this->post('/masuk', ['email' => $user->email, 'password' => 'password123'])->assertRedirect(route('verification.notice'));
        $this->assertGuest();
        Mail::assertSent(VerificationCodeMail::class, 1);

        $this->post('/masuk', ['email' => $user->email, 'password' => 'salahsalah'])->assertSessionHasErrors('email');
    }

    public function test_verification_page_needs_session_and_masks_email(): void
    {
        $this->get('/verifikasi-email')->assertRedirect(route('login'));

        Mail::fake();
        $this->register();
        $this->get('/verifikasi-email')->assertOk()->assertSee('ra***')->assertDontSee('rani@test.id');
    }

    public function test_code_input_is_validated(): void
    {
        Mail::fake();
        $this->register();
        $this->post('/verifikasi-email', ['code' => 'abcd'])->assertSessionHasErrors('code');
        $this->post('/verifikasi-email', ['code' => '123'])->assertSessionHasErrors('code');
    }

    public function test_verify_endpoint_is_rate_limited(): void
    {
        Mail::fake();
        $this->register();
        $bad = $this->wrongCode($this->lastOtp());
        for ($i = 0; $i < 10; $i++) {
            $this->post('/verifikasi-email', ['code' => $bad]);
        }
        $this->post('/verifikasi-email', ['code' => $bad])->assertStatus(429);
    }

    public function test_rejected_account_cannot_receive_codes(): void
    {
        Mail::fake();
        $user = $this->pendingUser();
        $user->forceFill(['account_status' => 'rejected'])->save();

        $this->assertSame('rejected', app(EmailOtpService::class)->issue($user)['error']);
        Mail::assertNothingSent();
    }

    public function test_admin_manual_approval_marks_email_verified(): void
    {
        $admin = User::create(['name' => 'Adm', 'email' => 'adm@test.id', 'password' => Hash::make('x12345678'), 'role' => 'admin']);
        $admin->forceFill(['account_status' => 'approved'])->save();
        $user = $this->pendingUser();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/setujui")->assertRedirect();
        $user->refresh();
        $this->assertSame('approved', $user->account_status);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_mask_hides_most_of_the_address(): void
    {
        $this->assertSame('ra***@t***.id', EmailOtpService::mask('rani@test.id'));
    }

    public function test_failed_send_is_logged_with_category_and_without_secrets(): void
    {
        Log::spy();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2, 'mail.mailers.smtp.password' => 'rahasia-sandi-123']);
        app('mail.manager')->forgetMailers();

        $this->register();

        Log::shouldHaveReceived('warning')->withArgs(function ($msg, $ctx) {
            return $msg === 'Pengiriman OTP gagal'
                && $ctx['kategori'] === 'connection'
                && ! str_contains(json_encode($ctx), 'rahasia-sandi-123');
        })->once();
    }

    public function test_code_starting_with_zeros_is_valid(): void
    {
        $user = $this->pendingUser();
        EmailVerificationCode::create([
            'user_id' => $user->id, 'code_hash' => hash_hmac('sha256', '00001234', config('app.key')),
            'expires_at' => now()->addMinutes(10), 'sent_at' => now(),
        ]);

        $this->withSession(['otp_user_id' => $user->id])
            ->post('/verifikasi-email', ['code' => '00001234'])->assertRedirect(route('home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_mailable_template_renders_code_with_leading_zero(): void
    {
        $html = (new VerificationCodeMail($this->pendingUser(), '01234567', 10))->render();
        $this->assertStringContainsString('01234567', $html);
        $this->assertStringContainsString('10 menit', $html);
    }

    public function test_cannot_verify_another_account_by_manipulating_the_request(): void
    {
        Mail::fake();
        $this->register();                              // akun 1 (sesi ini terikat ke akun 1)
        $otp = $this->lastOtp();
        $victim = $this->pendingUser();                 // akun 2

        $this->post('/verifikasi-email', ['code' => $otp, 'user_id' => $victim->id, 'email' => $victim->email]);

        $this->assertNull($victim->fresh()->email_verified_at);                              // akun 2 tidak terverifikasi
        $this->assertNotNull(User::where('email', 'rani@test.id')->first()->email_verified_at); // hanya akun 1
    }

    public function test_verification_endpoints_require_a_pending_session(): void
    {
        $this->post('/verifikasi-email', ['code' => '12345678'])->assertRedirect(route('login'));
        $this->postJson('/verifikasi-email/kirim-ulang')->assertStatus(401);
    }
}
