@foreach($products as $p)
    @include('partials.product-card', ['p' => $p])
@endforeach
<div data-next="{{ $products->nextPageUrl() }}" hidden></div>
