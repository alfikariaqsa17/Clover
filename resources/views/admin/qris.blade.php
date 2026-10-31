@extends('layouts.admin')
@section('title', 'Pengaturan QRIS')

@section('content')
<div class="ah"><div><h1>Pengaturan QRIS</h1><p>Pasang QRIS GoPay Merchant Anda. Gambar ini yang dilihat pelanggan saat membayar.</p></div><a class="btn btn-glass" href="{{ route('admin.payments.index') }}">Pembayaran QRIS</a></div>

<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>QRIS saat ini</h2>
        @if($imageUrl)
            <div style="background:#fff;border-radius:16px;overflow:hidden;width:min(260px,100%);aspect-ratio:3/4"><img src="{{ $imageUrl }}" alt="QRIS merchant saat ini" style="display:block;width:100%;height:100%;object-fit:{{ $fit }}"></div>
            <p style="margin:12px 0 0">@if($ready)<span class="status s-paid">Aktif di checkout</span>@else<span class="status s-dibatalkan">Belum tampil di checkout</span>@endif</p>
        @else
            <p class="muted">Belum ada gambar QRIS. Unggah di formulir sebelah.</p>
        @endif
        <div class="alert warn" style="margin-top:16px"><x-icon name="alert" /><span>Setelah mengganti QRIS, <b>uji dulu</b>: scan dengan aplikasi lain dan pastikan nama merchant serta rekening tujuannya benar milik Anda.</span></div>
    </section>

    <section class="box">
        <h2>Ganti / pasang QRIS</h2>
        <form method="POST" action="{{ route('admin.qris.update') }}" enctype="multipart/form-data" class="form" novalidate>
            @csrf @method('PUT')
            @include('partials.field', ['prefix' => 'qr', 'name' => 'qris_image', 'label' => 'Gambar QRIS (JPG/PNG/WebP, maks 4 MB)', 'type' => 'file', 'optional' => true, 'hint' => 'Unduh dari aplikasi GoPay Merchant (menu QRIS), pastikan gambar utuh, tajam, dan tidak terpotong.'])
            @include('partials.field', ['prefix' => 'qr', 'name' => 'qris_merchant', 'label' => 'Nama merchant (tampil ke pelanggan)', 'value' => $merchant, 'optional' => true, 'hint' => 'Sama seperti nama yang muncul saat QRIS dipindai.'])
            @include('partials.field', ['prefix' => 'qr', 'name' => 'qris_enabled', 'label' => 'Aktifkan pembayaran QRIS di checkout', 'type' => 'checkbox', 'value' => $enabled])
            <button class="btn btn-primary">Simpan</button>
        </form>
    </section>
</div>
@endsection
