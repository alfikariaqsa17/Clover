@extends('layouts.app')
@section('title', 'Akun Saya — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2.2rem">Halo, {{ auth()->user()->name }}</h1>
        <p class="muted">Kelola pesanan, koleksi tanaman, dan jadwal perawatan Anda.</p>
        <div class="grid g-4" style="margin:24px 0">
            <a class="stat-card" href="{{ route('account.orders') }}"><b>{{ $orderCount }}</b><span>Pesanan</span></a>
            <a class="stat-card" href="{{ route('account.bookings') }}"><b>{{ $bookingCount }}</b><span>Pemesanan jasa</span></a>
            <a class="stat-card" href="{{ route('account.plants') }}"><b>{{ $plantCount }}</b><span>Tanaman saya</span></a>
            <a class="stat-card" href="{{ route('account.wishlist') }}"><b>{{ $wishCount }}</b><span>Wishlist</span></a>
        </div>
        @if(auth()->user()->isAdmin())<p><a class="btn btn-primary" href="{{ route('admin.dashboard') }}"><x-icon name="settings" :size="18" /> Buka Dashboard Admin</a></p>@endif

        <h3 style="margin-top:32px">Perawatan terdekat</h3>
        @forelse($dueSchedules as $s)
            <div class="row-card"><span><b>{{ $s->type_label }}</b> — {{ $s->userPlant->nickname }}</span><span class="row">@include('partials.status', ['s' => $s->is_overdue ? 'Dibatalkan' : 'Diproses', 'label' => $s->is_overdue ? 'Terlambat' : $s->next_due_date->translatedFormat('j M')])
                <form method="POST" action="{{ route('account.schedules.done', $s) }}" data-keep>@csrf<button class="btn btn-glass btn-sm">Tandai selesai</button></form></span></div>
        @empty
            <p class="muted">Tidak ada jadwal perawatan dalam 3 hari ke depan. <a href="{{ route('account.plants') }}"><u>Tambahkan tanaman</u></a> untuk mengatur pengingat.</p>
        @endforelse

        <h3 style="margin-top:32px">Pesanan terbaru</h3>
        @forelse($latestOrders as $o)
            <a class="row-card" href="{{ route('account.orders.show', $o) }}"><b>{{ $o->number }}</b><span class="muted">{{ $o->created_at->format('d-m-Y') }}</span><span>Rp{{ number_format($o->total, 0, ',', '.') }}</span>@include('partials.status', ['s' => $o->status])</a>
        @empty
            @include('partials.empty', ['icon' => 'receipt', 'title' => 'Belum ada pesanan', 'text' => 'Pesanan Anda akan muncul di sini.', 'url' => route('products'), 'label' => 'Mulai Belanja'])
        @endforelse
    </div>
</div>
@endsection
