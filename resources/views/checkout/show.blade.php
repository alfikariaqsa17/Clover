@extends('layouts.app')
@section('title', 'Checkout — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Keranjang' => route('cart.page'), 'Checkout' => null]])
<div class="container page-head"><h1>Checkout</h1></div>
<div class="container" style="padding-bottom:96px">
    <div class="grid" style="grid-template-columns:1.5fr 1fr;gap:32px;align-items:start">
        <form method="POST" action="{{ route('checkout.store') }}" class="stack" novalidate data-checkout data-subtotal="{{ $subtotal }}" data-discount="{{ $quote['discount'] }}" id="checkout-form">
            @csrf
            <section class="panel">
                <h3>1. Alamat pengiriman</h3>
                <div class="field" style="margin-bottom:16px">
                    <label for="address_id">Pilih alamat tersimpan</label>
                    <select id="address_id" name="address_id" @error('address_id') aria-invalid="true" @enderror>
                        <option value="">Gunakan alamat baru</option>
                        @foreach($addresses as $a)<option value="{{ $a->id }}" @selected((string) old('address_id', $addresses->firstWhere('is_default', true)?->id) === (string) $a->id)>{{ $a->label }} — {{ $a->recipient }}, {{ \Illuminate\Support\Str::limit($a->address, 40) }}, {{ $a->city }}</option>@endforeach
                    </select>
                    @error('address_id')<small class="error" role="alert">{{ $message }}</small>@enderror
                </div>
                <div id="new-address" class="form">
                    @include('partials.field', ['prefix' => 'c', 'name' => 'recipient', 'label' => 'Nama penerima', 'value' => auth()->user()->name, 'optional' => true])
                    @include('partials.field', ['prefix' => 'c', 'name' => 'phone', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => auth()->user()->phone, 'optional' => true])
                    @include('partials.field', ['prefix' => 'c', 'name' => 'address', 'label' => 'Alamat lengkap', 'type' => 'textarea', 'optional' => true])
                    <div class="two">
                        @include('partials.field', ['prefix' => 'c', 'name' => 'city', 'label' => 'Kota', 'optional' => true])
                        @include('partials.field', ['prefix' => 'c', 'name' => 'postal_code', 'label' => 'Kode pos', 'optional' => true])
                    </div>
                    <label class="check"><input type="checkbox" name="save_address" value="1" @checked(old('save_address'))><span>Simpan alamat ini ke akun saya</span></label>
                </div>
            </section>

            <section class="panel">
                <h3>2. Metode pengiriman</h3>
                <p class="muted" id="zone-note" style="margin:0 0 12px;font-size:.9rem" aria-live="polite">@if($zoneLabel)Zona pengiriman: <b>{{ $zoneLabel }}</b> — ongkir dihitung dari jarak ke alamat tujuan.@else Ongkir dihitung otomatis dari jarak ke alamat tujuan. Pilih atau isi alamat terlebih dahulu.@endif</p>
                <div class="stack" id="ship-options" data-ship-url="{{ route('checkout.shipping') }}">
                    @foreach($shipping as $key => $m)
                        @php($cost = $m['final_cost'])
                        <label class="row-card" style="cursor:pointer;margin:0;flex-wrap:nowrap">
                            <input type="radio" name="shipping_method" value="{{ $key }}" data-cost="{{ $cost ?? '' }}" @checked(old('shipping_method', array_key_first($shipping)) === $key) style="accent-color:var(--forest-2)">
                            <span style="flex:1"><b>{{ $m['label'] }}</b><small class="muted" style="display:block">{{ $m['estimate'] }}@if($m['free_over']) · gratis untuk belanja Rp{{ number_format($m['free_over'], 0, ',', '.') }}+ ({{ collect($m['free_zones'] ?? [])->map(fn ($z) => config('clover.shipping_zones.' . $z . '.label'))->implode(', ') }})@endif</small></span>
                            <b data-ship-price="{{ $key }}">{{ $cost === null ? 'Isi alamat dulu' : ($cost ? 'Rp' . number_format($cost, 0, ',', '.') : 'Gratis') }}</b>
                        </label>
                    @endforeach
                    @error('shipping_method')<small class="error" role="alert">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="panel">
                <h3>3. Metode pembayaran</h3>
                <div class="stack">
                    @foreach($payments as $key => $m)
                        <label class="row-card" style="cursor:pointer;margin:0;flex-wrap:nowrap">
                            <input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($payments)) === $key) style="accent-color:var(--forest-2)">
                            <span style="flex:1"><b>{{ $m['label'] }}</b></span>
                        </label>
                        <small class="muted" data-pay-note="{{ $key }}" hidden>
                            {{ $m['note'] }}
                            @if($key === 'transfer')<br>Rekening: <b>{{ config('clover.bank.name') }} {{ config('clover.bank.number') }}</b> a.n. {{ config('clover.bank.holder') }}@endif
                        </small>
                    @endforeach
                    @error('payment_method')<small class="error" role="alert">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="panel">
                @include('partials.field', ['prefix' => 'c', 'name' => 'notes', 'label' => 'Catatan untuk pesanan (opsional)', 'type' => 'textarea', 'optional' => true])
            </section>

            <button class="btn btn-primary btn-lg btn-block">Buat Pesanan</button>
        </form>

        <aside class="panel glass" style="position:sticky;top:calc(var(--nav-h) + 16px)">
            <h3>Ringkasan pesanan</h3>
            @foreach($lines as $l)<div class="sum-row"><span>{{ $l['product']->name }} × {{ $l['qty'] }}</span><span>Rp{{ number_format($l['subtotal'], 0, ',', '.') }}</span></div>@endforeach
            <hr style="border:0;border-top:1px solid var(--line);margin:12px 0">
            <div class="sum-row"><span class="muted">Subtotal</span><span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span></div>
            @if($quote['discount'])<div class="sum-row"><span class="muted">Kupon {{ $couponCode }}</span><span style="color:var(--ok)">- Rp{{ number_format($quote['discount'], 0, ',', '.') }}</span></div>@endif
            <div class="sum-row"><span class="muted">Ongkir</span><span id="sum-ship">-</span></div>
            <div class="sum-row total"><span>Total</span><span id="sum-total">-</span></div>
            <small class="muted">Total dihitung ulang dan divalidasi server saat pesanan dibuat.</small>

            <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
            @if($couponCode)
                <form method="POST" action="{{ route('checkout.coupon.remove') }}" class="row between">@csrf @method('DELETE')<span><x-icon name="tag" :size="16" /> <b>{{ $couponCode }}</b> diterapkan</span><button class="link-danger">Lepas</button></form>
            @else
                <form method="POST" action="{{ route('checkout.coupon') }}" class="form" novalidate>
                    @csrf
                    <div class="field"><label for="coupon">Kode kupon</label>
                        <div class="row" style="flex-wrap:nowrap"><input id="coupon" name="code" value="{{ old('code') }}" placeholder="Masukkan kode" style="flex:1" @error('code') aria-invalid="true" @enderror><button class="btn btn-glass">Terapkan</button></div>
                        @error('code')<small class="error" role="alert">{{ $message }}</small>@enderror
                    </div>
                </form>
            @endif

            @if(! $couponCode && $availableCoupons->count())
                <div class="coupon-list">
                    <small class="muted">Kupon untuk Anda (hilang otomatis setelah dipakai)</small>
                    @foreach($availableCoupons as $c)
                        <form method="POST" action="{{ route('checkout.coupon') }}" class="coupon-card">
                            @csrf
                            <input type="hidden" name="code" value="{{ $c->code }}">
                            <div>
                                <b>{{ $c->code }}</b>
                                <span>{{ $c->type === 'percent' ? 'Diskon '.$c->value.'%' : 'Potongan Rp'.number_format($c->value, 0, ',', '.') }}@if($c->max_discount) (maks Rp{{ number_format($c->max_discount, 0, ',', '.') }})@endif</span>
                                <small class="muted">@if($c->min_subtotal)Min. belanja Rp{{ number_format($c->min_subtotal, 0, ',', '.') }}@else Tanpa minimal belanja @endif @if($c->expires_at)· s.d. {{ $c->expires_at->format('d/m/Y') }}@endif</small>
                            </div>
                            <button class="btn btn-glass btn-sm">Pakai</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
