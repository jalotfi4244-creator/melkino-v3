<?php
/** Melkino V2 — admin password tab. Fragment VERBATIM from admin-panel.php (tab-password div). Panel-shell saveAndExit button (outside the tab) intentionally not ported. */
?>
<div
    role="tabpanel"
    class="tab-content"
    id="tab-password"
>

    <div class="admin-card">

        <div class="card-header">

            <span class="card-title">
                تغییر رمز
            </span>

        </div>

        <div id="passwordContainer">
    <div class="admin-static-security">
        <div class="admin-section-head">
            <div>
                <div class="admin-section-title"><?= melkinoSvgIcon('lock') ?> امنیت پنل مدیریت</div>
                <div class="admin-section-help">رمز عبور، نشست ادمین و محافظت از ورود از همین بخش مدیریت می‌شود.</div>
            </div>
        </div>
        <div class="admin-grid-2">
            <div class="admin-field"><label>رمز فعلی</label><input type="password" placeholder="رمز فعلی" autocomplete="current-password"></div>
            <div class="admin-field"><label>رمز جدید</label><input type="password" placeholder="حداقل ۸ کاراکتر" autocomplete="new-password"></div>
            <div class="admin-field"><label>تکرار رمز جدید</label><input type="password" placeholder="تکرار رمز جدید" autocomplete="new-password"></div>
            <div class="admin-security-summary"><strong>امنیت فعال</strong><span>قفل ورود • لاگ ورود • Timeout نشست</span></div>
        </div>
        <div class="admin-security-note">برای مدیریت کامل تنظیمات امنیتی، تب تغییر رمز پس از بارگذاری JavaScript تکمیل می‌شود.</div>
    </div>
</div>

    </div>

</div>
