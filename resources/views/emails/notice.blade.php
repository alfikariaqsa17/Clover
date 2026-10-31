@extends('emails.layout')
@section('title', $heading)
@section('body')
<h1 style="margin:0 0 12px 0;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.3;color:#14251a;">{{ $heading }}</h1>
<p style="margin:0 0 14px 0;font-size:15px;line-height:1.6;color:#3d4d43;">Halo, {{ $recipientName }}.</p>
@foreach($lines as $line)
  <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#3d4d43;">{{ $line }}</p>
@endforeach
@if($ctaUrl)
  <p style="margin:22px 0 24px 0;"><a href="{{ $ctaUrl }}" style="display:inline-block;background:#1c4630;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:999px;">{{ $ctaLabel ?: 'Lihat detail' }}</a></p>
@endif
@endsection
