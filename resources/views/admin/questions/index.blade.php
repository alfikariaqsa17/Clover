@extends('layouts.admin')
@section('title', 'Tanya Jawab Produk')

@section('content')
<div class="ah"><div><h1>Tanya jawab produk</h1><p>Pertanyaan pelanggan di halaman produk. Jawaban tampil publik dan pelanggan diberi tahu lewat notifikasi dan email.</p></div></div>
<form method="GET" class="filters">
    <select name="status" aria-label="Filter" data-auto>
        <option value="belum" @selected($filter === 'belum')>Belum dijawab</option>
        <option value="sudah" @selected($filter === 'sudah')>Sudah dijawab</option>
        <option value="semua" @selected($filter === 'semua')>Semua</option>
    </select>
</form>
@forelse($questions as $q)
    <section class="box" style="margin-bottom:14px">
        <div class="row between">
            <div><b>{{ $q->product->name }}</b> <small class="muted">· {{ $q->user?->name ?? 'Pengunjung' }} · {{ $q->created_at->format('d-m-Y H:i') }}</small></div>
            <span class="status {{ $q->is_visible ? 's-selesai' : 's-dibatalkan' }}">{{ $q->is_visible ? 'Tampil' : 'Disembunyikan' }}</span>
        </div>
        <p style="margin:10px 0"><b>T:</b> {{ $q->question }}</p>
        <form method="POST" action="{{ route('admin.questions.answer', $q) }}" class="form" novalidate>
            @csrf
            <div class="field"><label for="a{{ $q->id }}">Jawaban</label><textarea id="a{{ $q->id }}" name="answer" required>{{ old('answer', $q->answer) }}</textarea>@error('answer')<small class="error" role="alert">{{ $message }}</small>@enderror</div>
            <div class="row">
                <button class="btn btn-primary btn-sm">{{ $q->answer ? 'Perbarui jawaban' : 'Kirim jawaban' }}</button>
            </div>
        </form>
        <div class="row" style="margin-top:10px">
            <form method="POST" action="{{ route('admin.questions.toggle', $q) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $q->is_visible ? 'Sembunyikan' : 'Tampilkan' }}</button></form>
            <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" data-confirm="Hapus pertanyaan ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form>
        </div>
    </section>
@empty
    <section class="box"><p class="muted" style="margin:0">Tidak ada pertanyaan.</p></section>
@endforelse
{{ $questions->links() }}
@endsection
