<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Clover — Botanical Living')</title>
    <meta name="description" content="@yield('description', 'Clover: toko tanaman hias, perlengkapan tanaman, dan jasa perawatan tanaman profesional.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @if(request()->routeIs('account*', 'checkout*', 'order.done', 'booking.done', 'cart.page', 'login*', 'register*', 'password.*', 'verification.*', 'search.suggest'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta property="og:site_name" content="Clover">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'Clover — Botanical Living')">
    <meta property="og:description" content="@yield('description', 'Clover: toko tanaman hias, perlengkapan tanaman, dan jasa perawatan tanaman profesional.')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('icons/icon-512.png'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#1c4630">
    @auth
        @if(config('clover.live.enabled', true))
            <meta name="clover-live" data-kind="user" data-interval="{{ config('clover.live.user_seconds', 15) }}" content="{{ route('account.live') }}">
        @endif
    @endauth
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <script>document.documentElement.classList.add('js');setTimeout(function(){if(!window.__cloverReady)document.documentElement.classList.add('reveal-all')},4000);</script>
    <script>try{var t=localStorage.getItem('clover_theme');if(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark'}if(t){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ @filemtime(public_path('css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ @filemtime(public_path('css/responsive.css')) }}">
    @auth<link rel="stylesheet" href="{{ asset('css/live.css') }}?v={{ @filemtime(public_path('css/live.css')) }}">@endauth
    @if(request()->routeIs('home'))
        @php
            $ld = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    ['@type' => 'Organization', 'name' => 'Clover', 'url' => url('/'), 'logo' => asset('icons/icon-512.png')],
                    ['@type' => 'WebSite', 'name' => 'Clover', 'url' => url('/'),
                        'potentialAction' => ['@type' => 'SearchAction', 'target' => route('products') . '?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
    @stack('head')
</head>
<body data-cart-url="{{ route('cart.add') }}">
<a class="skip" href="#main">Lewati ke konten</a>

@if($announcement)
    <div class="announce" id="announce" data-key="{{ $announcement->id }}-{{ $announcement->updated_at?->timestamp }}">
        <div class="container">
            <span>{{ $announcement->title }}@if($announcement->text) — {{ $announcement->text }}@endif</span>
            @if($announcement->link_url)<a href="{{ $announcement->link_url }}">{{ $announcement->link_label ?: 'Selengkapnya' }}</a>@endif
            <button type="button" data-announce-close aria-label="Tutup pengumuman"><x-icon name="x" :size="16" /></button>
        </div>
    </div>
@endif

<header class="nav" id="nav">
    <div class="container nav-inner">
        <a class="logo" href="{{ route('home') }}" aria-label="Clover, ke beranda">
            <x-icon name="sprout" :size="30" />
            <span>CLOVER<small>Botanical living</small></span>
        </a>

        <nav class="menu" id="menu" aria-label="Menu utama">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif>Beranda</a>

            <div class="nav-dd {{ request()->routeIs('products*') ? 'on' : '' }}">
                <button type="button" aria-expanded="false" aria-haspopup="true">Katalog <x-icon name="chevron-down" :size="14" /></button>
                <div class="dd-menu glass">
                    <a href="{{ route('products') }}"><x-icon name="grid" /><span>Semua Produk<small>Tanaman dan perlengkapan</small></span></a>
                    <hr>
                    @foreach($navCategories as $c)
                        <a href="{{ route('products', ['kategori' => $c->slug]) }}"><x-icon :name="$c->icon" /><span>{{ $c->name }}</span></a>
                    @endforeach
                </div>
            </div>

            <div class="nav-dd {{ request()->routeIs('services*', 'guide*', 'finder', 'health', 'booking.done') ? 'on' : '' }}">
                <button type="button" aria-expanded="false" aria-haspopup="true">Perawatan <x-icon name="chevron-down" :size="14" /></button>
                <div class="dd-menu glass">
                    <a href="{{ route('services') }}"><x-icon name="scissors" /><span>Jasa Perawatan<small>Pesan layanan profesional</small></span></a>
                    <a href="{{ route('guide') }}"><x-icon name="book" /><span>Panduan Perawatan<small>Cahaya, air, media tanam</small></span></a>
                    <a href="{{ route('finder') }}"><x-icon name="search" /><span>Plant Finder<small>Temukan tanaman yang cocok</small></span></a>
                    <a href="{{ route('health') }}"><x-icon name="lightbulb" /><span>Kesehatan Tanaman<small>Kenali masalah umum</small></span></a>
                </div>
            </div>

            <a href="{{ route('tutorial') }}" class="{{ request()->routeIs('tutorial*') ? 'on' : '' }}">Tips</a>

            <div class="nav-dd {{ request()->routeIs('about', 'contact', 'track', 'privacy', 'terms') ? 'on' : '' }}">
                <button type="button" aria-expanded="false" aria-haspopup="true">Perusahaan <x-icon name="chevron-down" :size="14" /></button>
                <div class="dd-menu glass">
                    <a href="{{ route('about') }}"><x-icon name="info" /><span>Tentang Kami</span></a>
                    <a href="{{ route('certificates') }}"><x-icon name="shield" /><span>Sertifikat &amp; Legalitas</span></a>
                    <a href="{{ route('contact') }}"><x-icon name="mail" /><span>Kontak</span></a>
                    <a href="{{ route('track') }}"><x-icon name="package" /><span>Lacak Pesanan</span></a>
                </div>
            </div>
            <button type="button" class="only-m menu-link" data-search="{{ route('products') }}"><x-icon name="search" :size="18" /> Cari produk</button>
        </nav>

        <div class="nav-actions">
            <div class="nav-pill">
                <button type="button" class="pill-btn pill-search" data-search="{{ route('products') }}" aria-label="Cari produk"><x-icon name="search" :size="20" /></button>
                <span class="pill-sep" aria-hidden="true"></span>
                <button type="button" class="pill-btn" data-open-cart aria-label="Buka keranjang, {{ $cartCount }} item"><x-icon name="cart" :size="20" />@if($cartCount)<i class="badge-dot">{{ $cartCount }}</i>@endif</button>
                <span class="pill-sep" aria-hidden="true"></span>
                <div class="nav-dd dd-right">
                    @auth
                        <button type="button" class="pill-btn pill-account" aria-expanded="false" aria-haspopup="true" aria-label="Menu akun {{ auth()->user()->name }}">
                            <span class="avatar">{{ auth()->user()->initials }}</span>
                            <i class="badge-dot" data-unread @if(! $unreadCount) hidden @endif>{{ $unreadCount }}</i>
                        </button>
                        <div class="dd-menu glass">
                            <div style="padding:8px 12px"><b>{{ auth()->user()->name }}</b><small class="muted" style="display:block">{{ auth()->user()->email }}</small></div>
                            <hr>
                            @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}"><x-icon name="settings" /><span>Dashboard Admin</span></a>@endif
                            <a href="{{ route('account') }}"><x-icon name="user" /><span>Akun Saya</span></a>
                            <a href="{{ route('account.orders') }}"><x-icon name="receipt" /><span>Pesanan</span></a>
                            <a href="{{ route('account.wishlist') }}"><x-icon name="heart" /><span>Wishlist</span></a>
                            <a href="{{ route('account.plants') }}"><x-icon name="sprout" /><span>Koleksi Tanaman Saya</span></a>
                            <a href="{{ route('account.calendar') }}"><x-icon name="calendar" /><span>Kalender Perawatan</span></a>
                            <a href="{{ route('account.notifications') }}"><x-icon name="bell" /><span>Notifikasi <span data-unread-text>@if($unreadCount)({{ $unreadCount }})@endif</span></span></a>
                            <hr>
                            <button type="button" class="dd-item" data-theme-toggle><x-icon name="moon" /><span>Mode gelap / terang</span></button>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="dd-item"><x-icon name="logout" /><span>Keluar</span></button></form>
                        </div>
                    @else
                        <button type="button" class="pill-btn pill-account" aria-expanded="false" aria-haspopup="true" aria-label="Menu akun"><x-icon name="user" :size="20" /></button>
                        <div class="dd-menu glass">
                            <a href="{{ route('login') }}"><x-icon name="user" /><span>Masuk<small>Ke akun Anda</small></span></a>
                            <a href="{{ route('register') }}"><x-icon name="plus" /><span>Daftar<small>Buat akun baru</small></span></a>
                            <hr>
                            <a href="{{ route('login') }}"><x-icon name="heart" /><span>Wishlist</span></a>
                            <button type="button" class="dd-item" data-theme-toggle><x-icon name="moon" /><span>Mode gelap / terang</span></button>
                        </div>
                    @endauth
                </div>
            </div>
            <button type="button" class="icon-btn burger" data-menu aria-label="Buka menu" aria-expanded="false"><x-icon name="menu" /></button>
        </div>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="logo" href="{{ route('home') }}"><x-icon name="sprout" :size="30" /><span>CLOVER<small>Botanical living</small></span></a>
                <p class="about">Toko tanaman hias, perlengkapan tanaman, dan jasa perawatan yang membantu Anda menghadirkan ruang hijau yang sehat dan indah.</p>
                <div class="social">
                    <a href="https://instagram.com" aria-label="Instagram"><x-icon name="instagram" /></a>
                    <a href="https://youtube.com" aria-label="YouTube"><x-icon name="youtube" /></a>
                    <a href="https://tiktok.com" aria-label="TikTok"><x-icon name="tiktok" /></a>
                    <a href="https://facebook.com" aria-label="Facebook"><x-icon name="facebook" /></a>
                </div>
            </div>
            <div>
                <h4>Belanja</h4>
                <a href="{{ route('products') }}">Katalog Tanaman</a>
                @foreach($navCategories->take(4) as $c)<a href="{{ route('products', ['kategori' => $c->slug]) }}">{{ $c->name }}</a>@endforeach
                <a href="{{ route('track') }}">Lacak Pesanan</a>
            </div>
            <div>
                <h4>Perawatan</h4>
                <a href="{{ route('services') }}">Jasa Perawatan</a>
                <a href="{{ route('guide') }}">Panduan Perawatan</a>
                <a href="{{ route('finder') }}">Plant Finder</a>
                <a href="{{ route('health') }}">Kesehatan Tanaman</a>
                <a href="{{ route('tutorial') }}">Tips Tanaman</a>
            </div>
            <div>
                <h4>Kontak</h4>
                <a href="https://wa.me/{{ \App\Models\Setting::wa() }}"><x-icon name="phone" :size="18" /> {{ \App\Models\Setting::get('whatsapp') }}</a>
                <a href="mailto:{{ \App\Models\Setting::get('email') }}"><x-icon name="mail" :size="18" /> {{ \App\Models\Setting::get('email') }}</a>
                <a href="{{ route('contact') }}"><x-icon name="map-pin" :size="18" /> {{ \App\Models\Setting::get('address') }}</a>
                <a href="{{ route('contact') }}"><x-icon name="clock" :size="18" /> {{ \App\Models\Setting::get('hours') }}</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} Clover. Hak cipta dilindungi.</span>
            <nav aria-label="Informasi hukum">
                <a href="{{ route('privacy') }}">Kebijakan Privasi</a>
                <a href="{{ route('terms') }}">Syarat Layanan</a>
                <a href="{{ route('about') }}">Tentang Kami</a>
                <a href="{{ route('certificates') }}">Sertifikat &amp; Legalitas</a>
            </nav>
        </div>
    </div>
</footer>

<div class="fab-wrap">
    <button type="button" class="fab" id="to-top" aria-label="Kembali ke atas"><x-icon name="arrow-up" /></button>
    <a class="fab" href="https://wa.me/{{ \App\Models\Setting::wa() }}?text={{ rawurlencode($__env->yieldContent('wa_text', 'Halo Clover, saya ingin bertanya.')) }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp Clover"><x-icon name="message" /></a>
</div>

<div class="overlay {{ session('open_cart') ? 'on' : '' }}" id="overlay" data-close-cart></div>
<aside class="drawer glass {{ session('open_cart') ? 'open' : '' }}" id="drawer" aria-label="Keranjang belanja" aria-hidden="{{ session('open_cart') ? 'false' : 'true' }}">
    <div class="drawer-head">
        <h2>Keranjang</h2>
        <button type="button" class="icon-btn" data-close-cart aria-label="Tutup keranjang"><x-icon name="x" /></button>
    </div>
    <div class="drawer-body">
        @forelse($cartLines as $l)
            @php($p = $l['product'])
            <div class="cart-line">
                @include('partials.photo', ['src' => $p->image_url, 'alt' => $p->name])
                <div>
                    <b>{{ $p->name }}</b>
                    <small>{{ $p->price_format }}</small>
                    <div class="qty-mini">
                        <form method="POST" action="{{ route('cart.update', $p) }}" class="inl" data-keep>@csrf @method('PATCH')<input type="hidden" name="qty" value="{{ $l['qty'] - 1 }}"><button aria-label="Kurangi jumlah {{ $p->name }}"><x-icon name="minus" :size="14" /></button></form>
                        <span>{{ $l['qty'] }}</span>
                        <form method="POST" action="{{ route('cart.update', $p) }}" class="inl" data-keep>@csrf @method('PATCH')<input type="hidden" name="qty" value="{{ $l['qty'] + 1 }}"><button aria-label="Tambah jumlah {{ $p->name }}" @disabled($l['qty'] >= $p->stock)><x-icon name="plus" :size="14" /></button></form>
                    </div>
                </div>
                <div style="text-align:right">
                    <b>Rp{{ number_format($l['subtotal'], 0, ',', '.') }}</b>
                    <form method="POST" action="{{ route('cart.remove', $p) }}" data-keep>@csrf @method('DELETE')<button class="link-danger" aria-label="Hapus {{ $p->name }}">Hapus</button></form>
                </div>
            </div>
        @empty
            @include('partials.empty', ['icon' => 'cart', 'title' => 'Keranjang Anda masih kosong', 'text' => 'Temukan tanaman favorit Anda dan tambahkan ke keranjang.', 'url' => route('products'), 'label' => 'Mulai Belanja'])
        @endforelse
    </div>
    @if($cartLines)
        <div class="drawer-foot">
            <div class="sum-row"><span class="muted">Subtotal</span><b>Rp{{ number_format($cartTotal, 0, ',', '.') }}</b></div>
            <small class="muted">Ongkir dan kupon dihitung saat checkout.</small>
            <a class="btn btn-primary btn-block" href="{{ route('checkout') }}">Checkout</a>
            <a class="btn btn-glass btn-block" href="{{ route('cart.page') }}">Lihat Keranjang</a>
        </div>
    @endif
</aside>

<div class="toasts" id="toasts" aria-live="polite">
    @if(session('toast'))<div class="toast glass" role="status"><x-icon name="check-circle" /><span>{{ session('toast') }}</span></div>@endif
</div>

<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
@auth<script src="{{ asset('js/live.js') }}?v={{ @filemtime(public_path('js/live.js')) }}" defer></script>@endauth
@stack('scripts')
</body>
</html>
