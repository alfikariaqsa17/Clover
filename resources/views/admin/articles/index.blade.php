@extends('layouts.admin')
@section('title', 'Tutorial & Tips')

@section('content')
<div class="ah"><h1>Tutorial & Tips</h1><a class="btn pri sm shine" href="{{ route('admin.articles.create') }}">+ Tambah Artikel</a></div>
<div class="tbw"><table class="tb">
    <thead><tr><th>Foto</th><th>Judul</th><th>Label</th><th>Terbit</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($articles as $a)
        <tr>
            <td><div class="ph thumb"><img src="{{ $a->image_url }}" alt="{{ $a->title }}" loading="lazy" onerror="this.style.display='none'"></div></td>
            <td><b>{{ $a->title }}</b></td>
            <td>{{ $a->tag }}</td>
            <td>{{ $a->published_at->format('d-m-Y') }}</td>
            <td class="acts">
                <a class="btn glass sm" href="{{ route('admin.articles.edit', $a) }}">Edit</a>
                <form method="POST" action="{{ route('admin.articles.destroy', $a) }}" onsubmit="return confirm('Hapus artikel ini?')">@csrf @method('DELETE')<button class="btn sm danger">Hapus</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Belum ada artikel.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $articles->links() }}
@endsection
