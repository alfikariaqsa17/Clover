<?php

namespace App\Http\Controllers;

use App\Support\Msg;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function email(Request $r)
    {
        $d = $r->validate(['email' => 'required|email'], Msg::messages(), Msg::attributes());
        Password::sendResetLink(['email' => strtolower($d['email'])]);

        // Pesan sama untuk semua kasus agar email terdaftar tidak bisa ditebak.
        return back()->with('toast', 'Jika email terdaftar, tautan reset kata sandi telah dikirim.');
    }

    public function form(Request $r, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $r->query('email')]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:100|confirmed',
        ], Msg::messages(), Msg::attributes());

        $status = Password::reset(
            ['email' => strtolower($d['email']), 'password' => $d['password'], 'password_confirmation' => $r->input('password_confirmation'), 'token' => $d['token']],
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('toast', 'Kata sandi berhasil diubah. Silakan masuk.');
        }

        return back()->withInput($r->only('email'))->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
    }
}
