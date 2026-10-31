@extends('layouts.admin')
@section('title', 'Subscriber')

@section('content')
<div class="ah"><h1>Subscriber newsletter</h1><a class="btn btn-glass" href="{{ route('admin.subscribers.export') }}"><x-icon name="download" :size="18" /> Ekspor CSV</a></div>
<div class="tbw"><table class="tb">
    <thead><tr><th>Email</th><th>Terdaftar</th><th>Persetujuan</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($subscribers as $s)
        <tr><td>{{ $s->email }}</td><td>{{ $s->created_at->format('d-m-Y H:i') }}</td><td>{{ $s->consent_at?->format('d-m-Y') ?? '-' }}</td>
            <td><form method="POST" action="{{ route('admin.subscribers.destroy', $s) }}" data-confirm="Hapus {{ $s->email }} dari daftar?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form></td></tr>
    @empty
        <tr><td colspan="4">Belum ada subscriber.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $subscribers->links() }}
@endsection
