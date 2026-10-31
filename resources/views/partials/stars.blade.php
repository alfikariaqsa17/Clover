@php($full = (int) round($rating))
<span class="stars" role="img" aria-label="Rating {{ number_format($rating, 1) }} dari 5">
    @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= $full ? '' : 'off' }}"><x-icon name="star" :size="15" /></span>@endfor
    @if(isset($count))<small>({{ $count }})</small>@endif
</span>
