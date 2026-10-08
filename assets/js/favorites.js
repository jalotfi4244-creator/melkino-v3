/* Melkino V2 — favorites: optimistic toggle with rollback (spec §20, §53). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Favorites = M.Favorites || {};
  if (M.Favorites.__init) return;
  M.Favorites.__init = true;

  M.Favorites.toggle = function (adId, btn) {
    var wasActive = btn.classList.contains('is-active');
    btn.classList.toggle('is-active', !wasActive);
    btn.setAttribute('aria-pressed', String(!wasActive));
    btn.disabled = true;
    return M.api('api/properties/favorite.php', { method: 'POST', json: { ad_id: adId } }).then(function (res) {
      btn.disabled = false;
      if (res && res.success) {
        var saved = !!(res.data && res.data.saved);
        btn.classList.toggle('is-active', saved);
        btn.setAttribute('aria-pressed', String(saved));
        var svg = btn.querySelector('svg');
        if (svg) svg.style.fill = saved ? 'currentColor' : 'none';
        M.announce(saved ? 'ملک ذخیره شد.' : 'ملک از ذخیره‌ها حذف شد.');
        return saved;
      }
      // Rollback.
      btn.classList.toggle('is-active', wasActive);
      btn.setAttribute('aria-pressed', String(wasActive));
      if (res && res.__status === 401) {
        M.UI.toast('error', 'برای ذخیره ملک ابتدا وارد شوید.');
        location.href = 'login.php?redirect=' + encodeURIComponent(location.pathname + location.search);
      } else {
        M.UI.toast('error', (res && res.message) || 'ذخیره انجام نشد.');
      }
      return wasActive;
    }).catch(function () {
      btn.disabled = false;
      btn.classList.toggle('is-active', wasActive);
      btn.setAttribute('aria-pressed', String(wasActive));
      M.UI.toast('error', 'ذخیره انجام نشد. اتصال را بررسی کنید.');
      return wasActive;
    });
  };

  M.on(document, 'click', '[data-fav-toggle]', function (e, el) {
    e.preventDefault();
    var id = parseInt(el.getAttribute('data-fav-toggle'), 10);
    if (id > 0) M.Favorites.toggle(id, el);
  });
})();
