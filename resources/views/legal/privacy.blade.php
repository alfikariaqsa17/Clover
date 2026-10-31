@extends('layouts.app')
@section('title', 'Kebijakan Privasi — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Kebijakan Privasi' => null]])
<div class="container" style="max-width:820px;padding:24px 0 96px">
    <h1 style="font-size:clamp(2rem,4vw,3rem)">Kebijakan Privasi</h1>
    <div class="alert warn" style="margin-bottom:24px"><x-icon name="info" /><span>Ini adalah naskah umum sebagai titik awal. Sesuaikan dan tinjau bersama penasihat hukum sebelum situs dipakai untuk bisnis nyata.</span></div>
    <p class="muted">Terakhir diperbarui: {{ now()->translatedFormat('j F Y') }}</p>
    <h3>Data yang kami kumpulkan</h3>
    <p>Kami mengumpulkan data yang Anda berikan secara langsung: nama, email, nomor telepon, alamat pengiriman, isi pesanan, pemesanan jasa, serta data koleksi tanaman dan jadwal perawatan yang Anda simpan.</p>
    <h3>Penggunaan data</h3>
    <p>Data dipakai untuk memproses pesanan dan pemesanan jasa, mengirim notifikasi terkait akun, memverifikasi email, dan, bila Anda setuju, mengirim newsletter. Kami tidak menjual data pribadi Anda.</p>
    <h3>Penyimpanan dan keamanan</h3>
    <p>Kata sandi disimpan dalam bentuk hash. Akses ke data pelanggan dibatasi untuk administrator yang berwenang.</p>
    <h3>Hak Anda</h3>
    <p>Anda dapat memperbarui profil dan alamat kapan saja, berhenti berlangganan newsletter, serta meminta penghapusan akun melalui halaman <a href="{{ route('contact') }}"><u>Kontak</u></a>.</p>
    <h3>Kontak</h3>
    <p>Pertanyaan tentang privasi dapat dikirim ke {{ \App\Models\Setting::get('email') }}.</p>
</div>
@endsection
