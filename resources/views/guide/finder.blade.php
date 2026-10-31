@extends('layouts.app')
@section('title', 'Plant Finder — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Plant Finder' => null]])
<div class="container page-head">
    <span class="eyebrow">Plant Finder</span>
    <h1>Temukan tanaman yang cocok untuk ruangan Anda</h1>
    <p>Jawab beberapa pertanyaan, dan kami cocokkan dengan data tanaman di database Clover.</p>
</div>
<div class="container" style="padding-bottom:96px">
    <form method="GET" action="{{ route('finder') }}" class="panel glass form" novalidate>
        <div class="two">
            <div class="field"><label for="f-cahaya">Seberapa terang ruangan Anda? *</label>
                <select id="f-cahaya" name="cahaya" required @error('cahaya') aria-invalid="true" @enderror>
                    <option value="">Pilih...</option>
                    <option value="rendah" @selected(($input['cahaya'] ?? '') === 'rendah')>Redup (jauh dari jendela)</option>
                    <option value="sedang" @selected(($input['cahaya'] ?? '') === 'sedang')>Sedang (dekat jendela, tanpa sinar langsung)</option>
                    <option value="tinggi" @selected(($input['cahaya'] ?? '') === 'tinggi')>Terang (terkena matahari beberapa jam)</option>
                </select>@error('cahaya')<small class="error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="f-pengalaman">Pengalaman merawat tanaman *</label>
                <select id="f-pengalaman" name="pengalaman" required>
                    <option value="">Pilih...</option>
                    <option value="pemula" @selected(($input['pengalaman'] ?? '') === 'pemula')>Pemula</option>
                    <option value="menengah" @selected(($input['pengalaman'] ?? '') === 'menengah')>Menengah</option>
                    <option value="mahir" @selected(($input['pengalaman'] ?? '') === 'mahir')>Mahir</option>
                </select>@error('pengalaman')<small class="error">{{ $message }}</small>@enderror</div>
        </div>
        <div class="two">
            <div class="field"><label for="f-ruang">Ditempatkan di</label>
                <select id="f-ruang" name="ruang"><option value="">Mana saja</option>@foreach(\App\Models\Plant::PLACEMENTS as $r)<option value="{{ $r }}" @selected(($input['ruang'] ?? '') === $r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
            <div class="field"><label for="f-siram">Preferensi penyiraman</label>
                <select id="f-siram" name="siram"><option value="">Tidak ada preferensi</option><option value="jarang" @selected(($input['siram'] ?? '') === 'jarang')>Jarang disiram (sering lupa)</option><option value="rutin" @selected(($input['siram'] ?? '') === 'rutin')>Rutin disiram</option></select></div>
        </div>
        <label class="check"><input type="checkbox" name="hewan" value="1" @checked(!empty($input['hewan']))><span>Aman untuk hewan peliharaan</span></label>
        <div><button class="btn btn-primary btn-lg"><x-icon name="search" :size="18" /> Temukan Tanaman</button></div>
    </form>

    @if($results !== null)
        <div class="section-head" style="margin-top:56px"><div><h2>{{ $results->count() }} rekomendasi untuk Anda</h2><p>Diurutkan dari yang paling sesuai dengan jawaban Anda.</p></div></div>
        @if($results->count())
            <div class="grid g-2">
                @foreach($results as $pl)
                    <article class="row-card reveal" style="align-items:flex-start;flex-wrap:nowrap;gap:20px">
                        <div style="width:120px;flex:none;border-radius:16px;overflow:hidden">@include('partials.photo', ['src' => $pl->image_url ?: optional($pl->products->first())->image_url, 'alt' => $pl->name])</div>
                        <div style="flex:1">
                            <h3 style="margin:0 0 4px">{{ $pl->name }}</h3>
                            <ul class="list-check" style="margin:8px 0 12px;font-size:.88rem">@foreach($pl->why as $w)<li><x-icon name="check" :size="16" /> {{ $w }}</li>@endforeach</ul>
                            <div class="row">
                                <a class="btn btn-glass btn-sm" href="{{ route('guide.show', $pl) }}">Panduan</a>
                                @if($pl->products->first())<a class="btn btn-primary btn-sm" href="{{ route('products.show', $pl->products->first()) }}">Lihat Produk</a>@endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            @include('partials.empty', ['icon' => 'search', 'title' => 'Belum ada tanaman yang cocok', 'text' => 'Coba longgarkan pilihan, misalnya pengalaman atau tingkat cahaya.'])
        @endif
    @endif
</div>
@endsection
