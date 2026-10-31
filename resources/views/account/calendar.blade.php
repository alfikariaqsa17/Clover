@extends('layouts.app')
@section('title', 'Kalender Perawatan — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Kalender Perawatan' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <div class="row between"><h1 style="font-size:2rem;margin:0">Kalender perawatan</h1>
            <div class="row"><a class="icon-btn" href="{{ route('account.calendar', ['bulan' => $prev]) }}" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" /></a><b style="min-width:150px;text-align:center">{{ $month->translatedFormat('F Y') }}</b><a class="icon-btn" href="{{ route('account.calendar', ['bulan' => $next]) }}" aria-label="Bulan berikutnya"><x-icon name="chevron-right" /></a></div></div>

        @if($overdue->count())
            <div class="alert bad" style="margin-top:20px"><x-icon name="alert" /><span><b>{{ $overdue->count() }} jadwal terlambat:</b> @foreach($overdue as $s){{ $s->type_label }} {{ $s->userPlant->nickname }}@if(! $loop->last), @endif @endforeach</span></div>
        @endif

        <div style="overflow-x:auto;margin-top:20px">
            <table class="cal" aria-label="Kalender {{ $month->translatedFormat('F Y') }}">
                <thead><tr>@foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)<th scope="col">{{ $d }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($weeks as $week)
                    <tr>@foreach($week as $day)
                        <td class="{{ $day['in_month'] ? '' : 'out' }} {{ $day['date']->isToday() ? 'today' : '' }}">
                            <span class="d">{{ $day['date']->day }}</span>
                            @foreach(array_slice($day['events'], 0, 3) as $ev)<span class="ev" title="{{ $ev->type_label }} {{ $ev->userPlant->nickname }}">{{ $ev->type_label }} · {{ $ev->userPlant->nickname }}</span>@endforeach
                            @if(count($day['events']) > 3)<small class="muted">+{{ count($day['events']) - 3 }} lagi</small>@endif
                        </td>
                    @endforeach</tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <h3 style="margin-top:32px">14 hari ke depan</h3>
        @forelse($upcoming as $s)
            <div class="row-card"><span><b>{{ $s->type_label }}</b> — <a href="{{ route('account.plants.show', $s->userPlant) }}"><u>{{ $s->userPlant->nickname }}</u></a><br><small class="muted">{{ $s->next_due_date->translatedFormat('l, j F') }}</small></span>
                <form method="POST" action="{{ route('account.schedules.done', $s) }}" data-keep>@csrf<button class="btn btn-glass btn-sm">Tandai selesai</button></form></div>
        @empty
            <p class="muted">Tidak ada jadwal dalam 14 hari ke depan. <a href="{{ route('account.plants') }}"><u>Atur jadwal tanaman Anda</u></a>.</p>
        @endforelse
    </div>
</div>
@endsection
