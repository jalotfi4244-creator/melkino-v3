/* Melkino V2 — studio/history: undo/redo stacks (preserved behavior). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  var undo = [], redo = [];
  M.Studio.history = {
    push: function (snap) { undo.push(JSON.stringify(snap)); if (undo.length > 50) undo.shift(); redo = []; },
    undo: function (current) {
      if (!undo.length) return null;
      redo.push(JSON.stringify(current));
      return JSON.parse(undo.pop());
    },
    redo: function (current) {
      if (!redo.length) return null;
      undo.push(JSON.stringify(current));
      return JSON.parse(redo.pop());
    }
  };
})();
