<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailOtpService;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Verifikasi email dengan OTP. Pengguna BELUM login selama verifikasi: identitas disimpan di session
 * (otp_user_id) hanya setelah pendaftaran atau setelah kata sandi benar saat login.
 */
class EmailVerificationController extends Controller
{
    public function __construct(private EmailOtpService $otp)
    {
    }

    private function pendingUser(Request $r): ?User
    {
        $id = $r->session()->get('otp_user_id');
        $user = $id ? User::find($id) : null;

        if (! $user || $user->isApproved() || $user->account_status === 'rejected') {
            $r->session()->forget('otp_user_id');

            return null;
        }

        return $user;
    }

    private function message(string $error, array $res = []): string
    {
        return EmailOtpService::describe($error, $res);
    }

    public function show(Request $r)
    {
        $user = $this->pendingUser($r);
        if (! $user) {
            return redirect()->route('login')->with('toast', 'Silakan masuk. Akun yang sudah terverifikasi dapat langsung login.');
        }

        $active = $this->otp->activeCode($user);

        return view('auth.verify-email', [
            'maskedEmail' => EmailOtpService::mask($user->email),
            'expiresIn' => $active ? max(0, (int) now()->diffInSeconds($active->expires_at, false)) : 0,
            'state' => $this->otp->state($user),
            'cooldown' => $this->otp->cooldownRemaining($user),
            'attemptsLeft' => $active ? max(0, $this->otp->maxAttempts() - (int) $active->attempts) : 0,
            'digits' => $this->otp->digits(),
            'ttl' => $this->otp->ttlMinutes(),
        ]);
    }

    public function verify(Request $r)
    {
        $user = $this->pendingUser($r);
        if (! $user) {
            return redirect()->route('login')->with('toast', 'Sesi verifikasi berakhir. Silakan masuk kembali.');
        }

        $digits = $this->otp->digits();
        $r->merge(['code' => preg_replace('/\D/', '', (string) $r->input('code'))]);
        $r->validate(
            ['code' => ['required', 'digits:' . $digits]],
            ['code.required' => 'Masukkan kode verifikasi.', 'code.digits' => 'Kode harus ' . $digits . ' digit angka.']
        );

        $res = $this->otp->verify($user, $r->input('code'));

        if (! $res['ok']) {
            if (($res['error'] ?? '') === 'already') {
                $r->session()->forget('otp_user_id');

                return redirect()->route('login')->with('toast', 'Email sudah terverifikasi. Silakan masuk.');
            }

            return back()->withErrors(['code' => $this->message($res['error'] ?? '', $res)]);
        }

        // Berhasil: baru sekarang pengguna dimasukkan (login) ke sistem.
        $fresh = $user->fresh();
        Auth::login($fresh);
        $r->session()->regenerate();
        $r->session()->forget('otp_user_id');
        Cart::mergeSessionInto($fresh);

        return redirect()->route('home')->with('toast', 'Email berhasil diverifikasi. Selamat datang di Clover.');
    }

    public function resend(Request $r)
    {
        $user = $this->pendingUser($r);
        if (! $user) {
            return $r->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Sesi berakhir. Silakan masuk kembali.', 'redirect' => route('login')], 401)
                : redirect()->route('login');
        }

        $res = $this->otp->issue($user);

        if ($res['ok']) {
            $payload = [
                'ok' => true,
                'message' => 'Kode baru dikirim ke ' . EmailOtpService::mask($user->email) . '.',
                'expires_in' => $this->otp->ttlMinutes() * 60,
                'cooldown' => $this->otp->cooldownSeconds(),
            ];

            return $r->expectsJson() ? response()->json($payload) : back()->with('toast', $payload['message']);
        }

        $payload = [
            'ok' => false,
            'message' => $this->message($res['error'] ?? '', $res),
            'cooldown' => (int) ($res['retry_after'] ?? 0),
            'hint' => EmailOtpService::devHint($res),
        ];
        $status = in_array($res['error'] ?? '', ['cooldown', 'hour_limit'], true) ? 429 : 422;

        return $r->expectsJson() ? response()->json($payload, $status) : back()->with('otp_error', $payload['message'])->with('otp_hint', $payload['hint']);
    }

    public function cancel(Request $r)
    {
        $r->session()->forget('otp_user_id');

        return redirect()->route('login');
    }
}
