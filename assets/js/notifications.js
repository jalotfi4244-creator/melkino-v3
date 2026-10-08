/* Melkino V2 — notification badge (server-authoritative, lightweight refresh, spec §148). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Notifications = M.Notifications || {};
  if (M.Notifications.__init) return;
  M.Notifications.__init = true;

  M.Notifications.refresh = function () {
    var badge = document.querySelector('[data-notif-count]');
    if (!badge && !document.querySelector('[data-notif-dot]')) return Promise.resolve(0);
    return fetch('api/notifications/count.php', { credentials: 'same-origin' }).then(function (r) {
      return r.json();
    }).then(function (res) {
      var n = res && res.data ? (res.data.unread || 0) : 0;
      if (badge) {
        badge.textContent = M.fa(n);
        badge.style.display = n > 0 ? '' : 'none';
      }
      document.querySelectorAll('[data-notif-dot]').forEach(function (d) {
        d.style.display = n > 0 ? '' : 'none';
      });
      return n;
    }).catch(function () { return 0; });
  };

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) M.Notifications.refresh();
  });
  setInterval(function () {
    if (!document.hidden) M.Notifications.refresh();
  }, 120000); // 2 minutes — no aggressive polling.
  if (document.readyState !== 'loading') M.Notifications.refresh();
  else document.addEventListener('DOMContentLoaded', function () { M.Notifications.refresh(); });
})();
