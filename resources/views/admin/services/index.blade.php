@extends('layouts.admin')
@section('title', 'Layanan Jasa')

@section('content')
<div class="ah"><h1>Layanan Jasa</h1><a class="btn pri sm shine" href="{{ route('admin.services.create') }}">+ Tambah Jasa</a></div>
<div class="tbw"><table class="tb">
    <thead><tr><th>Foto</th><th>Nama</th><th>Harga Mulai</th><th>Estimasi</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($services as $s)
        <tr>
            <td><div class="ph thumb"><img src="{{ $s->image_url }}" alt="{{ $s->name }}" loading="lazy" onerror="this.style.display='none'"></div></td>
            <td><b>{{ $s->icon }} {{ $s->name }}</b></td>
            <td>{{ $s->price_format }}</td>
            <td>{{ $s->estimate }}</td>
            <td class="acts">
                <a class="btn glass sm" href="{{ route('admin.services.edit', $s) }}">Edit</a>
                <form method="POST" action="{{ route('admin.services.destroy', $s) }}" onsubmit="return confirm('Hapus layanan ini?')">@csrf @method('DELETE')<button class="btn sm danger">Hapus</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Belum ada layanan.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $services->links() }}
@endsection
