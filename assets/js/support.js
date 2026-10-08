/* Melkino V2 — support forms (fetch POST, same flat {success,message,ticket_id} contract). */
(function () {
  'use strict';
  function postForm(form, onOk) {
    var fd = new FormData(form);
    fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success) {
          Melkino.UI.toast('success', res.message || 'انجام شد.');
          onOk(res);
        } else {
          Melkino.UI.toast('error', (res && res.message) || 'انجام نشد.');
        }
      })
      .catch(function () { Melkino.UI.toast('error', 'اتصال برقرار نشد.'); });
  }
  var fNew = document.getElementById('mxTicketNew');
  if (fNew) fNew.addEventListener('submit', function (ev) {
    ev.preventDefault();
    postForm(fNew, function (res) {
      window.location.href = res.ticket_id ? 'support.php?ticket=' + res.ticket_id : 'support.php';
    });
  });
  var fMsg = document.getElementById('mxMsgForm');
  if (fMsg) fMsg.addEventListener('submit', function (ev) {
    ev.preventDefault();
    postForm(fMsg, function () { window.location.reload(); });
  });
  var closeBtn = document.getElementById('mxTicketClose');
  if (closeBtn) closeBtn.addEventListener('click', function () {
    if (!window.confirm('این درخواست بسته شود؟')) return;
    var fd = new FormData();
    fd.append('action', 'close_ticket');
    fd.append('ticket_id', closeBtn.getAttribute('data-ticket'));
    if (window.MELKINO_CSRF) fd.append('csrf_token', window.MELKINO_CSRF);
    fetch('support.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success) window.location.reload();
        else Melkino.UI.toast('error', (res && res.message) || 'انجام نشد.');
      })
      .catch(function () { Melkino.UI.toast('error', 'اتصال برقرار نشد.'); });
  });
})();
