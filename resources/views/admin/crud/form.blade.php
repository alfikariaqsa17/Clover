@extends('layouts.admin')
@section('title', ($model->exists ? 'Edit ' : 'Tambah ') . $cfg['title'])

@section('content')
@php($hasFile = ($cfg['multipart'] ?? false) || collect($cfg['fields'])->contains(fn ($f) => ($f['type'] ?? '') === 'file'))
<div class="ah">
    <h1>{{ $model->exists ? 'Edit' : 'Tambah' }} {{ $cfg['title'] }}</h1>
    <a class="btn btn-glass" href="{{ route($cfg['route'] . '.index') }}"><x-icon name="arrow-left" :size="18" /> Kembali</a>
</div>
<form method="POST" action="{{ $model->exists ? route($cfg['route'] . '.update', $model) : route($cfg['route'] . '.store') }}" @if($hasFile) enctype="multipart/form-data" @endif class="panel form" novalidate>
    @csrf
    @if($model->exists) @method('PUT') @endif
    @foreach($cfg['fields'] as $f)
        @include('partials.field', $f + ['prefix' => 'a', 'value' => $values[$f['name']] ?? null])
    @endforeach
    @if(! empty($cfg['extra_in_form']))@include($cfg['extra_in_form'], ['model' => $model])@endif
    <div class="row"><button class="btn btn-primary btn-lg">{{ $model->exists ? 'Simpan Perubahan' : 'Simpan' }}</button><a class="btn btn-ghost btn-lg" href="{{ route($cfg['route'] . '.index') }}">Batal</a></div>
</form>
@if($model->exists && ! empty($cfg['extra_after']))@include($cfg['extra_after'], ['model' => $model])@endif
@if($model->exists && ! empty($cfg['image']) && $model->{$cfg['image']['field']})
    <p class="muted" style="margin-top:12px">Foto saat ini tersimpan. Unggah file baru untuk menggantinya.</p>
@endif
@endsection
