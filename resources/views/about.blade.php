@extends('layouts.app')
@section('title', 'Tentang Kami — Clover')

@section('content')
@include('partials.breadcrumb', ['items' => ['Tentang Kami' => null]])
<div class="container page-head"><span class="eyebrow">Tentang Clover</span><h1>Membantu Anda memiliki tanaman dan taman yang sehat, indah, dan terawat</h1></div>
<div class="container" style="padding-bottom:96px">
    <div class="grid g-2" style="gap:48px;align-items:center;margin:24px 0 72px">
        <div style="border-radius:var(--r-lg);overflow:hidden">
            <!-- GANTI FOTO: Dashboard Admin > Pengaturan Situs > Foto halaman Tentang Kami -->
            @include('partials.photo', ['src' => \App\Models\Setting::img('about_bg'), 'alt' => 'Tim Clover merawat tanaman', 'class' => 'r-4-3', 'eager' => true])
        </div>
        <div><h2>Cerita Clover</h2><p class="muted">Berawal dari kecintaan pada tanaman, Clover berkembang menjadi toko tanaman sekaligus penyedia jasa perawatan. Kami memilih tanaman yang sehat, menyediakan perlengkapan yang tepat, dan mendampingi pelanggan agar ruang hijaunya tetap terawat.</p><p class="muted">Kami percaya tanaman terbaik adalah tanaman yang cocok dengan ruang dan kebiasaan pemiliknya, karena itu kami membuat panduan perawatan dan Plant Finder.</p></div>
    </div>
    <div class="grid g-2">
        <div class="panel"><h3>Visi</h3><p class="muted" style="margin:0">Menjadi mitra tepercaya bagi setiap orang yang ingin hidup lebih dekat dengan alam.</p></div>
        <div class="panel"><h3>Misi</h3><ul class="list-check"><li><x-icon name="check" :size="18" /> Menyediakan tanaman sehat dan berkualitas</li><li><x-icon name="check" :size="18" /> Memberi layanan perawatan taman profesional</li><li><x-icon name="check" :size="18" /> Mengedukasi pelanggan merawat tanaman</li></ul></div>
    </div>
    <div class="section-head" style="margin-top:72px"><div><h2>Nilai kami</h2></div></div>
    <div class="trust">@foreach([['leaf', 'Kualitas', 'Tanaman dan layanan dengan standar yang konsisten.'], ['shield', 'Kepercayaan', 'Informasi jujur dan transparan.'], ['sprout', 'Keberlanjutan', 'Mengutamakan ruang hijau yang sehat.'], ['users', 'Kepedulian', 'Mendampingi pelanggan dari pemilihan hingga perawatan.']] as $v)<div class="trust-item glass"><span class="ico-wrap"><x-icon :name="$v[0]" :size="24" /></span><div><h3>{{ $v[1] }}</h3><p>{{ $v[2] }}</p></div></div>@endforeach</div>
</div>
@endsection
