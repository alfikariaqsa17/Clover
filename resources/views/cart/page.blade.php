@extends('layouts.app')
@section('title', 'Keranjang — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Keranjang' => null]])
<div class="container page-head"><h1>Keranjang belanja</h1></div>
<div class="container" style="padding-bottom:96px">
    @if($lines)
        <div class="grid" style="grid-template-columns:1.6fr 1fr;gap:32px;align-items:start">
            <div>
                @foreach($lines as $l)
                    @php($p = $l['product'])
                    <div class="row-card" style="flex-wrap:nowrap">
                        <div style="width:84px;flex:none;border-radius:16px;overflow:hidden">@include('partials.photo', ['src' => $p->image_url, 'alt' => $p->name])</div>
                        <div style="flex:1;min-width:0">
                            <a href="{{ route('products.show', $p) }}"><b>{{ $p->name }}</b></a>
                            <small class="muted" style="display:block">{{ $p->price_format }} · stok {{ $p->stock }}</small>
                            <div class="qty-mini">
                                <form method="POST" action="{{ route('cart.update', $p) }}" class="inl">@csrf @method('PATCH')<input type="hidden" name="from_page" value="1"><input type="hidden" name="qty" value="{{ $l['qty'] - 1 }}"><button aria-label="Kurangi jumlah"><x-icon name="minus" :size="14" /></button></form>
                                <span>{{ $l['qty'] }}</span>
                                <form method="POST" action="{{ route('cart.update', $p) }}" class="inl">@csrf @method('PATCH')<input type="hidden" name="from_page" value="1"><input type="hidden" name="qty" value="{{ $l['qty'] + 1 }}"><button aria-label="Tambah jumlah" @disabled($l['qty'] >= $p->stock)><x-icon name="plus" :size="14" /></button></form>
                            </div>
                        </div>
                        <div style="text-align:right"><b>Rp{{ number_format($l['subtotal'], 0, ',', '.') }}</b>
                            <form method="POST" action="{{ route('cart.remove', $p) }}" data-confirm="Hapus {{ $p->name }} dari keranjang?">@csrf @method('DELETE')<input type="hidden" name="from_page" value="1"><button class="link-danger">Hapus</button></form></div>
                    </div>
                @endforeach
            </div>
            <div class="panel glass" style="position:sticky;top:calc(var(--nav-h) + 16px)">
                <h3>Ringkasan</h3>
                <div class="sum-row"><span class="muted">Subtotal</span><b>Rp{{ number_format($subtotal, 0, ',', '.') }}</b></div>
                <small class="muted">Ongkos kirim dan kupon dihitung saat checkout.</small>
                <a class="btn btn-primary btn-block btn-lg" style="margin-top:16px" href="{{ route('checkout') }}">Lanjut ke Checkout</a>
                <a class="btn btn-ghost btn-block" style="margin-top:10px" href="{{ route('products') }}">Lanjut Belanja</a>
            </div>
        </div>
    @else
        @include('partials.empty', ['icon' => 'cart', 'title' => 'Keranjang Anda masih kosong', 'text' => 'Temukan tanaman favorit Anda.', 'url' => route('products'), 'label' => 'Mulai Belanja'])
    @endif
</div>
@endsection
