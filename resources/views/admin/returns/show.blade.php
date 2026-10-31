@extends('layouts.admin')
@section('title', 'Retur ' . $ret->number)

@section('content')
@php $paid = (int) ($ret->order->payment->amount ?? 0); @endphp
<div class="ah"><div><h1>{{ $ret->number }}</h1><p>Pesanan <a href="{{ route('admin.orders.show', $ret->order) }}"><u>{{ $ret->order->number }}</u></a> · {{ $ret->created_at->format('d-m-Y H:i') }}</p></div><a class="btn btn-glass" href="{{ route('admin.returns.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a></div>
<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>Pengajuan</h2>
        <dl class="kv">
            <dt>Status</dt><dd>@include('partials.status', ['s' => $ret->status])</dd>
            <dt>Pelanggan</dt><dd>{{ $ret->user?->name ?? $ret->order->name }} · {{ $ret->order->whatsapp }}</dd>
            <dt>Alasan</dt><dd>{{ $ret->reason_label }}</dd>
            <dt>Penjelasan</dt><dd>{{ $ret->description }}</dd>
            <dt>Total dibayar</dt><dd>Rp{{ number_format($paid, 0, ',', '.') }}</dd>
            <dt>Rekening refund</dt><dd>{{ $ret->bank_name }} · {{ $ret->account_number }} · a.n. {{ $ret->account_holder }}</dd>
            @if($ret->admin_note)<dt>Catatan admin</dt><dd>{{ $ret->admin_note }}</dd>@endif
            @if($ret->return_tracking)<dt>Kirim balik</dt><dd>{{ $ret->return_courier }} · {{ $ret->return_tracking }}</dd>@endif
        </dl>
        <div>@forelse($ret->evidenceUrls() as $u)<a href="{{ $u }}" target="_blank" rel="noopener"><img src="{{ $u }}" alt="Bukti" style="max-width:130px;border-radius:10px;margin:0 8px 8px 0"></a>@empty<p class="muted">Tidak ada foto bukti.</p>@endforelse</div>
    </section>
    <section class="box">
        <h2>Tindakan</h2>
        @if($ret->status === 'Diajukan')
            <form method="POST" action="{{ route('admin.returns.decide', $ret) }}" class="form" novalidate>
                @csrf
                <div class="field"><label for="dec">Keputusan *</label>
                    <select id="dec" name="decision"><option value="approve" @selected(old('decision') === 'approve')>Setujui</option><option value="reject" @selected(old('decision') === 'reject')>Tolak</option></select></div>
                <div class="field"><label for="amt">Nominal refund (maks Rp{{ number_format($paid, 0, ',', '.') }}) — diisi bila disetujui</label>
                    <input id="amt" type="number" name="refund_amount" min="1" max="{{ $paid }}" value="{{ old('refund_amount', max(0, $paid - (int) $ret->order->shipping)) }}">
                    <small class="hint">Bawaan: total dibayar dikurangi ongkir. Ubah bila perlu (mis. refund sebagian).</small>
                    @error('refund_amount')<small class="error" role="alert">{{ $message }}</small>@enderror</div>
                <label class="check"><input type="hidden" name="needs_return" value="0"><input type="checkbox" name="needs_return" value="1" @checked(old('needs_return'))><span>Barang harus dikirim balik (kosongkan untuk tanaman mati/rusak)</span></label>
                <div class="field"><label for="note">Catatan untuk pelanggan (wajib bila menolak)</label><textarea id="note" name="admin_note">{{ old('admin_note') }}</textarea>@error('admin_note')<small class="error" role="alert">{{ $message }}</small>@enderror</div>
                <button class="btn btn-primary">Simpan keputusan</button>
            </form>
        @elseif($ret->status === 'Dikirim Balik')
            <form method="POST" action="{{ route('admin.returns.receive', $ret) }}" class="form" data-confirm="Konfirmasi barang retur sudah diterima? Refund akan dibuat.">
                @csrf
                <label class="check"><input type="hidden" name="restock" value="0"><input type="checkbox" name="restock" value="1"><span>Kembalikan barang ke stok (hanya bila kondisi layak jual)</span></label>
                <button class="btn btn-primary">Barang sudah diterima</button>
            </form>
        @else
            <p class="muted">Tidak ada tindakan lanjutan di sini.@if($ret->refund) Lanjutkan di <a href="{{ route('admin.refunds.show', $ret->refund) }}"><u>refund {{ $ret->refund->number }}</u></a>.@endif</p>
        @endif
    </section>
</div>
@endsection
