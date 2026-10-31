@extends('layouts.app')
@section('title', 'Notifikasi — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Notifikasi' => null]])
<div class="container account">
    @include('account._nav')
    <div data-live="notifs">
        <div class="row between"><h1 style="font-size:2rem;margin:0">Notifikasi</h1>
            @if($unreadCount)<form method="POST" action="{{ route('account.notifications.readall') }}">@csrf<button class="btn btn-glass btn-sm">Tandai semua dibaca</button></form>@endif</div>
        <div style="margin-top:20px">
            @forelse($items as $n)
                <div class="notif {{ $n->read_at ? '' : 'unread' }}" data-key="n{{ $n->id }}">
                    <span class="ico-wrap"><x-icon :name="['order' => 'receipt', 'booking' => 'tool', 'care' => 'droplet'][$n->type] ?? 'bell'" :size="20" /></span>
                    <div style="flex:1"><b>{{ $n->title }}</b>@if($n->body)<p class="muted" style="margin:2px 0 0">{{ $n->body }}</p>@endif<small class="muted">{{ $n->created_at->diffForHumans() }}</small></div>
                    @if(! $n->read_at || $n->url)<form method="POST" action="{{ route('account.notifications.read', $n) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $n->url ? 'Buka' : 'Tandai dibaca' }}</button></form>@endif
                </div>
            @empty
                @include('partials.empty', ['icon' => 'bell', 'title' => 'Belum ada notifikasi', 'text' => 'Pembaruan pesanan, pemesanan jasa, dan pengingat perawatan akan muncul di sini.'])
            @endforelse
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
