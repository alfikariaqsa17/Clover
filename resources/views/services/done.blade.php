@extends('layouts.app')
@section('title', 'Pemesanan Jasa — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Jasa Perawatan' => route('services'), 'Pemesanan ' . $booking->code => null]])
<div class="container" style="max-width:760px;padding:24px 0 96px">
    <div class="panel glass">
        <div class="row"><x-icon name="check-circle" :size="34" style="color:var(--forest-2)" /><h1 style="font-size:2rem;margin:0">Permintaan jasa terkirim</h1></div>
        <p class="muted">Kode pemesanan <b>{{ $booking->code }}</b>. Tim Clover akan mengonfirmasi lewat WhatsApp.</p>
        <table class="table-simple">
            <tr><td>Layanan</td><td>{{ $booking->service_name }}</td></tr>
            <tr><td>Tanggal & jam</td><td>{{ $booking->date->translatedFormat('l, j F Y') }} · {{ $booking->time }}</td></tr>
            <tr><td>Nama</td><td>{{ $booking->name }}</td></tr>
            <tr><td>WhatsApp</td><td>{{ $booking->whatsapp }}</td></tr>
            <tr><td>Lokasi</td><td>{{ $booking->address }}</td></tr>
            <tr><td>Luas</td><td>{{ $booking->area }} m²</td></tr>
            <tr><td>Catatan</td><td>{{ $booking->notes ?: '-' }}</td></tr>
            <tr><td>Status</td><td>@include('partials.status', ['s' => $booking->status])</td></tr>
        </table>
        @if($booking->rescheduled_at)
            <p class="muted" style="margin:12px 0 0">Dijadwalkan ulang {{ $booking->reschedule_count }}x (sebelumnya {{ $booking->previous_date?->translatedFormat('j F Y') }} · {{ $booking->previous_time }}).</p>
        @endif
        @if($booking->status === 'Dibatalkan')
            <div class="alert bad" style="margin-top:16px"><x-icon name="alert" /><span>Pemesanan ini dibatalkan.@if($booking->cancel_reason) Alasan: {{ $booking->cancel_reason }}@endif</span></div>
        @endif
        @if($blockedNow)
            <div class="alert warn" style="margin-top:16px"><x-icon name="alert" /><span>Tanggal kunjungan ini kini libur / tidak tersedia. Mohon jadwalkan ulang ke tanggal lain.</span></div>
        @endif

        @if($booking->user_id && $booking->isActive())
            @if($booking->service && $booking->canReschedule())
                <details class="panel" style="margin-top:20px" @if($errors->has('date') || $errors->has('time') || $blockedNow) open @endif>
                    <summary style="cursor:pointer;font-weight:600">Jadwalkan ulang</summary>
                    <form method="POST" action="{{ route('booking.reschedule', $booking) }}" class="form" novalidate data-availability="{{ route('services.availability', $booking->service) }}" data-except="{{ $booking->code }}">
                        @csrf
                        <p class="muted" style="margin:12px 0 0">Bisa diubah maksimal {{ config('clover.booking.max_reschedules') }}x (sisa {{ config('clover.booking.max_reschedules') - $booking->reschedule_count }}x), minimal {{ config('clover.booking.change_min_hours') }} jam sebelum kunjungan. Jadwal baru perlu dikonfirmasi ulang oleh tim.</p>
                        <div class="two">
                            @include('partials.field', ['prefix' => 'rs', 'name' => 'date', 'label' => 'Tanggal baru', 'type' => 'date'])
                            <div class="field"><label for="rs-time">Jam baru *</label>
                                <select id="rs-time" name="time" required @error('time') aria-invalid="true" @enderror>
                                    <option value="">Pilih jam...</option>
                                    @foreach($times as $t)<option value="{{ $t }}" @selected(old('time') === $t)>{{ $t }}</option>@endforeach
                                </select>
                                @error('time')<small class="error" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <small class="hint" id="slot-info" aria-live="polite">Pilih tanggal untuk melihat jam yang tersedia.</small>
                        <div><button class="btn btn-primary">Simpan jadwal baru</button></div>
                    </form>
                </details>
            @endif
            @if($booking->canChange())
                <details class="panel" style="margin-top:14px" @if($errors->has('reason')) open @endif>
                    <summary style="cursor:pointer;font-weight:600">Batalkan pemesanan</summary>
                    <form method="POST" action="{{ route('booking.cancel', $booking) }}" class="form" novalidate data-confirm="Batalkan pemesanan {{ $booking->code }}?">
                        @csrf
                        @include('partials.field', ['prefix' => 'bc', 'name' => 'reason', 'label' => 'Alasan (opsional)', 'optional' => true])
                        <div><button class="btn btn-danger">Batalkan pemesanan</button></div>
                    </form>
                </details>
            @else
                <p class="muted" style="margin-top:16px;font-size:.9rem">Jadwal sudah terlalu dekat (kurang dari {{ config('clover.booking.change_min_hours') }} jam), sehingga tidak bisa diubah/dibatalkan sendiri. Hubungi kami lewat WhatsApp.</p>
            @endif
        @endif

        <div class="row" style="margin-top:20px"><a class="btn btn-primary" href="{{ route('account.bookings') }}">Lihat Pemesanan Saya</a><a class="btn btn-ghost" href="{{ route('services') }}">Layanan Lain</a></div>
    </div>
</div>
@endsection
