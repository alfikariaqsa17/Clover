@extends('layouts.app')
@section('title', 'Lupa Kata Sandi — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Lupa kata sandi?', 'text' => 'Masukkan email akun Anda dan kami kirim tautan untuk membuat kata sandi baru.'])
        <div class="auth-form">
            <h1>Reset kata sandi</h1>
            <form method="POST" action="{{ route('password.email') }}" class="form" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 'p', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email'])
                <button class="btn btn-primary btn-lg btn-block">Kirim tautan reset</button>
            </form>
            <p class="muted" style="margin-top:20px"><a href="{{ route('login') }}"><u>Kembali ke halaman masuk</u></a></p>
        </div>
    </div>
</div>
@endsection
