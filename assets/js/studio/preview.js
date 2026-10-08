/* Melkino V2 — studio/preview: live-applies theme state to the preview + page shell. */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  M.Studio.preview = {
    apply: function (st) {
      document.body.setAttribute('data-mx-style', st.style || 'modern');
      document.body.setAttribute('data-mx-color', st.color || 'teal');
      var root = document.documentElement;
      root.style.setProperty('--mx-radius-override', st.radius === 'tight' ? '8px' : (st.radius === 'rounded' ? '24px' : '16px'));
      root.style.setProperty('--mx-shadow-override', st.shadow === 'none' ? 'none' : (st.shadow === 'strong' ? '0 18px 50px rgba(0,0,0,.16)' : '0 10px 30px rgba(0,0,0,.08)'));
    }
  };
  document.addEventListener('DOMContentLoaded', function () {
    if (M.Studio.state) M.Studio.state.onChange(function (st) { M.Studio.preview.apply(st); });
  });
})();
