@extends('layouts.app')
@section('title', $service->name . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Jasa Perawatan' => route('services'), $service->name => null]])
<div class="container" style="padding:24px 0 96px">
    <div class="grid g-2" style="gap:56px;align-items:start">
        <div>
            <span class="eyebrow">Jasa Perawatan</span>
            <h1 style="font-size:clamp(2rem,4vw,3rem);margin-top:8px">{{ $service->name }}</h1>
            <p class="lead muted">{{ $service->description }}</p>
            <div style="border-radius:var(--r-lg);overflow:hidden;margin:24px 0">
                <!-- GANTI FOTO JASA: Dashboard Admin > Layanan Jasa > Edit > Foto -->
                @include('partials.photo', ['src' => $service->image_url, 'alt' => $service->name, 'class' => 'r-16-9', 'eager' => true])
            </div>
            <h3>Yang termasuk</h3>
            <ul class="list-check">@foreach($service->includes as $i)<li><x-icon name="check" :size="18" /> {{ $i }}</li>@endforeach</ul>
            <table class="table-simple" style="margin-top:24px">
                <tr><td>Jadwal kunjungan</td><td>{{ $service->schedule }}</td></tr>
                <tr><td>Durasi</td><td>{{ $service->estimate }}</td></tr>
                <tr><td>Harga mulai dari</td><td><b class="price">{{ $service->price_format }}</b></td></tr>
            </table>
            <p class="muted" style="font-size:.85rem;margin-top:10px">Harga final dikonfirmasi tim setelah memeriksa luas dan kondisi lokasi.</p>
        </div>

        <div class="panel glass" id="pesan" style="position:sticky;top:calc(var(--nav-h) + 16px)">
            <h2 style="font-size:1.6rem">Pesan layanan ini</h2>
            @guest
                <p class="muted">Masuk ke akun untuk memesan agar pemesanan dan notifikasinya tersimpan.</p>
                <a class="btn btn-primary btn-block" href="{{ route('login') }}">Masuk untuk memesan</a>
            @else
                <form method="POST" action="{{ route('services.book', $service) }}" class="form" novalidate data-availability="{{ route('services.availability', $service) }}">
                    @csrf
                    @include('partials.field', ['prefix' => 'j', 'name' => 'name', 'label' => 'Nama', 'value' => auth()->user()->name])
                    @include('partials.field', ['prefix' => 'j', 'name' => 'whatsapp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'value' => auth()->user()->phone, 'autocomplete' => 'tel'])
                    @include('partials.field', ['prefix' => 'j', 'name' => 'address', 'label' => 'Alamat / lokasi layanan'])
                    <div class="two">
                        @include('partials.field', ['prefix' => 'j', 'name' => 'date', 'label' => 'Tanggal', 'type' => 'date'])
                        <div class="field"><label for="j-time">Jam *</label>
                            <select id="j-time" name="time" required @error('time') aria-invalid="true" @enderror>
                                <option value="">Pilih jam...</option>
                                @foreach($times as $t)<option value="{{ $t }}" @selected(old('time') === $t)>{{ $t }}</option>@endforeach
                            </select>
                            @error('time')<small class="error" role="alert">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <small class="hint" id="slot-info" aria-live="polite">Pilih tanggal untuk melihat jam yang tersedia.</small>
                    @if(count($blocked))
                        <small class="hint">Tanggal libur / tidak tersedia: @foreach(array_slice($blocked, 0, 8, true) as $d => $why){{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('j M') }} ({{ $why }})@if(! $loop->last), @endif @endforeach @if(count($blocked) > 8) dan {{ count($blocked) - 8 }} tanggal lainnya @endif</small>
                    @endif
                    @include('partials.field', ['prefix' => 'j', 'name' => 'area', 'label' => 'Luas area / jumlah tanaman (m²)', 'type' => 'number'])
                    @include('partials.field', ['prefix' => 'j', 'name' => 'notes', 'label' => 'Catatan tambahan', 'type' => 'textarea', 'optional' => true])
                    <button class="btn btn-primary btn-lg btn-block">Ajukan Pemesanan</button>
                </form>
            @endif
        </div>
    </div>

    @if($others->count())
        <div class="section-head" style="margin-top:80px"><div><h2>Layanan lainnya</h2></div></div>
        <div class="grid g-3">@foreach($others as $s) @include('partials.service-card', ['s' => $s]) @endforeach</div>
    @endif
</div>
@endsection
