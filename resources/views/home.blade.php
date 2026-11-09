@extends('layouts.app')
@section('title', 'Clover — Tanaman Hias & Perawatan Tanaman Profesional')

@section('content')
{{-- C. HERO --}}
<section class="hero">
    <x-icon name="leaf" :size="220" class="deco-leaf" style="left:-60px;top:40px" />
    <x-icon name="sprout" :size="160" class="deco-leaf" style="right:6%;bottom:30px;animation-delay:-4s" />
    <div class="container hero-grid">
        <div>
            <span class="eyebrow reveal">{{ \App\Models\Setting::get('hero_eyebrow') }}</span>
            <h1 class="reveal" data-d="1">{{ \App\Models\Setting::get('hero_title') }}</h1>
            <p class="lead reveal" data-d="2"><b>{{ \App\Models\Setting::get('hero_subtitle') }}.</b> {{ \App\Models\Setting::get('hero_desc') }}</p>
            <div class="hero-cta reveal" data-d="3">
                <a class="btn btn-primary btn-lg" href="{{ route('products') }}">Jelajahi Tanaman <x-icon name="arrow-right" :size="18" class="ico ico-go" /></a>
                <a class="btn btn-glass btn-lg" href="{{ route('services') }}">Konsultasi Perawatan</a>
            </div>
            <div class="hero-stats reveal" data-d="3">
                <div><b>{{ $stats['products'] }}</b><span>Produk tersedia</span></div>
                <div><b>{{ $stats['plants'] }}</b><span>Panduan tanaman</span></div>
                <div><b>{{ $stats['services'] }}</b><span>Layanan perawatan</span></div>
            </div>
        </div>
        <div class="hero-media reveal" data-d="2">
            <div class="hero-photo" data-parallax>
                <!-- GANTI FOTO HERO: Dashboard Admin > Pengaturan Situs > Foto utama Hero -->
                @include('partials.photo', ['src' => \App\Models\Setting::img('hero_bg'), 'alt' => 'Tanaman hias di ruangan modern', 'eager' => true])
            </div>
            <div class="hero-chip glass c1"><x-icon name="package" :size="24" /><span>Pengemasan aman<small>Tanaman tiba segar</small></span></div>
            <div class="hero-chip glass c2"><x-icon name="message" :size="24" /><span>Konsultasi perawatan<small>Tanya tim kami</small></span></div>
        </div>
    </div>
</section>

{{-- D. TRUST --}}
<section class="section-sm">
    <div class="container trust">
        @foreach([['leaf', 'Tanaman Berkualitas', 'Dipilih satu per satu dan dirawat sebelum dikirim.'], ['package', 'Pengemasan Aman', 'Dikemas khusus agar tanaman tiba dalam kondisi baik.'], ['message', 'Konsultasi Perawatan', 'Tanyakan cara merawat tanaman Anda kepada tim kami.'], ['shield', 'Layanan Profesional', 'Perawatan taman oleh tim berpengalaman dan terjadwal.']] as $i => $t)
            <div class="trust-item glass reveal" data-d="{{ $i % 4 }}">
                <span class="ico-wrap"><x-icon :name="$t[0]" :size="24" /></span>
                <div><h3>{{ $t[1] }}</h3><p>{{ $t[2] }}</p></div>
            </div>
        @endforeach
    </div>
</section>

{{-- E. KATEGORI --}}
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div><span class="eyebrow">Kategori</span><h2>Temukan sesuai kebutuhan ruang Anda</h2></div>
            <a class="btn btn-ghost" href="{{ route('products') }}">Semua kategori <x-icon name="arrow-right" :size="16" /></a>
        </div>
        <div class="cat-grid">
            @foreach($categories as $c)
                <a class="cat-tile reveal" href="{{ route('products', ['kategori' => $c->slug]) }}">
                    <span class="ico-wrap"><x-icon :name="$c->icon" :size="26" /></span>
                    <div><h3>{{ $c->name }}</h3><small>{{ $c->products_count }} produk</small></div>
                    <x-icon name="arrow-right" class="ico arrow" />
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- F. PRODUK UNGGULAN --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head reveal">
            <div><span class="eyebrow">Produk Unggulan</span><h2>Pilihan favorit pelanggan</h2><p>Tanaman sehat dan perlengkapan terpilih untuk mempercantik ruang Anda.</p></div>
            <a class="btn btn-ghost" href="{{ route('products') }}">Lihat semua <x-icon name="arrow-right" :size="16" /></a>
        </div>
        @if($featured->count())
            <div class="product-grid">@foreach($featured as $p) @include('partials.product-card', ['p' => $p]) @endforeach</div>
        @else
            @include('partials.empty', ['icon' => 'leaf', 'title' => 'Belum ada produk unggulan', 'text' => 'Tandai produk sebagai unggulan dari dashboard admin.'])
        @endif
    </div>
</section>

{{-- G. PLANT OF THE WEEK --}}
@if($weekProduct)
    @php($wp = $weekProduct->plant)
    <section class="section" style="padding-top:0">
        <div class="container">
            <div class="potw reveal">
                <div class="potw-media">@include('partials.photo', ['src' => $weekProduct->image_url, 'alt' => $weekProduct->name])</div>
                <div class="potw-body">
                    <span class="eyebrow">Plant of the Week</span>
                    <h2>{{ $weekProduct->name }}</h2>
                    @if($wp && $wp->latin_name)<p class="muted" style="font-style:italic;margin:0">{{ $wp->latin_name }}</p>@endif
                    <p>{{ $weekProduct->description }}</p>
                    @if($wp)
                        <div class="chips">
                            <span class="chip"><x-icon name="sun" :size="16" /> {{ $wp->light_label }}</span>
                            <span class="chip"><x-icon name="droplet" :size="16" /> Siram setiap {{ $wp->water_days }} hari</span>
                            <span class="chip"><x-icon name="sprout" :size="16" /> Perawatan {{ strtolower($wp->care_label) }}</span>
                        </div>
                    @endif
                    <div class="row">
                        <a class="btn btn-primary" href="{{ route('products.show', $weekProduct) }}">Lihat Detail <x-icon name="arrow-right" :size="16" class="ico ico-go" /></a>
                        @if($wp)<a class="btn btn-glass" href="{{ route('guide.show', $wp) }}">Panduan Perawatan</a>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

{{-- H. JASA PERAWATAN --}}
<section class="section" style="background:var(--bg-2)">
    <div class="container">
        <div class="section-head reveal">
            <div><span class="eyebrow">Jasa Perawatan Tanaman</span><h2>Biarkan tim Clover merawat ruang hijau Anda</h2><p>Dari perawatan rutin rumah dan kantor, repotting, hingga penataan tanaman.</p></div>
            <a class="btn btn-ghost" href="{{ route('services') }}">Semua layanan <x-icon name="arrow-right" :size="16" /></a>
        </div>
        <div class="grid g-3">@foreach($services as $s) @include('partials.service-card', ['s' => $s]) @endforeach</div>
    </div>
</section>

@if($promos->count())
    <section class="section">
        <div class="container promo-grid">
            @foreach($promos as $b)
                <div class="promo reveal">
                    @include('partials.photo', ['src' => $b->image_url, 'alt' => $b->title])
                    <h3>{{ $b->title }}</h3>
                    @if($b->text)<p>{{ $b->text }}</p>@endif
                    @if($b->link_url)<div><a class="btn btn-glass" style="color:#fff;background:rgba(255,255,255,.16)" href="{{ $b->link_url }}">{{ $b->link_label ?: 'Selengkapnya' }}</a></div>@endif
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- I. INSPIRASI RUANGAN --}}
@if($inspirations->count())
    <section class="section">
        <div class="container">
            <div class="section-head reveal"><div><span class="eyebrow">Inspirasi Ruangan</span><h2>Tanaman yang hidup bersama ruang Anda</h2></div></div>
            <div class="gallery">
                @foreach($inspirations as $b)
                    <a href="{{ $b->link_url ?: route('products') }}" class="reveal">
                        <!-- GANTI FOTO: Dashboard Admin > Banner & Promo > jenis Galeri Inspirasi Ruangan -->
                        @include('partials.photo', ['src' => $b->image_url, 'alt' => $b->title])
                        <div class="cap"><h3>{{ $b->title }}</h3><p>{{ $b->text }}</p></div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- J. TIPS & ARTIKEL --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head reveal">
            <div><span class="eyebrow">Tips & Artikel</span><h2>Belajar merawat tanaman dengan benar</h2></div>
            <a class="btn btn-ghost" href="{{ route('tutorial') }}">Semua artikel <x-icon name="arrow-right" :size="16" /></a>
        </div>
        <div class="grid g-3">@foreach($articles as $a) @include('partials.article-card', ['a' => $a]) @endforeach</div>
    </div>
</section>

{{-- K. TESTIMONI (hanya ulasan nyata dari pembeli terverifikasi) --}}
@if($certificates->count())
<section class="section" id="sertifikat">
    <div class="container">
        <div class="section-head reveal">
            <div><span class="eyebrow">Kepercayaan</span><h2>Legalitas &amp; sertifikat kami</h2><p>Foto dokumen asli yang dapat Anda lihat dan perbesar sendiri.</p></div>
            <a class="btn btn-ghost btn-sm" href="{{ route('certificates') }}">Lihat semua <x-icon name="arrow-right" :size="16" /></a>
        </div>
        @include('partials.certificates', ['items' => $certificates])
    </div>
</section>
@endif

<section class="section" style="background:var(--bg-2)">
    <div class="container">
        <div class="section-head reveal"><div><span class="eyebrow">Testimoni</span><h2>Kata pelanggan yang sudah membeli</h2><p>Ulasan hanya ditampilkan dari pembeli dengan pesanan yang telah selesai.</p></div></div>
        @if($testimonials->count())
            <div class="grid g-3">
                @foreach($testimonials as $t)
                    <figure class="quote reveal" style="margin:0">
                        @include('partials.stars', ['rating' => $t->rating])
                        <p>{{ $t->body }}</p>
                        <footer><span class="avatar">{{ $t->user->initials }}</span><div><b>{{ $t->user->name }}</b><small>Membeli <a href="{{ route('products.show', $t->product) }}"><u>{{ $t->product->name }}</u></a></small></div></footer>
                    </figure>
                @endforeach
            </div>
        @else
            @include('partials.empty', ['icon' => 'star', 'title' => 'Belum ada ulasan', 'text' => 'Ulasan akan muncul di sini setelah pelanggan menyelesaikan pesanan dan menulis ulasannya.', 'url' => route('products'), 'label' => 'Mulai Belanja'])
        @endif
    </div>
</section>

{{-- Konsultasi CTA --}}
<section class="section">
    <div class="container">
        <div class="cta reveal">
            <!-- GANTI FOTO: Dashboard Admin > Pengaturan Situs > Background banner konsultasi -->
            @include('partials.photo', ['src' => \App\Models\Setting::img('cta_bg'), 'alt' => 'Taman hijau terawat'])
            <h2>{{ \App\Models\Setting::get('cta_title') }}</h2>
            <p>{{ \App\Models\Setting::get('cta_desc') }}</p>
            <div class="row" style="justify-content:center;margin-top:24px">
                <a class="btn btn-glass btn-lg" href="{{ route('services.show', 'konsultasi-perawatan') }}">Konsultasi Perawatan</a>
                <a class="btn btn-ghost btn-lg" style="color:#fff;border-color:rgba(255,255,255,.4)" href="{{ route('finder') }}">Coba Plant Finder</a>
            </div>
        </div>
    </div>
</section>

{{-- L. NEWSLETTER --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="newsletter reveal">
            @include('partials.photo', ['src' => \App\Models\Setting::img('newsletter_bg'), 'alt' => ''])
            <div>
                <span class="eyebrow" style="color:#c5d89a">Komunitas Clover</span>
                <h2>Tips tanaman langsung ke email Anda</h2>
                <p>Panduan perawatan, produk baru, dan promo untuk pecinta tanaman. Berhenti kapan saja.</p>
            </div>
            <form method="POST" action="{{ route('newsletter') }}" class="form" novalidate>
                @csrf
                <div class="field">
                    <label for="nl-email">Alamat email</label>
                    <input id="nl-email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email" @error('email') aria-invalid="true" @enderror>
                    @error('email')<small class="error" role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label class="check"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>Saya setuju data email saya digunakan untuk mengirim newsletter sesuai <a href="{{ route('privacy') }}"><u>Kebijakan Privasi</u></a>.</span></label>
                    @error('consent')<small class="error" role="alert">{{ $message }}</small>@enderror
                </div>
                <button class="btn btn-glass btn-lg" style="color:#fff;background:rgba(255,255,255,.16)">Berlangganan</button>
            </form>
        </div>
    </div>
</section>
@endsection
