@extends('layouts.app')
@section('title', 'Pesanan Saya — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Pesanan' => null]])
<div class="container account">
    @include('account._nav')
    <div data-live="orders-page">
        <h1 style="font-size:2.2rem">Pesanan saya</h1>
        @forelse($orders as $o)
            <a class="row-card" data-key="o{{ $o->id }}" href="{{ route('account.orders.show', $o) }}" style="align-items:flex-start">
                <div><b>{{ $o->number }}</b><br><small class="muted">{{ $o->created_at->format('d-m-Y H:i') }}</small>
                    <ul class="list-check" style="margin-top:8px;font-size:.88rem">@foreach($o->items->take(3) as $i)<li><x-icon name="check" :size="14" /> {{ $i->name }} × {{ $i->qty }}</li>@endforeach</ul></div>
                <div style="text-align:right"><b>Rp{{ number_format($o->total, 0, ',', '.') }}</b><br>@include('partials.status', ['s' => $o->status])@if($o->payment)<br><small class="muted">Bayar: {{ $o->payment->status_label }}</small>@endif</div>
            </a>
        @empty
            @include('partials.empty', ['icon' => 'receipt', 'title' => 'Belum ada pesanan', 'text' => 'Pesanan Anda akan tampil di sini setelah checkout.', 'url' => route('products'), 'label' => 'Mulai Belanja'])
        @endforelse
        {{ $orders->links() }}
    </div>
</div>
@endsection
