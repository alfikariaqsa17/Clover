@extends('layouts.admin')
@section('title', 'Edit Pengguna')

@section('content')
<div class="ah"><h1>Edit pengguna</h1><a class="btn btn-glass" href="{{ route('admin.users.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a></div>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="panel form" novalidate>
    @csrf @method('PUT')
    @include('partials.field', ['prefix' => 'u', 'name' => 'name', 'label' => 'Nama', 'value' => $user->name])
    @include('partials.field', ['prefix' => 'u', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $user->email])
    @include('partials.field', ['prefix' => 'u', 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => $user->phone, 'optional' => true])
    @include('partials.field', ['prefix' => 'u', 'name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => ['user' => 'Pelanggan', 'admin' => 'Administrator'], 'assoc' => true, 'value' => $user->role])
    @include('partials.field', ['prefix' => 'u', 'name' => 'account_status', 'label' => 'Status akun', 'type' => 'select', 'options' => $statuses, 'assoc' => true, 'value' => $user->account_status])
    @include('partials.field', ['prefix' => 'u', 'name' => 'status_note', 'label' => 'Catatan / alasan (untuk akun ditolak)', 'value' => $user->status_note, 'optional' => true])
    @include('partials.field', ['prefix' => 'u', 'name' => 'password', 'label' => 'Kata sandi baru', 'type' => 'password', 'optional' => true, 'hint' => 'Kosongkan jika tidak diganti.'])
    <div class="row"><button class="btn btn-primary">Simpan</button></div>
</form>
@endsection
