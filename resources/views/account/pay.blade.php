@extends('layouts.app')
@section('title', 'Bayar dengan QRIS — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Pesanan' => route('account.orders'), $order->number => route('account.orders.show', $order), 'Pembayaran QRIS' => null]])
@php
    $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
    $cancelled = $order->status === 'Dibatalkan';
    $map = ['submitted' => 'awaiting_verification', 'verified' => 'paid', 'rejected' => 'rejected'];
@endphp
<div class="container account">
    @include('account._nav')
    <div data-live="pay-page">
        <div class="row between">
            <h1 style="font-size:2rem;margin:0">Pembayaran QRIS GoPay</h1>
            @include('partials.status', ['s' => $pay->status, 'label' => $pay->status_label])
        </div>
        <p class="muted">Pesanan <a href="{{ route('account.orders.show', $order) }}"><u>{{ $order->number }}</u></a> · dibuat {{ $order->created_at->format('d-m-Y H:i') }}</p>

        @if($cancelled)
            <div class="alert bad"><x-icon name="alert" /><span>Pesanan ini sudah dibatalkan, jadi tidak perlu dibayar. Jangan transfer ke QRIS ini untuk pesanan ini.</span></div>
        @elseif($pay->status === 'paid')
            <div class="alert"><x-icon name="check-circle" /><span>Pembayaran sudah <b>diverifikasi admin</b>@if($pay->verified_at) pada {{ $pay->verified_at->format('d-m-Y H:i') }}@endif. Pesanan Anda sedang diproses.</span></div>
            <div class="row" style="margin:14px 0">
                <a class="btn btn-primary btn-sm" href="{{ route('account.orders.show', $order) }}">Lihat pesanan</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('account.orders.receipt', $order) }}" target="_blank" rel="noopener"><x-icon name="receipt" :size="16" /> Kuitansi</a>
            </div>
        @elseif($pay->status === 'awaiting_verification')
            <div class="alert warn"><x-icon name="clock" /><span><b>Bukti pembayaran sudah kami terima</b> dan sedang diverifikasi admin. Screenshot belum berarti lunas: pesanan diproses setelah admin memastikan dana {{ $rp($pay->amount) }} benar-benar masuk. Anda akan mendapat notifikasi.</span></div>
        @elseif($pay->status === 'rejected')
            <div class="alert bad"><x-icon name="alert" /><span><b>Bukti pembayaran ditolak.</b> Alasan: {{ $pay->rejection_reason }}. Silakan bayar sesuai petunjuk lalu unggah bukti yang baru.</span></div>
        @endif

        @if(! $cancelled && in_array($pay->status, ['pending', 'rejected'], true))   {{-- QR hanya saat BELUM bayar / bukti ditolak: cegah bayar dobel --}}
        <div class="qris-grid" style="margin-top:20px">
            <section class="panel glass qris-card">
                <h3>1. Scan QRIS</h3>
                @if($qrisReady && $qrisUrl)
                    <div class="qris-frame" style="--qris-fit: {{ $qrisFit }}"><img src="{{ $qrisUrl }}" alt="Kode QRIS pembayaran {{ $merchant }}" decoding="async"></div>
                    <p class="qris-merchant">{{ $merchant }}</p>
                    <small class="muted">QRIS GoPay Merchant. Dapat dipindai dari GoPay, dan e-wallet atau mobile banking lain yang mendukung QRIS.</small>
                @else
                    <div class="alert bad"><x-icon name="alert" /><span>QRIS sedang tidak tersedia. Hubungi kami lewat <a href="https://wa.me/{{ \App\Models\Setting::wa() }}?text={{ rawurlencode('Halo Clover, saya ingin membayar pesanan ' . $order->number) }}" target="_blank" rel="noopener"><u>WhatsApp</u></a>.</span></div>
                @endif
            </section>

            <section class="panel glass qris-pay">
                <h3>2. Masukkan nominal yang tepat</h3>

                <div class="qris-amount-box" aria-label="Nominal pembayaran {{ $rp($pay->amount) }}">
                    <span class="qris-amount-label">Total yang harus dibayar</span>
                    <div class="qris-amount">{{ $rp($pay->amount) }}</div>
                    <div class="qris-amount-type">
                        <span>Ketik di aplikasi:</span>
                        <code id="qris-raw">{{ (int) $pay->amount }}</code>
                        <small>(tanpa titik atau koma)</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm qris-copy" data-copy="{{ (int) $pay->amount }}" data-copied="Tersalin ✓">
                        <x-icon name="copy" :size="16" /> <span>Salin nominal</span>
                    </button>
                </div>

                <dl class="qris-info">
                    <div><dt>Kode pesanan</dt><dd><b>{{ $order->number }}</b></dd></div>
                    <div><dt>Merchant</dt><dd>{{ $merchant }}</dd></div>
                    <div><dt>Batas bayar</dt><dd>Segera, agar stok tidak terlepas</dd></div>
                </dl>

                <div class="alert warn qris-note">
                    <x-icon name="alert" />
                    <div>
                        <b>QRIS ini statis, nominal tidak terisi otomatis.</b>
                        <ul>
                            <li>Ketik nominal <b>{{ $rp($pay->amount) }}</b> secara manual di aplikasi.</li>
                            <li>Nominal yang berbeda dapat membuat pembayaran ditolak.</li>
                            <li>Bila ada kolom catatan, isi dengan <b>{{ $order->number }}</b>.</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <section class="panel" style="margin-top:20px">
            <h3>Cara membayar</h3>
            <ol class="qris-steps">
                <li>Buka aplikasi GoPay (atau e-wallet/mobile banking lain yang mendukung QRIS).</li>
                <li>Pilih <b>Bayar / Scan</b>, lalu pindai kode QRIS di atas.</li>
                <li>Masukkan nominal <b>{{ $rp($pay->amount) }}</b> secara manual, lalu konfirmasi pembayaran.</li>
                <li>Setelah berhasil, ambil <b>screenshot</b> halaman bukti transaksi (nominal, tanggal, jam, dan status berhasil harus terlihat).</li>
                <li>Klik <b>Saya Sudah Bayar</b> di bawah, lalu unggah screenshot tersebut.</li>
                <li>Tunggu verifikasi admin. Pesanan diproses setelah dana kami pastikan masuk.</li>
            </ol>
        </section>
        @endif

        @if($canUpload && ! $cancelled)
            <details class="pay-upload" style="margin-top:20px" @if($errors->any()) open @endif>
                <summary class="btn btn-primary btn-lg">Saya Sudah Bayar</summary>
                <form method="POST" action="{{ route('account.orders.pay.submit', $order) }}" enctype="multipart/form-data" class="form panel" novalidate>
                    @csrf
                    <p class="muted" style="margin:0">Unggah screenshot bukti transaksi. File disimpan privat dan hanya dapat dilihat oleh Anda dan admin.</p>
                    <div class="field">
                        <label for="pay-proof">Screenshot bukti transaksi *</label>
                        <input id="pay-proof" type="file" name="proof" accept="image/png,image/jpeg,image/webp" required @error('proof') aria-invalid="true" @enderror>
                        <small class="hint">JPG, PNG, atau WebP · maksimal 4 MB.</small>
                        @error('proof')<small class="error" role="alert">{{ $message }}</small>@enderror
                    </div>
                    @include('partials.field', ['prefix' => 'pay', 'name' => 'note', 'label' => 'Catatan (opsional, mis. nama pemilik akun GoPay)', 'optional' => true])
                    @include('partials.field', ['prefix' => 'pay', 'name' => 'confirm', 'label' => 'Saya menyatakan sudah membayar tepat ' . $rp($pay->amount) . ' dan bukti ini asli.', 'type' => 'checkbox'])
                    <button class="btn btn-primary">Kirim bukti pembayaran</button>
                </form>
            </details>
        @endif

        @if($proofs->count())
            <section class="panel" style="margin-top:20px">
                <h3>Riwayat pembayaran</h3>
                @foreach($proofs as $pf)
                    <div class="row between" style="padding:10px 0;border-bottom:1px solid var(--line);align-items:flex-start">
                        <span>
                            <b>{{ $pf->created_at->format('d-m-Y H:i') }}</b> · bukti diunggah
                            @if($pf->note)<br><small class="muted">Catatan Anda: {{ $pf->note }}</small>@endif
                            @if($pf->status === 'rejected' && $pf->reason)<br><small>Alasan penolakan: {{ $pf->reason }}</small>@endif
                            @if($pf->reviewed_at)<br><small class="muted">Ditinjau {{ $pf->reviewed_at->format('d-m-Y H:i') }}</small>@endif
                            <br><a href="{{ route('account.orders.pay.proof', [$order, $pf]) }}" target="_blank" rel="noopener"><small><u>Lihat bukti</u></small></a>
                        </span>
                        @include('partials.status', ['s' => $map[$pf->status] ?? $pf->status, 'label' => $pf->status_label])
                    </div>
                @endforeach
            </section>
        @endif

        <section class="panel" style="margin-top:20px">
            <h3>Ringkasan pesanan</h3>
            <table class="table-simple">
                @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>{{ $rp($i->price * $i->qty) }}</td></tr>@endforeach
                <tr><td>Subtotal</td><td>{{ $rp($order->subtotal) }}</td></tr>
                @if($order->discount)<tr><td>Diskon @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td>- {{ $rp($order->discount) }}</td></tr>@endif
                <tr><td>Ongkos kirim</td><td>{{ $order->shipping ? $rp($order->shipping) : 'Gratis' }}</td></tr>
                <tr><td><b>Total yang harus dibayar</b></td><td><b>{{ $rp($order->total) }}</b></td></tr>
            </table>
        </section>
    </div>
</div>
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.qris-copy');
    if (!btn) return;
    var v = btn.getAttribute('data-copy'), label = btn.querySelector('span'), old = label.textContent;
    function done() { label.textContent = btn.getAttribute('data-copied'); setTimeout(function () { label.textContent = old; }, 1800); }
    if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(v).then(done, done); return; }
    var t = document.createElement('textarea'); t.value = v; t.style.position = 'fixed'; t.style.opacity = '0';
    document.body.appendChild(t); t.select();
    try { document.execCommand('copy'); } catch (err) {}
    document.body.removeChild(t); done();
});
</script>
@endsection
