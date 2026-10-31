<div class="empty">
    <div class="ico-wrap"><x-icon :name="$icon ?? 'leaf'" :size="28" /></div>
    <h3>{{ $title }}</h3>
    @if(!empty($text))<p>{{ $text }}</p>@endif
    @if(!empty($url))<a class="btn btn-primary" href="{{ $url }}">{{ $label ?? 'Mulai' }}</a>@endif
</div>
