@extends('layouts.app')
@section('title', 'Syarat Layanan — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Syarat Layanan' => null]])
<div class="container" style="max-width:820px;padding:24px 0 96px">
    <h1 style="font-size:clamp(2rem,4vw,3rem)">Syarat Layanan</h1>
    <div class="alert warn" style="margin-bottom:24px"><x-icon name="info" /><span>Ini adalah naskah umum sebagai titik awal. Sesuaikan dan tinjau bersama penasihat hukum sebelum situs dipakai untuk bisnis nyata.</span></div>
    <h3>Pesanan produk</h3>
    <p>Harga, stok, dan biaya pengiriman dihitung oleh sistem saat pesanan dibuat. Pesanan dengan pembayaran transfer diproses setelah pembayaran diverifikasi admin. Pesanan dapat dibatalkan selama belum dikirim.</p>
    <h3>Tanaman sebagai produk hidup</h3>
    <p>Ukuran dan tampilan tanaman dapat sedikit berbeda dari foto. Periksa tanaman saat diterima dan hubungi kami dengan foto bila ada kerusakan.</p>
    <h3>Jasa perawatan</h3>
    <p>Pemesanan jasa bersifat permintaan hingga dikonfirmasi tim kami. Harga mulai dari bersifat estimasi dan dikonfirmasi setelah pemeriksaan lokasi.</p>
    <h3>Panduan perawatan</h3>
    <p>Panduan dan saran pada situs bersifat umum dan bukan diagnosis pasti. Kondisi tanaman dapat berbeda pada tiap lingkungan.</p>
    <h3>Akun</h3>
    <p>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan keakuratan data akun.</p>
</div>
@endsection
