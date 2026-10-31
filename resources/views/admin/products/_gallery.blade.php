<div class="box" style="margin-top:22px">
    <h2>Galeri foto produk</h2>
    @if($model->images->count())
        <div class="gal">
            @foreach($model->images as $img)
                <figure>
                    @include('partials.photo', ['src' => $img->url, 'alt' => $img->alt])
                    <figcaption>
                        @if($img->is_primary)<span class="status">Utama</span>@else
                            <form method="POST" action="{{ route('admin.products.images.primary', $img) }}">@csrf @method('PATCH')<button class="btn btn-glass btn-sm">Jadikan utama</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.products.images.destroy', $img) }}" data-confirm="Hapus foto ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus foto"><x-icon name="trash" :size="16" /></button></form>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    @else
        <p class="muted">Belum ada foto. Unggah pada formulir di atas.</p>
    @endif
</div>
