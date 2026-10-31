@extends('layouts.app')
@section('title', 'Pemesanan Jasa — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Pemesanan Jasa' => null]])
<div class="container account">
    @include('account._nav')
    <div data-live="bookings-page">
        <h1 style="font-size:2rem">Pemesanan jasa saya</h1>
        @forelse($bookings as $b)
            <a class="row-card" data-key="b{{ $b->id }}" href="{{ route('booking.done', $b) }}"><span><b>{{ $b->service_name }}</b><br><small class="muted">{{ $b->code }} · {{ $b->date->translatedFormat('j F Y') }} · {{ $b->time }}</small></span>@include('partials.status', ['s' => $b->status])</a>
        @empty
            @include('partials.empty', ['icon' => 'tool', 'title' => 'Belum ada pemesanan jasa', 'text' => 'Pesan layanan perawatan untuk tanaman Anda.', 'url' => route('services'), 'label' => 'Lihat Jasa'])
        @endforelse
        {{ $bookings->links() }}
    </div>
</div>
@endsection
