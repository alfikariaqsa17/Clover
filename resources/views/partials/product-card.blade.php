@php
    $out = $p->stock < 1;
    $low = ! $out && $p->stock <= 5;
    $wished = \App\Support\Wish::has($p->id);
@endphp
<article class="pcard reveal">
    <div class="pcard-top">
        <span class="tag {{ $out ? 'out' : ($low ? 'low' : 'ok') }}">{{ $out ? 'Habis' : ($low ? 'Stok terbatas' : 'Tersedia') }}</span>
        <form method="POST" action="{{ route('wishlist.toggle', $p) }}" class="fav-form" data-keep>
            @csrf
            <button class="fav {{ $wished ? 'on' : '' }}" aria-label="{{ $wished ? 'Hapus' : 'Tambah' }} {{ $p->name }} {{ $wished ? 'dari' : 'ke' }} wishlist" aria-pressed="{{ $wished ? 'true' : 'false' }}"><x-icon name="heart" :size="18" /></button>
        </form>
    </div>
    @include('partials.photo', ['src' => $p->image_url, 'alt' => $p->name, 'class' => ''])
    <div class="pcard-body">
        <span class="cat">{{ $p->category?->name ?? 'Tanaman' }}</span>
        <h3><a href="{{ route('products.show', $p) }}">{{ $p->name }}</a></h3>
        @if($p->has_rating)@include('partials.stars', ['rating' => $p->rating_avg, 'count' => $p->reviews_count])@endif
        <div class="pcard-foot">
            <span class="price">{{ $p->price_format }}</span>
            <div class="pcard-actions">
                <button type="button" class="icon-btn light" data-qv data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-cat="{{ $p->category?->name }}" data-price="{{ $p->price_format }}" data-desc="{{ \Illuminate\Support\Str::limit($p->description, 140) }}" data-img="{{ $p->image_url }}" data-url="{{ route('products.show', $p) }}" aria-label="Lihat cepat {{ $p->name }}"><x-icon name="eye" :size="18" /></button>
                <form method="POST" action="{{ route('cart.add') }}" class="inl" data-keep>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <button class="icon-btn" aria-label="Tambah {{ $p->name }} ke keranjang" @disabled($out)><x-icon name="cart" :size="18" /></button>
                </form>
            </div>
        </div>
    </div>
</article>
