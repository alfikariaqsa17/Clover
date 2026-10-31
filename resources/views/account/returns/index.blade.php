@extends('layouts.app')
@section('title', 'Retur & Refund — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Retur & Refund' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2rem">Retur & refund</h1>
        <p class="muted">Retur diajukan dari halaman detail pesanan yang sudah selesai, paling lambat {{ config('clover.return.days') }} hari setelah pesanan diterima. Refund karena pembatalan tampil di halaman detail pesanan yang bersangkutan.</p>
        @forelse($returns as $rt)
            <a class="row-card" href="{{ route('account.returns.show', $rt) }}"><span><b>{{ $rt->number }}</b> · pesanan {{ $rt->order->number }}<br><small class="muted">{{ $rt->reason_label }} · diajukan {{ $rt->created_at->format('d-m-Y') }}@if($rt->refund) · refund {{ $rt->refund->status }}@endif</small></span>@include('partials.status', ['s' => $rt->status])</a>
        @empty
            @include('partials.empty', ['icon' => 'package', 'title' => 'Belum ada pengajuan retur', 'text' => 'Bila pesanan Anda bermasalah, ajukan retur dari detail pesanan.', 'url' => route('account.orders'), 'label' => 'Lihat Pesanan'])
        @endforelse
        {{ $returns->links() }}
    </div>
</div>
@endsection
