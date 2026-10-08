<?php
/** Melkino V2 — legacy dashboard tab. Fragment VERBATIM from admin-dashboard.php
 * (own <style> travels with it; inline <script> moved to admin-tab-legacy-dash.js).
 * Root file kept for the panel. Named legacy-dash: V2 shell home is the new dashboard. */
?>
<style>
.mk-dash{padding:4px 2px 28px;display:flex;flex-direction:column;gap:16px}
.mk-dash-hero{
  display:grid;grid-template-columns:1.4fr .9fr;gap:14px;
  background:linear-gradient(135deg,#0b1f1c 0%,#12352f 42%,#1a4a3c 100%);
  border:1px solid rgba(201,162,39,.28);border-radius:22px;padding:18px 20px;color:#F4F1E8;
  box-shadow:0 18px 40px rgba(8,20,18,.35);
}
.mk-dash-kicker{font-size:11px;letter-spacing:.18em;color:#C9A227;font-weight:800}
.mk-dash-clock{font-size:42px;font-weight:900;letter-spacing:.04em;font-variant-numeric:tabular-nums;margin:6px 0 2px}
.mk-dash-date{font-size:15px;opacity:.92}
.mk-dash-cal{background:rgba(255,255,255,.06);border-radius:16px;padding:12px}
.mk-dash-cal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-weight:800;font-size:13px}
.mk-dash-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center}
.mk-dash-cal-wd{font-size:10px;opacity:.7;padding:2px 0}
.mk-dash-cal-day{height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.mk-dash-cal-day.is-today{background:#C9A227;color:#12231f}
.mk-dash-cal-day.is-empty{opacity:0}
.mk-dash-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.mk-dash-stat{
  border:1px solid var(--border);border-radius:18px;padding:16px 16px 14px;cursor:pointer;
  background:var(--surface);transition:transform .15s ease, box-shadow .15s ease;
}
.mk-dash-stat:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(0,0,0,.12)}
.mk-dash-stat b{display:block;font-size:32px;line-height:1;margin:8px 0 4px}
.mk-dash-stat span{font-size:12px;color:var(--text-secondary);font-weight:800}
.mk-dash-stat.gold{background:linear-gradient(180deg,rgba(201,162,39,.16),var(--surface))}
.mk-dash-stat.teal{background:linear-gradient(180deg,rgba(14,124,110,.16),var(--surface))}
.mk-dash-stat.blue{background:linear-gradient(180deg,rgba(29,111,184,.14),var(--surface))}
.mk-dash-stat.rose{background:linear-gradient(180deg,rgba(192,57,43,.14),var(--surface))}
.mk-dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mk-dash-card{border:1px solid var(--border);border-radius:18px;background:var(--surface);overflow:hidden}
.mk-dash-card h3{margin:0;padding:12px 16px;border-bottom:1px solid var(--border);font-size:14px}
.mk-dash-list{padding:8px 10px 12px;display:flex;flex-direction:column;gap:8px;max-height:280px;overflow:auto}
.mk-dash-row{display:flex;justify-content:space-between;gap:8px;padding:10px 10px;border-radius:12px;background:var(--bg);font-size:12px}
.mk-dash-row strong{display:block}
.mk-dash-empty{padding:18px;text-align:center;color:var(--text-secondary);font-size:12px}
.mk-dash-visit{padding:12px 14px 14px}
.mk-dash-visit-day{font-weight:900;margin:4px 0 8px;font-size:13px}
.mk-vitem{display:grid;grid-template-columns:88px 1fr auto;gap:8px;align-items:center;padding:10px;border-radius:12px;border:1px solid var(--border);margin-bottom:8px;background:var(--bg)}
.mk-vtime{font-weight:900;color:#0E7C6E;font-size:13px}
@media(max-width:900px){
  .mk-dash-hero,.mk-dash-stats,.mk-dash-grid,.mk-vitem{grid-template-columns:1fr}
  .mk-dash-clock{font-size:32px}
}
</style>
<div role="tabpanel" class="tab-content active" id="tab-dashboard">
  <div class="mk-dash">
    <div class="mk-dash-hero">
      <div>
        <div class="mk-dash-kicker">COMMAND CENTER</div>
        <div class="mk-dash-clock" id="mkDashClock">—:—</div>
        <div class="mk-dash-date" id="mkDashDate">در حال بارگذاری تقویم…</div>
        <div style="margin-top:12px;font-size:13px;opacity:.85">کارهای معوق، بازدیدهای امروز و فردا، و تیکت‌های باز در یک نگاه.</div>
      </div>
      <div class="mk-dash-cal">
        <div class="mk-dash-cal-head">
          <span id="mkDashCalTitle">—</span>
          <span style="font-size:11px;opacity:.75">تقویم شمسی</span>
        </div>
        <div class="mk-dash-cal-grid" id="mkDashCalHead"></div>
        <div class="mk-dash-cal-grid" id="mkDashCalBody"></div>
      </div>
    </div>
    <div class="mk-dash-stats">
      <div class="mk-dash-stat gold" data-goto="ads">
        <span>آگهی منتظر تأیید</span>
        <b id="mkDashPendingAds">…</b>
      </div>
      <div class="mk-dash-stat teal" data-goto="requests">
        <span>درخواست ملک جدید</span>
        <b id="mkDashNewReqs">…</b>
      </div>
      <div class="mk-dash-stat blue" data-goto="visits">
        <span>بازدید جدید</span>
        <b id="mkDashNewVisits">…</b>
      </div>
      <div class="mk-dash-stat rose" data-goto="support">
        <span>تیکت باز</span>
        <b id="mkDashNewTickets">…</b>
      </div>
    </div>
    <div class="mk-dash-grid">
      <div class="mk-dash-card">
        <h3>بازدیدهای امروز</h3>
        <div class="mk-dash-visit" id="mkDashVisitsToday"><div class="mk-dash-empty">در حال بارگذاری…</div></div>
      </div>
      <div class="mk-dash-card">
        <h3>بازدیدهای فردا</h3>
        <div class="mk-dash-visit" id="mkDashVisitsTomorrow"><div class="mk-dash-empty">در حال بارگذاری…</div></div>
      </div>
      <div class="mk-dash-card">
        <h3>آگهی‌های در صف تأیید</h3>
        <div class="mk-dash-list" id="mkDashAdsList"></div>
      </div>
      <div class="mk-dash-card">
        <h3>درخواست‌ها و تیکت‌ها</h3>
        <div class="mk-dash-list" id="mkDashSideList"></div>
      </div>
    </div>
  </div>
</div>
