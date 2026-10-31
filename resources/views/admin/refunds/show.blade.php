@extends('layouts.admin')
@section('title', 'Refund ' . $refund->number)

@section('content')
<div class="ah"><div><h1>{{ $refund->number }}</h1><p>Pesanan <a href="{{ route('admin.orders.show', $refund->order) }}"><u>{{ $refund->order->number }}</u></a> · {{ $refund->source_label }}</p></div><a class="btn btn-glass" href="{{ route('admin.refunds.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a></div>
<div class="agrid" style="grid-template-columns:1fr 1fr">
    <section class="box">
        <h2>Rincian</h2>
        <dl class="kv">
            <dt>Status</dt><dd>@include('partials.status', ['s' => $refund->status])</dd>
            <dt>Jumlah</dt><dd><b>Rp{{ number_format($refund->amount, 0, ',', '.') }}</b></dd>
            <dt>Alasan</dt><dd>{{ $refund->reason ?: '-' }}</dd>
            <dt>Pelanggan</dt><dd>{{ $refund->order->user?->name ?? $refund->order->name }} · {{ $refund->order->whatsapp }}</dd>
            <dt>Bank</dt><dd>{{ $refund->bank_name ?: '-' }}</dd>
            <dt>No. rekening</dt><dd>{{ $refund->account_number ?: '-' }}</dd>
            <dt>Atas nama</dt><dd>{{ $refund->account_holder ?: '-' }}</dd>
            @if($refund->transfer_reference)<dt>Ref. transfer</dt><dd>{{ $refund->transfer_reference }}@if($refund->proof_url) · <a href="{{ $refund->proof_url }}" target="_blank" rel="noopener"><u>bukti</u></a>@endif</dd>@endif
            @if($refund->admin_note)<dt>Catatan</dt><dd>{{ $refund->admin_note }}</dd>@endif
        </dl>
    </section>
    <section class="box">
        <h2>Proses refund</h2>
        @if($refund->status === 'Menunggu Rekening')
            <p class="muted">Pelanggan belum mengisi rekening tujuan. Mereka sudah diberi notifikasi/email. Refund baru bisa diselesaikan setelah rekening terisi.</p>
        @endif
        @if($refund->status === 'Diminta')
            <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" enctype="multipart/form-data" class="form" data-confirm="Tandai refund sudah ditransfer? Pelanggan akan diberi tahu.">
                @csrf
                <p class="muted" style="margin:0">Transfer <b>Rp{{ number_format($refund->amount, 0, ',', '.') }}</b> ke rekening di samping lebih dulu, lalu catat di sini.</p>
                @include('partials.field', ['prefix' => 'rf', 'name' => 'transfer_reference', 'label' => 'Referensi / nomor transfer'])
                <div class="field"><label for="rf-proof">Bukti transfer (opsional)</label><input id="rf-proof" type="file" name="proof" accept="image/png,image/jpeg,image/webp,application/pdf">@error('proof')<small class="error" role="alert">{{ $message }}</small>@enderror</div>
                @include('partials.field', ['prefix' => 'rf', 'name' => 'admin_note', 'label' => 'Catatan (opsional)', 'type' => 'textarea', 'optional' => true])
                <button class="btn btn-primary">Tandai sudah ditransfer</button>
            </form>
        @endif
        @if(in_array($refund->status, ['Menunggu Rekening', 'Diminta'], true))
            <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="form" data-confirm="Tolak refund ini?" style="margin-top:18px">
                @csrf
                @include('partials.field', ['prefix' => 'rj', 'name' => 'admin_note', 'label' => 'Alasan penolakan'])
                <button class="btn btn-danger">Tolak refund</button>
            </form>
        @endif
        @if(in_array($refund->status, ['Selesai', 'Ditolak'], true))<p class="muted">Refund sudah final.</p>@endif
    </section>
</div>
@endsection
