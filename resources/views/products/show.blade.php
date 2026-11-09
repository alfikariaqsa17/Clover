@extends('layouts.app')
@section('title', $product->name . ' — Clover')
@section('description', \Illuminate\Support\Str::limit($product->description, 150))
@section('og_type', 'product')
@section('wa_text', 'Halo Clover, saya tertarik dengan ' . $product->name . ' (' . url()->current() . ')')
@php
    $imgUrl = $product->image_url;
    $absImg = $imgUrl ? (\Illuminate\Support\Str::startsWith($imgUrl, ['http://', 'https://']) ? $imgUrl : url($imgUrl)) : null;
@endphp
@if($absImg)@section('og_image', $absImg)@endif
@push('head')
@php
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => \Illuminate\Support\Str::limit(strip_tags($product->description), 300),
        'sku' => $product->slug,
        'brand' => ['@type' => 'Brand', 'name' => 'Clover'],
        'offers' => [
            '@type' => 'Offer', 'url' => url()->current(), 'priceCurrency' => 'IDR', 'price' => (string) $product->price,
            'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];
    if ($absImg) { $ld['image'] = [$absImg]; }
    if ($product->has_rating) {
        $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (string) $product->rating_avg, 'reviewCount' => (string) $product->reviews_count];
    }
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
@php
    $plant = $product->plant;
    $gallery = $product->gallery;
    $out = $product->stock < 1;
    $wished = \App\Support\Wish::has($product->id);
@endphp
@include('partials.breadcrumb', ['items' => array_filter(['Katalog Tanaman' => route('products'), ($product->category?->name ?? '') => $product->category ? route('products', ['kategori' => $product->category->slug]) : null, $product->name => null], fn ($v, $k) => $k !== '', ARRAY_FILTER_USE_BOTH)])

<div class="container pd">
    <div class="pd-gallery">
        <div class="pd-main">
            <div class="ph">
                <!-- GANTI FOTO PRODUK: Dashboard Admin > Produk > Edit > Foto produk -->
                @if($gallery)<img id="main-photo" src="{{ $gallery[0]['url'] }}" alt="{{ $gallery[0]['alt'] }}" fetchpriority="high" decoding="async" onerror="this.remove()">@endif
            </div>
        </div>
        @if(count($gallery) > 1)
            <div class="pd-thumbs">
                @foreach($gallery as $i => $g)
                    <button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-thumb="{{ $g['url'] }}" data-alt="{{ $g['alt'] }}" aria-label="Tampilkan foto {{ $i + 1 }}">
                        <div class="ph"><img src="{{ $g['url'] }}" alt="" loading="lazy"></div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <span class="eyebrow">{{ $product->category?->name ?? 'Tanaman' }}</span>
        <h1 style="font-size:clamp(2rem,4vw,3rem);margin-top:8px">{{ $product->name }}</h1>
        <div class="row" style="margin-bottom:6px">
            @if($product->has_rating)@include('partials.stars', ['rating' => $product->rating_avg, 'count' => $product->reviews_count])@else<span class="muted" style="font-size:.88rem">Belum ada ulasan</span>@endif
            <span class="tag {{ $out ? 'out' : ($product->stock <= 5 ? 'low' : 'ok') }}">{{ $out ? 'Stok habis' : ($product->stock <= 5 ? 'Sisa ' . $product->stock : 'Tersedia (' . $product->stock . ')') }}</span>
        </div>
        <div class="pd-price">{{ $product->price_format }}</div>
        <p>{{ $product->description }}</p>

        <div class="spec">
            @if($product->size)<div><x-icon name="package" /><span><small>Ukuran</small><b>{{ $product->size }}</b></span></div>@endif
            @if($plant)
                <div><x-icon name="sun" /><span><small>Kebutuhan cahaya</small><b>{{ $plant->light_text ?: $plant->light_label }}</b></span></div>
                <div><x-icon name="droplet" /><span><small>Jadwal penyiraman</small><b>{{ $plant->water_text ?: 'Setiap ' . $plant->water_days . ' hari' }}</b></span></div>
                <div><x-icon name="sprout" /><span><small>Tingkat perawatan</small><b>{{ $plant->care_label }}</b></span></div>
                @if($plant->soil)<div><x-icon name="pot" /><span><small>Media tanam</small><b>{{ $plant->soil }}</b></span></div>@endif
                @if($plant->pet_safe !== null)<div><x-icon name="paw" /><span><small>Hewan peliharaan</small><b>{{ $plant->pet_safe ? 'Aman' : 'Tidak aman jika tergigit' }}</b></span></div>@endif
            @endif
        </div>

        @if(! $out)
            <form method="POST" action="{{ route('cart.add') }}" class="stack" data-keep>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="row">
                    <div class="qty" data-qty>
                        <button type="button" data-step="-1" aria-label="Kurangi"><x-icon name="minus" :size="16" /></button>
                        <input type="number" name="qty" value="1" min="1" max="{{ min(99, $product->stock) }}" aria-label="Jumlah">
                        <button type="button" data-step="1" aria-label="Tambah"><x-icon name="plus" :size="16" /></button>
                    </div>
                    <span class="muted" style="font-size:.85rem">Maks. {{ min(99, $product->stock) }} per pesanan</span>
                </div>
                <div class="row">
                    <button class="btn btn-primary btn-lg"><x-icon name="cart" :size="18" /> Tambah ke Keranjang</button>
                    <button class="btn btn-glass btn-lg" name="buy" value="1">Beli Sekarang</button>
                </div>
            </form>
        @else
            <div class="alert warn"><x-icon name="alert" /><span>Stok produk ini sedang habis.</span></div>
        @endif

        <form method="POST" action="{{ route('wishlist.toggle', $product) }}" data-keep style="margin-top:14px">
            @csrf
            <button class="btn btn-ghost btn-sm"><x-icon name="heart" :size="16" /> {{ $wished ? 'Hapus dari wishlist' : 'Simpan ke wishlist' }}</button>
        </form>

        <div class="panel glass" style="margin-top:28px;padding:22px">
            <h3 style="font-size:1.05rem;margin-bottom:10px"><x-icon name="truck" :size="20" /> Estimasi pengiriman</h3>
            @foreach(config('clover.shipping') as $mk => $m)
                @php($min = \App\Support\CheckoutService::minCost($mk))
                <div class="sum-row"><span>{{ $m['label'] }}@if($min !== null && $min > 0) <small class="muted">· mulai Rp{{ number_format($min, 0, ',', '.') }}</small>@endif</span><span class="muted">{{ $m['estimate'] }}</span></div>
            @endforeach
            <small class="muted">Ongkir dihitung otomatis dari jarak ke alamat tujuan saat checkout. Estimasi waktu bergantung alamat dan konfirmasi pesanan.</small>
        </div>

        @if($plant)
            <p style="margin-top:20px"><a class="btn btn-ghost btn-sm" href="{{ route('guide.show', $plant) }}"><x-icon name="book" :size="16" /> Baca panduan perawatan lengkap</a></p>
        @endif
    </div>
</div>

@if($plant && $plant->tips)
    <section class="container" style="padding-bottom:40px">
        <div class="alert"><x-icon name="lightbulb" /><span><b>Tips perawatan:</b> {{ $plant->tips }}</span></div>
    </section>
@endif

<section class="container" id="ulasan" style="padding-bottom:72px">
    <div class="section-head"><div><span class="eyebrow">Ulasan</span><h2>Ulasan pembeli</h2></div></div>
    <div class="grid g-2" style="align-items:start">
        <div>
            @forelse($reviews as $rv)
                <div class="review">
                    <header><span class="avatar">{{ $rv->user->initials }}</span><div><b>{{ $rv->user->name }}</b><br>@include('partials.stars', ['rating' => $rv->rating])</div><span class="tag" style="margin-left:auto"><x-icon name="check" :size="14" /> Pembeli terverifikasi</span></header>
                    <p>{{ $rv->body }}</p>
                    @if($rv->photoUrls())
                        <div class="review-photos">@foreach($rv->photoUrls() as $u)<a href="{{ $u }}" target="_blank" rel="noopener"><img src="{{ $u }}" alt="Foto ulasan dari {{ $rv->user->name }}" loading="lazy" decoding="async"></a>@endforeach</div>
                    @endif
                    <small class="muted">{{ $rv->created_at->translatedFormat('j F Y') }}</small>
                </div>
            @empty
                @include('partials.empty', ['icon' => 'star', 'title' => 'Belum ada ulasan', 'text' => 'Ulasan hanya dari pembeli yang pesanannya sudah selesai.'])
            @endforelse
        </div>
        <div class="panel glass">
            @guest
                <h3>Tulis ulasan</h3><p class="muted">Masuk ke akun untuk menulis ulasan.</p><a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
            @else
                @if($canReview)
                    <h3>Tulis ulasan</h3>
                    <form method="POST" action="{{ route('reviews.store', $product) }}" class="form" enctype="multipart/form-data" novalidate>
                        @csrf
                        @include('partials.field', ['prefix' => 'r', 'name' => 'rating', 'label' => 'Rating', 'type' => 'select', 'options' => [5 => '5 - Sangat baik', 4 => '4 - Baik', 3 => '3 - Cukup', 2 => '2 - Kurang', 1 => '1 - Buruk'], 'assoc' => true])
                        @include('partials.field', ['prefix' => 'r', 'name' => 'body', 'label' => 'Ulasan Anda', 'type' => 'textarea'])
                        <div class="field">
                            <label for="r-photos">Foto (opsional, maks 3 foto)</label>
                            <input id="r-photos" type="file" name="photos[]" accept="image/png,image/jpeg,image/webp" multiple @error('photos') aria-invalid="true" @enderror>
                            @error('photos')<small class="error" role="alert">{{ $message }}</small>@enderror
                            @error('photos.*')<small class="error" role="alert">{{ $message }}</small>@enderror
                        </div>
                        <button class="btn btn-primary">Kirim Ulasan</button>
                    </form>
                @else
                    <h3>Tulis ulasan</h3>
                    <p class="muted">Ulasan dapat ditulis setelah pesanan yang memuat produk ini berstatus selesai, dan hanya satu ulasan per produk.</p>
                @endif
            @endguest
        </div>
    </div>
</section>

@if($related->count())
    <section class="container" style="padding-bottom:96px">
        <div class="section-head"><div><span class="eyebrow">Produk terkait</span><h2>Mungkin Anda suka</h2></div></div>
        <div class="product-grid">@foreach($related as $p) @include('partials.product-card', ['p' => $p]) @endforeach</div>
    </section>
@endif

<section class="container" id="tanya-jawab" style="padding-bottom:72px">
    <div class="section-head"><div><span class="eyebrow">Tanya jawab</span><h2>Ada pertanyaan tentang produk ini?</h2></div></div>
    <div class="grid g-2" style="align-items:start">
        <div>
            @forelse($questions as $qa)
                <div class="qa">
                    <p class="qa-q"><x-icon name="message" :size="18" /> <span>{{ $qa->question }}</span></p>
                    @if($qa->answer)
                        <p class="qa-a"><b>Clover:</b> {{ $qa->answer }}</p>
                    @else
                        <p class="qa-a muted">Menunggu jawaban tim Clover.</p>
                    @endif
                    <small class="muted">{{ $qa->user?->name ?? 'Pengunjung' }} · {{ $qa->created_at->translatedFormat('j F Y') }}</small>
                </div>
            @empty
                @include('partials.empty', ['icon' => 'message', 'title' => 'Belum ada pertanyaan', 'text' => 'Jadilah yang pertama bertanya tentang ukuran, perawatan, atau pengiriman.'])
            @endforelse
        </div>
        <div class="panel glass">
            <h3>Ajukan pertanyaan</h3>
            @guest
                <p class="muted">Masuk ke akun untuk bertanya.</p><a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
            @else
                <form method="POST" action="{{ route('questions.store', $product) }}" class="form" novalidate>
                    @csrf
                    @include('partials.field', ['prefix' => 'q', 'name' => 'question', 'label' => 'Pertanyaan Anda', 'type' => 'textarea'])
                    <button class="btn btn-primary">Kirim Pertanyaan</button>
                </form>
            @endguest
            <p class="muted" style="margin:12px 0 0;font-size:.88rem">Perlu jawaban cepat? <a href="https://wa.me/{{ \App\Models\Setting::wa() }}?text={{ rawurlencode('Halo Clover, saya tertarik dengan ' . $product->name) }}" target="_blank" rel="noopener"><u>Chat via WhatsApp</u></a>.</p>
        </div>
    </div>
</section>
@endsection
