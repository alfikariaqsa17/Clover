@extends('layouts.admin')
@section('title', 'Retur')

@section('content')
<div class="ah"><h1>Retur barang</h1></div>
<form method="GET" class="filters">
    <select name="status" aria-label="Filter status" data-auto><option value="">Semua status</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach</select>
    @if($status)<a class="btn btn-ghost btn-sm" href="{{ route('admin.returns.index') }}">Reset</a>@endif
</form>
<div class="tbw" data-live="list"><table class="tb">
    <thead><tr><th>Nomor</th><th>Pesanan</th><th>Pelanggan</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($returns as $rt)
        <tr data-key="r{{ $rt->id }}">
            <td><b>{{ $rt->number }}</b><br><small class="muted">{{ $rt->created_at->format('d-m-Y') }}</small></td>
            <td>{{ $rt->order->number }}</td>
            <td>{{ $rt->user?->name ?? $rt->order->name }}</td>
            <td>{{ $rt->reason_label }}</td>
            <td>@include('partials.status', ['s' => $rt->status])</td>
            <td><a class="btn btn-glass btn-sm" href="{{ route('admin.returns.show', $rt) }}">Tinjau</a></td>
        </tr>
    @empty
        <tr><td colspan="6">Tidak ada pengajuan retur.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $returns->links() }}
@endsection
