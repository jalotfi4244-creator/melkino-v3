/* Melkino V2 — ui: toasts, modals (focus trap + ESC), delegated closers. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.UI = M.UI || {};
  if (M.UI.__init) return;
  M.UI.__init = true;

  function ensureToasts() {
    var box = document.getElementById('mxToasts');
    if (!box) {
      box = document.createElement('div');
      box.className = 'mx-toasts';
      box.id = 'mxToasts';
      box.setAttribute('aria-live', 'polite');
      document.body.appendChild(box);
    }
    return box;
  }

  M.UI.toast = function (type, message) {
    var box = ensureToasts();
    var el = document.createElement('div');
    el.className = 'mx-toast mx-toast--' + type;
    el.setAttribute('role', 'status');
    el.innerHTML = '<span></span><button type="button" class="mx-toast__close" data-toast-close aria-label="بستن">✕</button>';
    el.querySelector('span').textContent = message;
    box.appendChild(el);
    M.announce(message);
    setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 5000);
  };

  M.UI.openModal = function (id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    m.__prevFocus = document.activeElement;
    var dlg = m.querySelector('.mx-modal__dialog');
    var f = m.querySelector('[data-autofocus]') || (dlg && dlg.querySelector('button, input, select, a[href]'));
    if (f) setTimeout(function () { try { f.focus(); } catch (e) {} }, 50);
    m.__trap = function (e) {
      if (e.key === 'Escape') { M.UI.closeModal(id); return; }
      if (e.key !== 'Tab') return;
      var items = m.querySelectorAll('button, input, select, textarea, a[href]');
      if (!items.length) return;
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };
    document.addEventListener('keydown', m.__trap);
  };

  M.UI.closeModal = function (id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('is-open');
    document.body.style.overflow = '';
    if (m.__trap) document.removeEventListener('keydown', m.__trap);
    if (m.__prevFocus && m.__prevFocus.focus) { try { m.__prevFocus.focus(); } catch (e) {} }
  };

  document.addEventListener('click', function (e) {
    var c = e.target.closest ? e.target.closest('[data-toast-close]') : null;
    if (c) { var t = c.closest('.mx-toast'); if (t && t.parentNode) t.parentNode.removeChild(t); return; }
    var o = e.target.closest ? e.target.closest('[data-modal-open]') : null;
    if (o) { M.UI.openModal(o.getAttribute('data-modal-open')); return; }
    var x = e.target.closest ? e.target.closest('[data-modal-close]') : null;
    if (x) { var m = x.closest('.mx-modal'); if (m && m.id) M.UI.closeModal(m.id); return; }
    var b = e.target.closest ? e.target.closest('.mx-modal__backdrop') : null;
    if (b) { var mm = b.closest('.mx-modal'); if (mm && mm.id) M.UI.closeModal(mm.id); }
  });
})();
