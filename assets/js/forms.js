/* Melkino V2 — forms: wizard steps, OTP boxes, autosave drafts, double-submit guard. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Forms = M.Forms || {};
  if (M.Forms.__init) return;
  M.Forms.__init = true;

  // Wizard: [data-wizard] > [data-step] panels + [data-next]/[data-prev] + [data-steps] indicator.
  document.addEventListener('click', function (e) {
    var nav = e.target.closest ? e.target.closest('[data-next],[data-prev]') : null;
    if (nav) {
      var wiz = nav.closest('[data-wizard]');
      if (!wiz) return;
      var steps = Array.prototype.slice.call(wiz.querySelectorAll('[data-step]'));
      var cur = steps.findIndex(function (s) { return !s.hidden; });
      if (cur < 0) cur = 0;
      var dir = nav.hasAttribute('data-next') ? 1 : -1;
      if (dir === 1 && !M.Forms.validateStep(steps[cur])) return;
      var nxt = cur + dir;
      if (nxt < 0 || nxt >= steps.length) return;
      steps[cur].hidden = true;
      steps[nxt].hidden = false;
      M.Forms.paintSteps(wiz, nxt);
      wiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
      M.Forms.autosave(wiz);
      return;
    }
    var opt = e.target.closest ? e.target.closest('[data-option]') : null;
    if (opt) {
      var group = opt.parentElement;
      if (group) group.querySelectorAll('[data-option]').forEach(function (b) { b.classList.remove('is-selected'); });
      opt.classList.add('is-selected');
      var input = opt.closest('[data-step]') ? opt.closest('[data-step]').querySelector('input[type="hidden"][data-option-value]') : null;
      if (!input && opt.closest('form')) input = opt.closest('form').querySelector('input[name="' + opt.getAttribute('data-option-name') + '"]');
      if (input) input.value = opt.getAttribute('data-option') || '';
      var wiz2 = opt.closest('[data-wizard]');
      if (wiz2) M.Forms.autosave(wiz2);
    }
  });

  M.Forms.validateStep = function (panel) {
    if (!panel) return true;
    var required = panel.querySelectorAll('[required]');
    for (var i = 0; i < required.length; i++) {
      var f = required[i];
      var v = (f.value || '').trim();
      if (!v) {
        M.UI.toast('warning', 'لطفاً این مرحله را کامل کنید.');
        try { f.focus(); } catch (err) {}
        f.closest('.mx-field') && f.closest('.mx-field').classList.add('has-error');
        return false;
      }
      f.closest('.mx-field') && f.closest('.mx-field').classList.remove('has-error');
    }
    // Option-group required check.
    var groups = panel.querySelectorAll('[data-option-group-required]');
    for (var g = 0; g < groups.length; g++) {
      if (!groups[g].querySelector('[data-option].is-selected')) {
        M.UI.toast('warning', 'یک گزینه انتخاب کنید.');
        return false;
      }
    }
    return true;
  };

  M.Forms.paintSteps = function (wiz, idx) {
    var bars = wiz.querySelectorAll('[data-steps] span');
    bars.forEach(function (s, i) {
      s.classList.toggle('is-done', i < idx);
      s.classList.toggle('is-current', i === idx);
    });
  };

  // Draft autosave (request wizard): localStorage, survives refresh (spec §119, §120).
  M.Forms.autosave = function (wiz) {
    try {
      var key = wiz.getAttribute('data-autosave');
      if (!key) return;
      var data = {};
      wiz.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (f.name && f.type !== 'password') data[f.name] = f.value;
      });
      localStorage.setItem(key, JSON.stringify({ at: Date.now(), data: data }));
      var ind = wiz.querySelector('[data-autosave-indicator]');
      if (ind) ind.textContent = 'پیش‌نویس ذخیره شد';
    } catch (err) {}
  };

  M.Forms.restore = function (wiz) {
    try {
      var key = wiz.getAttribute('data-autosave');
      if (!key) return;
      var raw = localStorage.getItem(key);
      if (!raw) return;
      var saved = JSON.parse(raw);
      Object.keys(saved.data || {}).forEach(function (name) {
        var f = wiz.querySelector('[name="' + name + '"]');
        if (f && !f.value) {
          f.value = saved.data[name];
          if (f.type === 'hidden') {
            var btn = wiz.querySelector('[data-option="' + CSS.escape(saved.data[name]) + '"]');
            if (btn) btn.classList.add('is-selected');
          }
        }
      });
    } catch (err) {}
  };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wizard][data-autosave]').forEach(function (w) { M.Forms.restore(w); });
    // Clear draft on successful submit.
    document.querySelectorAll('[data-wizard] form, form[data-wizard]').forEach(function (form) {
      form.addEventListener('submit', function () {
        var wiz = form.closest('[data-wizard]') || form;
        var key = wiz.getAttribute && wiz.getAttribute('data-autosave');
        if (key) { try { localStorage.removeItem(key); } catch (err) {} }
      });
    });
    // OTP auto-advance.
    document.querySelectorAll('.mx-otp').forEach(function (box) {
      var inputs = box.querySelectorAll('input');
      inputs.forEach(function (inp, i) {
        inp.addEventListener('input', function () {
          inp.value = M.en(inp.value).replace(/\D/g, '').slice(0, 1);
          if (inp.value && inputs[i + 1]) inputs[i + 1].focus();
          var hidden = box.parentElement.querySelector('input[data-otp-value]');
          if (hidden) hidden.value = Array.prototype.map.call(inputs, function (x) { return x.value; }).join('');
        });
        inp.addEventListener('keydown', function (ev) {
          if (ev.key === 'Backspace' && !inp.value && inputs[i - 1]) inputs[i - 1].focus();
        });
      });
    });
    // Double-submit guard.
    document.querySelectorAll('form[data-guard]').forEach(function (form) {
      form.addEventListener('submit', function () {
        form.querySelectorAll('[type="submit"]').forEach(function (b) {
          b.disabled = true;
          b.classList.add('mx-btn--loading');
        });
      });
    });
  });
})();
