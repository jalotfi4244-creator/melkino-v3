/* Melkino V2 — core (spec §50). Single namespace, no global pollution, CSRF auto-header. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  if (M.__core) return;
  M.__core = true;

  M.csrf = function () {
    if (window.MELKINO_CSRF) return window.MELKINO_CSRF;
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  M.api = function (url, options) {
    options = options || {};
    options.headers = options.headers || {};
    var method = String(options.method || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD') {
      var t = M.csrf();
      if (t && !options.headers['X-CSRF-Token']) options.headers['X-CSRF-Token'] = t;
    }
    if (options.json !== undefined) {
      options.headers['Content-Type'] = 'application/json; charset=utf-8';
      options.body = JSON.stringify(options.json);
      delete options.json;
    }
    return fetch(url, Object.assign({ credentials: 'same-origin' }, options)).then(function (res) {
      return res.text().then(function (txt) {
        var data = null;
        try { data = JSON.parse(txt); } catch (e) { data = { success: false, message: txt }; }
        if (!res.ok && data && !data.message) data.message = 'خطای سرور (' + res.status + ')';
        data.__status = res.status;
        return data;
      });
    });
  };

  M.on = function (root, event, selector, handler) {
    (root || document).addEventListener(event, function (e) {
      var el = e.target && e.target.closest ? e.target.closest(selector) : null;
      if (el) handler(e, el);
    });
  };

  M.fa = function (s) {
    return String(s).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; });
  };
  M.en = function (s) {
    return String(s).replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
      .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
  };

  M.announce = function (msg) {
    var live = document.getElementById('mxLive');
    if (!live) {
      live = document.createElement('div');
      live.id = 'mxLive';
      live.className = 'mx-sr-only';
      live.setAttribute('aria-live', 'polite');
      document.body.appendChild(live);
    }
    live.textContent = msg;
  };

  // Generic destructive-action confirm (delegated; works with dynamically added forms).
  document.addEventListener('submit', function (ev) {
    var form = ev.target && ev.target.closest ? ev.target.closest('form[data-confirm]') : null;
    if (!form) return;
    var msg = form.getAttribute('data-confirm') || 'مطمئن هستید؟';
    if (!window.confirm(msg)) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  }, true);
})();
