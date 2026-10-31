<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — Clover</title>
    <script>try{var t=localStorage.getItem('clover_theme');if(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark'}if(t){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
</head>
<body>
<main style="min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 70% 20%,color-mix(in srgb,var(--sage) 40%,transparent),transparent 60%),var(--bg)">
    <div class="panel glass" style="max-width:560px;text-align:center;padding:48px 36px">
        <a class="logo" href="/" style="justify-content:center;margin-bottom:24px"><x-icon name="sprout" :size="30" /><span>CLOVER</span></a>
        <div class="eyebrow">Error @yield('code')</div>
        <h1 style="font-size:2.4rem;margin:8px 0">@yield('title')</h1>
        <p class="muted">@yield('message')</p>
        <div class="row" style="justify-content:center;margin-top:24px">
            <a class="btn btn-primary" href="/">Kembali ke Beranda</a>
            <a class="btn btn-glass" href="javascript:history.back()">Halaman sebelumnya</a>
        </div>
    </div>
</main>
</body>
</html>
