@extends('layouts.admin')
@section('title', 'Pesan')

@section('content')
<div class="ah"><h1>{{ $message->subject }}</h1><a class="btn btn-glass" href="{{ route('admin.messages.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a></div>
<section class="box">
    <dl class="kv">
        <dt>Dari</dt><dd>{{ $message->name }}</dd>
        <dt>Email</dt><dd><a href="mailto:{{ $message->email }}"><u>{{ $message->email }}</u></a></dd>
        <dt>WhatsApp</dt><dd><a href="https://wa.me/{{ preg_replace('/\D/', '', $message->whatsapp) }}" target="_blank" rel="noopener"><u>{{ $message->whatsapp }}</u></a></dd>
        <dt>Tanggal</dt><dd>{{ $message->created_at->format('d-m-Y H:i') }}</dd>
    </dl>
    <p style="white-space:pre-line;margin-top:18px">{{ $message->body }}</p>
</section>
@endsection
