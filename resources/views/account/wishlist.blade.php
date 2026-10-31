@extends('layouts.app')
@section('title', 'Wishlist — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Akun Saya' => route('account'), 'Wishlist' => null]])
<div class="container account">
    @include('account._nav')
    <div>
        <h1 style="font-size:2rem">Wishlist</h1>
        @if($products->count())
            <div class="product-grid" style="grid-template-columns:repeat(3,1fr)">@foreach($products as $p) @include('partials.product-card', ['p' => $p]) @endforeach</div>
        @else
            @include('partials.empty', ['icon' => 'heart', 'title' => 'Wishlist masih kosong', 'text' => 'Simpan tanaman favorit Anda agar mudah ditemukan lagi.', 'url' => route('products'), 'label' => 'Jelajahi Tanaman'])
        @endif
    </div>
</div>
@endsection
