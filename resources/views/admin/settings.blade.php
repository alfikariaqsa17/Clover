@extends('layouts.admin')
@section('title', 'Pengaturan Situs')

@section('content')
<div class="ah"><div><h1>Pengaturan situs</h1><p>Ganti foto background dan teks utama tanpa menyentuh kode.</p></div><a class="btn btn-glass" href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external" :size="18" /> Lihat hasil</a></div>
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    <h2 class="sect-title">Foto & background</h2>
    <div class="img-grid">
        @foreach($images as $key => $def)
            <div class="img-card">
                <div id="prev-{{ $key }}" style="border-radius:14px;overflow:hidden">@include('partials.photo', ['src' => \App\Models\Setting::img($key), 'alt' => 'Pratinjau ' . $def[0], 'class' => 'r-16-9'])</div>
                <h3 style="font-family:var(--font-body);font-size:1rem;margin:0">{{ $def[0] }}</h3>
                <small class="muted">{{ $def[2] }}</small>
                @include('partials.field', ['prefix' => 'img', 'name' => $key, 'label' => 'Unggah foto baru', 'type' => 'file', 'optional' => true, 'preview' => '#prev-' . $key])
                @if(\App\Models\Setting::custom($key))<label class="check"><input type="checkbox" name="remove_{{ $key }}" value="1"><span>Kembalikan ke foto bawaan</span></label>@endif
            </div>
        @endforeach
    </div>
    <h2 class="sect-title">Teks & kontak</h2>
    <div class="panel form">
        @foreach($texts as $key => $def)
            @include('partials.field', ['prefix' => 'txt', 'name' => $key, 'label' => $def[0], 'type' => $def[2], 'value' => \App\Models\Setting::get($key), 'optional' => true])
        @endforeach
    </div>
    <p class="muted" style="margin-top:14px">Foto Galeri Inspirasi, banner promo, dan announcement bar diatur di menu <a href="{{ route('admin.banners.index') }}"><u>Banner & Promo</u></a>. Foto produk, jasa, dan artikel diatur pada halaman masing-masing.</p>
    <p style="margin-top:14px"><button class="btn btn-primary btn-lg">Simpan Pengaturan</button></p>
</form>
@endsection
