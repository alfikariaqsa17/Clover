<nav class="breadcrumb container" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Beranda</a>
    @foreach($items as $label => $url)
        <x-icon name="chevron-right" :size="14" />
        @if($url)<a href="{{ $url }}">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif
    @endforeach
</nav>
