{{-- Galeri foto sertifikat. Variabel: $items (koleksi Certificate). Klik = foto diperbesar (tanpa JS: membuka foto di tab baru). --}}
<div class="cert-grid">
    @foreach($items as $c)
        <a class="cert-card reveal" href="{{ $c->image_url }}" target="_blank" rel="noopener" data-cert aria-label="Perbesar foto sertifikat {{ $loop->iteration }}">
            <div class="cert-frame">
                @include('partials.photo', ['src' => $c->thumb_url, 'alt' => 'Foto sertifikat Clover ' . $loop->iteration, 'class' => 'cert-ph r-4-3'])
                <span class="cert-zoom" aria-hidden="true"><x-icon name="search" :size="18" /></span>
            </div>
        </a>
    @endforeach
</div>
