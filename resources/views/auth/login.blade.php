@extends('layouts.app')
@section('title', 'Masuk — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Selamat datang kembali', 'text' => 'Masuk untuk melihat pesanan, wishlist, koleksi tanaman, dan jadwal perawatan Anda.'])
        <div class="auth-form">
            <h1>Masuk</h1>
            @error('email')<div class="alert {{ str_contains($message, 'menunggu') || str_contains($message, 'ditolak') ? 'warn' : 'bad' }}" role="alert" style="margin-bottom:16px"><x-icon name="alert" /><span>{{ $message }}</span></div>@enderror
            <form method="POST" action="{{ route('login.attempt') }}" class="form" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 'l', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email'])
                @include('partials.field', ['prefix' => 'l', 'name' => 'password', 'label' => 'Kata sandi', 'type' => 'password', 'autocomplete' => 'current-password'])
                <div class="row between">
                    <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya</span></label>
                    <a href="{{ route('password.request') }}" style="font-size:.9rem"><u>Lupa kata sandi?</u></a>
                </div>
                <button class="btn btn-primary btn-lg btn-block">Masuk</button>
            </form>
            <p class="muted" style="margin-top:20px">Belum punya akun? <a href="{{ route('register') }}"><u>Daftar sekarang</u></a></p>
        </div>
    </div>
</div>
@endsection
