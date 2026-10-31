@extends('layouts.app')
@section('title', 'Perawatan ' . $plant->name . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Panduan Perawatan' => route('guide'), $plant->name => null]])
<div class="container pd" style="padding-bottom:40px">
    <div class="pd-gallery"><div class="pd-main">@include('partials.photo', ['src' => $plant->image_url, 'alt' => $plant->name, 'eager' => true])</div></div>
    <div>
        <span class="eyebrow">Plant Care Assistant</span>
        <h1 style="font-size:clamp(2rem,4vw,3rem);margin-top:8px">{{ $plant->name }}</h1>
        @if($plant->latin_name)<p class="muted" style="font-style:italic">{{ $plant->latin_name }}</p>@endif
        @if($plant->description)<p>{{ $plant->description }}</p>@endif
        <div class="spec">
            <div><x-icon name="sun" /><span><small>Cahaya</small><b>{{ $plant->light_text ?: $plant->light_label }}</b></span></div>
            <div><x-icon name="droplet" /><span><small>Penyiraman</small><b>{{ $plant->water_text ?: 'Setiap ' . $plant->water_days . ' hari' }}</b></span></div>
            @if($plant->humidity)<div><x-icon name="wind" /><span><small>Kelembapan</small><b>{{ $plant->humidity }}</b></span></div>@endif
            @if($plant->temperature)<div><x-icon name="thermometer" /><span><small>Suhu</small><b>{{ $plant->temperature }}</b></span></div>@endif
            @if($plant->soil)<div><x-icon name="pot" /><span><small>Media tanam</small><b>{{ $plant->soil }}</b></span></div>@endif
            @if($plant->fertilizer)<div><x-icon name="sprout" /><span><small>Pemupukan</small><b>{{ $plant->fertilizer }}</b></span></div>@endif
            <div><x-icon name="leaf" /><span><small>Tingkat perawatan</small><b>{{ $plant->care_label }}</b></span></div>
            @if($plant->pet_safe !== null)<div><x-icon name="paw" /><span><small>Hewan peliharaan</small><b>{{ $plant->pet_safe ? 'Aman' : 'Tidak aman jika tergigit' }}</b></span></div>@endif
        </div>
        @if($plant->placement_list)
            <p><b>Cocok untuk:</b></p>
            <div class="chips" style="margin-top:8px">@foreach($plant->placement_list as $pl)<span class="chip">{{ ucfirst($pl) }}</span>@endforeach</div>
        @endif
        @if($plant->tips)<div class="alert"><x-icon name="lightbulb" /><span><b>Tips:</b> {{ $plant->tips }}</span></div>@endif
        <div class="row" style="margin-top:20px">
            @auth<a class="btn btn-primary" href="{{ route('account.plants') }}">Simpan ke Koleksi Saya</a>@endauth
            <a class="btn btn-glass" href="{{ route('health') }}">Panduan Kesehatan Tanaman</a>
        </div>
    </div>
</div>

@if($plant->products->count())
    <section class="container" style="padding-bottom:96px">
        <div class="section-head"><div><span class="eyebrow">Belanja</span><h2>{{ $plant->name }} di Clover</h2></div></div>
        <div class="product-grid">@foreach($plant->products as $p) @include('partials.product-card', ['p' => $p]) @endforeach</div>
    </section>
@endif
@endsection
