<?php
/** Melkino V2 — admin support tab. Fragment VERBATIM from admin-panel.php (SUPPORT section). */
?>
<!-- =========================================================
     SUPPORT
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-support"
>

    <div class="admin-card">

        <div class="card-header">
            <span class="card-title"><?= melkinoSvgIcon('headset') ?> تیکت‌های پشتیبانی</span>
            <span id="supportTicketCount" class="admin-section-help"></span>
        </div>

        <div class="stats-grid" style="padding:0 16px;">

            <div class="stat-card">
                <div class="number" id="supportStatOpen">…</div>
                <div class="label">تیکت باز</div>
            </div>

            <div class="stat-card">
                <div class="number" id="supportStatTotal">…</div>
                <div class="label">کل تیکت‌ها</div>
            </div>

        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap; padding:0 16px 12px;">
            <button type="button" class="btn-secondary support-filter-btn active" data-status="all" data-act="sup-filter" data-f="all">همه</button>
            <button type="button" class="btn-secondary support-filter-btn" data-status="open" data-act="sup-filter" data-f="open">در انتظار بررسی</button>
            <button type="button" class="btn-secondary support-filter-btn" data-status="answered" data-act="sup-filter" data-f="answered">پاسخ داده‌شده</button>
            <button type="button" class="btn-secondary support-filter-btn" data-status="closed" data-act="sup-filter" data-f="closed">بسته‌شده</button>
            <button type="button" class="btn-secondary" data-act="sup-reload" style="margin-inline-start:auto;">↻ بروزرسانی</button>
        </div>

        <div id="supportTicketsList" style="padding:0 16px 16px;">در حال بارگذاری…</div>

    </div>

    <div class="admin-card" id="supportConversationCard" style="display:none;">

        <div class="card-header">
            <span class="card-title" id="supportConversationTitle">گفتگو</span>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn-secondary" id="supportCloseBtn" data-act="sup-status" data-st="close">بستن تیکت</button>
                <button type="button" class="btn-secondary" id="supportReopenBtn" data-act="sup-status" data-st="reopen" style="display:none;">بازگشایی تیکت</button>
                <button type="button" class="btn-secondary" data-act="sup-hide">✕ بستن</button>
            </div>
        </div>

        <div id="supportMessages" style="padding:16px; display:flex; flex-direction:column; gap:10px; max-height:420px; overflow-y:auto;"></div>

        <div style="display:flex; gap:8px; padding:16px; border-top:1px solid var(--border);">
            <textarea id="supportReplyText" rows="2" placeholder="پاسخ خود را بنویسید..." style="flex:1; resize:vertical; border:1px solid var(--border); border-radius:8px; padding:10px; font-family:inherit; background:var(--bg); color:var(--text-primary);"></textarea>
            <button type="button" class="btn-primary" data-act="sup-reply">ارسال پاسخ</button>
        </div>

    </div>

</div>
