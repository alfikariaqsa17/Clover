@extends('layouts.app')
@section('title', 'Sertifikat & Legalitas — Clover')
@section('description', 'Foto sertifikat dan legalitas Clover. Klik foto untuk memperbesar.')

@section('content')
@include('partials.breadcrumb', ['items' => ['Sertifikat & Legalitas' => null]])
<div class="container page-head">
    <span class="eyebrow">Kepercayaan</span>
    <h1>Sertifikat &amp; legalitas Clover</h1>
    <p class="muted" style="max-width:640px">Kami menampilkan dokumen asli agar Anda dapat menilai sendiri. Klik foto untuk memperbesar.</p>
</div>

<div class="container" style="padding-bottom:96px">
    @if($items->count())
        @include('partials.certificates', ['items' => $items])
        {{ $items->links() }}
    @else
        @include('partials.empty', ['icon' => 'shield', 'title' => 'Belum ada dokumen', 'text' => 'Foto sertifikat akan ditampilkan di sini setelah diunggah admin.'])
    @endif

    <div class="panel glass" style="margin-top:48px">
        <h3>Ingin memastikan keaslian dokumen?</h3>
        <p class="muted" style="margin:0 0 14px">Hubungi kami dan kami bantu menunjukkan dokumen asli atau menghubungkan Anda dengan penerbitnya.</p>
        <a class="btn btn-primary btn-sm" href="{{ route('contact') }}">Hubungi kami</a>
    </div>
</div>
@endsection
