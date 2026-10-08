/* Melkino V2 — studio/state: single theme state + subscribers. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  var state = { style: 'modern', color: 'teal', card: 'standard', radius: 'soft', shadow: 'soft' };
  var listeners = [];
  M.Studio.state = {
    get: function () { return Object.assign({}, state); },
    set: function (patch) {
      Object.assign(state, patch || {});
      listeners.forEach(function (fn) { try { fn(state); } catch (e) {} });
    },
    onChange: function (fn) { listeners.push(fn); }
  };
})();
