@extends('layouts.app')
@section('title', 'Jasa Perawatan Tanaman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Jasa Perawatan' => null]])
<div class="container page-head">
    <span class="eyebrow">Plant Care Booking</span>
    <h1>Jasa perawatan tanaman profesional</h1>
    <p>Pilih layanan, tentukan jadwal yang tersedia, dan biarkan tim Clover merawat ruang hijau Anda.</p>
</div>
<div class="container" style="padding-bottom:96px">
    @if($services->count())
        <div class="grid g-3">@foreach($services as $s) @include('partials.service-card', ['s' => $s]) @endforeach</div>
    @else
        @include('partials.empty', ['icon' => 'scissors', 'title' => 'Layanan belum tersedia'])
    @endif
    <div class="section-head" style="margin-top:80px"><div><h2>Cara kerja</h2></div></div>
    <ol class="steps">
        <li><b>Pilih layanan</b><p class="muted">Tentukan layanan sesuai kebutuhan.</p></li>
        <li><b>Pilih jadwal</b><p class="muted">Sistem menampilkan jam yang masih tersedia.</p></li>
        <li><b>Konfirmasi</b><p class="muted">Tim kami mengonfirmasi pemesanan Anda.</p></li>
        <li><b>Perawatan</b><p class="muted">Tim datang sesuai jadwal.</p></li>
    </ol>
</div>
@endsection
