@extends('layouts.admin')
@section('title', $service->exists ? 'Edit Jasa' : 'Tambah Jasa')

@section('content')
<div class="ah"><h1>{{ $service->exists ? 'Edit Jasa' : 'Tambah Jasa' }}</h1><a class="btn glass sm" href="{{ route('admin.services.index') }}">← Kembali</a></div>
<form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" enctype="multipart/form-data" class="fm pan glass" novalidate>
    @csrf
    @if($service->exists) @method('PUT') @endif
    <div class="two">
        @include('partials.field', ['prefix' => 's', 'name' => 'name', 'label' => 'Nama Layanan', 'value' => $service->name])
        @include('partials.field', ['prefix' => 's', 'name' => 'icon', 'label' => 'Ikon (emoji)', 'value' => $service->icon])
    </div>
    @include('partials.field', ['prefix' => 's', 'name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'value' => $service->description])
    <div class="two">
        @include('partials.field', ['prefix' => 's', 'name' => 'price', 'label' => 'Harga Mulai (Rp)', 'type' => 'number', 'min' => 0, 'value' => $service->price])
        @include('partials.field', ['prefix' => 's', 'name' => 'estimate', 'label' => 'Estimasi Waktu', 'value' => $service->estimate])
    </div>
    @include('partials.field', ['prefix' => 's', 'name' => 'schedule', 'label' => 'Jadwal Kunjungan', 'value' => $service->schedule])
    @include('partials.field', ['prefix' => 's', 'name' => 'includes', 'label' => 'Yang Termasuk (satu per baris)', 'type' => 'textarea', 'value' => $service->exists ? implode("\n", $service->includes ?? []) : ''])
    @if($service->exists)
        <div class="ph thumb big"><img src="{{ $service->image_url }}" alt="{{ $service->name }}" onerror="this.style.display='none'"></div>
    @endif
    @include('partials.field', ['prefix' => 's', 'name' => 'image', 'label' => 'Foto Jasa (JPG/PNG/WEBP, maks 3 MB)', 'type' => 'file', 'optional' => true, 'hint' => $service->exists ? 'Kosongkan jika tidak ingin mengganti foto.' : ''])
    <button class="btn pri shine">{{ $service->exists ? 'Simpan Perubahan' : 'Simpan Jasa' }}</button>
</form>
@endsection
