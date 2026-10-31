@extends('layouts.admin')
@section('title', 'Ulasan Pelanggan')

@section('content')
<div class="ah"><h1>Ulasan pelanggan</h1></div>
<form method="GET" class="filters"><select name="status" aria-label="Filter status" data-auto><option value="">Semua status</option>@foreach(\App\Models\Review::STATUSES as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach</select></form>
<div class="tbw"><table class="tb">
    <thead><tr><th>Produk</th><th>Pelanggan</th><th>Rating</th><th>Ulasan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($reviews as $r)
        <tr>
            <td>{{ $r->product?->name ?? '-' }}</td><td>{{ $r->user?->name }}<br><small class="muted">{{ $r->is_verified ? 'Pembeli terverifikasi' : 'Belum terverifikasi' }}</small></td>
            <td>{{ $r->rating }}/5</td><td style="max-width:320px">{{ \Illuminate\Support\Str::limit($r->body, 120) }}</td>
            <td>@include('partials.status', ['s' => $r->status, 'label' => \App\Models\Review::STATUSES[$r->status] ?? $r->status])</td>
            <td class="acts">
                <form method="POST" action="{{ route('admin.reviews.update', $r) }}" class="row" style="flex-wrap:nowrap">@csrf @method('PATCH')<select name="status" aria-label="Status ulasan" style="min-height:38px;border-radius:999px;border:1px solid var(--line);background:var(--surface);padding:0 10px">@foreach(\App\Models\Review::STATUSES as $k => $l)<option value="{{ $k }}" @selected($r->status === $k)>{{ $l }}</option>@endforeach</select><button class="btn btn-glass btn-sm">Simpan</button></form>
                <form method="POST" action="{{ route('admin.reviews.destroy', $r) }}" data-confirm="Hapus ulasan ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6">Belum ada ulasan.</td></tr>
    @endforelse
    </tbody>
</table></div>
<p class="muted" style="font-size:.85rem">Hanya ulasan berstatus "Tampil" dari pembeli terverifikasi yang muncul di situs dan dihitung pada rating produk.</p>
{{ $reviews->links() }}
@endsection
