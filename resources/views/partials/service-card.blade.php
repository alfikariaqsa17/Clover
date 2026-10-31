<article class="svc-card reveal">
    <a href="{{ route('services.show', $s) }}" aria-label="Lihat {{ $s->name }}">@include('partials.photo', ['src' => $s->image_url, 'alt' => $s->name, 'class' => 'r-16-10'])</a>
    <div class="svc-body">
        <span class="ico-wrap"><x-icon :name="$s->icon" :size="22" /></span>
        <h3><a href="{{ route('services.show', $s) }}">{{ $s->name }}</a></h3>
        <p>{{ $s->description }}</p>
        <div class="svc-foot">
            <div><small>Mulai dari</small><span class="price">{{ $s->price_format }}</span></div>
            <div class="row" style="gap:8px">
                <a class="btn btn-glass btn-sm" href="{{ route('services.show', $s) }}">Detail</a>
                <a class="btn btn-primary btn-sm" href="{{ route('services.show', $s) }}#pesan">Pesan</a>
            </div>
        </div>
    </div>
</article>
