KUITANSI PEMBAYARAN CLOVER

No. kuitansi : {{ $order->receipt_number }}
Pesanan      : {{ $order->number }}
Tanggal bayar: {{ ($order->payment?->paid_at ?? now())->format('d-m-Y H:i') }}
Metode       : {{ $order->payment ? $order->payment->method_label : '-' }}

@foreach($order->items as $i)
- {{ $i->name }} x {{ $i->qty }}: Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}
@endforeach
Subtotal: Rp{{ number_format($order->subtotal, 0, ',', '.') }}
@if($order->discount)
Diskon: -Rp{{ number_format($order->discount, 0, ',', '.') }}
@endif
Ongkos kirim: {{ $order->shipping ? 'Rp' . number_format($order->shipping, 0, ',', '.') : 'Gratis' }}
TOTAL DIBAYAR: Rp{{ number_format($order->total, 0, ',', '.') }}

Cetak kuitansi: {{ route('account.orders.receipt', $order) }}
