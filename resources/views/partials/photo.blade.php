<div class="ph {{ $class ?? '' }}" @if(empty($src)) role="img" aria-label="{{ $alt ?? '' }}" @endif>
    @if(!empty($src))<img src="{{ $src }}" alt="{{ $alt ?? '' }}" @if(empty($eager)) loading="lazy" decoding="async" @endif onerror="this.remove()">@endif
</div>
