(function () {
  'use strict';
  var form = document.getElementById('otp-form');
  if (!form) return;

  var input = document.getElementById('otp-code');
  var submit = document.getElementById('otp-submit');
  var timer = document.getElementById('otp-timer');
  var resendForm = document.getElementById('otp-resend');
  var resendBtn = document.getElementById('otp-resend-btn');
  var alertBox = document.getElementById('otp-alert');
  var alertText = document.getElementById('otp-alert-text');
  var digits = parseInt(form.getAttribute('data-digits'), 10) || 8;
  var remaining = parseInt(timer.getAttribute('data-remaining'), 10) || 0;
  var emptyText = timer.getAttribute('data-empty-text') || 'Kode sudah kedaluwarsa. Minta kode baru.';
  var cooldown = parseInt(resendForm.getAttribute('data-cooldown'), 10) || 0;
  var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function showAlert(msg) { alertText.textContent = msg; alertBox.hidden = false; }
  function hideAlert() { alertBox.hidden = true; }

  function renderTimer() {
    if (remaining > 0) {
      timer.textContent = 'Kode berlaku ' + pad(Math.floor(remaining / 60)) + ':' + pad(remaining % 60);
      if (!submit.getAttribute('data-busy')) submit.disabled = false;
    } else {
      timer.textContent = emptyText;
      submit.disabled = true;
    }
  }
  function renderCooldown() {
    if (cooldown > 0) { resendBtn.disabled = true; resendBtn.textContent = 'Kirim ulang kode (' + cooldown + ' dtk)'; }
    else if (!resendBtn.getAttribute('data-busy')) { resendBtn.disabled = false; resendBtn.textContent = 'Kirim ulang kode'; }
  }
  renderTimer(); renderCooldown();
  setInterval(function () {
    if (remaining > 0) { remaining--; if (remaining === 0) emptyText = 'Kode sudah kedaluwarsa. Minta kode baru.'; }
    if (cooldown > 0) cooldown--;
    renderTimer(); renderCooldown();
  }, 1000);

  // Hanya angka
  input.addEventListener('input', function () {
    input.value = input.value.replace(/\D/g, '').slice(0, digits);
    hideAlert();
  });

  // Validasi sisi klien + status loading. Validasi sebenarnya tetap di server.
  form.addEventListener('submit', function (e) {
    if (input.value.length !== digits) {
      e.preventDefault();
      showAlert('Kode harus ' + digits + ' digit angka.');
      input.focus();
      return;
    }
    submit.setAttribute('data-busy', '1');
    submit.disabled = true;
    submit.textContent = 'Memverifikasi...';
  });

  // Kirim ulang tanpa memuat ulang halaman (dengan penanganan jaringan terputus)
  resendForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (cooldown > 0) return;
    hideAlert();
    resendBtn.setAttribute('data-busy', '1');
    resendBtn.disabled = true;
    resendBtn.textContent = 'Mengirim...';

    fetch(resendForm.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
      .then(function (res) {
        resendBtn.removeAttribute('data-busy');
        if (res.data && res.data.redirect) { window.location.href = res.data.redirect; return; }
        if (res.data && res.data.ok) {
          remaining = res.data.expires_in || 600;
          cooldown = res.data.cooldown || 60;
          input.value = ''; input.focus();
          timer.textContent = ''; renderTimer(); renderCooldown();
          var t = document.getElementById('toasts');
          if (t) {
            var n = document.createElement('div'); n.className = 'toast glass'; n.setAttribute('role', 'status');
            n.innerHTML = '<span></span>'; n.firstChild.textContent = res.data.message; t.appendChild(n);
            setTimeout(function () { n.remove(); }, 4500);
          }
        } else {
          cooldown = (res.data && res.data.cooldown) || 0;
          showAlert(((res.data && res.data.message) || 'Gagal mengirim ulang kode. Coba lagi.') + (res.data && res.data.hint ? ' [Info pengembang] ' + res.data.hint : ''));
          renderCooldown();
        }
      })
      .catch(function () {
        resendBtn.removeAttribute('data-busy');
        showAlert('Koneksi terputus atau sesi berakhir. Muat ulang halaman lalu coba lagi.');
        renderCooldown();
      });
  });
})();
