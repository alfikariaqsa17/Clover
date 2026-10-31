@extends('layouts.admin')
@section('title', 'Laporan Penjualan')

@section('content')
<div class="ah"><div><h1>Laporan penjualan</h1><p>Pesanan berstatus dibatalkan tidak dihitung.</p></div>
    <a class="btn btn-glass" href="{{ route('admin.reports.export', ['dari' => $from->toDateString(), 'sampai' => $to->toDateString()]) }}"><x-icon name="download" :size="18" /> Ekspor CSV</a></div>
<form method="GET" class="filters">
    <label class="sr-only" for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ $from->toDateString() }}">
    <label class="sr-only" for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $to->toDateString() }}">
    <button class="btn btn-primary btn-sm">Terapkan</button>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.reports', ['dari' => now()->subDays(6)->toDateString(), 'sampai' => now()->toDateString()]) }}">7 hari</a>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.reports', ['dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->toDateString()]) }}">Bulan ini</a>
</form>
<div class="cards">
    <div class="kpi"><small>Pesanan</small><b>{{ $summary['orders'] }}</b></div>
    <div class="kpi"><small>Pendapatan (total)</small><b>Rp{{ number_format($summary['total'], 0, ',', '.') }}</b></div>
    <div class="kpi"><small>Rata-rata per pesanan</small><b>Rp{{ number_format($summary['avg'], 0, ',', '.') }}</b></div>
    <div class="kpi"><small>Diskon diberikan</small><b>Rp{{ number_format($summary['discount'], 0, ',', '.') }}</b></div>
    <div class="kpi"><small>Subtotal produk</small><b>Rp{{ number_format($summary['subtotal'], 0, ',', '.') }}</b></div>
    <div class="kpi"><small>Ongkir</small><b>Rp{{ number_format($summary['shipping'], 0, ',', '.') }}</b></div>
    <div class="kpi"><small>Pesanan selesai</small><b>{{ $summary['completed'] }}</b></div>
    <div class="kpi"><small>Pesanan dibatalkan</small><b>{{ $summary['cancelled'] }}</b></div>
</div>
<div class="agrid">
    <section class="box"><h2>Penjualan harian</h2>
        <div class="tbw" style="border:0"><table class="tb" style="min-width:0"><thead><tr><th>Tanggal</th><th>Pesanan</th><th>Total</th></tr></thead>
            <tbody>@forelse($daily as $d)<tr><td>{{ \Illuminate\Support\Carbon::parse($d->d)->format('d-m-Y') }}</td><td>{{ $d->c }}</td><td>Rp{{ number_format((int) $d->t, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3">Tidak ada data pada periode ini.</td></tr>@endforelse</tbody></table></div></section>
    <section class="box"><h2>Produk terlaris</h2>
        @forelse($top as $t)<div class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)"><span>{{ $t->name }}<br><small class="muted">{{ $t->qty }} terjual</small></span><b>Rp{{ number_format((int) $t->revenue, 0, ',', '.') }}</b></div>@empty<p class="muted">Belum ada data.</p>@endforelse</section>
</div>
@endsection
