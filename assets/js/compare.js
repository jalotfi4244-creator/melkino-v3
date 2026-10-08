/* Melkino V2 — compare UI (spec §52). Server owns guest/user state; merge on login preserved. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Compare = M.Compare || {};
  if (M.Compare.__init) return;
  M.Compare.__init = true;

  M.Compare.toggle = function (adId, btn) {
    btn.disabled = true;
    return M.api('api/properties/compare.php', { method: 'POST', json: { ad_id: adId } }).then(function (res) {
      btn.disabled = false;
      if (res && res.success) {
        var added = !!(res.data && res.data.added);
        btn.setAttribute('aria-pressed', String(added));
        M.UI.toast('success', added ? 'به مقایسه اضافه شد.' : 'از مقایسه حذف شد.');
        M.Compare.refreshBadge(res.data);
        return added;
      }
      M.UI.toast('error', (res && res.message) || 'انجام نشد.');
      return null;
    }).catch(function () {
      btn.disabled = false;
      M.UI.toast('error', 'انجام نشد. اتصال را بررسی کنید.');
      return null;
    });
  };

  M.Compare.refreshBadge = function (data) {
    var badge = document.querySelector('[data-compare-count]');
    if (badge && data && typeof data.count !== 'undefined') badge.textContent = M.fa(data.count);
  };

  M.on(document, 'click', '[data-compare-toggle]', function (e, el) {
    e.preventDefault();
    var id = parseInt(el.getAttribute('data-compare-toggle'), 10);
    if (id > 0) M.Compare.toggle(id, el);
  });
})();
