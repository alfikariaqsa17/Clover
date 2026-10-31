<?php

namespace App\Http\Controllers;

use App\Support\Msg;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    /** Tombol "verifikasi sekarang" hanya untuk pengembangan lokal dengan email mode log. */
    public static function devVerifyEnabled(): bool
    {
        return app()->environment('local')
            && config('mail.default') === 'log'
            && (bool) config('clover.dev_verify');
    }

    public function notice(Request $r)
    {
        if ($r->user()->hasVerifiedEmail()) {
            return redirect()->route('account');
        }

        return view('auth.verify', [
            'devVerify' => self::devVerifyEnabled(),
            'mailLog' => config('mail.default') === 'log',
        ]);
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('account')->with('toast', 'Email berhasil diverifikasi. Selamat datang di Clover.');
    }

    public function send(Request $r)
    {
        if ($r->user()->hasVerifiedEmail()) {
            return redirect()->route('account');
        }

        try {
            $r->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('toast', 'Email belum dapat dikirim. Periksa pengaturan email server atau hubungi admin.');
        }

        return back()->with('toast', 'Tautan verifikasi dikirim ulang ke ' . $r->user()->email . '.');
    }

    /** Verifikasi instan, hanya untuk lokal (APP_ENV=local, MAIL_MAILER=log). */
    public function devVerify(Request $r)
    {
        abort_unless(self::devVerifyEnabled(), 404);

        $user = $r->user();
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('account')->with('toast', 'Email ditandai terverifikasi (mode pengembangan).');
    }

    /** Ganti email bila salah ketik saat mendaftar. */
    public function changeEmail(Request $r)
    {
        $user = $r->user();
        abort_if($user->hasVerifiedEmail(), 404);

        $r->merge(['email' => strtolower(trim((string) $r->input('email')))]);
        $data = $r->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ], Msg::messages(), Msg::attributes());

        $user->forceFill(['email' => $data['email'], 'email_verified_at' => null])->save();

        try {
            $user->sendEmailVerificationNotification();
            $msg = 'Email diganti. Tautan verifikasi dikirim ke ' . $user->email . '.';
        } catch (\Throwable $e) {
            report($e);
            $msg = 'Email diganti, tetapi tautan verifikasi belum terkirim. Klik "Kirim ulang tautan".';
        }

        return back()->with('toast', $msg);
    }
}
