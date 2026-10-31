@extends('layouts.admin')
@section('title', 'Pesan Masuk')

@section('content')
<div class="ah"><h1>Pesan masuk</h1></div>
<div class="tbw"><table class="tb">
    <thead><tr><th></th><th>Pengirim</th><th>Subjek</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($messages as $m)
        <tr class="{{ $m->is_read ? '' : 'unread' }}">
            <td>@if(! $m->is_read)<span class="status">Baru</span>@endif</td>
            <td>{{ $m->name }}<br><small class="muted">{{ $m->email }}</small></td><td>{{ $m->subject }}</td><td>{{ $m->created_at->format('d-m-Y H:i') }}</td>
            <td class="acts"><a class="btn btn-glass btn-sm" href="{{ route('admin.messages.show', $m) }}">Buka</a>
                <form method="POST" action="{{ route('admin.messages.destroy', $m) }}" data-confirm="Hapus pesan ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form></td>
        </tr>
    @empty
        <tr><td colspan="5">Belum ada pesan.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $messages->links() }}
@endsection
