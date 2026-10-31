<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Hanya akun yang sudah disetujui admin yang boleh memakai area pelanggan. */
class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && ! $user->isApproved()) {
            $message = $user->account_status === 'rejected'
                ? 'Pendaftaran akun Anda ditolak.' . ($user->status_note ? ' Alasan: ' . $user->status_note : '')
                : 'Email Anda belum terverifikasi. Silakan masuk lalu masukkan kode verifikasi.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
