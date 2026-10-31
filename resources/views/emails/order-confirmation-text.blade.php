Terima kasih, {{ $order->name }}!

Pesanan {{ $order->number }} sudah kami terima ({{ $order->created_at->format('d-m-Y H:i') }}).

@foreach($order->items as $i)
- {{ $i->name }} x {{ $i->qty }}: Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}
@endforeach
Subtotal: Rp{{ number_format($order->subtotal, 0, ',', '.') }}
@if($order->discount)
Diskon: -Rp{{ number_format($order->discount, 0, ',', '.') }}
@endif
Ongkos kirim: {{ $order->shipping ? 'Rp' . number_format($order->shipping, 0, ',', '.') : 'Gratis' }}
TOTAL: Rp{{ number_format($order->total, 0, ',', '.') }}

Dikirim ke: {{ $order->name }}, {{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}

@if($order->payment && $order->payment->method === 'transfer')
Transfer Rp{{ number_format($order->total, 0, ',', '.') }} ke {{ config('clover.bank.name') }} {{ config('clover.bank.number') }} a.n. {{ config('clover.bank.holder') }}, lalu unggah bukti pembayaran. Kuitansi dikirim setelah pembayaran terverifikasi.
@elseif($order->payment && $order->payment->method === 'qris_gopay')
Bayar lewat QRIS: pindai kode di {{ route('account.orders.pay', $order) }}, ketik nominal Rp{{ number_format($order->total, 0, ',', '.') }} secara manual (QRIS statis), lalu unggah bukti transaksi. Pesanan diproses setelah admin memastikan dana masuk.
@elseif($order->payment && $order->payment->method === 'cod')
Pembayaran tunai saat pesanan diterima. Kuitansi dikirim setelah pesanan selesai.
@endif

Lihat pesanan: {{ route('account.orders.show', $order) }}
