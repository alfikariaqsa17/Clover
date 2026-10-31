@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="ah">
    <div><h1>Dashboard</h1><p>Ringkasan aktivitas toko Clover.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.settings') }}"><x-icon name="image" :size="18" /> Ganti background situs</a>
</div>

<div class="cards" data-live="dash-cards">
    <div class="kpi"><small><x-icon name="chart" :size="16" /> Total penjualan</small><b>Rp{{ number_format($stats['sales'], 0, ',', '.') }}</b></div>
    <a class="kpi" href="{{ route('admin.orders.index') }}"><small><x-icon name="package" :size="16" /> Total pesanan</small><b>{{ $stats['orders'] }}</b></a>
    <a class="kpi" href="{{ route('admin.orders.index', ['status' => 'Menunggu Pembayaran']) }}"><small><x-icon name="clock" :size="16" /> Menunggu pembayaran</small><b>{{ $stats['pending'] }}</b></a>
    <a class="kpi" href="{{ route('admin.bookings.index', ['status' => 'Menunggu Konfirmasi']) }}"><small><x-icon name="tool" :size="16" /> Booking menunggu</small><b>{{ $stats['bookings'] }}</b></a>
    <a class="kpi" href="{{ route('admin.products.index') }}"><small><x-icon name="sprout" :size="16" /> Produk</small><b>{{ $stats['products'] }}</b></a>
    <a class="kpi" href="{{ route('admin.users.index', ['status' => 'pending']) }}"><small><x-icon name="user" :size="16" /> Akun menunggu persetujuan</small><b>{{ $stats['accounts'] }}</b></a>
    <a class="kpi" href="{{ route('admin.users.index') }}"><small><x-icon name="users" :size="16" /> Pelanggan</small><b>{{ $stats['users'] }}</b></a>
    <a class="kpi" href="{{ route('admin.reviews.index', ['status' => 'pending']) }}"><small><x-icon name="star" :size="16" /> Ulasan menunggu</small><b>{{ $stats['reviews'] }}</b></a>
</div>

<div class="agrid" data-live="dash-chart">
    <section class="box">
        <div class="row between"><h2 style="margin:0">Penjualan {{ $days }} hari terakhir</h2>
            <div class="row" style="gap:6px">@foreach([7, 14, 30, 90] as $d)<a class="chip {{ $days === $d ? 'on' : '' }}" href="{{ route('admin.dashboard', ['periode' => $d]) }}" style="padding:5px 12px">{{ $d }} hari</a>@endforeach</div></div>
        <div class="bars" role="img" aria-label="Grafik penjualan harian" style="margin-top:14px">
            @foreach($series as $s)
                <div class="bar"><span class="tip">{{ $s['label'] }}: Rp{{ number_format($s['total'], 0, ',', '.') }} ({{ $s['count'] }} pesanan)</span><i style="height: {{ max(2, round($s['total'] / $max * 100)) }}%"></i>@if($days <= 14)<small>{{ $s['label'] }}</small>@endif</div>
            @endforeach
        </div>
        <p class="muted" style="font-size:.82rem;margin:10px 0 0">Pesanan berstatus dibatalkan tidak dihitung.</p>
    </section>
    <section class="box">
        <h2>Stok menipis (5 atau kurang)</h2>
        @forelse($lowStock as $p)<div class="row between" style="padding:6px 0"><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a><b>{{ $p->stock }}</b></div>@empty<p class="muted">Semua stok aman.</p>@endforelse
    </section>
</div>

<div class="agrid" data-live="dash-recent">
    <section class="box">
        <h2>Transaksi terbaru</h2>
        <div class="tbw" style="border:0"><table class="tb" style="min-width:0">
            <thead><tr><th>Nomor</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>@forelse($recent as $o)<tr data-key="o{{ $o->id }}"><td><a href="{{ route('admin.orders.show', $o) }}"><u>{{ $o->number }}</u></a></td><td>{{ $o->name }}</td><td>Rp{{ number_format($o->total, 0, ',', '.') }}</td><td>@include('partials.status', ['s' => $o->status])</td></tr>@empty<tr><td colspan="4">Belum ada pesanan.</td></tr>@endforelse</tbody>
        </table></div>
    </section>
    <section class="box">
        <h2>Booking jasa terbaru</h2>
        @forelse($recentBookings as $b)<div class="row between" data-key="b{{ $b->id }}" style="padding:8px 0;border-bottom:1px solid var(--line)"><span>{{ $b->service_name }}<br><small class="muted">{{ $b->name }} · {{ $b->date->format('d-m-Y') }} {{ $b->time }}</small></span>@include('partials.status', ['s' => $b->status])</div>@empty<p class="muted">Belum ada booking.</p>@endforelse
    </section>
</div>
@endsection
