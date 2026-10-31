<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailOtpService;
use App\Support\Cart;
use App\Support\Msg;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Auth::check() ? redirect()->route('home') : view('auth.login');
    }

    public function login(Request $r)
    {
        $r->merge(['email' => strtolower(trim((string) $r->input('email')))]);
        $cred = $r->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], Msg::messages(), Msg::attributes());

        $user = User::where('email', $cred['email'])->first();

        if (! $user || ! Hash::check($cred['password'], $user->password)) {
            return back()->withInput($r->only('email'))->withErrors(['email' => 'Email atau kata sandi salah.']);
        }

        if (! $user->isAdmin() && $user->account_status === 'rejected') {
            return back()->withInput($r->only('email'))->withErrors(['email' => 'Pendaftaran akun Anda ditolak.' . ($user->status_note ? ' Alasan: ' . $user->status_note . '.' : '') . ' Hubungi admin untuk bantuan.']);
        }

        // Email belum terverifikasi: kata sandi benar, tetapi BELUM login. Arahkan ke verifikasi OTP.
        if (! $user->isAdmin() && ! $user->isApproved()) {
            session(['otp_user_id' => $user->id]);
            $otp = app(EmailOtpService::class);
            $flash = ['toast' => 'Verifikasi email diperlukan sebelum Anda dapat masuk.'];
            if (! $otp->activeCode($user)) {
                $res = $otp->issue($user);
                if ($res['ok']) {
                    $flash = ['toast' => 'Kami mengirim kode verifikasi ke ' . EmailOtpService::mask($user->email) . '.'];
                } elseif (! in_array($res['error'] ?? '', ['cooldown'], true)) {
                    $flash = ['otp_error' => EmailOtpService::describe($res['error'] ?? '', $res), 'otp_hint' => EmailOtpService::devHint($res)];
                }
            }

            return redirect()->route('verification.notice')->with($flash);
        }

        Auth::login($user, $r->boolean('remember'));
        $r->session()->regenerate();
        Cart::mergeSessionInto($user);

        $to = $user->isAdmin() ? route('admin.dashboard') : route('home');

        return redirect()->intended($to)->with('toast', 'Selamat datang, ' . $user->name . '.');
    }

    public function showRegister()
    {
        return Auth::check() ? redirect()->route('home') : view('auth.register');
    }

    public function register(Request $r)
    {
        $r->merge(['email' => strtolower(trim((string) $r->input('email')))]);
        Msg::wa($r, 'phone');
        $data = $r->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => ['required', 'regex:' . Msg::WA_RULE],
            'password' => 'required|string|min:8|max:100|confirmed',
        ], Msg::messages(), Msg::attributes());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'user',   // status akun otomatis "pending" (menunggu persetujuan admin)
        ]);

        // Beri tahu semua admin
        User::where('role', 'admin')->pluck('id')->each(fn ($id) => Notify::to(
            $id, 'account', 'Pendaftaran baru: ' . $user->name, $user->email . ' menunggu verifikasi email.',
            route('admin.users.index', ['status' => 'pending'])
        ));

        // Belum login. Kirim OTP lalu arahkan ke halaman verifikasi.
        session(['otp_user_id' => $user->id]);
        $res = app(EmailOtpService::class)->issue($user);

        $flash = $res['ok']
            ? ['toast' => 'Akun dibuat. Kami mengirim kode verifikasi ke ' . EmailOtpService::mask($user->email) . '.']
            : ['otp_error' => 'Akun Anda sudah dibuat, tetapi ' . lcfirst(EmailOtpService::describe($res['error'] ?? '', $res)), 'otp_hint' => EmailOtpService::devHint($res)];

        return redirect()->route('verification.notice')->with($flash);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('home')->with('toast', 'Anda telah keluar.');
    }
}
