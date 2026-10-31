@extends('layouts.app')
@section('title', 'Verifikasi Email — Clover')

@section('content')
<div class="container">
    <div class="auth">
        @include('auth._side', ['heading' => 'Satu langkah lagi', 'text' => 'Verifikasi email diperlukan untuk checkout, pemesanan jasa, dan ulasan.'])
        <div class="auth-form">
            <h1>Verifikasi email Anda</h1>
            <p class="muted">Kami mengirim tautan verifikasi ke <b>{{ auth()->user()->email }}</b>.</p>

            <ol class="list-check" style="margin:8px 0 20px">
                <li><x-icon name="mail" :size="18" /> Buka email Anda (periksa juga folder Spam/Promosi).</li>
                <li><x-icon name="check" :size="18" /> Klik tombol "Verifikasi Email" pada pesan dari Clover.</li>
                <li><x-icon name="user" :size="18" /> Selesai. Anda langsung bisa berbelanja dan memesan jasa.</li>
            </ol>

            @if($devVerify)
                <div class="alert warn" style="margin-bottom:16px"><x-icon name="info" /><span><b>Mode pengembangan lokal.</b> Email belum dikirim sungguhan (MAIL_MAILER=log). Anda dapat memverifikasi langsung tanpa email. Tombol ini otomatis hilang di server produksi.</span></div>
                <form method="POST" action="{{ route('verification.dev') }}" style="margin-bottom:12px">@csrf<button class="btn btn-primary btn-lg btn-block"><x-icon name="check" :size="18" /> Verifikasi sekarang (mode pengembangan)</button></form>
            @elseif($mailLog)
                <div class="alert warn" style="margin-bottom:16px"><x-icon name="info" /><span>Email belum dikirim sungguhan karena MAIL_MAILER=log. Hubungi admin untuk verifikasi manual, atau atur SMTP di server.</span></div>
            @endif

            <div class="row">
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn {{ $devVerify ? 'btn-glass' : 'btn-primary' }}">Kirim ulang tautan</button></form>
                <a class="btn btn-ghost" href="{{ route('verification.notice') }}">Saya sudah verifikasi</a>
            </div>

            <details style="margin-top:22px">
                <summary style="cursor:pointer;font-weight:600">Salah ketik email? Ganti email</summary>
                <form method="POST" action="{{ route('verification.change') }}" class="form" style="margin-top:14px" novalidate>
                    @csrf @method('PUT')
                    @include('partials.field', ['prefix' => 'v', 'name' => 'email', 'label' => 'Email yang benar', 'type' => 'email', 'value' => auth()->user()->email, 'autocomplete' => 'email'])
                    <div><button class="btn btn-glass btn-sm">Ganti dan kirim ulang</button></div>
                </form>
            </details>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:20px">@csrf<button class="btn btn-ghost btn-sm">Keluar</button></form>
        </div>
    </div>
</div>
@endsection
