@extends('layouts.app')
@section('title', $up->nickname . ' — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Koleksi Tanaman' => route('account.plants'), $up->nickname => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <div class="grid g-2" style="align-items:start">
            <div>
                <div style="border-radius:var(--r-lg);overflow:hidden">@include('partials.photo', ['src' => $up->photo_url ?: optional($up->plant)->image_url, 'alt' => $up->nickname, 'class' => 'r-4-3', 'eager' => true])</div>
            </div>
            <div>
                <h1 style="font-size:2rem">{{ $up->nickname }}</h1>
                <p class="muted">{{ $up->plant?->name ?? 'Tanaman kustom' }}@if($up->location) · {{ $up->location }}@endif @if($up->acquired_at) · sejak {{ $up->acquired_at->translatedFormat('j F Y') }}@endif</p>
                @if($up->notes)<p>{{ $up->notes }}</p>@endif
                @if($up->plant)
                    <div class="chips" style="margin:12px 0"><span class="chip"><x-icon name="sun" :size="16" /> {{ $up->plant->light_label }}</span><span class="chip"><x-icon name="droplet" :size="16" /> Siram {{ $up->plant->water_days }} hari sekali</span></div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('guide.show', $up->plant) }}">Panduan perawatan</a>
                @endif
                <details style="margin-top:16px"><summary class="btn btn-glass btn-sm" style="display:inline-flex;cursor:pointer">Ubah data tanaman</summary>
                    <form method="POST" action="{{ route('account.plants.update', $up) }}" enctype="multipart/form-data" class="form" style="margin-top:14px" novalidate>
                        @csrf @method('PUT')
                        @include('partials.field', ['prefix' => 'e', 'name' => 'nickname', 'label' => 'Nama panggilan', 'value' => $up->nickname])
                        @include('partials.field', ['prefix' => 'e', 'name' => 'plant_id', 'label' => 'Jenis tanaman', 'type' => 'select', 'options' => $catalog->pluck('name', 'id')->all(), 'assoc' => true, 'optional' => true, 'value' => $up->plant_id])
                        @include('partials.field', ['prefix' => 'e', 'name' => 'location', 'label' => 'Lokasi', 'optional' => true, 'value' => $up->location])
                        @include('partials.field', ['prefix' => 'e', 'name' => 'acquired_at', 'label' => 'Tanggal didapat', 'type' => 'date', 'nomin' => true, 'optional' => true, 'value' => $up->acquired_at?->format('Y-m-d')])
                        @include('partials.field', ['prefix' => 'e', 'name' => 'photo', 'label' => 'Ganti foto', 'type' => 'file', 'optional' => true])
                        @include('partials.field', ['prefix' => 'e', 'name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea', 'optional' => true, 'value' => $up->notes])
                        <button class="btn btn-primary btn-sm">Simpan</button>
                    </form>
                    <form method="POST" action="{{ route('account.plants.destroy', $up) }}" data-confirm="Hapus {{ $up->nickname }} dari koleksi beserta jadwal dan riwayatnya?" style="margin-top:10px">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus tanaman</button></form>
                </details>
            </div>
        </div>

        <h2 style="font-size:1.6rem;margin-top:40px">Jadwal perawatan</h2>
        @forelse($up->schedules as $s)
            <div class="row-card">
                <span><b>{{ $s->type_label }}</b> setiap {{ $s->every_days }} hari<br><small class="muted">Berikutnya: {{ $s->next_due_date->translatedFormat('l, j F Y') }}@if($s->last_done_at) · terakhir {{ $s->last_done_at->format('d-m-Y') }}@endif</small></span>
                <span class="row">
                    @if($s->is_overdue)<span class="status s-dibatalkan">Terlambat</span>@endif
                    <form method="POST" action="{{ route('account.schedules.done', $s) }}" data-keep>@csrf<button class="btn btn-primary btn-sm"><x-icon name="check" :size="16" /> Selesai</button></form>
                    <form method="POST" action="{{ route('account.schedules.destroy', $s) }}" data-confirm="Hapus jadwal {{ $s->type_label }}?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus jadwal"><x-icon name="trash" :size="16" /></button></form>
                </span>
            </div>
        @empty
            <p class="muted">Belum ada jadwal. Tambahkan di bawah.</p>
        @endforelse
        <details class="panel glass" style="margin-top:14px"><summary style="cursor:pointer;font-weight:600">Tambah jadwal</summary>
            <form method="POST" action="{{ route('account.plants.schedules.store', $up) }}" class="form" style="margin-top:16px" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 's', 'name' => 'type', 'label' => 'Jenis perawatan', 'type' => 'select', 'options' => \App\Models\CareSchedule::TYPES, 'assoc' => true])
                <div class="two">@include('partials.field', ['prefix' => 's', 'name' => 'every_days', 'label' => 'Ulangi setiap (hari)', 'type' => 'number', 'min' => 1, 'value' => 7])@include('partials.field', ['prefix' => 's', 'name' => 'next_due_date', 'label' => 'Tanggal berikutnya', 'type' => 'date', 'value' => date('Y-m-d')])</div>
                <button class="btn btn-primary btn-sm">Simpan Jadwal</button>
            </form>
        </details>

        <h2 style="font-size:1.6rem;margin-top:40px">Riwayat perawatan</h2>
        @forelse($up->logs->take(15) as $log)
            <div class="notif"><span class="ico-wrap"><x-icon name="check" :size="18" /></span><div><b>{{ \App\Models\CareSchedule::TYPES[$log->type] ?? ucfirst($log->type) }}</b> <span class="muted">· {{ $log->done_at->translatedFormat('j F Y') }}</span>@if($log->note)<p class="muted" style="margin:2px 0 0">{{ $log->note }}</p>@endif</div></div>
        @empty
            <p class="muted">Belum ada riwayat.</p>
        @endforelse
        <details class="panel glass" style="margin-top:14px"><summary style="cursor:pointer;font-weight:600">Catat perawatan manual</summary>
            <form method="POST" action="{{ route('account.plants.logs.store', $up) }}" class="form" style="margin-top:16px" novalidate>
                @csrf
                <div class="two">@include('partials.field', ['prefix' => 'l', 'name' => 'type', 'label' => 'Jenis', 'type' => 'select', 'options' => \App\Models\CareSchedule::TYPES, 'assoc' => true])@include('partials.field', ['prefix' => 'l', 'name' => 'done_at', 'label' => 'Tanggal', 'type' => 'date', 'nomin' => true, 'value' => date('Y-m-d')])</div>
                @include('partials.field', ['prefix' => 'l', 'name' => 'note', 'label' => 'Catatan', 'optional' => true])
                <button class="btn btn-primary btn-sm">Simpan Catatan</button>
            </form>
        </details>
    </div>
</div>
@endsection
