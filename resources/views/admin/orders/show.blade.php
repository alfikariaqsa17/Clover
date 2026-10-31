@extends('layouts.admin')
@section('title', 'Pesanan ' . $order->number)

@section('content')
<div class="ah"><div><h1>{{ $order->number }}</h1><p>Dibuat {{ $order->created_at->format('d-m-Y H:i') }}</p></div><a class="btn btn-glass" href="{{ route('admin.orders.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a></div>
<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>Item pesanan</h2>
        <table class="table-simple">
            @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}</td></tr>@endforeach
            <tr><td>Subtotal</td><td>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
            @if($order->discount)<tr><td>Diskon ({{ $order->coupon_code }})</td><td>- Rp{{ number_format($order->discount, 0, ',', '.') }}</td></tr>@endif
            <tr><td>Ongkir</td><td>{{ $order->shipping ? 'Rp' . number_format($order->shipping, 0, ',', '.') : 'Gratis' }}</td></tr>
            <tr><td><b>Total</b></td><td><b>Rp{{ number_format($order->total, 0, ',', '.') }}</b></td></tr>
        </table>
    </section>
    <section class="box">
        <h2>Pelanggan & pengiriman</h2>
        <dl class="kv">
            <dt>Penerima</dt><dd>{{ $order->name }}</dd>
            <dt>WhatsApp</dt><dd><a href="https://wa.me/{{ preg_replace('/\D/', '', $order->whatsapp) }}" target="_blank" rel="noopener"><u>{{ $order->whatsapp }}</u></a></dd>
            <dt>Alamat</dt><dd>{{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}</dd>
            <dt>Catatan</dt><dd>{{ $order->notes ?: '-' }}</dd>
            <dt>Zona ongkir</dt><dd>{{ $order->shipping_zone ? config('clover.shipping_zones.' . $order->shipping_zone . '.label', $order->shipping_zone) : '-' }}</dd>
            <dt>Akun</dt><dd>{{ $order->user ? $order->user->email : 'Tanpa akun' }}</dd>
        </dl>
    </section>
</div>

<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>Status pesanan</h2>
        <p>@include('partials.status', ['s' => $order->status])</p>
        @if($order->nextStatuses())
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="form" data-confirm="Ubah status pesanan? Pelanggan akan menerima notifikasi.">
                @csrf @method('PATCH')
                <div class="field"><label for="st">Ubah ke</label><select id="st" name="status">@foreach($order->nextStatuses() as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
                <div class="field"><label for="rsn">Alasan (dipakai bila dibatalkan)</label><input id="rsn" name="reason" maxlength="250" placeholder="Mis. stok habis"></div>
                <button class="btn btn-primary">Perbarui Status</button>
                <small class="hint">Pembatalan mengembalikan stok dan kuota kupon; bila sudah dibayar, refund penuh dibuat otomatis. Alur: Menunggu Pembayaran, Diproses, Dikirim, Selesai.</small>
            </form>
        @else
            <p class="muted">Status akhir. Tidak ada perubahan lanjutan.</p>
        @endif
    </section>
    <section class="box">
        <h2>Pembayaran</h2>
        @if($order->payment)
            <dl class="kv">
                <dt>Metode</dt><dd>{{ $order->payment->method_label }}</dd>
                <dt>Jumlah</dt><dd>Rp{{ number_format($order->payment->amount, 0, ',', '.') }}</dd>
                <dt>Status</dt><dd>@include('partials.status', ['s' => $order->payment->status, 'label' => $order->payment->status_label])</dd>
                <dt>Dibayar</dt><dd>{{ $order->payment->paid_at?->format('d-m-Y H:i') ?? '-' }}</dd>
                <dt>Bukti</dt><dd>@if($order->payment->proof_url)<a href="{{ $order->payment->proof_url }}" target="_blank" rel="noopener"><u>Lihat bukti transfer</u></a>@else Belum diunggah @endif</dd>
            </dl>
            @if(in_array($order->payment->status, ['paid', 'refunded'], true))
                <p style="margin-top:14px"><a class="btn btn-ghost btn-sm" href="{{ route('admin.orders.receipt', $order) }}" target="_blank" rel="noopener"><x-icon name="receipt" :size="16" /> Lihat kuitansi</a></p>
            @endif
            @if($order->payment->isQris())
                <p style="margin-top:14px"><a class="btn btn-primary btn-sm" href="{{ route('admin.payments.show', $order) }}"><x-icon name="eye" :size="16" /> {{ $order->payment->status === 'awaiting_verification' ? 'Periksa bukti & verifikasi' : 'Lihat pembayaran QRIS' }}</a></p>
            @elseif($order->payment->status === 'pending' && $order->status === 'Menunggu Pembayaran')
                <form method="POST" action="{{ route('admin.orders.payment', $order) }}" data-confirm="Verifikasi pembayaran sebagai lunas dan proses pesanan?" style="margin-top:14px">@csrf<button class="btn btn-primary"><x-icon name="check" :size="18" /> Verifikasi pembayaran lunas</button></form>
            @endif
        @endif
    </section>
</div>

@if($order->refunds->count() || $order->returns->count() || $order->cancel_reason)
<section class="box" style="margin-bottom:20px">
    <h2>Pembatalan, retur & refund</h2>
    @if($order->cancel_reason)<p class="muted">Dibatalkan {{ $order->cancelled_by === 'customer' ? 'oleh pelanggan' : 'oleh admin' }}: {{ $order->cancel_reason }}</p>@endif
    @foreach($order->returns as $rt)<p>Retur <a href="{{ route('admin.returns.show', $rt) }}"><u>{{ $rt->number }}</u></a> @include('partials.status', ['s' => $rt->status])</p>@endforeach
    @foreach($order->refunds as $rf)<p>Refund <a href="{{ route('admin.refunds.show', $rf) }}"><u>{{ $rf->number }}</u></a> · Rp{{ number_format($rf->amount, 0, ',', '.') }} @include('partials.status', ['s' => $rf->status])</p>@endforeach
</section>
@endif

@if($order->shipment)
<section class="box">
    <h2>Pengiriman</h2>
    <form method="POST" action="{{ route('admin.orders.shipment', $order) }}" class="form" novalidate>
        @csrf @method('PUT')
        <div class="two">
            @include('partials.field', ['prefix' => 'sh', 'name' => 'courier', 'label' => 'Kurir', 'value' => $order->shipment->courier])
            @include('partials.field', ['prefix' => 'sh', 'name' => 'tracking_number', 'label' => 'Nomor resi', 'value' => $order->shipment->tracking_number, 'optional' => true])
        </div>
        <p class="muted" style="margin:0">Layanan: {{ $order->shipment->service }} · Biaya Rp{{ number_format($order->shipment->cost, 0, ',', '.') }} · Status: {{ $order->shipment->status }}</p>
        <div><button class="btn btn-primary btn-sm">Simpan Pengiriman</button></div>
    </form>
</section>
@endif
@endsection
