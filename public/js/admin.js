(function () {
  'use strict';
  var b = document.querySelector('[data-aside]');
  var a = document.getElementById('aside');
  if (b && a) {
    b.addEventListener('click', function () {
      var o = a.classList.toggle('open');
      b.setAttribute('aria-expanded', o);
    });
  }
  // Pesan sukses hilang otomatis
  var al = document.querySelector('.alert:not(.bad)');
  if (al) setTimeout(function () { al.style.transition = 'opacity .5s'; al.style.opacity = 0; }, 4000);
})();
