@extends('layouts.app')
@section('title', 'Panduan Perawatan Tanaman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Panduan Perawatan' => null]])
<div class="container page-head">
    <span class="eyebrow">Plant Care Assistant</span>
    <h1>Panduan perawatan tanaman</h1>
    <p>Lihat kebutuhan cahaya, penyiraman, kelembapan, media tanam, dan tips untuk setiap tanaman.</p>
</div>
<div class="container" style="padding-bottom:80px">
    <form method="GET" class="row glass" style="padding:16px;border-radius:24px;margin-bottom:28px">
        <div class="field" style="flex:2;min-width:200px"><label class="sr-only" for="gq">Cari tanaman</label><input id="gq" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama tanaman..."></div>
        <div class="field" style="flex:1;min-width:150px"><label class="sr-only" for="gc">Cahaya</label>
            <select id="gc" name="cahaya" data-auto><option value="">Semua cahaya</option>@foreach(\App\Models\Plant::LIGHT as $k => $l)<option value="{{ $k }}" @selected(request('cahaya') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="field" style="flex:1;min-width:150px"><label class="sr-only" for="gp">Perawatan</label>
            <select id="gp" name="perawatan" data-auto><option value="">Semua tingkat</option>@foreach(\App\Models\Plant::CARE as $k => $l)<option value="{{ $k }}" @selected(request('perawatan') === $k)>{{ $l }}</option>@endforeach</select></div>
        <button class="btn btn-primary">Cari</button>
    </form>

    @if($plants->count())
        <div class="grid g-4">
            @foreach($plants as $pl)
                <a class="plant-card reveal" href="{{ route('guide.show', $pl) }}">
                    @include('partials.photo', ['src' => $pl->image_url, 'alt' => $pl->name, 'class' => 'r-4-3'])
                    <div>
                        <h3 style="margin:0 0 4px;font-size:1.1rem;font-family:var(--font-body)">{{ $pl->name }}</h3>
                        @if($pl->latin_name)<small class="muted" style="font-style:italic">{{ $pl->latin_name }}</small>@endif
                        <div class="chips" style="margin:12px 0 0;gap:6px"><span class="chip" style="padding:5px 10px;font-size:.76rem"><x-icon name="sun" :size="14" /> {{ $pl->light_label }}</span><span class="chip" style="padding:5px 10px;font-size:.76rem"><x-icon name="droplet" :size="14" /> {{ $pl->water_days }} hari</span></div>
                    </div>
                </a>
            @endforeach
        </div>
        {{ $plants->links() }}
    @else
        @include('partials.empty', ['icon' => 'search', 'title' => 'Tanaman tidak ditemukan', 'text' => 'Coba kata kunci atau filter lain.', 'url' => route('guide'), 'label' => 'Reset'])
    @endif
</div>
@endsection
