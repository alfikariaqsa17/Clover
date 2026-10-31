@extends('layouts.app')
@section('title', 'Panduan Kesehatan Tanaman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Kesehatan Tanaman' => null]])
<div class="container page-head">
    <span class="eyebrow">Plant Health Guide</span>
    <h1>Kenali masalah umum pada tanaman</h1>
    <p>Gejala yang sama bisa punya beberapa penyebab. Panduan ini memberi saran umum, bukan diagnosis pasti.</p>
</div>
<div class="container" style="padding-bottom:96px;max-width:900px">
    <div class="alert warn" style="margin-bottom:28px"><x-icon name="info" /><span>Jika tanaman tidak membaik atau kondisinya cepat memburuk, <a href="{{ route('services.show', 'konsultasi-perawatan') }}"><u>konsultasikan dengan tim Clover</u></a> untuk pemeriksaan langsung.</span></div>
    @forelse($problems as $p)
        <details class="panel reveal" style="margin-bottom:14px;padding:0" @if($loop->first) open @endif>
            <summary style="padding:22px 28px;cursor:pointer;display:flex;gap:14px;align-items:center;font-weight:600;font-size:1.05rem;list-style:none"><span class="trust-item" style="padding:0;display:inline-flex;gap:0;background:none;box-shadow:none"><span class="ico-wrap"><x-icon :name="$p->icon" :size="22" /></span></span> {{ $p->title }}</summary>
            <div style="padding:0 28px 26px" class="stack">
                <div><b>Gejala</b><p class="muted" style="margin:4px 0 0">{{ $p->symptoms }}</p></div>
                <div><b>Kemungkinan penyebab</b><p class="muted" style="margin:4px 0 0">{{ $p->causes }}</p></div>
                <div><b>Saran umum</b><p style="margin:4px 0 0">{{ $p->suggestions }}</p></div>
            </div>
        </details>
    @empty
        @include('partials.empty', ['icon' => 'leaf', 'title' => 'Panduan belum tersedia'])
    @endforelse
</div>
@endsection
