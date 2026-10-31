@extends('layouts.app')
@section('title', 'Pesanan ' . $order->number . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Pesanan' => route('account.orders'), $order->number => null]])
<div class="container" style="max-width:820px;padding:24px 0 96px">
    <div class="panel glass">
        <div class="row"><x-icon name="check-circle" :size="34" style="color:var(--forest-2)" /><h1 style="font-size:2rem;margin:0">Pesanan berhasil dibuat</h1></div>
        <p class="muted">Nomor pesanan <b>{{ $order->number }}</b>. Terima kasih, {{ $order->name }}.</p>

        @if($order->payment && $order->payment->isQris())
            <div class="alert warn"><x-icon name="clock" /><span>Selesaikan pembayaran lewat QRIS lalu unggah bukti di <a href="{{ route('account.orders.pay', $order) }}"><u>halaman pembayaran</u></a>.</span></div>
        @elseif($order->payment && $order->payment->method === 'transfer' && $order->payment->status === 'pending')
            <div class="alert warn" style="margin:16px 0"><x-icon name="info" /><span>Transfer <b>Rp{{ number_format($order->total, 0, ',', '.') }}</b> ke {{ config('clover.bank.name') }} <b>{{ config('clover.bank.number') }}</b> a.n. {{ config('clover.bank.holder') }}, lalu unggah bukti pembayaran di halaman detail pesanan. Pesanan diproses setelah admin memverifikasi.</span></div>
        @elseif($order->payment && $order->payment->method === 'cod')
            <div class="alert" style="margin:16px 0"><x-icon name="info" /><span>Pesanan dibayar tunai saat diterima.</span></div>
        @endif

        <table class="table-simple">
            @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}</td></tr>@endforeach
            <tr><td>Subtotal</td><td>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
            @if($order->discount)<tr><td>Diskon ({{ $order->coupon_code }})</td><td>- Rp{{ number_format($order->discount, 0, ',', '.') }}</td></tr>@endif
            <tr><td>Ongkir</td><td>{{ $order->shipping ? 'Rp' . number_format($order->shipping, 0, ',', '.') : 'Gratis' }}</td></tr>
            <tr><td><b>Total</b></td><td><b>Rp{{ number_format($order->total, 0, ',', '.') }}</b></td></tr>
            <tr><td>Pengiriman</td><td>{{ $order->name }}, {{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}</td></tr>
            <tr><td>Status</td><td>@include('partials.status', ['s' => $order->status])</td></tr>
        </table>
        <div class="row" style="margin-top:20px"><a class="btn btn-primary" href="{{ route('account.orders.show', $order) }}">Detail & Pelacakan</a><a class="btn btn-ghost" href="{{ route('products') }}">Belanja Lagi</a></div>
    </div>
</div>
@endsection
