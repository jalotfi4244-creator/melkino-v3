/* Melkino V2 — properties listing helpers (filter modal submit, sort select). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Properties = M.Properties || {};
  if (M.Properties.__init) return;
  M.Properties.__init = true;

  document.addEventListener('change', function (e) {
    var s = e.target.closest ? e.target.closest('[data-sort-select]') : null;
    if (s && s.form) s.form.submit();
  });
})();
