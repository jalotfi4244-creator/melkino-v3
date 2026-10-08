/* Melkino V2 — studio/wizard: boot, save/publish/undo/redo wiring. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  document.addEventListener('DOMContentLoaded', function () {
    var mount = document.querySelector('[data-studio-inspector]');
    if (!mount) return;
    var initial = {};
    try { initial = JSON.parse(mount.getAttribute('data-initial') || '{}'); } catch (e) {}
    M.Studio.state.set(initial);
    M.Studio.inspector.render(mount);
    M.Studio.state.onChange(function () { M.Studio.inspector.paint(mount); });
    M.Studio.preview.apply(M.Studio.state.get());
    M.on(document, 'click', '[data-studio-save]', function (e) {
      e.preventDefault();
      M.Studio.api.save(M.Studio.state.get()).then(function (res) {
        M.UI.toast(res && res.success ? 'success' : 'error', (res && res.message) || (res && res.success ? 'پیش‌نویس ذخیره شد.' : 'ذخیره نشد.'));
      });
    });
    M.on(document, 'click', '[data-studio-publish]', function (e) {
      e.preventDefault();
      M.Studio.api.publish().then(function (res) {
        M.UI.toast(res && res.success ? 'success' : 'error', (res && res.message) || (res && res.success ? 'منتشر شد.' : 'انتشار ناموفق بود.'));
      });
    });
    M.on(document, 'click', '[data-studio-undo]', function (e) {
      e.preventDefault();
      var prev = M.Studio.history.undo(M.Studio.state.get());
      if (prev) M.Studio.state.set(prev);
    });
    M.on(document, 'click', '[data-studio-redo]', function (e) {
      e.preventDefault();
      var next = M.Studio.history.redo(M.Studio.state.get());
      if (next) M.Studio.state.set(next);
    });
  });
})();
