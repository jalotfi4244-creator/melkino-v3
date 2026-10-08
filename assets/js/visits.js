/* Melkino V2 — visits delete (same endpoint/payload as legacy visits.php). */
(function () {
  'use strict';
  document.addEventListener('click', function (ev) {
    var btn = ev.target && ev.target.closest ? ev.target.closest('[data-visit-del]') : null;
    if (!btn) return;
    var id = btn.getAttribute('data-visit-del');
    if (!id || !window.confirm('این درخواست بازدید حذف شود؟')) return;
    var fd = new FormData();
    fd.append('id', String(id));
    if (window.MELKINO_CSRF) fd.append('csrf_token', window.MELKINO_CSRF);
    var headers = {};
    if (window.MELKINO_CSRF) headers['X-CSRF-Token'] = window.MELKINO_CSRF;
    fetch('visit-request-api.php?action=delete', { method: 'POST', body: fd, credentials: 'same-origin', headers: headers })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success) {
          var el = document.getElementById('mxVisit' + id);
          if (el) el.remove();
          Melkino.UI.toast('success', (data.message || 'حذف شد.'));
          if (!document.querySelector('#mxVisits article')) window.location.reload();
        } else {
          Melkino.UI.toast('error', (data && data.message) || 'حذف نشد.');
        }
      })
      .catch(function () { Melkino.UI.toast('error', 'اتصال برقرار نشد.'); });
  });
})();
