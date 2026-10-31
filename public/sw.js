/* Service worker Clover.
 * - Aset statis (CSS/JS/gambar/font): stale-while-revalidate.
 * - Halaman HTML TIDAK di-cache (agar keranjang, akun, dan harga selalu terbaru); bila offline tampil /offline.html.
 * - Permintaan non-GET, /admin, /akun, /checkout, dan JSON dibiarkan lewat jaringan.
 * Naikkan VERSION setiap kali isi PRECACHE berubah. */
var VERSION = 'clover-v1';
var PRECACHE = ['/offline.html', '/icons/icon-192.png'];

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(VERSION).then(function (c) { return c.addAll(PRECACHE); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k !== VERSION; }).map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

var PRIVATE = /^\/(admin|akun|checkout|keranjang|masuk|daftar|keluar|lupa-password|reset-password|verifikasi)/;

self.addEventListener('fetch', function (e) {
  var req = e.request, url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== self.location.origin || PRIVATE.test(url.pathname)) return;

  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(function () { return caches.match('/offline.html'); }));
    return;
  }

  if (/\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)$/i.test(url.pathname)) {
    e.respondWith(caches.open(VERSION).then(function (cache) {
      return cache.match(req).then(function (hit) {
        var net = fetch(req).then(function (res) {
          if (res && res.status === 200) cache.put(req, res.clone());
          return res;
        }).catch(function () { return hit; });
        return hit || net;
      });
    }));
  }
});
