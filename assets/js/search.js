/* Melkino V2 — search: hero tabs + saved-search save (spec §13, §14). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Search = M.Search || {};
  if (M.Search.__init) return;
  M.Search.__init = true;

  document.addEventListener('click', function (e) {
    var tab = e.target.closest ? e.target.closest('[data-tx-tab]') : null;
    if (tab) {
      var wrap = tab.closest('[data-tx-tabs]');
      if (wrap) {
        wrap.querySelectorAll('[data-tx-tab]').forEach(function (b) { b.classList.remove('is-active'); });
        tab.classList.add('is-active');
        var hidden = wrap.parentElement ? wrap.parentElement.querySelector('input[name="tx"]') : null;
        if (hidden) hidden.value = tab.getAttribute('data-tx-tab');
      }
    }
    var save = e.target.closest ? e.target.closest('[data-save-search]') : null;
    if (save) {
      e.preventDefault();
      var query = save.getAttribute('data-save-search') || location.search.replace(/^\?/, '');
      M.api('saved-search-save.php', { method: 'POST', json: { query: query } }).then(function (res) {
        if (res && res.success) M.UI.toast('success', res.message || 'این جستجو ذخیره شد.');
        else M.UI.toast('error', (res && res.message) || 'ذخیره انجام نشد.');
      }).catch(function () { M.UI.toast('error', 'ذخیره انجام نشد. اتصال را بررسی کنید.'); });
    }
  });
})();
