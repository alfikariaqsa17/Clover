@extends('layouts.app')
@section('title', 'Alamat Pengiriman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Alamat' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2rem">Alamat pengiriman</h1>
        @foreach($addresses as $a)
            <details class="panel" style="margin-bottom:14px;padding:0">
                <summary style="padding:20px 24px;cursor:pointer;display:flex;justify-content:space-between;gap:12px;align-items:center;list-style:none"><span><b>{{ $a->label }}</b> @if($a->is_default)<span class="status">Utama</span>@endif<br><small class="muted">{{ $a->recipient }} · {{ \Illuminate\Support\Str::limit($a->address, 60) }}, {{ $a->city }}</small></span><x-icon name="edit" :size="18" /></summary>
                <div style="padding:0 24px 24px">
                    <form method="POST" action="{{ route('account.addresses.update', $a) }}" class="form" novalidate>
                        @csrf @method('PUT')
                        <div class="two">@include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'label', 'label' => 'Label', 'value' => $a->label])@include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'recipient', 'label' => 'Nama penerima', 'value' => $a->recipient])</div>
                        @include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => $a->phone])
                        @include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'address', 'label' => 'Alamat lengkap', 'type' => 'textarea', 'value' => $a->address])
                        <div class="two">@include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'city', 'label' => 'Kota', 'value' => $a->city])@include('partials.field', ['prefix' => 'a' . $a->id, 'name' => 'postal_code', 'label' => 'Kode pos', 'value' => $a->postal_code])</div>
                        <label class="check"><input type="checkbox" name="is_default" value="1" @checked($a->is_default)><span>Jadikan alamat utama</span></label>
                        <div class="row"><button class="btn btn-primary btn-sm">Simpan</button></div>
                    </form>
                    <form method="POST" action="{{ route('account.addresses.destroy', $a) }}" data-confirm="Hapus alamat {{ $a->label }}?" style="margin-top:10px">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus alamat</button></form>
                </div>
            </details>
        @endforeach
        @if(! $addresses->count())@include('partials.empty', ['icon' => 'map-pin', 'title' => 'Belum ada alamat tersimpan', 'text' => 'Tambahkan alamat agar checkout lebih cepat.'])@endif

        <div class="panel glass" style="margin-top:24px">
            <h3>Tambah alamat baru</h3>
            <form method="POST" action="{{ route('account.addresses.store') }}" class="form" novalidate>
                @csrf
                <div class="two">@include('partials.field', ['prefix' => 'n', 'name' => 'label', 'label' => 'Label (Rumah, Kantor...)'])@include('partials.field', ['prefix' => 'n', 'name' => 'recipient', 'label' => 'Nama penerima', 'value' => auth()->user()->name])</div>
                @include('partials.field', ['prefix' => 'n', 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => auth()->user()->phone])
                @include('partials.field', ['prefix' => 'n', 'name' => 'address', 'label' => 'Alamat lengkap', 'type' => 'textarea'])
                <div class="two">@include('partials.field', ['prefix' => 'n', 'name' => 'city', 'label' => 'Kota'])@include('partials.field', ['prefix' => 'n', 'name' => 'postal_code', 'label' => 'Kode pos'])</div>
                <label class="check"><input type="checkbox" name="is_default" value="1"><span>Jadikan alamat utama</span></label>
                <button class="btn btn-primary">Simpan Alamat</button>
            </form>
        </div>
    </div>
</div>
@endsection
