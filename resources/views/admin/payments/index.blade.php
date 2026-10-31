@extends('layouts.admin')
@section('title', 'Pembayaran QRIS')

@section('content')
@php
    $labels = ['awaiting_verification' => 'Menunggu verifikasi', 'rejected' => 'Bukti ditolak', 'paid' => 'Lunas', 'pending' => 'Belum bayar', 'failed' => 'Gagal / batal', 'semua' => 'Semua'];
@endphp
<div class="ah">
    <div><h1>Pembayaran QRIS</h1><p>Periksa bukti transaksi pelanggan, pastikan dana benar-benar masuk di aplikasi GoPay Merchant, lalu verifikasi atau tolak.</p></div>
    <a class="btn btn-glass" href="{{ route('admin.qris.edit') }}"><x-icon name="settings" :size="18" /> Pengaturan QRIS</a>
</div>
@unless($qrisReady)
    <div class="alert warn" style="margin-bottom:16px"><x-icon name="alert" /><span>QRIS belum aktif di checkout. <a href="{{ route('admin.qris.edit') }}"><u>Unggah gambar QRIS merchant</u></a> agar pelanggan dapat memilihnya.</span></div>
@endunless
<form method="GET" class="filters">
    <select name="status" aria-label="Filter status" data-auto>
        @foreach($labels as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}@if($k !== 'semua' && ($counts[$k] ?? 0)) ({{ $counts[$k] }})@endif</option>@endforeach
    </select>
</form>
<div class="tbw"><table class="tb">
    <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($payments as $p)
        <tr>
            <td><b>{{ $p->order->number }}</b><br><small class="muted">{{ $p->order->created_at->format('d-m-Y H:i') }}</small></td>
            <td>{{ $p->order->name }}<br><small class="muted">{{ $p->order->whatsapp }}</small></td>
            <td>Rp{{ number_format($p->amount, 0, ',', '.') }}</td>
            <td>@include('partials.status', ['s' => $p->status, 'label' => $p->status_label])</td>
            <td><a class="btn btn-glass btn-sm" href="{{ route('admin.payments.show', $p->order) }}">{{ $p->status === 'awaiting_verification' ? 'Periksa' : 'Buka' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="5">Tidak ada pembayaran pada filter ini.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $payments->links() }}
@endsection
