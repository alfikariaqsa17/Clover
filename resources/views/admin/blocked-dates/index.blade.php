@extends('layouts.admin')
@section('title', 'Libur & Blokir Jadwal')

@section('content')
<div class="ah"><div><h1>Libur & blokir jadwal</h1><p>Tanggal di sini tidak bisa dipesan pelanggan. Pemesanan aktif yang bertabrakan otomatis diberi tahu agar menjadwal ulang.</p></div></div>
<section class="box" style="margin-bottom:20px">
    <h2>Blokir tanggal</h2>
    <form method="POST" action="{{ route('admin.blocked-dates.store') }}" class="form" novalidate>
        @csrf
        <div class="field"><label for="bd-service">Berlaku untuk</label>
            <select id="bd-service" name="service_id">
                <option value="">Semua layanan</option>
                @foreach($services as $sv)<option value="{{ $sv->id }}" @selected((string) old('service_id') === (string) $sv->id)>{{ $sv->name }}</option>@endforeach
            </select>
            @error('service_id')<small class="error" role="alert">{{ $message }}</small>@enderror
        </div>
        <div class="two">
            @include('partials.field', ['prefix' => 'bd', 'name' => 'start_date', 'label' => 'Tanggal mulai', 'type' => 'date'])
            @include('partials.field', ['prefix' => 'bd', 'name' => 'end_date', 'label' => 'Tanggal selesai (kosongkan bila 1 hari)', 'type' => 'date', 'optional' => true])
        </div>
        @include('partials.field', ['prefix' => 'bd', 'name' => 'reason', 'label' => 'Alasan (tampil ke pelanggan, mis. Libur Natal)', 'optional' => true])
        <div><button class="btn btn-primary">Blokir tanggal</button></div>
    </form>
</section>
<div class="tbw"><table class="tb">
    <thead><tr><th>Tanggal</th><th>Layanan</th><th>Alasan</th><th>Booking bertabrakan</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        <tr>
            <td><b>{{ $r->date->translatedFormat('l, j F Y') }}</b></td>
            <td>{{ $r->service?->name ?? 'Semua layanan' }}</td>
            <td>{{ $r->reason ?: '-' }}</td>
            <td>@if(($affected[$r->id] ?? 0) > 0)<a href="{{ route('admin.bookings.index', ['tanggal' => $r->date->toDateString()]) }}"><u>{{ $affected[$r->id] }} pemesanan</u></a>@else 0 @endif</td>
            <td><form method="POST" action="{{ route('admin.blocked-dates.destroy', $r) }}" data-confirm="Buka kembali tanggal ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Buka kembali"><x-icon name="trash" :size="16" /></button></form></td>
        </tr>
    @empty
        <tr><td colspan="5">Belum ada tanggal yang diblokir.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $rows->links() }}
@endsection
