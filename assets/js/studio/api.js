/* Melkino V2 — studio/api: persistence calls (draft/publish/history). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  M.Studio.api = {
    save: function (theme) {
      return M.api('design-studio-api.php', { method: 'POST', json: { action: 'save_draft', theme: theme } });
    },
    publish: function () {
      return M.api('design-studio-api.php', { method: 'POST', json: { action: 'publish' } });
    },
    history: function () {
      return M.api('design-studio-api.php?action=history', { method: 'GET' });
    }
  };
})();
