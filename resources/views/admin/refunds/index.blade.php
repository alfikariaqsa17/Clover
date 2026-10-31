@extends('layouts.admin')
@section('title', 'Refund')

@section('content')
<div class="ah"><h1>Refund</h1></div>
<form method="GET" class="filters">
    <select name="status" aria-label="Filter status" data-auto><option value="">Semua status</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach</select>
    @if($status)<a class="btn btn-ghost btn-sm" href="{{ route('admin.refunds.index') }}">Reset</a>@endif
</form>
<div class="tbw" data-live="list"><table class="tb">
    <thead><tr><th>Nomor</th><th>Pesanan</th><th>Sumber</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($refunds as $rf)
        <tr data-key="f{{ $rf->id }}">
            <td><b>{{ $rf->number }}</b><br><small class="muted">{{ $rf->created_at->format('d-m-Y') }}</small></td>
            <td>{{ $rf->order->number }}</td>
            <td>{{ $rf->source_label }}</td>
            <td>Rp{{ number_format($rf->amount, 0, ',', '.') }}</td>
            <td>@include('partials.status', ['s' => $rf->status])</td>
            <td><a class="btn btn-glass btn-sm" href="{{ route('admin.refunds.show', $rf) }}">Buka</a></td>
        </tr>
    @empty
        <tr><td colspan="6">Tidak ada refund.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $refunds->links() }}
@endsection
