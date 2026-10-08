/* Melkino V2 — gallery (thumbs, keyboard, fullscreen) + visit modal. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Detail = M.Detail || {};
  if (M.Detail.__init) return;
  M.Detail.__init = true;

  document.addEventListener('click', function (e) {
    var th = e.target.closest ? e.target.closest('[data-gallery-thumb]') : null;
    if (th) {
      var main = document.querySelector('[data-gallery-main] img');
      var src = th.getAttribute('data-full') || (th.querySelector('img') || {}).src;
      if (main && src) { main.src = src; }
      document.querySelectorAll('[data-gallery-thumb]').forEach(function (b) { b.classList.remove('is-active'); });
      th.classList.add('is-active');
      return;
    }
    var mainBtn = e.target.closest ? e.target.closest('[data-gallery-main]') : null;
    if (mainBtn && mainBtn.tagName === 'BUTTON') {
      var img = mainBtn.querySelector('img');
      if (img && img.requestFullscreen) { try { img.requestFullscreen(); } catch (err) {} }
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-thumb]'));
    if (thumbs.length < 2) return;
    var idx = thumbs.findIndex(function (b) { return b.classList.contains('is-active'); });
    if (idx < 0) idx = 0;
    var next = e.key === 'ArrowLeft' ? (idx + 1) % thumbs.length : (idx - 1 + thumbs.length) % thumbs.length;
    thumbs[next].click();
  });
})();
