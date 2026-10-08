/* Melkino V2 — VIP filters (same logic as legacy vip.php; shared mkRenderAdCards). */
(function () {
  'use strict';
  var host = document.getElementById('mxVipAdsList');
  var dataEl = document.getElementById('mxVipData');
  if (!host || !dataEl) return;
  var all = [];
  try { all = JSON.parse(dataEl.textContent || '[]'); } catch (e) { all = []; }
  var pF = document.getElementById('vipPropertyFilter');
  var tF = document.getElementById('vipTransactionFilter');
  var reset = document.getElementById('vipFilterReset');
  var empty = document.getElementById('mxVipEmpty');
  var count = document.getElementById('mxVipCount');
  function fa(n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); }
  function norm(v) { return String(v || '').trim(); }
  function apply() {
    if (!window.mkRenderAdCards) return;
    var p = pF ? norm(pF.value) : '', tr = tF ? norm(tF.value) : '';
    var list = all.filter(function (a) {
      return (!p || norm(a.property_type) === p) && (!tr || norm(a.transaction_type) === tr);
    });
    window.mkRenderAdCards(host, list);
    if (empty) empty.hidden = list.length > 0;
    if (count) count.textContent = fa(list.length) + ' فایل VIP';
  }
  if (pF) pF.addEventListener('change', apply);
  if (tF) tF.addEventListener('change', apply);
  if (reset) reset.addEventListener('click', function () {
    if (pF) pF.value = ''; if (tF) tF.value = ''; apply();
  });
  apply();
})();
