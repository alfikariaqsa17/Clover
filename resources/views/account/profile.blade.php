@extends('layouts.app')
@section('title', 'Profil — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Profil' => null]])
<div class="container account">
    @include('account._nav')
    <div class="grid g-2" style="align-items:start">
        <div class="panel">
            <h3>Data diri</h3>
            <form method="POST" action="{{ route('account.profile.update') }}" class="form" novalidate>
                @csrf @method('PUT')
                @include('partials.field', ['prefix' => 'p', 'name' => 'name', 'label' => 'Nama', 'value' => auth()->user()->name])
                <div class="field"><label>Email</label><input value="{{ auth()->user()->email }}" disabled><small class="hint">Akun Anda sudah disetujui admin.</small></div>
                @include('partials.field', ['prefix' => 'p', 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => auth()->user()->phone, 'optional' => true])
                <button class="btn btn-primary">Simpan Profil</button>
            </form>
        </div>
        <div class="panel">
            <h3>Ganti kata sandi</h3>
            <form method="POST" action="{{ route('account.password') }}" class="form" novalidate>
                @csrf @method('PUT')
                @include('partials.field', ['prefix' => 'w', 'name' => 'current_password', 'label' => 'Kata sandi saat ini', 'type' => 'password'])
                @include('partials.field', ['prefix' => 'w', 'name' => 'password', 'label' => 'Kata sandi baru', 'type' => 'password', 'hint' => 'Minimal 8 karakter.'])
                @include('partials.field', ['prefix' => 'w', 'name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'type' => 'password'])
                <button class="btn btn-primary">Ganti Kata Sandi</button>
            </form>
        </div>
    </div>
</div>
@endsection
