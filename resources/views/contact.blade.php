@extends('layouts.app')
@section('title', 'Kontak — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Kontak' => null]])
<div class="container page-head"><h1>Hubungi kami</h1><p>Kami siap membantu kebutuhan tanaman dan taman Anda.</p></div>
<div class="container" style="padding-bottom:96px">
    <div class="grid" style="grid-template-columns:1fr 1.3fr;gap:32px;align-items:start">
        <div class="panel glass stack">
            <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><x-icon name="phone" /><div><b>WhatsApp</b><br><a href="https://wa.me/{{ \App\Models\Setting::wa() }}">{{ \App\Models\Setting::get('whatsapp') }}</a></div></div>
            <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><x-icon name="mail" /><div><b>Email</b><br><a href="mailto:{{ \App\Models\Setting::get('email') }}">{{ \App\Models\Setting::get('email') }}</a></div></div>
            <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><x-icon name="map-pin" /><div><b>Alamat</b><br>{{ \App\Models\Setting::get('address') }}</div></div>
            <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><x-icon name="clock" /><div><b>Jam operasional</b><br>{{ \App\Models\Setting::get('hours') }}</div></div>
        </div>
        <div class="panel">
            <form method="POST" action="{{ route('contact.store') }}" class="form" novalidate>
                @csrf
                @include('partials.field', ['prefix' => 'k', 'name' => 'name', 'label' => 'Nama', 'value' => auth()->user()->name ?? ''])
                <div class="two">
                    @include('partials.field', ['prefix' => 'k', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => auth()->user()->email ?? ''])
                    @include('partials.field', ['prefix' => 'k', 'name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'value' => auth()->user()->phone ?? ''])
                </div>
                @include('partials.field', ['prefix' => 'k', 'name' => 'subject', 'label' => 'Subjek'])
                @include('partials.field', ['prefix' => 'k', 'name' => 'body', 'label' => 'Pesan', 'type' => 'textarea'])
                <button class="btn btn-primary btn-lg">Kirim Pesan</button>
            </form>
        </div>
    </div>
</div>
@endsection
