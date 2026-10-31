{{ $heading }}

Halo, {{ $recipientName }}.

@foreach($lines as $line)
{{ $line }}

@endforeach
@if($ctaUrl)
{{ $ctaLabel ?: 'Lihat detail' }}: {{ $ctaUrl }}
@endif
