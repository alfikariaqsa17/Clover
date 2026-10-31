@extends('layouts.admin')
@section('title', 'Pembayaran ' . $order->number)

@section('content')
@php
    $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
    $map = ['submitted' => 'awaiting_verification', 'verified' => 'paid', 'rejected' => 'rejected'];
    $latest = $proofs->first();
    $review = $pay->status === 'awaiting_verification' && $latest && $latest->status === 'submitted';
@endphp
<div class="ah">
    <div><h1>Pembayaran {{ $order->number }}</h1><p>Pesanan <a href="{{ route('admin.orders.show', $order) }}"><u>{{ $order->number }}</u></a> · {{ $order->created_at->format('d-m-Y H:i') }}</p></div>
    <a class="btn btn-glass" href="{{ route('admin.payments.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a>
</div>

@if($ownOrder)
    <div class="alert bad" style="margin-bottom:16px"><x-icon name="alert" /><span>Ini pesanan akun Anda sendiri. Demi keamanan, admin tidak dapat memverifikasi atau menolak pesanannya sendiri. Minta admin lain.</span></div>
@endif

<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>Bukti transaksi</h2>
        @if($latest)
            <a href="{{ route('admin.payments.proof', [$order, $latest]) }}" target="_blank" rel="noopener"><img src="{{ route('admin.payments.proof', [$order, $latest]) }}" alt="Bukti pembayaran terbaru dari {{ $order->name }}" style="max-width:100%;max-height:520px;border-radius:12px;border:1px solid var(--line)"></a>
            <p class="muted" style="margin:8px 0 0">Diunggah {{ $latest->created_at->format('d-m-Y H:i') }} · {{ round($latest->size / 1024) }} KB @if($latest->note)· Catatan pelanggan: {{ $latest->note }}@endif</p>
        @else
            <p class="muted">Pelanggan belum mengunggah bukti.</p>
        @endif
        <div class="alert warn" style="margin-top:14px"><x-icon name="alert" /><span><b>Screenshot bukan bukti dana masuk.</b> Screenshot mudah dipalsukan. Cocokkan nominal, tanggal, dan jam dengan <u>riwayat transaksi di aplikasi GoPay Merchant</u> Anda sebelum memverifikasi.</span></div>
    </section>

    <section class="box">
        <h2>Detail pesanan</h2>
        <dl class="kv">
            <dt>Status pembayaran</dt><dd>@include('partials.status', ['s' => $pay->status, 'label' => $pay->status_label])</dd>
            <dt>Status pesanan</dt><dd>@include('partials.status', ['s' => $order->status])</dd>
            <dt>Nominal yang harus masuk</dt><dd><b style="font-size:1.2rem">{{ $rp($pay->amount) }}</b></dd>
            <dt>Kode pesanan</dt><dd>{{ $order->number }}</dd>
            <dt>Pelanggan</dt><dd>{{ $order->name }} · {{ $order->whatsapp }}@if($order->user)<br><small class="muted">{{ $order->user->email }}</small>@endif</dd>
            @if($pay->verified_at)<dt>Diverifikasi</dt><dd>{{ $pay->verified_at->format('d-m-Y H:i') }} oleh {{ $pay->verifier?->name ?? 'admin' }}@if($pay->reference)<br><small class="muted">Ref: {{ $pay->reference }}</small>@endif</dd>@endif
            @if($pay->status === 'rejected')<dt>Alasan penolakan</dt><dd>{{ $pay->rejection_reason }}</dd>@endif
        </dl>
        <table class="table-simple">
            @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>{{ $rp($i->price * $i->qty) }}</td></tr>@endforeach
            <tr><td>Subtotal</td><td>{{ $rp($order->subtotal) }}</td></tr>
            @if($order->discount)<tr><td>Diskon</td><td>- {{ $rp($order->discount) }}</td></tr>@endif
            <tr><td>Ongkos kirim</td><td>{{ $order->shipping ? $rp($order->shipping) : 'Gratis' }}</td></tr>
            <tr><td><b>Total</b></td><td><b>{{ $rp($order->total) }}</b></td></tr>
        </table>
    </section>
</div>

@if($review && ! $ownOrder)
<div class="agrid" style="grid-template-columns:1fr 1fr;margin-top:20px">
    <section class="box">
        <h2>Verifikasi pembayaran</h2>
        <form method="POST" action="{{ route('admin.payments.verify', $order) }}" class="form" data-confirm="Verifikasi pembayaran {{ $order->number }} sebesar {{ $rp($pay->amount) }}? Pesanan akan langsung diproses.">
            @csrf
            @include('partials.field', ['prefix' => 'vf', 'name' => 'reference', 'label' => 'Referensi transaksi dari aplikasi merchant (opsional)', 'optional' => true])
            @include('partials.field', ['prefix' => 'vf', 'name' => 'confirm_funds', 'label' => 'Saya sudah memeriksa aplikasi GoPay Merchant dan dana ' . $rp($pay->amount) . ' benar-benar masuk.', 'type' => 'checkbox'])
            <button class="btn btn-primary"><x-icon name="check-circle" :size="18" /> Verifikasi</button>
        </form>
    </section>
    <section class="box">
        <h2>Tolak bukti</h2>
        <form method="POST" action="{{ route('admin.payments.reject', $order) }}" class="form" data-confirm="Tolak bukti pembayaran ini? Pelanggan akan diminta mengunggah ulang.">
            @csrf
            @include('partials.field', ['prefix' => 'rj', 'name' => 'rejection_reason', 'label' => 'Alasan penolakan (dilihat pelanggan)', 'type' => 'textarea'])
            <button class="btn btn-danger">Tolak</button>
        </form>
    </section>
</div>
@elseif(! $review)
    <section class="box" style="margin-top:20px"><p class="muted" style="margin:0">Tidak ada tindakan: pembayaran ini tidak sedang menunggu verifikasi.</p></section>
@endif

<section class="box" style="margin-top:20px">
    <h2>Riwayat bukti</h2>
    @forelse($proofs as $pf)
        <div class="row between" style="padding:10px 0;border-bottom:1px solid var(--line);align-items:flex-start">
            <span><b>{{ $pf->created_at->format('d-m-Y H:i') }}</b> · {{ round($pf->size / 1024) }} KB
                @if($pf->note)<br><small class="muted">Catatan pelanggan: {{ $pf->note }}</small>@endif
                @if($pf->reason)<br><small>Alasan: {{ $pf->reason }}</small>@endif
                @if($pf->reviewed_at)<br><small class="muted">Ditinjau {{ $pf->reviewed_at->format('d-m-Y H:i') }} oleh {{ $pf->reviewer?->name ?? 'admin' }}</small>@endif
                <br><a href="{{ route('admin.payments.proof', [$order, $pf]) }}" target="_blank" rel="noopener"><small><u>Buka bukti</u></small></a></span>
            @include('partials.status', ['s' => $map[$pf->status] ?? $pf->status, 'label' => $pf->status_label])
        </div>
    @empty
        <p class="muted" style="margin:0">Belum ada bukti.</p>
    @endforelse
</section>
@endsection
