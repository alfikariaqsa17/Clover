@extends('layouts.app')
@section('title', 'Lacak Pesanan — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Lacak Pesanan' => null]])
<div class="container page-head"><h1>Lacak pesanan</h1><p>Masukkan nomor pesanan Anda (contoh: CLV-261012-AB12). Anda harus masuk ke akun pemilik pesanan.</p></div>
<div class="container" style="max-width:760px;padding-bottom:96px">
    <form method="GET" action="{{ route('track') }}" class="panel glass form">
        <div class="field"><label for="nomor">Nomor pesanan</label><input id="nomor" name="nomor" value="{{ request('nomor') }}" required></div>
        <button class="btn btn-primary">Cari</button>
    </form>
    @if($searched && ! $order)
        <div class="alert bad" style="margin-top:20px" role="alert"><x-icon name="alert" /><span>Pesanan tidak ditemukan atau Anda tidak memiliki akses. @guest <a href="{{ route('login') }}"><u>Masuk</u></a> bila pesanan ada di akun Anda. @endguest</span></div>
    @elseif($order)
        <div class="panel" style="margin-top:20px">
            <div class="row between"><h3 style="margin:0">{{ $order->number }}</h3>@include('partials.status', ['s' => $order->status])</div>
            <table class="table-simple" style="margin-top:12px">
                @foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}</td></tr>@endforeach
                <tr><td><b>Total</b></td><td><b>Rp{{ number_format($order->total, 0, ',', '.') }}</b></td></tr>
                @if($order->shipment && $order->shipment->tracking_number)<tr><td>Nomor resi</td><td>{{ $order->shipment->courier }} — {{ $order->shipment->tracking_number }}</td></tr>@endif
            </table>
        </div>
    @endif
</div>
@endsection
