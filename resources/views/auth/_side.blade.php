<div class="auth-side">
    <!-- GANTI FOTO: Dashboard Admin > Pengaturan Situs > Foto halaman Masuk & Daftar -->
    @include('partials.photo', ['src' => \App\Models\Setting::img('auth_bg'), 'alt' => 'Tanaman hijau Clover', 'eager' => true])
    <div class="in"><x-icon name="sprout" :size="34" /><h2>{{ $heading }}</h2><p style="opacity:.9;margin:0">{{ $text }}</p></div>
</div>
