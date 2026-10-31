@extends('layouts.app')
@section('title', 'Buat Kata Sandi Baru — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Buat kata sandi baru', 'text' => 'Gunakan kata sandi yang kuat dan belum pernah dipakai di tempat lain.'])
        <div class="auth-form">
            <h1>Kata sandi baru</h1>
            <form method="POST" action="{{ route('password.update') }}" class="form" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                @include('partials.field', ['prefix' => 'r', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $email, 'autocomplete' => 'email'])
                @include('partials.field', ['prefix' => 'r', 'name' => 'password', 'label' => 'Kata sandi baru', 'type' => 'password', 'hint' => 'Minimal 8 karakter.', 'autocomplete' => 'new-password'])
                @include('partials.field', ['prefix' => 'r', 'name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'type' => 'password', 'autocomplete' => 'new-password'])
                <button class="btn btn-primary btn-lg btn-block">Simpan kata sandi</button>
            </form>
        </div>
    </div>
</div>
@endsection
