/* Melkino V2 — studio/inspector: renders preset groups, emits state changes. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  M.Studio.inspector = {
    render: function (mount) {
      if (!mount || !M.Studio.presets) return;
      var st = M.Studio.state.get();
      mount.innerHTML = '';
      M.Studio.presets.groups.forEach(function (g) {
        var box = document.createElement('div');
        box.className = 'mx-studio__group';
        var h = document.createElement('h4');
        h.textContent = g.label;
        box.appendChild(h);
        var opts = document.createElement('div');
        opts.className = 'mx-studio__opts';
        g.options.forEach(function (o) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'mx-studio__opt' + (st[g.key] === o[0] ? ' is-active' : '');
          b.textContent = o[1];
          b.setAttribute('data-studio-key', g.key);
          b.setAttribute('data-studio-val', o[0]);
          opts.appendChild(b);
        });
        box.appendChild(opts);
        mount.appendChild(box);
      });
    },
    paint: function (mount) {
      if (!mount) return;
      var st = M.Studio.state.get();
      mount.querySelectorAll('[data-studio-key]').forEach(function (b) {
        b.classList.toggle('is-active', st[b.getAttribute('data-studio-key')] === b.getAttribute('data-studio-val'));
      });
    }
  };
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-studio-key]') : null;
    if (!b) return;
    var patch = {};
    patch[b.getAttribute('data-studio-key')] = b.getAttribute('data-studio-val');
    M.Studio.history.push(M.Studio.state.get());
    M.Studio.state.set(patch);
  });
})();
