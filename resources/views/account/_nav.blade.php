@php($items = [
    ['account', 'home', 'Ringkasan', 'account'],
    ['account.orders', 'receipt', 'Pesanan', 'account.orders*'],
    ['account.returns', 'package', 'Retur & Refund', 'account.returns*'],
    ['account.bookings', 'tool', 'Pemesanan Jasa', 'account.bookings'],
    ['account.wishlist', 'heart', 'Wishlist', 'account.wishlist'],
    ['account.plants', 'sprout', 'Koleksi Tanaman', 'account.plants*'],
    ['account.calendar', 'calendar', 'Kalender Perawatan', 'account.calendar'],
    ['account.addresses', 'map-pin', 'Alamat', 'account.addresses*'],
    ['account.notifications', 'bell', 'Notifikasi', 'account.notifications*'],
    ['account.profile', 'user', 'Profil & Keamanan', 'account.profile'],
])
<nav class="sidenav glass" aria-label="Menu akun">
    @foreach($items as [$r, $ic, $label, $pat])
        <a href="{{ route($r) }}" class="{{ request()->routeIs($pat) ? 'on' : '' }}"><x-icon :name="$ic" :size="18" /> {{ $label }}@if($r === 'account.notifications')<i class="badge-dot" data-unread style="position:static;margin-left:auto" @if(! ($unreadCount ?? 0)) hidden @endif>{{ $unreadCount ?? 0 }}</i>@endif</a>
    @endforeach
</nav>
