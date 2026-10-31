@extends('layouts.app')
@section('title', 'Verifikasi Email — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Satu langkah lagi', 'text' => 'Verifikasi email memastikan akun ini benar-benar milik Anda.'])
        <div class="auth-form">
            <h1>Verifikasi email Anda</h1>
            <p class="muted">Masukkan kode <b>{{ $digits }} digit</b> yang kami kirim ke <b>{{ $maskedEmail }}</b>. Periksa juga folder Spam atau Promosi.</p>

            @if(session('otp_error'))<div class="alert bad" role="alert" style="margin-bottom:14px"><x-icon name="alert" /><span>{{ session('otp_error') }}</span></div>@endif
            @if(session('otp_hint'))<div class="alert warn" role="status" style="margin-bottom:14px"><x-icon name="info" /><span><b>Info pengembang (hanya tampil di lokal):</b> {{ session('otp_hint') }}</span></div>@endif
            <div class="alert bad" id="otp-alert" role="alert" hidden style="margin-bottom:14px"><x-icon name="alert" /><span id="otp-alert-text"></span></div>

            <form id="otp-form" method="POST" action="{{ route('verification.verify') }}" class="form" novalidate data-digits="{{ $digits }}">
                @csrf
                <div class="field">
                    <label for="otp-code">Kode verifikasi</label>
                    <input id="otp-code" class="otp-input" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="{{ $digits }}" placeholder="{{ str_repeat('0', $digits) }}" required autofocus @error('code') aria-invalid="true" @enderror>
                    @error('code')<small class="error" role="alert">{{ $message }}</small>@enderror
                    <small class="hint" id="otp-timer" aria-live="polite" data-remaining="{{ $expiresIn }}" data-empty-text="{{ ['none' => 'Kode belum dikirim. Klik "Kirim ulang kode".', 'expired' => 'Kode sudah kedaluwarsa. Minta kode baru.', 'locked' => 'Kode terkunci karena terlalu banyak percobaan salah. Minta kode baru.', 'active' => ''][$state] }}">@if($expiresIn > 0)Kode berlaku {{ sprintf('%02d:%02d', intdiv($expiresIn, 60), $expiresIn % 60) }}@else {{ ['none' => 'Kode belum dikirim. Klik "Kirim ulang kode".', 'expired' => 'Kode sudah kedaluwarsa. Minta kode baru.', 'locked' => 'Kode terkunci karena terlalu banyak percobaan salah. Minta kode baru.', 'active' => ''][$state] }} @endif</small>
                </div>
                <button id="otp-submit" class="btn btn-primary btn-lg btn-block" @disabled($expiresIn <= 0)>Verifikasi</button>
            </form>

            <form id="otp-resend" method="POST" action="{{ route('verification.resend') }}" data-cooldown="{{ $cooldown }}" style="margin-top:14px">
                @csrf
                <button id="otp-resend-btn" class="btn btn-glass btn-block" type="submit" @disabled($cooldown > 0)>Kirim ulang kode</button>
            </form>
            <p class="hint" style="margin-top:10px">Kode berlaku {{ $ttl }} menit dan hanya bisa dipakai sekali. Jangan bagikan kode kepada siapa pun.</p>

            <form method="POST" action="{{ route('verification.cancel') }}" style="margin-top:18px">@csrf<button class="btn btn-ghost btn-sm">Kembali ke halaman masuk</button></form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/otp.js') }}" defer></script>
@endpush
