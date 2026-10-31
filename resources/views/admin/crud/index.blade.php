@extends('layouts.admin')
@section('title', $cfg['heading'])

@section('content')
<div class="ah">
    <h1>{{ $cfg['heading'] }}</h1>
    <a class="btn btn-primary" href="{{ route($cfg['route'] . '.create') }}"><x-icon name="plus" :size="18" /> Tambah</a>
</div>
@if(! empty($cfg['search']))
    <form method="GET" class="filters"><input type="search" name="q" value="{{ $q }}" placeholder="Cari..." aria-label="Cari"><button class="btn btn-glass btn-sm">Cari</button>@if($q)<a class="btn btn-ghost btn-sm" href="{{ route($cfg['route'] . '.index') }}">Reset</a>@endif</form>
@endif
<div class="tbw"><table class="tb">
    <thead><tr>@foreach($cfg['columns'] as $c)<th>{{ $c[0] }}</th>@endforeach<th>Aksi</th></tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            @foreach($cfg['columns'] as [$label, $key, $type])
                @php($v = data_get($row, $key))
                <td>
                    @if($type === 'image')<div class="thumb">@include('partials.photo', ['src' => $v, 'alt' => ''])</div>
                    @elseif($type === 'bool')@include('partials.status', ['s' => $v ? 'Selesai' : 'Dibatalkan', 'label' => $v ? 'Ya' : 'Tidak'])
                    @elseif($type === 'money')Rp{{ number_format((int) $v, 0, ',', '.') }}
                    @elseif($type === 'date'){{ $v ? \Illuminate\Support\Carbon::parse($v)->format('d-m-Y') : '-' }}
                    @else{{ \Illuminate\Support\Str::limit((string) $v, 60) }}@endif
                </td>
            @endforeach
            <td class="acts">
                <a class="btn btn-glass btn-sm" href="{{ route($cfg['route'] . '.edit', $row) }}"><x-icon name="edit" :size="16" /> Edit</a>
                <form method="POST" action="{{ route($cfg['route'] . '.destroy', $row) }}" data-confirm="Hapus data ini? Tindakan tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash" :size="16" /></button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="{{ count($cfg['columns']) + 1 }}">Belum ada data.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $rows->links() }}
@endsection
