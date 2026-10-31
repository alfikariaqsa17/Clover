@extends('layouts.admin')
@section('title', 'Pengguna')

@section('content')
<div class="ah"><div><h1>Pengguna & persetujuan akun</h1><p>Pendaftar baru masuk setelah memverifikasi email dengan kode OTP. Anda dapat menyetujui manual bila email pendaftar bermasalah.</p></div></div>

<div class="row" style="margin-bottom:16px">
    <a class="chip {{ $status === '' ? 'on' : '' }}" href="{{ route('admin.users.index') }}">Semua</a>
    @foreach($statuses as $k => $l)
        <a class="chip {{ $status === $k ? 'on' : '' }}" href="{{ route('admin.users.index', ['status' => $k]) }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
</div>
<form method="GET" class="filters"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="q" value="{{ $q }}" placeholder="Cari nama / email..." aria-label="Cari pengguna"><button class="btn btn-glass btn-sm">Cari</button></form>

<div class="tbw"><table class="tb">
    <thead><tr><th>Nama</th><th>Kontak</th><th>Role</th><th>Status akun</th><th>Daftar</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach($users as $u)
        <tr>
            <td><b>{{ $u->name }}</b></td>
            <td>{{ $u->email }}@if($u->phone)<br><small><a href="https://wa.me/{{ preg_replace('/\D/', '', preg_replace('/^0/', '62', $u->phone)) }}" target="_blank" rel="noopener"><u>{{ $u->phone }}</u></a></small>@endif</td>
            <td>{{ $u->role === 'admin' ? 'Administrator' : 'Pelanggan' }}</td>
            <td>@include('partials.status', ['s' => ['pending' => 'Menunggu Pembayaran', 'approved' => 'Selesai', 'rejected' => 'Dibatalkan'][$u->account_status] ?? 'Selesai', 'label' => $statuses[$u->account_status] ?? $u->account_status])@if($u->account_status === 'rejected' && $u->status_note)<br><small class="muted">{{ $u->status_note }}</small>@endif</td>
            <td>{{ $u->created_at?->format('d-m-Y H:i') }}</td>
            <td class="acts">
                @if($u->account_status !== 'approved')
                    <form method="POST" action="{{ route('admin.users.approve', $u) }}" data-confirm="Setujui manual akun {{ $u->name }}? Verifikasi email akan dilewati.">@csrf<button class="btn btn-primary btn-sm"><x-icon name="check" :size="16" /> Setujui manual</button></form>
                @endif
                @if($u->account_status === 'pending' && (int) $u->id !== (int) auth()->id())
                    <details style="position:relative"><summary class="btn btn-danger btn-sm" style="cursor:pointer;list-style:none">Tolak</summary>
                        <form method="POST" action="{{ route('admin.users.reject', $u) }}" class="panel glass form" style="position:absolute;right:0;z-index:5;width:260px;padding:14px;margin-top:6px">
                            @csrf
                            <div class="field"><label for="note-{{ $u->id }}">Alasan (opsional)</label><input id="note-{{ $u->id }}" name="note" maxlength="200" placeholder="Contoh: data tidak lengkap"></div>
                            <button class="btn btn-danger btn-sm">Tolak pendaftaran</button>
                        </form>
                    </details>
                @endif
                <a class="btn btn-glass btn-sm" href="{{ route('admin.users.edit', $u) }}">Edit</a>
                @if((int) $u->id !== (int) auth()->id())<form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Hapus pengguna {{ $u->name }}? Data akunnya ikut terhapus.">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form>@endif
            </td>
        </tr>
    @endforeach
    @if(! $users->count())<tr><td colspan="6">Tidak ada pengguna pada filter ini.</td></tr>@endif
    </tbody>
</table></div>
{{ $users->links() }}
@endsection
