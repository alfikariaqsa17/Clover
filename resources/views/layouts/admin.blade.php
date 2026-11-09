<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @if(config('clover.live.enabled', true))<meta name="clover-live" data-kind="admin" data-interval="{{ config('clover.live.admin_seconds', 10) }}" content="{{ route('admin.live') }}">@endif
    <title>@yield('title', 'Admin') — Clover Admin</title>
    <script>try{var t=localStorage.getItem('clover_theme');if(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark'}if(t){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ @filemtime(public_path('css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/live.css') }}?v={{ @filemtime(public_path('css/live.css')) }}">
</head>
<body class="adm" data-cart-url="">
@php
    $groups = [
        'Ringkasan' => [['admin.dashboard', 'chart', 'Dashboard', 'admin.dashboard', null], ['admin.reports', 'receipt', 'Laporan Penjualan', 'admin.reports*', null]],
        'Transaksi' => [['admin.orders.index', 'package', 'Pesanan', 'admin.orders.*', 'orders'], ['admin.payments.index', 'check-circle', 'Pembayaran QRIS', 'admin.payments.*', 'payments'], ['admin.returns.index', 'package', 'Retur', 'admin.returns.*', 'returns'], ['admin.refunds.index', 'receipt', 'Refund', 'admin.refunds.*', 'refunds'], ['admin.bookings.index', 'tool', 'Booking Jasa', 'admin.bookings.*', 'bookings'], ['admin.blocked-dates.index', 'calendar', 'Libur & Blokir Jadwal', 'admin.blocked-dates.*', null], ['admin.questions.index', 'message', 'Tanya Jawab', 'admin.questions.*', 'questions'], ['admin.reviews.index', 'star', 'Ulasan', 'admin.reviews.*', 'reviews']],
        'Katalog' => [['admin.products.index', 'sprout', 'Produk & Stok', 'admin.products.*', null], ['admin.categories.index', 'grid', 'Kategori', 'admin.categories.*', null], ['admin.plants.index', 'leaf', 'Data Tanaman', 'admin.plants.*', null], ['admin.problems.index', 'lightbulb', 'Panduan Kesehatan', 'admin.problems.*', null]],
        'Layanan & Konten' => [['admin.services.index', 'scissors', 'Layanan Jasa', 'admin.services.*', null], ['admin.articles.index', 'book', 'Artikel Tips', 'admin.articles.*', null], ['admin.article-categories.index', 'tag', 'Kategori Artikel', 'admin.article-categories.*', null], ['admin.certificates.index', 'shield', 'Sertifikat & Legalitas', 'admin.certificates.*', null], ['admin.banners.index', 'image', 'Banner & Promo', 'admin.banners.*', null], ['admin.coupons.index', 'tag', 'Kupon', 'admin.coupons.*', null]],
        'Pengguna' => [['admin.users.index', 'users', 'Pengguna & Persetujuan', 'admin.users.*', 'accounts'], ['admin.messages.index', 'mail', 'Pesan Masuk', 'admin.messages.*', 'messages'], ['admin.subscribers.index', 'mail', 'Subscriber', 'admin.subscribers.*', null]],
        'Sistem' => [['admin.settings', 'settings', 'Pengaturan Situs', 'admin.settings*', null], ['admin.qris.edit', 'image', 'Pengaturan QRIS', 'admin.qris.*', null]],
    ];
@endphp
<a class="skip" href="#content">Lewati ke konten</a>
<div class="ashell">
    <aside class="aside" id="aside" aria-label="Menu admin">
        <a class="logo" href="{{ route('admin.dashboard') }}"><x-icon name="sprout" :size="28" /><span>CLOVER<small>Dashboard admin</small></span></a>
        <nav>
            @foreach($groups as $title => $items)
                <div class="grp">{{ $title }}</div>
                @foreach($items as [$r, $ic, $label, $pat, $badge])
                    <a href="{{ route($r) }}" class="{{ request()->routeIs($pat) ? 'on' : '' }}"@if($badge) data-badge="{{ $badge }}"@endif><x-icon :name="$ic" :size="18" /> {{ $label }}@if($badge && ($badges[$badge] ?? 0))<span class="cnt">{{ $badges[$badge] }}</span>@endif</a>
                @endforeach
            @endforeach
        </nav>
        <div class="aside-foot">
            <a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external" :size="18" /> Lihat situs</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button><x-icon name="logout" :size="18" /> Keluar</button></form>
        </div>
    </aside>
    <div class="amain">
        <div class="atop">
            <button type="button" class="icon-btn aburger" data-aside aria-label="Buka menu admin" aria-expanded="false"><x-icon name="menu" /></button>
            <span>Masuk sebagai <b>{{ auth()->user()->name }}</b></span>
            <button type="button" class="icon-btn" data-theme-toggle aria-label="Ganti mode tampilan"><x-icon name="moon" /></button>
        </div>
        @if(session('toast'))<div class="flash" role="status"><x-icon name="check-circle" /> {{ session('toast') }}</div>@endif
        @if($errors->any())<div class="flash bad" role="alert"><x-icon name="alert" /> Ada isian yang perlu diperbaiki. Periksa pesan merah pada formulir.</div>@endif
        <main id="content">@yield('content')</main>
    </div>
</div>
<div class="toasts" id="toasts"></div>
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/live.js') }}?v={{ @filemtime(public_path('js/live.js')) }}" defer></script>
</body>
</html>
