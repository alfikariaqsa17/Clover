<a class="acard reveal" href="{{ route('tutorial.show', $a) }}">
    @include('partials.photo', ['src' => $a->image_url, 'alt' => $a->title, 'class' => 'r-16-10'])
    <div class="acard-body">
        <span class="eyebrow">{{ $a->category?->name ?? 'Tips' }}</span>
        <h3>{{ $a->title }}</h3>
        <p>{{ $a->excerpt }}</p>
        <div class="meta"><span>{{ $a->published_at->translatedFormat('j M Y') }}</span><span>{{ $a->read_minutes }} menit baca</span></div>
    </div>
</a>
