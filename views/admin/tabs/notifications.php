<?php
/**
 * Melkino V2 — admin notifications tab (fragment from admin-notifications.php; only onclick
 * converted to data-act; root file kept for the panel AND the ?action= API).
 */
?>
<div role="tabpanel" class="tab-content" id="tab-notifications">
<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('bell') ?> اعلان‌ها</span>
        <button type="button" class="btn-secondary" style="padding:6px 14px;font-size:12px;" data-act="notif-reload"><?= melkinoSvgIcon('restore') ?> به‌روزرسانی</button>
    </div>
    <div style="padding:0 16px 16px;">
        <div id="notifStatsRow" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px;"></div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('gear') ?> رویدادهای اعلان سیستمی</span>
        <button type="button" class="btn-secondary" style="padding:6px 14px;font-size:12px;" data-act="notif-events-reload"><?= melkinoSvgIcon('restore') ?> به‌روزرسانی</button>
    </div>
    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:10px;">
            فهرست کامل ارتباطات خودکار سیستم با کاربران. هر رویداد را می‌توانید جداگانه فعال یا غیرفعال کنید.
            <br>سوئیچ اصلی «اعلان‌ها» در تب <strong>تنظیمات عمومی</strong> بالادست همهٔ رویدادهاست؛ اگر خاموش باشد هیچ اعلان خودکاری ارسال نمی‌شود.
            <span id="notifEventsMasterNote" style="font-weight:700;"></span>
        </div>
        <div id="notifEventsContainer"><div class="admin-field-help">در حال بارگذاری…</div></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center;">
            <button type="button" class="btn-primary" style="padding:8px 18px;font-size:13px;" data-act="notif-events-save"><?= melkinoSvgIcon('save') ?> ذخیرهٔ تنظیمات اعلان‌ها</button>
            <span id="notifEventsStatus" class="admin-status-msg"></span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('megaphone') ?> ارسال اعلان عمومی</span>
    </div>
    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:10px;">
            این اعلان برای <strong>همه کاربران</strong> ارسال می‌شود و در صفحه «اعلان‌ها»ی هر کس نمایش داده می‌شود.
            هر کاربر می‌تواند نسخه خودش را بخواند یا حذف کند.
        </div>
        <label class="admin-field-label">عنوان</label>
        <input type="text" id="broadcastTitle" class="admin-input" style="width:100%;box-sizing:border-box;" placeholder="مثلاً: 🎉 جشنواره فروش ویژه ملکینو" maxlength="200">
        <label class="admin-field-label" style="margin-top:10px;display:block;">متن اعلان</label>
        <textarea id="broadcastMessage" class="admin-input" rows="3" style="width:100%;box-sizing:border-box;padding:10px;font-family:inherit;font-size:13px;resize:vertical;" placeholder="متن کامل اعلان..."></textarea>
        <label class="admin-field-label" style="margin-top:10px;display:block;">لینک (اختیاری)</label>
        <input type="text" id="broadcastUrl" class="admin-input" style="width:100%;box-sizing:border-box;" dir="ltr" placeholder="properties.php یا https://...">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center;">
            <button type="button" class="btn-primary" style="padding:8px 18px;font-size:13px;" data-act="notif-broadcast"><?= melkinoSvgIcon('send') ?> ارسال برای همه</button>
            <span id="broadcastStatus" class="admin-status-msg"></span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('list') ?> اعلان‌های عمومی ارسال‌شده</span>
    </div>
    <div id="broadcastsListContainer" style="padding:0 16px 16px;"></div>
</div>
</div>
