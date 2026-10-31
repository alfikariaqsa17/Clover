@extends('layouts.app')
@section('title', 'Daftar — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Bergabung dengan Clover', 'text' => 'Daftar, verifikasi email dengan kode OTP, lalu simpan wishlist, lacak pesanan, dan kelola koleksi tanaman.'])
        <div class="auth-form">
            <h1>Buat akun</h1>
            <div class="alert" style="margin-bottom:16px"><x-icon name="info" /><span>Kami akan mengirim <b>kode verifikasi 8 digit</b> ke email Anda. Pastikan email aktif.</span></div>
            <form method="POST" action="{{ route('register.attempt') }}" class="form" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 'g', 'name' => 'name', 'label' => 'Nama lengkap', 'autocomplete' => 'name'])
                @include('partials.field', ['prefix' => 'g', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email'])
                @include('partials.field', ['prefix' => 'g', 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'hint' => 'Untuk konfirmasi pesanan dan layanan.', 'autocomplete' => 'tel'])
                @include('partials.field', ['prefix' => 'g', 'name' => 'password', 'label' => 'Kata sandi', 'type' => 'password', 'hint' => 'Minimal 8 karakter.', 'autocomplete' => 'new-password'])
                @include('partials.field', ['prefix' => 'g', 'name' => 'password_confirmation', 'label' => 'Ulangi kata sandi', 'type' => 'password', 'autocomplete' => 'new-password'])
                <button class="btn btn-primary btn-lg btn-block">Daftar</button>
            </form>
            <p class="muted" style="margin-top:20px">Sudah punya akun? <a href="{{ route('login') }}"><u>Masuk</u></a></p>
        </div>
    </div>
</div>
@endsection
