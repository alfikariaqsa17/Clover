@extends('layouts.app')
@section('title', 'Koleksi Tanaman Saya — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Koleksi Tanaman' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2rem">Koleksi tanaman saya</h1>
        <p class="muted">Simpan tanaman yang Anda miliki, catat perawatannya, dan atur pengingat jadwal.</p>

        @if($plants->count())
            <div class="grid g-3" style="margin:24px 0">
                @foreach($plants as $up)
                    @php($next = $up->schedules->where('is_active', true)->sortBy('next_due_date')->first())
                    <a class="plant-card reveal" href="{{ route('account.plants.show', $up) }}">
                        @include('partials.photo', ['src' => $up->photo_url ?: optional($up->plant)->image_url, 'alt' => $up->nickname, 'class' => 'r-4-3'])
                        <div><h3 style="margin:0 0 4px;font-size:1.1rem;font-family:var(--font-body)">{{ $up->nickname }}</h3>
                            <small class="muted">{{ $up->plant?->name ?? 'Tanaman kustom' }}@if($up->location) · {{ $up->location }}@endif</small>
                            @if($next)<p style="margin:10px 0 0;font-size:.85rem"><x-icon name="calendar" :size="14" /> {{ $next->type_label }}: {{ $next->next_due_date->translatedFormat('j M') }}</p>@endif</div>
                    </a>
                @endforeach
            </div>
        @else
            @include('partials.empty', ['icon' => 'sprout', 'title' => 'Koleksi masih kosong', 'text' => 'Tambahkan tanaman pertama Anda di bawah ini.'])
        @endif

        <div class="panel glass" style="margin-top:28px">
            <h3>Tambah tanaman</h3>
            <form method="POST" action="{{ route('account.plants.store') }}" enctype="multipart/form-data" class="form" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 'u', 'name' => 'nickname', 'label' => 'Nama panggilan tanaman', 'hint' => 'Contoh: Monty si Monstera'])
                @include('partials.field', ['prefix' => 'u', 'name' => 'plant_id', 'label' => 'Jenis tanaman (opsional)', 'type' => 'select', 'options' => $catalog->pluck('name', 'id')->all(), 'assoc' => true, 'optional' => true, 'hint' => 'Jika dipilih, jadwal siram dibuat otomatis dari data tanaman.'])
                <div class="two">
                    @include('partials.field', ['prefix' => 'u', 'name' => 'location', 'label' => 'Lokasi', 'optional' => true, 'hint' => 'Contoh: ruang tamu'])
                    @include('partials.field', ['prefix' => 'u', 'name' => 'acquired_at', 'label' => 'Tanggal didapat', 'type' => 'date', 'nomin' => true, 'optional' => true])
                </div>
                @include('partials.field', ['prefix' => 'u', 'name' => 'photo', 'label' => 'Foto (opsional)', 'type' => 'file', 'optional' => true])
                @include('partials.field', ['prefix' => 'u', 'name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea', 'optional' => true])
                <button class="btn btn-primary">Tambah ke Koleksi</button>
            </form>
        </div>
    </div>
</div>
@endsection
