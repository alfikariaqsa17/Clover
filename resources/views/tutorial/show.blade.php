@extends('layouts.app')
@section('title', $article->title . ' — Clover')
@section('description', $article->excerpt)

@section('content')
@include('partials.breadcrumb', ['items' => ['Tips Tanaman' => route('tutorial'), \Illuminate\Support\Str::limit($article->title, 40) => null]])
<article class="container" style="max-width:820px;padding:24px 0 72px">
    <span class="eyebrow">{{ $article->category?->name ?? 'Tips' }}</span>
    <h1 style="font-size:clamp(2rem,4vw,3rem);margin-top:8px">{{ $article->title }}</h1>
    <div class="meta" style="margin-bottom:24px"><span>{{ $article->published_at->translatedFormat('j F Y') }}</span><span>{{ $article->read_minutes }} menit baca</span></div>
    <div style="border-radius:var(--r-lg);overflow:hidden;margin-bottom:32px">
        <!-- GANTI FOTO ARTIKEL: Dashboard Admin > Tips & Artikel > Edit > Foto -->
        @include('partials.photo', ['src' => $article->image_url, 'alt' => $article->title, 'class' => 'r-16-9', 'eager' => true])
    </div>
    <div style="font-size:1.08rem">@foreach(explode("\n\n", $article->body) as $para)<p>{{ $para }}</p>@endforeach</div>
    <div class="panel glass" style="margin-top:36px"><h3>Butuh bantuan langsung?</h3><p class="muted">Tim Clover siap membantu merawat tanaman dan taman Anda.</p><a class="btn btn-primary" href="{{ route('services') }}">Lihat Jasa Perawatan</a></div>
</article>
@if($others->count())
    <section class="container" style="padding-bottom:96px"><div class="section-head"><div><h2>Artikel lainnya</h2></div></div><div class="grid g-3">@foreach($others as $a) @include('partials.article-card', ['a' => $a]) @endforeach</div></section>
@endif
@endsection
