@extends('layouts.app')
@section('title', 'Menunggu Persetujuan — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Pendaftaran diterima', 'text' => 'Tim Clover akan memeriksa dan menyetujui akun Anda.'])
        <div class="auth-form">
            <div class="row"><x-icon name="clock" :size="30" style="color:var(--forest-2)" /><h1 style="margin:0">Menunggu persetujuan admin</h1></div>
            <p class="muted" style="margin-top:12px">Terima kasih. Akun untuk <b>{{ $email }}</b> sudah kami terima. Anda <b>belum dapat masuk</b> sampai admin menyetujuinya.</p>
            <ol class="list-check" style="margin:8px 0 20px">
                <li><x-icon name="check" :size="18" /> Admin memeriksa data pendaftaran Anda.</li>
                <li><x-icon name="check" :size="18" /> Setelah disetujui, Anda dapat masuk dengan email dan kata sandi tadi.</li>
                <li><x-icon name="check" :size="18" /> Mencoba masuk lebih awal akan menampilkan status akun Anda.</li>
            </ol>
            <a class="btn btn-primary btn-lg btn-block" href="https://wa.me/{{ \App\Models\Setting::wa() }}?text={{ urlencode('Halo admin Clover, saya baru mendaftar dengan email ' . $email . '. Mohon disetujui, terima kasih.') }}" target="_blank" rel="noopener"><x-icon name="message" :size="18" /> Percepat lewat WhatsApp admin</a>
            <div class="row" style="margin-top:12px"><a class="btn btn-glass" href="{{ route('login') }}">Ke halaman masuk</a><a class="btn btn-ghost" href="{{ route('home') }}">Kembali ke beranda</a></div>
        </div>
    </div>
</div>
@endsection
