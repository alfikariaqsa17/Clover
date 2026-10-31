@extends('layouts.app')
@section('title', 'Katalog Tanaman — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Katalog Tanaman' => null]])
<div class="container page-head">
    <h1>Temukan tanaman yang tepat</h1>
    <p>Cari berdasarkan kategori, kebutuhan cahaya, dan tingkat perawatan. Belum yakin? Coba <a href="{{ route('finder') }}"><u>Plant Finder</u></a>.</p>
</div>

<div class="container catalog" style="padding-bottom:80px">
    <aside>
        <button type="button" class="btn btn-glass filter-toggle btn-block" data-filter-toggle aria-expanded="false" aria-controls="filter-panel" style="margin-bottom:14px"><x-icon name="filter" :size="18" /> Filter produk</button>
        <form id="filter-panel" class="filter-panel glass" method="GET" action="{{ route('products') }}">
            <div class="field"><label for="fq">Cari</label><input id="fq" type="search" name="q" value="{{ request('q') }}" placeholder="Nama tanaman..."></div>
            <div class="field"><label for="fk">Kategori</label>
                <select id="fk" name="kategori" data-auto>
                    <option value="">Semua kategori</option>
                    @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('kategori') === $c->slug)>{{ $c->name }}</option>@endforeach
                </select></div>
            <div class="field"><label for="fh">Harga</label>
                <select id="fh" name="harga" data-auto>
                    <option value="">Semua harga</option>
                    <option value="a" @selected(request('harga') === 'a')>Di bawah Rp50.000</option>
                    <option value="b" @selected(request('harga') === 'b')>Rp50.000 – Rp100.000</option>
                    <option value="c" @selected(request('harga') === 'c')>Rp100.000 – Rp200.000</option>
                    <option value="d" @selected(request('harga') === 'd')>Di atas Rp200.000</option>
                </select></div>
            <div class="field"><label for="fc">Kebutuhan cahaya</label>
                <select id="fc" name="cahaya" data-auto>
                    <option value="">Semua</option>
                    @foreach(\App\Models\Plant::LIGHT as $k => $l)<option value="{{ $k }}" @selected(request('cahaya') === $k)>{{ $l }}</option>@endforeach
                </select></div>
            <div class="field"><label for="fp">Tingkat perawatan</label>
                <select id="fp" name="perawatan" data-auto>
                    <option value="">Semua</option>
                    @foreach(\App\Models\Plant::CARE as $k => $l)<option value="{{ $k }}" @selected(request('perawatan') === $k)>{{ $l }}</option>@endforeach
                </select></div>
            <div class="field"><label for="fr">Rating minimal</label>
                <select id="fr" name="rating" data-auto>
                    <option value="">Semua rating</option>
                    <option value="4" @selected(request('rating') === '4')>4 ke atas</option>
                    <option value="3" @selected(request('rating') === '3')>3 ke atas</option>
                </select></div>
            <label class="check"><input type="checkbox" name="stok" value="tersedia" @checked(request('stok') === 'tersedia') data-auto><span>Hanya yang tersedia</span></label>
            <input type="hidden" name="urut" value="{{ request('urut', request('q') ? 'relevan' : 'terbaru') }}">
            <div class="row"><button class="btn btn-primary btn-sm" style="flex:1">Terapkan</button><a class="btn btn-ghost btn-sm" href="{{ route('products') }}">Reset</a></div>
        </form>
    </aside>

    <section aria-live="polite">
        <div class="toolbar">
            <p class="muted" style="margin:0" role="status"><b>{{ $products->total() }}</b> produk ditemukan</p>
            <div class="field" style="flex-direction:row;align-items:center;gap:10px">
                <label for="fu" class="muted" style="font-weight:500">Urutkan</label>
                <form method="GET" action="{{ route('products') }}">
                    @foreach(request()->except(['urut', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <select id="fu" name="urut" data-auto style="min-height:42px;border-radius:999px;border:1px solid var(--line);background:var(--surface);padding:0 16px">
                        @php($sorts = (request('q') ? ['relevan' => 'Paling relevan'] : []) + ['terbaru' => 'Terbaru', 'populer' => 'Terpopuler', 'rating' => 'Rating tertinggi', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi'])
                        @foreach($sorts as $k => $l)<option value="{{ $k }}" @selected(request('urut', request('q') ? 'relevan' : 'terbaru') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </form>
            </div>
        </div>

        @if($products->count())
            <div class="product-grid" id="product-grid" style="grid-template-columns:repeat(3,1fr)">
                @foreach($products as $p) @include('partials.product-card', ['p' => $p]) @endforeach
            </div>
            @if($products->hasMorePages())
                <div class="row" style="justify-content:center;margin-top:36px">
                    <button type="button" class="btn btn-glass btn-lg" data-loadmore="{{ $products->nextPageUrl() }}">Muat lebih banyak</button>
                </div>
            @endif
            <noscript>{{ $products->links() }}</noscript>
        @else
            @if($didYouMean)<p style="margin:0 0 14px">Maksud Anda: <a href="{{ route('products', ['q' => $didYouMean]) }}"><u><b>{{ $didYouMean }}</b></u></a>?</p>@endif
            @include('partials.empty', ['icon' => 'search', 'title' => 'Produk tidak ditemukan', 'text' => 'Coba kata kunci lain atau longgarkan filter yang dipilih.', 'url' => route('products'), 'label' => 'Reset Filter'])
        @endif
    </section>
</div>
@endsection
