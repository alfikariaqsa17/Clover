@extends('layouts.admin')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="ah"><h1>{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1><a class="btn glass sm" href="{{ route('admin.products.index') }}">← Kembali</a></div>
<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="fm pan glass" novalidate>
    @csrf
    @if($product->exists) @method('PUT') @endif
    @include('partials.field', ['prefix' => 'a', 'name' => 'name', 'label' => 'Nama Tanaman', 'value' => $product->name])
    <div class="two">
        @include('partials.field', ['prefix' => 'a', 'name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'options' => \App\Models\Product::CATEGORIES, 'value' => $product->category])
        @include('partials.field', ['prefix' => 'a', 'name' => 'price', 'label' => 'Harga (Rp)', 'type' => 'number', 'min' => 0, 'value' => $product->price])
    </div>
    <div class="two">
        @include('partials.field', ['prefix' => 'a', 'name' => 'stock', 'label' => 'Stok', 'type' => 'number', 'min' => 0, 'value' => $product->stock])
        @include('partials.field', ['prefix' => 'a', 'name' => 'size', 'label' => 'Ukuran', 'value' => $product->size])
    </div>
    <div class="two">
        @include('partials.field', ['prefix' => 'a', 'name' => 'rating', 'label' => 'Rating (0–5)', 'type' => 'number', 'min' => 0, 'step' => '0.1', 'value' => $product->rating])
        @include('partials.field', ['prefix' => 'a', 'name' => 'reviews', 'label' => 'Jumlah Ulasan', 'type' => 'number', 'min' => 0, 'value' => $product->reviews])
    </div>
    <div class="two">
        @include('partials.field', ['prefix' => 'a', 'name' => 'light', 'label' => 'Kebutuhan Cahaya', 'value' => $product->light])
        @include('partials.field', ['prefix' => 'a', 'name' => 'water', 'label' => 'Penyiraman', 'value' => $product->water])
    </div>
    @include('partials.field', ['prefix' => 'a', 'name' => 'media', 'label' => 'Media Tanam', 'value' => $product->media])
    @include('partials.field', ['prefix' => 'a', 'name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'value' => $product->description])
    @if($product->exists)
        <div class="ph thumb big"><img src="{{ $product->image_url }}" alt="{{ $product->name }}" onerror="this.style.display='none'"></div>
    @endif
    @include('partials.field', ['prefix' => 'a', 'name' => 'image', 'label' => 'Foto Produk (JPG/PNG/WEBP, maks 3 MB)', 'type' => 'file', 'optional' => true, 'hint' => $product->exists ? 'Kosongkan jika tidak ingin mengganti foto.' : 'Boleh dikosongkan, foto bisa ditambahkan nanti.'])
    <button class="btn pri shine">{{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
</form>
@endsection
