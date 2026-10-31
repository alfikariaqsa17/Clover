@extends('layouts.app')
@section('title', 'Tips Tanaman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Tips Tanaman' => null]])
<div class="container page-head">
    <span class="eyebrow">Tips & Artikel</span>
    <h1>Tips merawat tanaman</h1>
    <p>Perawatan, media tanam, penyiraman, pencahayaan, dan pencegahan hama.</p>
</div>
<div class="container" style="padding-bottom:96px">
    <div class="row" style="margin-bottom:28px">
        <a class="chip {{ request('kategori') ? '' : 'on' }}" href="{{ route('tutorial') }}">Semua</a>
        @foreach($categories as $c)<a class="chip {{ request('kategori') === $c->slug ? 'on' : '' }}" href="{{ route('tutorial', ['kategori' => $c->slug]) }}">{{ $c->name }}</a>@endforeach
    </div>
    @if($articles->count())
        <div class="grid g-3">@foreach($articles as $a) @include('partials.article-card', ['a' => $a]) @endforeach</div>
        {{ $articles->links() }}
    @else
        @include('partials.empty', ['icon' => 'book', 'title' => 'Belum ada artikel', 'text' => 'Artikel pada kategori ini belum tersedia.', 'url' => route('tutorial'), 'label' => 'Lihat Semua'])
    @endif
</div>
@endsection
