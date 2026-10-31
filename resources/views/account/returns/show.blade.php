@extends('layouts.app')
@section('title', 'Retur ' . $ret->number . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Retur & Refund' => route('account.returns'), $ret->number => null]])
@php
    $flow = $ret->needs_return ? ['Diajukan', 'Disetujui', 'Dikirim Balik', 'Diterima', 'Selesai'] : ['Diajukan', 'Disetujui', 'Selesai'];
    $idx = array_search($ret->status, $flow, true);
    $rejected = $ret->status === 'Ditolak';
@endphp
<div class="container account">
    @include('account._nav')
    <div data-live="return-page">
        <div class="row between"><h1 style="font-size:2rem;margin:0">{{ $ret->number }}</h1>@include('partials.status', ['s' => $ret->status])</div>
        <p class="muted">Untuk pesanan <a href="{{ route('account.orders.show', $ret->order) }}"><u>{{ $ret->order->number }}</u></a> · diajukan {{ $ret->created_at->format('d-m-Y H:i') }}</p>

        @if(! $rejected)
            <div class="timeline" aria-label="Progres retur">
                @foreach($flow as $i => $st)<div class="t {{ $idx !== false && $i <= $idx ? 'done' : '' }}">{{ $st }}</div>@endforeach
            </div>
        @else
            <div class="alert bad"><x-icon name="alert" /><span>Pengajuan retur ditolak. Alasan: {{ $ret->admin_note }}</span></div>
        @endif

        @if($ret->status === 'Disetujui' && ! $ret->needs_return)
            <div class="alert" style="margin-top:16px"><x-icon name="check-circle" /><span>Retur disetujui. Barang tidak perlu dikirim balik; refund sedang diproses admin.@if($ret->admin_note) Catatan: {{ $ret->admin_note }}@endif</span></div>
        @endif

        @if($ret->canShipBack())
            <div class="panel glass stack" style="margin:20px 0">
                <h3>Kirim barang kembali</h3>
                <p class="muted" style="margin:0">Retur disetujui. Kirim barang ke {{ \App\Models\Setting::get('address') }}, lalu isi data pengiriman. Ongkos kirim balik ditanggung pelanggan kecuali barang salah/rusak dari toko (sesuai kebijakan toko).@if($ret->admin_note) Catatan admin: {{ $ret->admin_note }}@endif</p>
                <form method="POST" action="{{ route('account.returns.ship', $ret) }}" class="form" novalidate>
                    @csrf
                    <div class="two">
                        @include('partials.field', ['prefix' => 'sb', 'name' => 'return_courier', 'label' => 'Kurir'])
                        @include('partials.field', ['prefix' => 'sb', 'name' => 'return_tracking', 'label' => 'Nomor resi'])
                    </div>
                    <div><button class="btn btn-primary">Simpan data pengiriman</button></div>
                </form>
            </div>
        @elseif($ret->status === 'Dikirim Balik')
            <div class="alert" style="margin-top:16px"><x-icon name="truck" /><span>Barang dikirim balik via {{ $ret->return_courier }} (resi {{ $ret->return_tracking }}). Menunggu admin menerima barang.</span></div>
        @endif

        <div class="grid g-2" style="margin-top:20px;align-items:start">
            <div class="panel"><h3>Pengajuan</h3>
                <table class="table-simple">
                    <tr><td>Alasan</td><td>{{ $ret->reason_label }}</td></tr>
                    <tr><td>Penjelasan</td><td>{{ $ret->description }}</td></tr>
                    <tr><td>Refund</td><td>@if($ret->refund)Rp{{ number_format($ret->refund->amount, 0, ',', '.') }} — @include('partials.status', ['s' => $ret->refund->status]) @elseif($ret->refund_amount)Rp{{ number_format($ret->refund_amount, 0, ',', '.') }} (dibuat setelah barang diterima) @else Ditentukan admin @endif</td></tr>
                    @if($ret->refund?->status === 'Selesai')<tr><td>Referensi transfer</td><td>{{ $ret->refund->transfer_reference }}</td></tr>@endif
                </table>
            </div>
            <div class="panel"><h3>Foto bukti</h3>
                @forelse($ret->evidenceUrls() as $u)<a href="{{ $u }}" target="_blank" rel="noopener"><img src="{{ $u }}" alt="Bukti retur" style="max-width:120px;border-radius:10px;margin:0 8px 8px 0"></a>@empty<p class="muted" style="margin:0">Tidak ada foto dilampirkan.</p>@endforelse
            </div>
        </div>
    </div>
</div>
@endsection
