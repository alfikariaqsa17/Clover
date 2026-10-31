@extends('layouts.app')
@section('title', 'Pesanan ' . $order->number . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Pesanan' => route('account.orders'), $order->number => null]])
@php
    $flow = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai'];
    $idx = array_search($order->status, $flow, true);
    $cancelled = $order->status === 'Dibatalkan';
@endphp
<div class="container account">
    @include('account._nav')
    <div data-live="order-page">
        <div class="row between"><h1 style="font-size:2rem;margin:0">{{ $order->number }}</h1>@include('partials.status', ['s' => $order->status])</div>
        <div class="timeline {{ $cancelled ? 'cancelled' : '' }}" aria-label="Progres pesanan">
            @foreach($flow as $i => $st)<div class="t {{ ! $cancelled && $idx !== false && $i <= $idx ? 'done' : '' }}">{{ $st }}</div>@endforeach
        </div>
        @if($cancelled)<div class="alert bad"><x-icon name="alert" /><span>Pesanan ini dibatalkan{{ $order->cancelled_by === 'customer' ? ' atas permintaan Anda' : ($order->cancelled_by === 'admin' ? ' oleh admin' : '') }}.@if($order->cancel_reason) Alasan: {{ $order->cancel_reason }}@endif</span></div>@endif

        @foreach($order->refunds as $rf)
            <div class="panel glass stack" style="margin:20px 0">
                <div class="row between"><h3 style="margin:0">Refund {{ $rf->number }}</h3>@include('partials.status', ['s' => $rf->status])</div>
                <p class="muted" style="margin:0">{{ $rf->source_label }} · <b>Rp{{ number_format($rf->amount, 0, ',', '.') }}</b>@if($rf->hasBank()) · ke {{ $rf->bank_name }} {{ $rf->maskedAccount() }} a.n. {{ $rf->account_holder }}@endif</p>
                @if($rf->status === 'Selesai')
                    <p style="margin:0">Dana sudah ditransfer{{ $rf->processed_at ? ' pada ' . $rf->processed_at->format('d-m-Y H:i') : '' }}. Referensi: <b>{{ $rf->transfer_reference }}</b>@if($rf->proof_url) · <a href="{{ $rf->proof_url }}" target="_blank" rel="noopener"><u>Lihat bukti transfer</u></a>@endif</p>
                @elseif($rf->status === 'Ditolak')
                    <p style="margin:0">Refund tidak dapat diproses. Alasan: {{ $rf->admin_note }}</p>
                @elseif($rf->status === 'Diminta')
                    <p class="muted" style="margin:0">Menunggu admin mentransfer dana ke rekening di atas.</p>
                @endif
                @if(in_array($rf->status, ['Menunggu Rekening', 'Diminta'], true))
                    <form method="POST" action="{{ route('account.refunds.bank', $rf) }}" class="form" novalidate>
                        @csrf
                        <p style="margin:0"><b>{{ $rf->status === 'Menunggu Rekening' ? 'Isi rekening tujuan refund' : 'Ubah rekening tujuan' }}</b></p>
                        <div class="two">
                            @include('partials.field', ['prefix' => 'rb' . $rf->id, 'name' => 'bank_name', 'label' => 'Nama bank', 'value' => $rf->bank_name])
                            @include('partials.field', ['prefix' => 'rb' . $rf->id, 'name' => 'account_number', 'label' => 'Nomor rekening', 'value' => ''])
                        </div>
                        @include('partials.field', ['prefix' => 'rb' . $rf->id, 'name' => 'account_holder', 'label' => 'Nama pemilik rekening', 'value' => $rf->account_holder])
                        <div><button class="btn btn-primary btn-sm">Simpan rekening</button></div>
                    </form>
                @endif
            </div>
        @endforeach

        @if($order->payment && $order->payment->isQris() && ! $cancelled)
            @php($qp = $order->payment)
            <div class="panel glass stack" style="margin:20px 0">
                <div class="row between"><h3 style="margin:0">Pembayaran QRIS GoPay</h3>@include('partials.status', ['s' => $qp->status, 'label' => $qp->status_label])</div>
                @if($qp->status === 'paid')
                    <p class="muted" style="margin:0">Pembayaran terverifikasi{{ $qp->verified_at ? ' pada ' . $qp->verified_at->format('d-m-Y H:i') : '' }}.</p>
                @elseif($qp->status === 'awaiting_verification')
                    <p class="muted" style="margin:0">Bukti Anda sedang diverifikasi admin. Pesanan diproses setelah dana dipastikan masuk.</p>
                @elseif($qp->status === 'rejected')
                    <p style="margin:0"><b>Bukti ditolak:</b> {{ $qp->rejection_reason }}. Silakan unggah ulang.</p>
                @else
                    <p class="muted" style="margin:0">Bayar <b>Rp{{ number_format($qp->amount, 0, ',', '.') }}</b> lewat QRIS (nominal diketik manual), lalu unggah bukti transaksi.</p>
                @endif
                <div><a class="btn btn-primary btn-sm" href="{{ route('account.orders.pay', $order) }}">{{ in_array($qp->status, ['pending', 'rejected'], true) ? 'Bayar / unggah bukti' : 'Lihat detail pembayaran' }}</a></div>
            </div>
        @endif

        @if($order->payment && $order->payment->method === 'transfer' && $order->payment->status === 'pending' && $order->status === 'Menunggu Pembayaran')
            <div class="panel glass stack" style="margin:20px 0">
                <h3>Pembayaran transfer</h3>
                <p class="muted" style="margin:0">Transfer <b>Rp{{ number_format($order->total, 0, ',', '.') }}</b> ke {{ config('clover.bank.name') }} <b>{{ config('clover.bank.number') }}</b> a.n. {{ config('clover.bank.holder') }}, lalu unggah bukti pembayaran.</p>
                @if($order->payment->proof_url)<p style="margin:0"><a class="btn btn-ghost btn-sm" href="{{ $order->payment->proof_url }}" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> Lihat bukti yang diunggah</a> <span class="muted">— menunggu verifikasi admin.</span></p>@endif
                <form method="POST" action="{{ route('account.orders.proof', $order) }}" enctype="multipart/form-data" class="form" novalidate>
                    @csrf
                    <div class="field"><label for="proof">Bukti pembayaran (JPG, PNG, PDF, maks 3 MB)</label><input id="proof" type="file" name="proof" accept="image/png,image/jpeg,image/webp,application/pdf" required @error('proof') aria-invalid="true" @enderror>@error('proof')<small class="error" role="alert">{{ $message }}</small>@enderror</div>
                    <button class="btn btn-primary"><x-icon name="upload" :size="18" /> Unggah bukti</button>
                </form>
            </div>
        @endif

        <div class="grid g-2" style="margin-top:20px;align-items:start">
            <div class="panel"><h3>Item</h3>
                <table class="table-simple">
                    @foreach($order->items as $i)<tr><td>@if($i->product)<a href="{{ route('products.show', $i->product) }}">{{ $i->name }}</a>@else{{ $i->name }}@endif × {{ $i->qty }}</td><td>Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}</td></tr>@endforeach
                    <tr><td>Subtotal</td><td>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
                    @if($order->discount)<tr><td>Diskon ({{ $order->coupon_code }})</td><td>- Rp{{ number_format($order->discount, 0, ',', '.') }}</td></tr>@endif
                    <tr><td>Ongkir</td><td>{{ $order->shipping ? 'Rp' . number_format($order->shipping, 0, ',', '.') : 'Gratis' }}</td></tr>
                    <tr><td><b>Total</b></td><td><b>Rp{{ number_format($order->total, 0, ',', '.') }}</b></td></tr>
                </table></div>
            <div class="panel"><h3>Pengiriman & pembayaran</h3>
                <table class="table-simple">
                    <tr><td>Penerima</td><td>{{ $order->name }}<br><small class="muted">{{ $order->whatsapp }}</small></td></tr>
                    <tr><td>Alamat</td><td>{{ $order->address }}, {{ $order->city }} {{ $order->postal_code }}</td></tr>
                    @if($order->shipment)<tr><td>Kurir</td><td>{{ $order->shipment->courier }} ({{ $order->shipment->service }})</td></tr>
                    <tr><td>Nomor resi</td><td>{{ $order->shipment->tracking_number ?: 'Belum tersedia' }}</td></tr>@endif
                    @if($order->payment)<tr><td>Pembayaran</td><td>{{ $order->payment->method_label }} — @include('partials.status', ['s' => $order->payment->status, 'label' => $order->payment->status_label])</td></tr>@endif
                    <tr><td>Catatan</td><td>{{ $order->notes ?: '-' }}</td></tr>
                </table></div>
        </div>

        <div class="row" style="margin-top:20px">
            @if(in_array($order->payment?->status, ['paid', 'refunded'], true))
                <a class="btn btn-ghost btn-sm" href="{{ route('account.orders.receipt', $order) }}" target="_blank" rel="noopener"><x-icon name="receipt" :size="16" /> Lihat & cetak kuitansi</a>
            @endif
            @if($order->status === 'Selesai' && $order->canReturn())
                <a class="btn btn-ghost btn-sm" href="{{ route('account.returns.create', $order) }}"><x-icon name="package" :size="16" /> Ajukan retur</a>
            @endif
        </div>

        @if($order->status === 'Selesai')
            @foreach($order->returns as $rt)
                <div class="alert" style="margin-top:14px"><x-icon name="package" /><span>Retur <a href="{{ route('account.returns.show', $rt) }}"><u>{{ $rt->number }}</u></a>: {{ $rt->status }}.</span></div>
            @endforeach
            @if(! $order->returns->count())
                @if($order->canReturn())
                    <small class="muted" style="display:block;margin-top:10px">Retur dapat diajukan sampai {{ $order->returnDeadline()->translatedFormat('j F Y') }} bila barang bermasalah.</small>
                @else
                    <small class="muted" style="display:block;margin-top:10px">Batas pengajuan retur ({{ config('clover.return.days') }} hari setelah pesanan selesai) sudah lewat.</small>
                @endif
            @endif
        @endif

        @if($order->canCancel())
            @php($paidNow = $order->payment?->status === 'paid')
            <details class="panel" style="margin-top:20px" @if($errors->has('reason') || $errors->has('bank_name') || $errors->has('account_number') || $errors->has('account_holder')) open @endif>
                <summary style="cursor:pointer;font-weight:600">Batalkan pesanan</summary>
                <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="form" novalidate data-confirm="Batalkan pesanan {{ $order->number }}? Tindakan ini tidak dapat dibatalkan.">
                    @csrf
                    <p class="muted" style="margin:12px 0 0">Pesanan hanya bisa dibatalkan sebelum dikirim. Stok dan kupon dikembalikan otomatis.@if($paidNow) Karena sudah dibayar, dana <b>Rp{{ number_format($order->payment->amount, 0, ',', '.') }}</b> dikembalikan penuh ke rekening Anda.@endif</p>
                    @include('partials.field', ['prefix' => 'cx', 'name' => 'reason', 'label' => 'Alasan pembatalan', 'type' => 'select', 'options' => config('clover.cancel_reasons'), 'assoc' => true])
                    @include('partials.field', ['prefix' => 'cx', 'name' => 'reason_note', 'label' => 'Keterangan tambahan', 'type' => 'textarea', 'optional' => true])
                    @if($paidNow)
                        <p style="margin:0"><b>Rekening tujuan refund</b></p>
                        <div class="two">
                            @include('partials.field', ['prefix' => 'cx', 'name' => 'bank_name', 'label' => 'Nama bank'])
                            @include('partials.field', ['prefix' => 'cx', 'name' => 'account_number', 'label' => 'Nomor rekening'])
                        </div>
                        @include('partials.field', ['prefix' => 'cx', 'name' => 'account_holder', 'label' => 'Nama pemilik rekening'])
                    @endif
                    <div><button class="btn btn-danger">Batalkan pesanan</button></div>
                </form>
            </details>
        @endif

        @if($order->status === 'Selesai')
            <div class="alert" style="margin-top:20px"><x-icon name="star" /><span>Pesanan selesai. Tulis ulasan pada halaman produk: @foreach($order->items as $i)@if($i->product)<a href="{{ route('products.show', $i->product) }}#ulasan"><u>{{ $i->name }}</u></a>@if(! $loop->last), @endif @endif @endforeach</span></div>
        @endif
    </div>
</div>
@endsection
