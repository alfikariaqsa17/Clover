@extends('layouts.admin')
@section('title', 'Produk Tanaman')

@section('content')
<div class="ah"><h1>Produk Tanaman</h1><a class="btn pri sm shine" href="{{ route('admin.products.create') }}">+ Tambah Produk</a></div>
<form method="GET" class="afilter"><input name="q" value="{{ $q }}" placeholder="Cari nama produk..." aria-label="Cari produk"><button class="btn glass sm">Cari</button></form>
<div class="tbw"><table class="tb">
    <thead><tr><th>Foto</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Rating</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($products as $p)
        <tr>
            <td><div class="ph thumb"><img src="{{ $p->image_url }}" alt="{{ $p->name }}" loading="lazy" onerror="this.style.display='none'"></div></td>
            <td><b>{{ $p->name }}</b></td>
            <td>{{ $p->category }}</td>
            <td>{{ $p->price_format }}</td>
            <td>{{ $p->stock }}</td>
            <td>{{ number_format($p->rating, 1) }} ({{ $p->reviews }})</td>
            <td class="acts">
                <a class="btn glass sm" href="{{ route('admin.products.edit', $p) }}">Edit</a>
                <form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')<button class="btn sm danger">Hapus</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7">Belum ada produk.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $products->links() }}
@endsection
