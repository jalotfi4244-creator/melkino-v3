/* Melkino V2 — requests shell (same endpoints/copy as legacy; CSP-safe delegation). */
(function () {
  'use strict';
  var TG_ID = '';
  try {
    var de = document.getElementById('mxReqData');
    TG_ID = de ? (JSON.parse(de.textContent || '{}').tg || '') : '';
  } catch (e) { TG_ID = ''; }
  var rowsById = {};
  var esc = function (v) {
    return String(v === null || v === undefined ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
  };
  var money = function (v) {
    if (v === null || v === undefined || v === '') return '—';
    var n = Number(v);
    return Number.isFinite(n) ? n.toLocaleString('en-US') : esc(v);
  };
  var numOrBlank = function (v) {
    if (v === null || v === undefined || v === '') return '';
    var n = Number(v);
    return Number.isFinite(n) ? n.toLocaleString('en-US') : String(v);
  };
  var unmoney = function (v) { return String(v === null || v === undefined ? '' : v).replace(/,/g, '').replace(/٬/g, '').replace(/،/g, ''); };
  function showNotice(msg, ok) {
    var n = document.getElementById('notice');
    n.hidden = false;
    n.className = 'mx-notice ' + (ok ? 'mx-notice--info' : 'mx-notice--warn');
    n.textContent = msg;
  }
  function closeEdit() {
    var m = document.getElementById('editModal');
    m.hidden = true;
    m.setAttribute('aria-hidden', 'true');
  }
  function openEdit(r) {
    document.getElementById('editId').value = r.id || '';
    document.getElementById('editTransaction').value = r.transaction_type || 'فروش';
    document.getElementById('editProperty').value = r.property_type || '';
    document.getElementById('editLocation').value = r.location || '';
    document.getElementById('editMinArea').value = numOrBlank(r.min_area);
    document.getElementById('editMaxArea').value = numOrBlank(r.max_area);
    document.getElementById('editMinPrice').value = numOrBlank(r.min_price);
    document.getElementById('editMaxPrice').value = numOrBlank(r.max_price);
    document.getElementById('editMinDeposit').value = numOrBlank(r.min_deposit);
    document.getElementById('editMaxDeposit').value = numOrBlank(r.max_deposit);
    document.getElementById('editMinRent').value = numOrBlank(r.min_rent);
    document.getElementById('editMaxRent').value = numOrBlank(r.max_rent);
    document.getElementById('editDateNeeded').value = r.date_needed || '';
    document.getElementById('editUrgency').value = r.urgency || 'فوری';
    document.getElementById('editRahn').checked = Number(r.rahn_kamal) === 1;
    document.getElementById('editNotKeyed').checked = Number(r.is_not_keyed) === 1;
    var m = document.getElementById('editModal');
    m.hidden = false;
    m.setAttribute('aria-hidden', 'false');
  }
  function card(r) {
    var code = esc(r.tracking_code || '');
    var meta = [r.transaction_type, r.property_type, r.location].filter(Boolean).map(esc).join(' · ');
    return '<article class="mx-card-surface mx-p-4">' +
      '<div class="mx-visit__head"><b>' + code + '</b><span class="mx-small">' + Number(r.match_count || 0).toLocaleString('en-US') + ' فایل مناسب</span></div>' +
      '<p class="mx-mt-2">' + (meta || 'مشخصات درخواست ثبت نشده است.') + '</p>' +
      '<p class="mx-tiny mx-muted">ثبت: ' + esc(r.created_at || '—') + ' · وضعیت: ' + esc(r.status || '—') + '</p>' +
      '<p class="mx-tiny mx-muted">متراژ: ' + money(r.min_area) + ' تا ' + money(r.max_area) + ' متر · بودجه: ' + money(r.min_price) + ' تا ' + money(r.max_price) + ' تومان</p>' +
      '<div class="mx-flex mx-mt-4" style="gap:8px">' +
      '<a class="mx-btn mx-btn--primary mx-btn--sm" href="my-request-matches.php?code=' + encodeURIComponent(r.tracking_code || '') + '">مشاهده فایل‌های مناسب</a>' +
      '<button class="mx-btn mx-btn--ghost mx-btn--sm" type="button" data-req-edit="' + Number(r.id) + '">ویرایش</button>' +
      '<button class="mx-btn mx-btn--danger mx-btn--sm" type="button" data-req-del="' + Number(r.id) + '">حذف</button>' +
      '</div></article>';
  }
  function loadRequests() {
    var grid = document.getElementById('grid');
    fetch('requests.php?action=list&telegram_id=' + encodeURIComponent(TG_ID), { cache: 'no-store' })
      .then(function (res) { return res.json(); })
      .then(function (d) {
        if (!d.success) { showNotice(d.message || 'خطا در دریافت درخواست‌ها.', false); return; }
        var rows = d.requests || [];
        rowsById = {};
        rows.forEach(function (r) { rowsById[Number(r.id)] = r; });
        grid.innerHTML = rows.length ? rows.map(card).join('') : '<div class="mx-card-surface mx-p-4 mx-text-center mx-muted">هنوز درخواست ملکی برای شما ثبت نشده است.</div>';
      })
      .catch(function () { showNotice('ارتباط با سرور انجام نشد. لطفاً دوباره تلاش کنید.', false); });
  }
  function deleteRequest(id) {
    if (!window.confirm('این درخواست حذف شود؟ این کار تطبیق‌های مرتبط با درخواست را نیز حذف می‌کند.')) return;
    var body = new URLSearchParams({ id: String(id), telegram_id: TG_ID });
    fetch('requests.php?action=delete', { method: 'POST', body: body })
      .then(function (res) { return res.json(); })
      .then(function (d) {
        showNotice(d.message || 'عملیات انجام شد.', !!d.success);
        if (d.success) loadRequests();
      })
      .catch(function () { showNotice('حذف درخواست انجام نشد. لطفاً دوباره تلاش کنید.', false); });
  }
  document.getElementById('grid').addEventListener('click', function (ev) {
    var t = ev.target && ev.target.closest ? ev.target.closest('[data-req-edit],[data-req-del]') : null;
    if (!t) return;
    if (t.hasAttribute('data-req-edit')) {
      var r = rowsById[Number(t.getAttribute('data-req-edit'))];
      if (r) openEdit(r);
    } else {
      deleteRequest(Number(t.getAttribute('data-req-del')));
    }
  });
  document.querySelectorAll('[data-close-edit]').forEach(function (b) {
    b.addEventListener('click', closeEdit);
  });
  document.querySelectorAll('.money').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var raw = unmoney(inp.value).replace(/\D/g, '');
      inp.value = raw ? Number(raw).toLocaleString('en-US') : '';
    });
  });
  document.getElementById('editForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = document.getElementById('saveRequestBtn');
    var old = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'در حال ذخیره...';
    var fd = new FormData(e.target);
    ['min_area', 'max_area', 'min_price', 'max_price', 'min_deposit', 'max_deposit', 'min_rent', 'max_rent'].forEach(function (key) {
      fd.set(key, unmoney(fd.get(key)));
    });
    fetch('requests.php?action=update', { method: 'POST', body: new URLSearchParams(fd) })
      .then(function (res) { return res.json(); })
      .then(function (d) {
        showNotice(d.message || 'عملیات انجام شد.', !!d.success);
        if (d.success) { closeEdit(); loadRequests(); }
      })
      .catch(function () { showNotice('ویرایش درخواست انجام نشد. لطفاً دوباره تلاش کنید.', false); })
      .finally(function () { btn.disabled = false; btn.textContent = old; });
  });
  loadRequests();
})();
