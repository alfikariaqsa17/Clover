<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Kuitansi {{ $order->receipt_number }} — Clover</title>
<style>
  :root { color-scheme: light; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #f1f5ee; font-family: Arial, Helvetica, sans-serif; color: #14251a; }
  .sheet { max-width: 720px; margin: 24px auto; background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 6px 24px rgba(20, 37, 26, .08); }
  .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #1c4630; padding-bottom: 16px; }
  .brand { font-family: Georgia, 'Times New Roman', serif; font-size: 28px; letter-spacing: 3px; font-weight: bold; color: #1c4630; }
  .brand small { display: block; font-family: Arial, sans-serif; font-size: 11px; letter-spacing: 2px; color: #7a8a80; text-transform: uppercase; font-weight: normal; }
  h1 { margin: 0; font-size: 20px; text-align: right; }
  .meta { font-size: 13px; color: #5b6a60; text-align: right; line-height: 1.6; }
  .cols { display: flex; gap: 24px; flex-wrap: wrap; margin: 20px 0; font-size: 14px; line-height: 1.6; }
  .cols > div { flex: 1 1 240px; }
  .cols h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #7a8a80; margin: 0 0 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  td, th { padding: 9px 0; border-bottom: 1px solid #e8eee4; text-align: left; }
  td:last-child, th:last-child { text-align: right; white-space: nowrap; }
  tr.total td { border-top: 2px solid #1c4630; border-bottom: 0; font-weight: bold; font-size: 16px; padding-top: 14px; }
  .stamp { display: inline-block; border: 2px solid #2c7a4b; color: #2c7a4b; padding: 4px 14px; border-radius: 8px; font-weight: bold; letter-spacing: 2px; transform: rotate(-4deg); margin-top: 18px; }
  .stamp.refunded { border-color: #8a2f28; color: #8a2f28; }
  .foot { margin-top: 24px; font-size: 12px; color: #7a8a80; line-height: 1.6; }
  .actions { max-width: 720px; margin: 16px auto 0; display: flex; gap: 10px; padding: 0 12px; }
  .actions a, .actions button { font: inherit; font-size: 14px; padding: 10px 18px; border-radius: 999px; border: 1px solid #1c4630; background: #fff; color: #1c4630; text-decoration: none; cursor: pointer; }
  .actions button { background: #1c4630; color: #fff; }
  @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; } .actions { display: none; } }
</style>
</head>
<body>
@php
    $pay = $order->payment;
    $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
@endphp
<div class="actions"><a href="{{ $back }}">← Kembali</a><button type="button" onclick="window.print()">Cetak kuitansi</button></div>
<div class="sheet">
    <div class="head">
        <div class="brand">CLOVER<small>Botanical living</small></div>
        <div><h1>KUITANSI</h1><div class="meta">No. {{ $order->receipt_number }}<br>Pesanan {{ $order->number }}<br>Tanggal bayar {{ ($pay->paid_at ?? $order->created_at)->format('d-m-Y H:i') }}</div></div>
    </div>
    <div class="cols">
        <div><h2>Diterima dari</h2>{{ $order->name }}<br>{{ $order->whatsapp }}<br>{{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}</div>
        <div><h2>Pembayaran</h2>Metode: {{ $pay->method_label }}<br>Status: {{ $pay->status_label }}@if($pay->status === 'refunded')<br>Dikembalikan: {{ $pay->refunded_at?->format('d-m-Y') }}@endif</div>
    </div>
    <table>
        <thead><tr><th>Item</th><th>Jumlah</th></tr></thead>
        <tbody>
        @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }} @ {{ $rp($i->price) }}</td><td>{{ $rp($i->price * $i->qty) }}</td></tr>@endforeach
        <tr><td>Subtotal</td><td>{{ $rp($order->subtotal) }}</td></tr>
        @if($order->discount)<tr><td>Diskon @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td>- {{ $rp($order->discount) }}</td></tr>@endif
        <tr><td>Ongkos kirim @if($order->shipment)({{ $order->shipment->service }})@endif</td><td>{{ $order->shipping ? $rp($order->shipping) : 'Gratis' }}</td></tr>
        <tr class="total"><td>Total dibayar</td><td>{{ $rp($order->total) }}</td></tr>
        </tbody>
    </table>
    <div class="stamp {{ $pay->status === 'refunded' ? 'refunded' : '' }}">{{ $pay->status === 'refunded' ? 'DIKEMBALIKAN' : 'LUNAS' }}</div>
    <p class="foot">Kuitansi ini dibuat otomatis oleh sistem Clover dan sah sebagai bukti pembayaran tanpa tanda tangan basah.</p>
</div>
</body>
</html>
