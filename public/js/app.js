(function () {
  'use strict';
  window.__cloverReady = true;   // penanda untuk pengaman tampilan di <head>
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var ICON = {
    x: '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    check: '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>',
    alert: '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l10 17H2L12 3Z"/><path d="M12 10v5M12 18h.01"/></svg>'
  };
  var root = document.documentElement;
  document.body.classList.add('page-in');

  /* ---------- tema gelap/terang ---------- */
  function setTheme(t) { root.setAttribute('data-theme', t); try { localStorage.setItem('clover_theme', t); } catch (e) {} }
  $$('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () { setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); });
  });

  /* ---------- navbar ---------- */
  var nav = $('#nav'), toTop = $('#to-top'), navOn = false, topOn = false, scrollTick = false;
  var onScroll = function () {   // hanya menyentuh DOM saat statusnya benar-benar berubah
    var y = window.scrollY, a = y > 12, b = y > 600;
    if (a !== navOn) { navOn = a; if (nav) nav.classList.toggle('is-scrolled', a); }
    if (b !== topOn) { topOn = b; if (toTop) toTop.classList.toggle('on', b); }
  };
  onScroll();
  window.addEventListener('scroll', function () {
    if (scrollTick) return; scrollTick = true;
    requestAnimationFrame(function () { scrollTick = false; onScroll(); });
  }, { passive: true });

  var menu = $('#menu'), burger = $('[data-menu]');
  if (burger && menu) {
    burger.addEventListener('click', function () {
      var o = menu.classList.toggle('open');
      burger.setAttribute('aria-expanded', o ? 'true' : 'false');
    });
  }
  $$('.nav-dd > button').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      var p = b.parentNode, open = !p.classList.contains('open');
      $$('.nav-dd.open').forEach(function (x) { x.classList.remove('open'); x.firstElementChild.setAttribute('aria-expanded', 'false'); });
      if (open) { p.classList.add('open'); b.setAttribute('aria-expanded', 'true'); }
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.nav-dd')) $$('.nav-dd.open').forEach(function (x) { x.classList.remove('open'); x.firstElementChild.setAttribute('aria-expanded', 'false'); });
  });

  /* ---------- announcement bar ---------- */
  var ann = $('#announce');
  if (ann) {
    var key = 'clover_ann_' + (ann.getAttribute('data-key') || '0');
    try { if (localStorage.getItem(key)) ann.remove(); } catch (e) {}
    var ab = $('[data-announce-close]');
    if (ab) ab.addEventListener('click', function () { try { localStorage.setItem(key, '1'); } catch (e) {} ann.remove(); });
  }

  /* ---------- toast ---------- */
  var toasts = $('#toasts');
  function toast(msg, type) {
    if (!toasts) return;
    var t = document.createElement('div');
    t.className = 'toast glass' + (type === 'err' ? ' err' : '');
    t.setAttribute('role', 'status');
    t.innerHTML = (type === 'err' ? ICON.alert : ICON.check) + '<span>' + esc(msg) + '</span>';
    toasts.appendChild(t);
    setTimeout(function () { t.classList.add('out'); }, 3600);
    setTimeout(function () { t.remove(); }, 4100);
  }
  $$('.toast').forEach(function (t) {
    setTimeout(function () { t.classList.add('out'); }, 3800);
    setTimeout(function () { t.remove(); }, 4300);
  });

  /* ---------- modal ---------- */
  function openModal(html, cls) {
    var m = document.createElement('div');
    m.className = 'modal' + (cls ? ' ' + cls : '');
    m.innerHTML = '<div class="modal-box glass" role="dialog" aria-modal="true">' + html + '<button type="button" class="icon-btn close" aria-label="Tutup">' + ICON.x + '</button></div>';
    var last = document.activeElement;
    var close = function () { m.classList.add('out'); setTimeout(function () { m.remove(); }, 250); document.removeEventListener('keydown', key); if (last && last.focus) last.focus(); };
    var key = function (e) { if (e.key === 'Escape') close(); };
    m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('.close') || e.target.closest('[data-close-modal]')) close(); });
    document.addEventListener('keydown', key);
    document.body.appendChild(m);
    var f = $('input,button,a', $('.modal-box', m)); if (f) f.focus();
    return { el: m, close: close };
  }

  /* ---------- konfirmasi hapus ---------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.__ok) return;
    e.preventDefault();
    var msg = f.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
    var m = openModal('<h3>Konfirmasi</h3><p class="muted">' + esc(msg) + '</p><div class="row" style="justify-content:flex-end;margin-top:20px"><button type="button" class="btn btn-ghost" data-close-modal>Batal</button><button type="button" class="btn btn-danger" data-go>Ya, lanjutkan</button></div>');
    $('[data-go]', m.el).addEventListener('click', function () { f.__ok = true; m.close(); f.submit(); });
  });

  /* ---------- keranjang drawer ---------- */
  var dr = $('#drawer'), ov = $('#overlay');
  function openCart() { if (!dr) return; dr.classList.add('open'); ov.classList.add('on'); dr.setAttribute('aria-hidden', 'false'); var c = $('.drawer-head .icon-btn', dr); if (c) c.focus(); }
  function closeCart() { if (!dr) return; dr.classList.remove('open'); ov.classList.remove('on'); dr.setAttribute('aria-hidden', 'true'); }
  $$('[data-open-cart]').forEach(function (b) { b.addEventListener('click', openCart); });
  $$('[data-close-cart]').forEach(function (b) { b.addEventListener('click', closeCart); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeCart(); });

  /* ---------- saran pencarian instan ---------- */
  function initSuggest(root, input, action) {
    var list = $('#sg-list', root), timer = null, ctrl = null, cur = -1;
    if (!input || !list) return;
    var items = function () { return $$('.sg-item', list); };
    var mark = function (n) {
      var it = items(); if (!it.length) return;
      cur = (n + it.length) % it.length;
      it.forEach(function (e, k) { e.classList.toggle('on', k === cur); });
      it[cur].scrollIntoView({ block: 'nearest' });
    };
    var hide = function () { list.hidden = true; input.setAttribute('aria-expanded', 'false'); cur = -1; };
    var render = function (d, term) {
      var h = '';
      if (d.products.length) {
        h += '<h4>Produk</h4>' + d.products.map(function (p) {
          return '<a class="sg-item" role="option" href="' + esc(p.url) + '">' + (p.image ? '<img class="sg-img" src="' + esc(p.image) + '" alt="" loading="lazy" onerror="this.remove()">' : '<span class="sg-img"></span>') +
            '<span><span class="sg-name">' + esc(p.name) + '</span><small>' + esc(p.category || '') + (p.in_stock ? '' : ' · stok habis') + '</small></span><b>' + esc(p.price) + '</b></a>';
        }).join('');
      }
      if (d.categories.length) {
        h += '<h4>Kategori</h4>' + d.categories.map(function (c) { return '<a class="sg-item" role="option" href="' + esc(c.url) + '"><span class="sg-name">' + esc(c.name) + '</span></a>'; }).join('');
      }
      if (!d.products.length && !d.categories.length) {
        h = '<div class="sg-empty">Tidak ada hasil untuk &ldquo;' + esc(term) + '&rdquo;.' + (d.did_you_mean ? ' Maksud Anda <a href="' + esc(action) + '?q=' + encodeURIComponent(d.did_you_mean) + '"><b>' + esc(d.did_you_mean) + '</b></a>?' : '') + '</div>';
      } else {
        h += '<a class="sg-item" href="' + esc(action) + '?q=' + encodeURIComponent(term) + '"><span class="sg-name">Lihat semua hasil untuk &ldquo;' + esc(term) + '&rdquo;</span></a>';
      }
      list.innerHTML = h; list.hidden = false; input.setAttribute('aria-expanded', 'true'); cur = -1;
    };
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var term = input.value.trim();
      if (term.length < 2) { hide(); return; }
      timer = setTimeout(function () {
        if (ctrl && ctrl.abort) ctrl.abort();
        ctrl = window.AbortController ? new AbortController() : null;
        fetch('/cari/saran?q=' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
          .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
          .then(function (d) { if (input.value.trim() === term) render(d, term); })
          .catch(function () { /* diam: Enter tetap membuka hasil pencarian biasa */ });
      }, 220);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); mark(cur + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); mark(cur - 1); }
      else if (e.key === 'Enter' && cur >= 0) { e.preventDefault(); items()[cur].click(); }
    });
  }

  /* ---------- pencarian ---------- */
  $$('[data-search]').forEach(function (b) {
    b.addEventListener('click', function () {
      var m = openModal('', 'search-modal');
      var box = $('.modal-box', m.el);
      var action = b.getAttribute('data-search');
      box.outerHTML = '<div class="search-wrap"><form class="search-box glass" action="' + esc(action) + '" method="GET" role="search"><input name="q" placeholder="Cari tanaman, perlengkapan..." aria-label="Cari" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="sg-list"><button class="btn btn-primary btn-sm">Cari</button></form><div class="search-suggest glass" id="sg-list" role="listbox" hidden></div></div>';
      var i = $('input', m.el); if (i) i.focus();
      initSuggest(m.el, i, action);
    });
  });

  /* ---------- pertahankan posisi scroll ---------- */
  $$('form[data-keep]').forEach(function (f) {
    f.addEventListener('submit', function () { try { sessionStorage.setItem('clover_sy', String(window.scrollY)); } catch (e) {} });
  });
  try {
    var sy = sessionStorage.getItem('clover_sy');
    if (sy !== null) { sessionStorage.removeItem('clover_sy'); window.scrollTo({ top: parseInt(sy, 10) || 0, behavior: 'instant' }); }
  } catch (e) {}

  /* ---------- reveal & parallax ---------- */
  var rv = $$('.reveal');
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { threshold: .12 });
    rv.forEach(function (e) { io.observe(e); });
  } else { rv.forEach(function (e) { e.classList.add('in'); }); }
  var par = $('[data-parallax]');
  if (par && !reduce) {
    var tick = false, parY = -1;
    window.addEventListener('scroll', function () {
      if (tick || (parY === 60 && window.scrollY > 750)) return;   // sudah mentok: tidak perlu menggambar ulang
      tick = true;
      requestAnimationFrame(function () { parY = Math.min(60, window.scrollY * 0.08); par.style.transform = 'translateY(' + parY + 'px)'; tick = false; });
    }, { passive: true });
  }
  var tt = $('#to-top'); if (tt) tt.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

  /* ---------- quick view ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-qv]');
    if (!b) return;
    var token = ($('meta[name=csrf-token]') || {}).content || '';
    var url = document.body.getAttribute('data-cart-url') || '/keranjang';
    var img = b.getAttribute('data-img');
    var m = openModal('<div class="grid g-2" style="gap:24px"><div class="ph r-4-5" style="border-radius:20px">' + (img ? '<img src="' + esc(img) + '" alt="' + esc(b.getAttribute('data-name')) + '">' : '') + '</div><div><span class="eyebrow">' + esc(b.getAttribute('data-cat')) + '</span><h3 style="margin-top:6px">' + esc(b.getAttribute('data-name')) + '</h3><p class="price">' + esc(b.getAttribute('data-price')) + '</p><p class="muted">' + esc(b.getAttribute('data-desc')) + '</p><form method="POST" action="' + esc(url) + '" data-keep><input type="hidden" name="_token" value="' + esc(token) + '"><input type="hidden" name="product_id" value="' + esc(b.getAttribute('data-id')) + '"><button class="btn btn-primary btn-block">Tambah ke Keranjang</button></form><a class="btn btn-glass btn-block" style="margin-top:10px" href="' + esc(b.getAttribute('data-url')) + '">Lihat Detail</a></div></div>');
    var f = $('form', m.el);
    if (f) f.addEventListener('submit', function () { try { sessionStorage.setItem('clover_sy', String(window.scrollY)); } catch (er) {} });
  });

  /* ---------- sertifikat: perbesar foto (tanpa JS, tautan membuka foto di tab baru) ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-cert]');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;   // biarkan "buka di tab baru"
    e.preventDefault();
    var img = $('img', a);
    openModal('<div class="cert-view"><img src="' + esc(a.getAttribute('href')) + '" alt="' + esc(img ? img.alt : 'Foto sertifikat') + '"></div>', 'cert-modal');
  });

  /* ---------- galeri detail produk ---------- */
  $$('[data-thumb]').forEach(function (b) {
    b.addEventListener('click', function () {
      var main = $('#main-photo');
      if (main) { main.src = b.getAttribute('data-thumb'); main.alt = b.getAttribute('data-alt') || ''; }
      $$('[data-thumb]').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
  });

  /* ---------- stepper jumlah ---------- */
  $$('[data-qty]').forEach(function (w) {
    var i = $('input', w), min = parseInt(i.min || '1', 10), max = parseInt(i.max || '99', 10);
    $$('[data-step]', w).forEach(function (b) {
      b.addEventListener('click', function () {
        var v = (parseInt(i.value, 10) || min) + parseInt(b.getAttribute('data-step'), 10);
        i.value = Math.max(min, Math.min(max, v));
      });
    });
    i.addEventListener('change', function () { i.value = Math.max(min, Math.min(max, parseInt(i.value, 10) || min)); });
  });

  /* ---------- filter katalog ---------- */
  var ft = $('[data-filter-toggle]'), fp = $('#filter-panel');
  if (ft && fp) ft.addEventListener('click', function () { var o = fp.classList.toggle('open'); ft.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  $$('[data-auto]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

  /* ---------- muat produk bertahap ---------- */
  var more = $('[data-loadmore]');
  if (more) {
    var gridEl = $('#product-grid');
    more.addEventListener('click', function () {
      var url = more.getAttribute('data-loadmore');
      if (!url) return;
      more.disabled = true; more.textContent = 'Memuat...';
      var sk = '';
      for (var n = 0; n < 4; n++) sk += '<div class="skeleton sk-tmp"><div class="sk-img"></div><div class="sk-line"></div><div class="sk-line s"></div></div>';
      gridEl.insertAdjacentHTML('beforeend', sk);
      fetch(url, { headers: { 'X-Partial': '1', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.text(); })
        .then(function (html) {
          $$('.sk-tmp', gridEl).forEach(function (x) { x.remove(); });
          var tmp = document.createElement('div'); tmp.innerHTML = html;
          var next = $('[data-next]', tmp);
          $$('.pcard', tmp).forEach(function (c) { c.classList.add('reveal', 'in'); gridEl.appendChild(c); });
          var nu = next ? next.getAttribute('data-next') : '';
          if (nu) { more.setAttribute('data-loadmore', nu); more.disabled = false; more.textContent = 'Muat lebih banyak'; }
          else { more.remove(); }
        })
        .catch(function () {
          $$('.sk-tmp', gridEl).forEach(function (x) { x.remove(); });
          more.disabled = false; more.textContent = 'Muat lebih banyak';
          toast('Gagal memuat produk. Periksa koneksi lalu coba lagi.', 'err');
        });
    });
  }

  /* ---------- ketersediaan jadwal jasa ---------- */
  var bk = $('[data-availability]');
  if (bk) {
    var dateI = $('[name=date]', bk), timeS = $('[name=time]', bk), info = $('#slot-info');
    var load = function () {
      if (!dateI.value) return;
      info.textContent = 'Memeriksa ketersediaan...';
      var exc = bk.getAttribute('data-except');
      fetch(bk.getAttribute('data-availability') + '?date=' + encodeURIComponent(dateI.value) + (exc ? '&except=' + encodeURIComponent(exc) : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
        .then(function (d) {
          var cur = timeS.value, html = '<option value="">Pilih jam...</option>', any = false;
          d.slots.forEach(function (s) {
            var full = s.left < 1; if (!full) any = true;
            html += '<option value="' + esc(s.time) + '"' + (full ? ' disabled' : '') + (s.time === cur && !full ? ' selected' : '') + '>' + esc(s.time) + (full ? ' - penuh' : ' - sisa ' + s.left) + '</option>';
          });
          timeS.innerHTML = html;
          info.textContent = d.blocked ? 'Tanggal ini tidak tersedia (' + d.blocked + '). Pilih tanggal lain.' : (any ? 'Pilih jam yang tersedia.' : 'Semua jam pada tanggal ini penuh. Pilih tanggal lain.');
        })
        .catch(function () { info.textContent = 'Tidak dapat memeriksa ketersediaan. Jadwal tetap diperiksa saat Anda mengirim form.'; });
    };
    dateI.addEventListener('change', load);
    if (dateI.value) load();
  }

  /* ---------- ringkasan checkout ---------- */
  var co = $('[data-checkout]');
  if (co) {
    var fmt = function (n) { return 'Rp' + Math.max(0, n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var sub = parseInt(co.getAttribute('data-subtotal'), 10) || 0, disc = parseInt(co.getAttribute('data-discount'), 10) || 0;
    var upd = function () {
      var r = $('input[name=shipping_method]:checked', co), raw = r ? r.getAttribute('data-cost') : '0';
      var known = raw !== null && raw !== '', cost = known ? parseInt(raw, 10) || 0 : 0;   // '' = ongkir belum bisa dihitung (alamat belum diisi)
      $('#sum-ship').textContent = !known ? '-' : (cost ? fmt(cost) : 'Gratis');
      $('#sum-total').textContent = known ? fmt(sub - disc + cost) : '-';
    };
    $$('input[name=shipping_method]', co).forEach(function (r) { r.addEventListener('change', upd); });

    /* ongkir berdasarkan jarak: hitung ulang saat alamat dipilih / kode pos & kota diisi */
    var shipBox = $('#ship-options', co), zoneNote = $('#zone-note', co), shipTimer = null;
    var refreshShip = function () {
      if (!shipBox) return;
      var sel = $('#address_id', co), q;
      if (sel && sel.value) q = 'address_id=' + encodeURIComponent(sel.value);
      else {
        var pc = $('[name=postal_code]', co), ct = $('[name=city]', co);
        q = 'postal_code=' + encodeURIComponent(pc ? pc.value : '') + '&city=' + encodeURIComponent(ct ? ct.value : '');
      }
      fetch(shipBox.getAttribute('data-ship-url') + '?' + q, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
        .then(function (d) {
          Object.keys(d.options).forEach(function (k) {
            var radio = $('input[name=shipping_method][value="' + k + '"]', co);
            if (radio) radio.setAttribute('data-cost', d.options[k].cost === null ? '' : d.options[k].cost);
            var price = $('[data-ship-price="' + k + '"]', co);
            if (price) price.textContent = d.options[k].text;
          });
          if (zoneNote) zoneNote.innerHTML = d.zone ? 'Zona pengiriman: <b>' + esc(d.zone) + '</b> — ongkir dihitung dari jarak ke alamat tujuan.' : 'Ongkir dihitung otomatis dari jarak ke alamat tujuan. Pilih atau isi alamat terlebih dahulu.';
          upd();
        })
        .catch(function () { /* nilai lama dipertahankan; server tetap menghitung ulang saat pesanan dibuat */ });
    };
    var refreshSoon = function () { clearTimeout(shipTimer); shipTimer = setTimeout(refreshShip, 400); };
    ['postal_code', 'city'].forEach(function (n) {
      var el = $('[name=' + n + ']', co);
      if (el) { el.addEventListener('input', refreshSoon); el.addEventListener('change', refreshSoon); }
    });
    var payNote = function () {
      $$('[data-pay-note]', co).forEach(function (n) { n.hidden = true; });
      var p = $('input[name=payment_method]:checked', co);
      if (p) { var n = $('[data-pay-note="' + p.value + '"]', co); if (n) n.hidden = false; }
    };
    $$('input[name=payment_method]', co).forEach(function (r) { r.addEventListener('change', payNote); });
    var addrSel = $('#address_id', co), newAddr = $('#new-address', co);
    if (addrSel && newAddr) {
      var tg = function () { newAddr.hidden = addrSel.value !== ''; };
      addrSel.addEventListener('change', function () { tg(); refreshShip(); }); tg();
    }
    upd(); payNote();
  }

  /* ---------- admin ---------- */
  var ab2 = $('[data-aside]'), aside = $('#aside');
  if (ab2 && aside) ab2.addEventListener('click', function () { var o = aside.classList.toggle('open'); ab2.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  $$('.flash:not(.bad)').forEach(function (f) { setTimeout(function () { f.style.transition = 'opacity .6s'; f.style.opacity = 0; }, 4500); });

  /* pratinjau berkas gambar yang dipilih */
  $$('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var tgt = $(inp.getAttribute('data-preview')); if (!tgt || !inp.files || !inp.files[0]) return;
      var rd = new FileReader(); rd.onload = function (ev) { tgt.innerHTML = '<img src="' + ev.target.result + '" alt="Pratinjau">'; }; rd.readAsDataURL(inp.files[0]);
    });
  });
})();
