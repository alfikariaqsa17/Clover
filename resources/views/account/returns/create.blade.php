@extends('layouts.app')
@section('title', 'Ajukan retur — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Pesanan' => route('account.orders'), $order->number => route('account.orders.show', $order), 'Retur' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2rem">Ajukan retur · {{ $order->number }}</h1>
        <p class="muted">Batas pengajuan: <b>{{ $deadline->translatedFormat('j F Y') }}</b>. Admin akan meninjau pengajuan Anda. Untuk tanaman yang mati/rusak, foto bukti wajib dilampirkan dan biasanya barang tidak perlu dikirim balik.</p>
        <div class="panel" style="margin-bottom:20px"><h3>Item pesanan</h3>
            <table class="table-simple">@foreach($order->items as $i)<tr><td>{{ $i->name }} × {{ $i->qty }}</td><td>Rp{{ number_format($i->price * $i->qty, 0, ',', '.') }}</td></tr>@endforeach
            <tr><td><b>Total dibayar</b></td><td><b>Rp{{ number_format($order->payment->amount, 0, ',', '.') }}</b></td></tr></table>
        </div>
        <form method="POST" action="{{ route('account.returns.store', $order) }}" enctype="multipart/form-data" class="panel form" novalidate>
            @csrf
            @include('partials.field', ['prefix' => 'rt', 'name' => 'reason_code', 'label' => 'Alasan retur', 'type' => 'select', 'options' => $reasons, 'assoc' => true])
            @include('partials.field', ['prefix' => 'rt', 'name' => 'description', 'label' => 'Jelaskan masalahnya', 'type' => 'textarea'])
            <div class="field">
                <label for="rt-evidence">Foto bukti (maks 3 foto JPG/PNG, 3 MB per foto)</label>
                <input id="rt-evidence" type="file" name="evidence[]" accept="image/png,image/jpeg,image/webp" multiple @error('evidence') aria-invalid="true" @enderror>
                <small class="hint">Wajib untuk alasan: {{ collect(config('clover.return.evidence_required'))->map(fn ($c) => config('clover.return.reasons.' . $c))->implode('; ') }}.</small>
                @error('evidence')<small class="error" role="alert">{{ $message }}</small>@enderror
                @error('evidence.*')<small class="error" role="alert">{{ $message }}</small>@enderror
            </div>
            <h3 style="margin-top:8px">Rekening tujuan refund</h3>
            <div class="two">
                @include('partials.field', ['prefix' => 'rt', 'name' => 'bank_name', 'label' => 'Nama bank'])
                @include('partials.field', ['prefix' => 'rt', 'name' => 'account_number', 'label' => 'Nomor rekening'])
            </div>
            @include('partials.field', ['prefix' => 'rt', 'name' => 'account_holder', 'label' => 'Nama pemilik rekening'])
            <button class="btn btn-primary">Kirim pengajuan retur</button>
        </form>
    </div>
</div>
@endsection
