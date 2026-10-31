@extends('emails.layout')
@section('title', 'Kuitansi ' . $order->receipt_number)
@section('body')
@php $pay = $order->payment; @endphp
<h1 style="margin:0 0 6px 0;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.3;color:#14251a;">Kuitansi pembayaran</h1>
<p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#3d4d43;">Halo, {{ $order->name }}. Pembayaran Anda untuk pesanan <b>{{ $order->number }}</b> sudah kami terima. Terima kasih!</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;color:#3d4d43;background:#eef4ea;border-radius:12px;margin-bottom:20px;">
  <tr><td style="padding:14px 16px;line-height:1.8;">
    No. kuitansi: <b>{{ $order->receipt_number }}</b><br>
    Tanggal bayar: <b>{{ ($pay?->paid_at ?? now())->format('d-m-Y H:i') }}</b><br>
    Metode: <b>{{ $pay ? $pay->method_label : '-' }}</b><br>
    Status: <b>{{ $pay ? $pay->status_label : '-' }}</b>
  </td></tr>
</table>

@include('emails._order-table', ['order' => $order])

<p style="margin:24px 0;"><a href="{{ route('account.orders.receipt', $order) }}" style="display:inline-block;background:#1c4630;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:999px;">Buka &amp; cetak kuitansi</a></p>
<p style="margin:0 0 12px 0;font-size:12px;line-height:1.6;color:#7a8a80;">Kuitansi ini juga selalu tersedia di halaman detail pesanan Anda. Simpan sebagai bukti pembayaran.</p>
@endsection
