@extends('emails.layout')
@section('title', 'Pesanan ' . $order->number)
@section('body')
@php $pay = $order->payment; @endphp
<h1 style="margin:0 0 12px 0;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.3;color:#14251a;">Terima kasih, {{ $order->name }}!</h1>
<p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#3d4d43;">Pesanan <b>{{ $order->number }}</b> sudah kami terima pada {{ $order->created_at->format('d-m-Y H:i') }}.</p>

@if($pay && $pay->method === 'transfer')
  <p style="margin:0 0 20px 0;font-size:14px;line-height:1.6;color:#7a4a00;background:#fff6e5;border-radius:10px;padding:12px 14px;">
    <b>Langkah berikutnya:</b> transfer <b>Rp{{ number_format($order->total, 0, ',', '.') }}</b> ke {{ config('clover.bank.name') }} <b>{{ config('clover.bank.number') }}</b> a.n. {{ config('clover.bank.holder') }}, lalu unggah bukti pembayaran di halaman pesanan. Pesanan diproses setelah admin memverifikasi. Kuitansi akan kami kirim setelah pembayaran terverifikasi.
  </p>
@elseif($pay && $pay->method === 'qris_gopay')
  <p style="margin:0 0 20px 0;font-size:14px;line-height:1.6;color:#7a4a00;background:#fff6e5;border-radius:10px;padding:12px 14px;">
    <b>Langkah berikutnya:</b> buka halaman pembayaran, pindai QRIS, lalu ketik nominal <b>Rp{{ number_format($order->total, 0, ',', '.') }}</b> secara manual (QRIS ini statis, nominal tidak terisi otomatis). Setelah itu unggah screenshot bukti transaksi. Pesanan diproses setelah admin memastikan dana masuk.
    <br><a href="{{ route('account.orders.pay', $order) }}" style="color:#1c4630;font-weight:bold;">Buka halaman pembayaran</a>
  </p>
@elseif($pay && $pay->method === 'cod')
  <p style="margin:0 0 20px 0;font-size:14px;line-height:1.6;color:#3d4d43;background:#eef4ea;border-radius:10px;padding:12px 14px;">Pembayaran dilakukan <b>tunai saat pesanan diterima</b>. Kuitansi dikirim setelah pesanan selesai.</p>
@endif

@include('emails._order-table', ['order' => $order])

<p style="margin:20px 0 4px 0;font-size:14px;font-weight:bold;color:#14251a;">Dikirim ke</p>
<p style="margin:0 0 4px 0;font-size:14px;line-height:1.6;color:#3d4d43;">{{ $order->name }} · {{ $order->whatsapp }}<br>{{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}</p>
@if($order->shipment)<p style="margin:0 0 16px 0;font-size:13px;color:#5b6a60;">{{ $order->shipment->courier }} — {{ $order->shipment->service }}</p>@endif

<p style="margin:20px 0 24px 0;font-size:14px;line-height:1.6;color:#3d4d43;">Pesanan yang belum dikirim dapat dibatalkan dari halaman pesanan. Setelah diterima, retur dapat diajukan dalam {{ config('clover.return.days') }} hari bila ada masalah.</p>
<p style="margin:0 0 24px 0;"><a href="{{ route('account.orders.show', $order) }}" style="display:inline-block;background:#1c4630;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:999px;">Lihat pesanan saya</a></p>
@endsection
