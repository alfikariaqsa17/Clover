@extends('layouts.admin')
@section('title', $article->exists ? 'Edit Artikel' : 'Tambah Artikel')

@section('content')
<div class="ah"><h1>{{ $article->exists ? 'Edit Artikel' : 'Tambah Artikel' }}</h1><a class="btn glass sm" href="{{ route('admin.articles.index') }}">← Kembali</a></div>
<form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data" class="fm pan glass" novalidate>
    @csrf
    @if($article->exists) @method('PUT') @endif
    @include('partials.field', ['prefix' => 't', 'name' => 'title', 'label' => 'Judul', 'value' => $article->title])
    <div class="two">
        @include('partials.field', ['prefix' => 't', 'name' => 'tag', 'label' => 'Label (Tips/Panduan/Edukasi)', 'value' => $article->tag])
        @include('partials.field', ['prefix' => 't', 'name' => 'published_at', 'label' => 'Tanggal Terbit', 'type' => 'date', 'nomin' => true, 'value' => $article->published_at ? $article->published_at->format('Y-m-d') : ''])
    </div>
    @include('partials.field', ['prefix' => 't', 'name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea', 'value' => $article->excerpt])
    @include('partials.field', ['prefix' => 't', 'name' => 'body', 'label' => 'Isi Artikel (pisahkan paragraf dengan baris kosong)', 'type' => 'textarea', 'value' => $article->body])
    @if($article->exists)
        <div class="ph thumb big"><img src="{{ $article->image_url }}" alt="{{ $article->title }}" onerror="this.style.display='none'"></div>
    @endif
    @include('partials.field', ['prefix' => 't', 'name' => 'image', 'label' => 'Foto Artikel (JPG/PNG/WEBP, maks 3 MB)', 'type' => 'file', 'optional' => true])
    <button class="btn pri shine">{{ $article->exists ? 'Simpan Perubahan' : 'Simpan Artikel' }}</button>
</form>
@endsection
