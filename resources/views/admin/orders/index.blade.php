@extends('layouts.admin')
@section('title', 'Pesanan')

@section('content')
<div class="ah"><h1>Pesanan</h1><a class="btn btn-glass" href="{{ route('admin.orders.export', request()->query()) }}"><x-icon name="download" :size="18" /> Ekspor CSV</a></div>
<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nomor / nama..." aria-label="Cari pesanan">
    <select name="status" aria-label="Filter status" data-auto><option value="">Semua status</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach</select>
    <button class="btn btn-glass btn-sm">Filter</button>
</form>
<div class="tbw" data-live="list"><table class="tb">
    <thead><tr><th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($orders as $o)
        <tr data-key="o{{ $o->id }}">
            <td><b>{{ $o->number }}</b></td><td>{{ $o->created_at->format('d-m-Y H:i') }}</td>
            <td>{{ $o->name }}<br><small class="muted">{{ $o->whatsapp }}</small></td>
            <td>Rp{{ number_format($o->total, 0, ',', '.') }}</td>
            <td>@if($o->payment){{ $o->payment->method_label }} @include('partials.status', ['s' => $o->payment->status, 'label' => $o->payment->status_label])@endif</td>
            <td>@include('partials.status', ['s' => $o->status])</td>
            <td><a class="btn btn-glass btn-sm" href="{{ route('admin.orders.show', $o) }}">Detail</a></td>
        </tr>
    @empty
        <tr><td colspan="7">Tidak ada pesanan.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $orders->links() }}
@endsection
