/* Melkino V2 — legacy dashboard tab JS VERBATIM from admin-dashboard.php inline <script>.
 * API: admin-dashboard-api.php (untouched). 5 template + 4 fragment switchTab()
 * calls -> data-goto navigation (V2 has no switchTab; same destinations).
 */
(function () {
  function faDigits(n) {
    return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.charAt(+d); });
  }
  function g2j(gy, gm, gd) {
    var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
      + Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
    var jy = -1595 + (33 * Math.floor(days / 12053));
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    var jm, jd;
    if (days < 186) { jm = 1 + Math.floor(days / 31); jd = 1 + (days % 31); }
    else { jm = 7 + Math.floor((days - 186) / 30); jd = 1 + ((days - 186) % 30); }
    return [jy, jm, jd];
  }
  function j2g(jy, jm, jd) {
    jy += 1595;
    var days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4) + jd
      + ((jm < 7) ? ((jm - 1) * 31) : (((jm - 7) * 30) + 186));
    var gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    var gd = days + 1;
    var sal = [0, 31, (((gy % 4 === 0) && (gy % 100 !== 0)) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 1;
    for (var i = 1; i <= 12; i++) {
      if (gd <= sal[i]) { gm = i; break; }
      gd -= sal[i];
    }
    return [gy, gm, gd];
  }
  function jMonthDays(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    return [1, 5, 9, 13, 17, 22, 26, 30].indexOf(jy % 33) >= 0 ? 30 : 29;
  }
  var months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  function renderCal() {
    var now = new Date();
    var j = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
    var title = document.getElementById('mkDashCalTitle');
    if (title) title.textContent = months[j[1] - 1] + ' ' + faDigits(j[0]);
    var head = document.getElementById('mkDashCalHead');
    if (head && !head.childElementCount) {
      ['ش','ی','د','س','چ','پ','ج'].forEach(function (w) {
        var d = document.createElement('div'); d.className = 'mk-dash-cal-wd'; d.textContent = w; head.appendChild(d);
      });
    }
    var body = document.getElementById('mkDashCalBody');
    if (!body) return;
    body.innerHTML = '';
    var g1 = j2g(j[0], j[1], 1);
    var phpW = new Date(g1[0], g1[1] - 1, g1[2]).getDay();
    var start = (phpW + 1) % 7;
    var dim = jMonthDays(j[0], j[1]);
    var i;
    for (i = 0; i < start; i++) {
      var e = document.createElement('div'); e.className = 'mk-dash-cal-day is-empty'; body.appendChild(e);
    }
    for (var day = 1; day <= dim; day++) {
      var cell = document.createElement('div');
      cell.className = 'mk-dash-cal-day' + (day === j[2] ? ' is-today' : '');
      cell.textContent = faDigits(day);
      body.appendChild(cell);
    }
  }
  function tickClock() {
    var d = new Date();
    var c = document.getElementById('mkDashClock');
    var dt = document.getElementById('mkDashDate');
    if (c) c.textContent = d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    if (dt) dt.textContent = d.toLocaleDateString('fa-IR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]);
    });
  }
  function visitHtml(list) {
    if (!list || !list.length) return '<div class="mk-dash-empty">بازدیدی ثبت نشده است.</div>';
    return list.map(function (v) {
      return '<div class="mk-vitem">'
        + '<div class="mk-vtime">' + esc(v.slot || v.when || '—') + '</div>'
        + '<div><strong>' + esc(v.ad_title) + '</strong><div style="opacity:.75">' + esc(v.user) + ' · ' + esc(v.phone) + '</div></div>'
        + '<button type="button" class="btn-secondary" style="font-size:11px" data-goto="visits">پیگیری</button>'
        + '</div>';
    }).join('');
  }
  window.loadAdminDashboard = async function () {
    tickClock();
    renderCal();
    try {
      var r = await fetch('admin-dashboard-api.php', { cache: 'no-store', credentials: 'same-origin' });
      var data = await r.json();
      if (!data || !data.success) return;
      var set = function (id, v) { var el = document.getElementById(id); if (el) el.textContent = faDigits(v == null ? 0 : v); };
      set('mkDashPendingAds', data.pending_ads_count);
      set('mkDashNewReqs', data.new_requests_count);
      set('mkDashNewVisits', data.new_visits_count);
      set('mkDashNewTickets', data.new_tickets_count);
      var t = document.getElementById('mkDashVisitsToday');
      var tm = document.getElementById('mkDashVisitsTomorrow');
      if (t) t.innerHTML = visitHtml(data.visits_today);
      if (tm) tm.innerHTML = visitHtml(data.visits_tomorrow);
      var ads = document.getElementById('mkDashAdsList');
      if (ads) {
        var adRows = (data.pending_ads || []).map(function (a) {
          return '<div class="mk-dash-row"><div><strong>' + esc(a.title || a.id) + '</strong><span>' + esc(a.property_type || '') + ' · ' + esc(a.transaction_type || '') + '</span></div><button type="button" class="btn-secondary" style="font-size:11px" data-goto="ads">بررسی</button></div>';
        });
        var mkpRows = (data.pending_partnership || []).map(function (a) {
          return '<div class="mk-dash-row"><div><strong>🤝 ' + esc(a.title || a.code || a.id) + '</strong><span>مشارکت در ساخت · ' + esc(a.property_type || '') + '</span></div><button type="button" class="btn-secondary" style="font-size:11px" data-goto="ads">بررسی</button></div>';
        });
        ads.innerHTML = (adRows.length + mkpRows.length) ? adRows.concat(mkpRows).join('') : '<div class="mk-dash-empty">موردی منتظر تأیید نیست.</div>';
      }
      var side = document.getElementById('mkDashSideList');
      if (side) {
        var html = '';
        (data.new_requests || []).slice(0, 6).forEach(function (q) {
          html += '<div class="mk-dash-row"><div><strong>درخواست ' + esc(q.tracking_code || q.id) + '</strong><span>' + esc(q.name || q.phone || '') + '</span></div><button type="button" class="btn-secondary" style="font-size:11px" data-goto="requests">باز</button></div>';
        });
        (data.new_tickets || []).slice(0, 6).forEach(function (q) {
          html += '<div class="mk-dash-row"><div><strong>تیکت #' + esc(q.id) + '</strong><span>' + esc(q.subject || q.status || '') + '</span></div><button type="button" class="btn-secondary" style="font-size:11px" data-goto="support">پاسخ</button></div>';
        });
        side.innerHTML = html || '<div class="mk-dash-empty">مورد جدیدی نیست.</div>';
      }
    } catch (e) {}
  };
  setInterval(tickClock, 1000);
  tickClock();
  renderCal();
})();

/* V2: tab-link navigation + boot (panel switchTab('dashboard') calls loadAdminDashboard). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-goto]') : null;
    if (el) window.location.href = 'admin.php?tab=' + el.getAttribute('data-goto');
});
document.addEventListener('DOMContentLoaded', function () {
    try { if (typeof loadAdminDashboard === 'function') loadAdminDashboard(); } catch (e) {}
});
