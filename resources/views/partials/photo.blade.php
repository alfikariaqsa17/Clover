<div class="ph {{ $class ?? '' }}" @if(empty($src)) role="img" aria-label="{{ $alt ?? '' }}" @endif>
    @if(!empty($src))
        {{-- Bila ada salinan WebP (dibuat otomatis saat unggah), browser yang mendukung memakai WebP; lainnya tetap memakai gambar asli. --}}
        @php($webp = \App\Support\Upload::webpUrl($src))
        @if($webp)<picture><source srcset="{{ $webp }}" type="image/webp">@endif
        <img src="{{ $src }}" alt="{{ $alt ?? '' }}" @if(empty($eager)) loading="lazy" decoding="async" @else fetchpriority="high" @endif onerror="this.remove()">
        @if($webp)</picture>@endif
    @endif
</div>
