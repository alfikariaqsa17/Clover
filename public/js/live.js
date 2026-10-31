/* Pembaruan otomatis tanpa refresh (polling ringan).
 * - Admin  : badge menu, toast untuk pesanan/booking/retur/refund/pertanyaan baru, dan dashboard/daftar ikut diperbarui.
 * - Pelanggan: badge notifikasi, toast notifikasi baru, dan halaman pesanan/booking/notifikasi ikut diperbarui.
 * Hanya area bertanda [data-live] yang diganti. Area TIDAK diganti bila pengguna sedang mengisi form di dalamnya,
 * sedang fokus di dalamnya, atau ada <details> yang terbuka, sehingga ketikan tidak pernah hilang. */
(function () {
  'use strict';

  var meta = document.querySelector('meta[name="clover-live"]');
  if (!meta || !window.fetch) return;

  var url = meta.getAttribute('content');
  var kind = meta.getAttribute('data-kind') === 'admin' ? 'admin' : 'user';
  var BASE = kind === 'admin' ? 7000 : 9000;     // jeda antar pengecekan (ms)
  var MAX_TOASTS = 3;

  var delay = BASE, timer = null, busy = false, stopped = false, first = true;
  var since = null, prevSig = null, prevProofs = 0, needRefresh = false, refreshing = false;

  /* ---------- util ---------- */
  function each(list, fn) { Array.prototype.forEach.call(list, fn); }

  function safeUrl(u) {
    if (!u) return null;
    try {
      var a = new URL(u, location.href);
      // Hanya path situs ini (host dari APP_URL bisa berbeda dengan alamat yang dibuka, mis. localhost vs 127.0.0.1).
      return (a.protocol === 'http:' || a.protocol === 'https:') ? a.pathname + a.search + a.hash : null;
    } catch (e) { return null; }
  }

  /* ---------- toast ---------- */
  var BELL = '<svg class="ico" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';

  function notify(title, text, link) {
    var box = document.getElementById('toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast glass live-toast';
    t.setAttribute('role', 'status');
    var href = safeUrl(link);
    var wrap = document.createElement(href ? 'a' : 'div');
    if (href) wrap.href = href;
    wrap.className = 'live-toast-in';
    wrap.insertAdjacentHTML('beforeend', BELL);
    var body = document.createElement('span');
    var b = document.createElement('b'); b.textContent = title;
    body.appendChild(b);
    if (text) { var s = document.createElement('small'); s.textContent = text; body.appendChild(s); }
    wrap.appendChild(body);
    t.appendChild(wrap);
    box.appendChild(t);
    var live = box.querySelectorAll('.live-toast');
    if (live.length > MAX_TOASTS) live[0].remove();
    setTimeout(function () { t.classList.add('out'); }, 8000);
    setTimeout(function () { t.remove(); }, 8500);
  }

  /* ---------- badge ---------- */
  function applyAdminBadges(b) {
    if (!b) return;
    Object.keys(b).forEach(function (k) {
      var n = parseInt(b[k], 10) || 0;
      each(document.querySelectorAll('a[data-badge="' + k + '"]'), function (a) {
        var c = a.querySelector('.cnt');
        if (n > 0) {
          if (!c) { c = document.createElement('span'); c.className = 'cnt'; a.appendChild(c); }
          if (c.textContent !== String(n)) c.textContent = String(n);
        } else if (c) { c.remove(); }
      });
    });
  }

  function applyUnread(n) {
    n = parseInt(n, 10) || 0;
    each(document.querySelectorAll('[data-unread]'), function (el) {
      el.textContent = String(n);
      el.hidden = n <= 0;
    });
    each(document.querySelectorAll('[data-unread-text]'), function (el) {
      el.textContent = n > 0 ? '(' + n + ')' : '';
    });
  }

  /* ---------- perbarui area [data-live] ---------- */
  function isDirty(root) {
    if (root.querySelector('details[open]')) return true;
    if (document.activeElement && root.contains(document.activeElement) &&
        /^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement.tagName)) return true;
    var fields = root.querySelectorAll('input,select,textarea'), i, j, f;
    for (i = 0; i < fields.length; i++) {
      f = fields[i];
      if (f.type === 'hidden' || f.type === 'submit' || f.type === 'button') continue;
      if (f.tagName === 'SELECT') {
        if (f.multiple) {
          for (j = 0; j < f.options.length; j++) if (f.options[j].selected !== f.options[j].defaultSelected) return true;
        } else {
          var di = 0;
          for (j = 0; j < f.options.length; j++) if (f.options[j].defaultSelected) { di = j; break; }
          if (f.selectedIndex !== di) return true;
        }
      } else if (f.type === 'checkbox' || f.type === 'radio') {
        if (f.checked !== f.defaultChecked) return true;
      } else if (f.value !== f.defaultValue) {
        return true;
      }
    }
    return false;
  }

  function keysOf(el) {
    var s = {};
    each(el.querySelectorAll('[data-key]'), function (n) { s[n.getAttribute('data-key')] = true; });
    return s;
  }

  /** @returns {Promise<boolean>} true bila selesai (atau tidak ada area), false bila ditunda/gagal */
  function refreshRegions() {
    var regions = document.querySelectorAll('[data-live]');
    if (!regions.length) return Promise.resolve(true);
    if (refreshing) return Promise.resolve(false);
    refreshing = true;
    return fetch(location.href, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' })
      .then(function (res) {
        var ct = res.headers.get('content-type') || '';
        var same = false;
        try { same = new URL(res.url).pathname === location.pathname; } catch (e) {}
        if (!res.ok || !same || ct.indexOf('text/html') === -1) throw new Error('skip');
        return res.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var ok = true;
        each(regions, function (el) {
          var key = el.getAttribute('data-live');
          var fresh = doc.querySelector('[data-live="' + key + '"]');
          if (!fresh) return;
          if (el.innerHTML === fresh.innerHTML) return;
          if (isDirty(el)) { ok = false; return; }   // pengguna sedang mengisi: tunda
          var old = keysOf(el);
          el.innerHTML = fresh.innerHTML;
          each(el.querySelectorAll('[data-key]'), function (n) {
            if (!old[n.getAttribute('data-key')]) n.classList.add('live-new');
          });
        });
        return ok;
      })
      .catch(function () { return false; })
      .then(function (ok) { refreshing = false; return ok; });
  }

  function maybeRefresh() {
    if (!needRefresh) return;
    refreshRegions().then(function (ok) { if (ok) needRefresh = false; });
  }

  /* ---------- pemrosesan data dari server ---------- */
  function handleAdmin(d) {
    if (first) {
      first = false; since = d.max || {}; prevSig = d.sig;
      prevProofs = (d.counts && d.counts.proofs) || 0;
      applyAdminBadges(d.badges);
      return;
    }
    var n = d.new || {};
    Object.keys(n).forEach(function (k) {
      notify(n[k].title + (n[k].count > 1 ? ' (+' + n[k].count + ')' : ''), n[k].text, n[k].url);
    });
    var proofs = (d.counts && d.counts.proofs) || 0;
    if (proofs > prevProofs) notify('Bukti pembayaran diunggah', 'Periksa dan verifikasi pembayaran pelanggan.', d.proof_url);
    prevProofs = proofs;
    since = d.max || since;
    applyAdminBadges(d.badges);
    if (d.sig !== prevSig) { prevSig = d.sig; needRefresh = true; }
    maybeRefresh();
  }

  function handleUser(d) {
    applyUnread(d.unread);
    if (first) {
      first = false; since = d.max || 0; prevSig = d.sig;
      return;
    }
    (d.new || []).forEach(function (n) { notify(n.title, n.body, n.url); });
    since = Math.max(since || 0, d.max || 0);
    if (d.sig !== prevSig) { prevSig = d.sig; needRefresh = true; }
    maybeRefresh();
  }

  /* ---------- polling ---------- */
  function endpoint() {
    if (first) return url + (url.indexOf('?') === -1 ? '?' : '&') + 'init=1';
    var q;
    if (kind === 'admin') {
      q = Object.keys(since || {}).map(function (k) { return encodeURIComponent('since[' + k + ']') + '=' + encodeURIComponent(since[k]); }).join('&');
    } else {
      q = 'since=' + encodeURIComponent(since || 0);
    }
    return url + (url.indexOf('?') === -1 ? '?' : '&') + q;
  }

  function schedule() {
    clearTimeout(timer);
    if (!stopped) timer = setTimeout(poll, delay);
  }

  function poll() {
    if (stopped || busy) return;
    if (document.hidden) { schedule(); return; }      // tab tidak terlihat: tunggu, jangan membebani server
    busy = true;
    var ctrl = window.AbortController ? new AbortController() : null;
    var to = setTimeout(function () { if (ctrl) ctrl.abort(); }, 10000);
    fetch(endpoint(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store', signal: ctrl ? ctrl.signal : undefined })
      .then(function (r) {
        if (r.status === 401 || r.status === 403 || r.status === 419) { stopped = true; throw new Error('stop'); }
        if (!r.ok) throw new Error('http ' + r.status);
        // Sesi berakhir -> server mengalihkan ke halaman HTML (login): hentikan, jangan terus mencoba.
        if (r.redirected && (r.headers.get('content-type') || '').indexOf('json') === -1) { stopped = true; throw new Error('stop'); }
        return r.json();
      })
      .then(function (d) {
        delay = BASE;
        if (kind === 'admin') handleAdmin(d); else handleUser(d);
      })
      .catch(function () { if (!stopped) delay = Math.min(delay * 2, 60000); })   // gagal: perlambat, jangan spam server
      .then(function () { clearTimeout(to); busy = false; schedule(); });
  }

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && !stopped) { clearTimeout(timer); delay = BASE; poll(); }
  });
  window.addEventListener('online', function () { if (!stopped) { clearTimeout(timer); delay = BASE; poll(); } });

  poll();
})();
