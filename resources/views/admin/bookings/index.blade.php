@extends('layouts.admin')
@section('title', 'Booking Jasa')

@section('content')
<div class="ah"><h1>Booking jasa</h1></div>
<form method="GET" class="filters">
    <select name="status" aria-label="Filter status" data-auto><option value="">Semua status</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach</select>
    <label class="sr-only" for="tgl">Tanggal</label><input id="tgl" type="date" name="tanggal" value="{{ $date }}">
    <button class="btn btn-glass btn-sm">Filter</button>
    @if($status || $date)<a class="btn btn-ghost btn-sm" href="{{ route('admin.bookings.index') }}">Reset</a>@endif
</form>
<div class="tbw" data-live="list"><table class="tb">
    <thead><tr><th>Kode</th><th>Pelanggan</th><th>Layanan</th><th>Jadwal</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($bookings as $b)
        <tr data-key="b{{ $b->id }}">
            <td><b>{{ $b->code }}</b></td>
            <td>{{ $b->name }}<br><small><a href="https://wa.me/{{ preg_replace('/\D/', '', $b->whatsapp) }}" target="_blank" rel="noopener"><u>{{ $b->whatsapp }}</u></a></small></td>
            <td>{{ $b->service_name }}</td>
            <td>{{ $b->date->format('d-m-Y') }}<br><small class="muted">{{ $b->time }} · {{ $b->area }} m²</small>@if($b->reschedule_count)<br><small class="muted">Dijadwal ulang {{ $b->reschedule_count }}x (dari {{ $b->previous_date?->format('d-m-Y') }} {{ $b->previous_time }})</small>@endif</td>
            <td>{{ $b->address }}@if($b->notes)<br><small class="muted">Catatan: {{ $b->notes }}</small>@endif</td>
            <td><form method="POST" action="{{ route('admin.bookings.status', $b) }}" class="row" style="flex-wrap:nowrap">@csrf @method('PATCH')<select name="status" aria-label="Status {{ $b->code }}" style="min-height:38px;border-radius:999px;border:1px solid var(--line);background:var(--surface);padding:0 10px">@foreach($statuses as $s)<option value="{{ $s }}" @selected($b->status === $s)>{{ $s }}</option>@endforeach</select><button class="btn btn-glass btn-sm">Simpan</button></form></td>
            <td><form method="POST" action="{{ route('admin.bookings.destroy', $b) }}" data-confirm="Hapus pemesanan {{ $b->code }}?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form></td>
        </tr>
    @empty
        <tr><td colspan="7">Tidak ada pemesanan jasa.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $bookings->links() }}
@endsection
