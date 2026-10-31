@props(['name' => 'leaf', 'size' => 20])
<svg {{ $attributes->merge(['class' => 'ico']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! \App\Support\Icons::get($name) !!}</svg>
