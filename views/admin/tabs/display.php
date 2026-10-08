<?php
/** Melkino V2 — admin display tab. Fragment VERBATIM from admin-panel.php (DISPLAY section). */
?>

<!-- =========================================================
     DISPLAY — مدیریت کارت‌های آگهی (راند ۲۰)
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-display"
>
    <!-- راند ۲۹: ساب‌تب‌های سیستم مدیریت فیلدها -->
    <div class="fd-subtabs">
        <button type="button" class="fd-subtab active" data-fdsub="home" data-act="fd-sub" data-sub="home"><svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg> کارت صفحهٔ اصلی</button>
        <button type="button" class="fd-subtab" data-fdsub="details" data-act="fd-sub" data-sub="details"><svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/><path d="M9 13h6M9 17h4"/></svg> صفحهٔ جزئیات ملک</button>
        <button type="button" class="fd-subtab" data-fdsub="cards" data-act="fd-sub" data-sub="cards"><svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M9 9v11"/></svg> کارت فهرست/VIP و حباب‌ها</button>
    </div>

    <!-- ============ ساب‌تب: کارت صفحهٔ اصلی (راند ۲۹) ============ -->
    <div class="fd-subpane" id="fdSubHome">
        <div class="admin-card">
            <div class="card-header">
                <span class="card-title"><?= melkinoSvgIcon('puzzle') ?> مدیریت فیلدهای کارت صفحهٔ اصلی</span>
                <span id="fdHomeMeta" class="fd-meta"></span>
            </div>
            <div style="padding:0 16px 16px;">
                <div class="admin-field-help" style="margin-bottom:12px;">
                    ترتیب فیلدها را با <strong>کشیدن و رها کردن</strong> عوض کنید؛ برای هر فیلد
                    <strong>نمایش/عدم نمایش</strong>، <strong>عنوان</strong>، <strong>آیکون</strong>،
                    <strong>حالت (حباب/چیپ)</strong>، <strong>فرمت ارقام</strong> و
                    <strong>نمایش در موبایل/تبلت/دسکتاپ</strong> را تنظیم کنید.
                    پیش‌نمایش پایین با <strong>آگهی واقعی</strong> و <strong>رندرر واقعی سایت</strong> زنده به‌روز می‌شود.
                    تا «ذخیره» را نزنید هیچ تغییری در دیتابیس ثبت نمی‌شود.
                    این تب <strong>فقط کارت‌های صفحهٔ اصلی (خانه)</strong> را تنظیم می‌کند؛
                    فهرست آگهی‌ها و VIP تب جدا دارند. پیش‌نمایش پایین همزمان با هر تغییر به‌روز می‌شود.
                </div>
                <div class="fd-toolbar">
                    <label class="fd-toolbar-label">آگهی نمونه:
                        <select id="fdHomeSample" class="fd-select"></select>
                    </label>
                    <span class="fd-devices" id="fdHomeDevices">
                        <button type="button" data-w="320">320</button>
                        <button type="button" data-w="375">375</button>
                        <button type="button" data-w="390" class="on">390</button>
                        <button type="button" data-w="430">430</button>
                        <button type="button" data-w="768">768</button>
                        <button type="button" data-w="820">820</button>
                        <button type="button" data-w="1024">1024</button>
                        <button type="button" data-w="1280">1280</button>
                        <button type="button" data-w="1440">1440</button>
                    </span>
                    <button type="button" class="btn-secondary fd-btn-sm" data-act="fd-refresh" data-scope="home">↻ پیش‌نمایش</button>
                </div>
                <div id="fdHomeFields"><div class="admin-field-help">در حال بارگذاری…</div></div>
                <div class="fd-actions">
                    <button type="button" class="btn-primary" data-act="fd-save" data-scope="home"><?= melkinoSvgIcon('save') ?>  ذخیرهٔ تغییرات</button>
                    <button type="button" class="btn-secondary" data-act="fd-cancel" data-scope="home"><?= melkinoSvgIcon('x') ?> لغو</button>
                    <button type="button" class="btn-secondary" id="fdHomeRestore" data-act="fd-restore" data-scope="home">↩ بازگردانی آخرین تنظیمات</button>
                    <span id="fdHomeStatus" class="admin-status-msg"></span>
                </div>
            </div>
        </div>
        <div class="admin-card">
            <div class="card-header"><span class="card-title"><?= melkinoSvgIcon('eye') ?> پیش‌نمایش واقعی کارت (رندرر سایت + دادهٔ واقعی)</span></div>
            <div style="padding:14px 16px;">
                <div class="fd-device-frame"><iframe id="fdHomePreview" title="پیش‌نمایش کارت صفحهٔ اصلی"></iframe></div>
            </div>
        </div>
    </div>

    <!-- ============ ساب‌تب: صفحهٔ جزئیات (راند ۲۹) ============ -->
    <div class="fd-subpane" id="fdSubDetails" style="display:none">
        <div class="admin-card">
            <div class="card-header">
                <span class="card-title"><?= melkinoSvgIcon('list') ?> مدیریت بخش‌ها و فیلدهای صفحهٔ جزئیات ملک</span>
                <span id="fdDetailsMeta" class="fd-meta"></span>
            </div>
            <div style="padding:0 16px 16px;">
                <div class="admin-field-help" style="margin-bottom:12px;">
                    بخش‌های صفحهٔ جزئیات (گالری، اطلاعات اصلی، قیمت، مشخصات، امکانات، توضیحات، نوار تماس) را
                    با <strong>کشیدن و رها کردن</strong> جابه‌جا، خاموش/روشن کنید و عنوان و آیکونشان را تغییر دهید.
                    با «▸ مدیریت فیلدها» فیلدهای داخل هر بخش (و مشخصات هر نوع ملک) را مدیریت کنید.
                </div>
                <div class="fd-toolbar">
                    <label class="fd-toolbar-label">آگهی نمونه:
                        <select id="fdDetailsSample" class="fd-select"></select>
                    </label>
                    <span class="fd-devices" id="fdDetailsDevices">
                        <button type="button" data-w="320">320</button>
                        <button type="button" data-w="375">375</button>
                        <button type="button" data-w="390" class="on">390</button>
                        <button type="button" data-w="430">430</button>
                        <button type="button" data-w="768">768</button>
                        <button type="button" data-w="820">820</button>
                        <button type="button" data-w="1024">1024</button>
                        <button type="button" data-w="1280">1280</button>
                        <button type="button" data-w="1440">1440</button>
                    </span>
                    <button type="button" class="btn-secondary fd-btn-sm" data-act="fd-refresh" data-scope="details">↻ پیش‌نمایش</button>
                </div>
                <div id="fdDetailsSections"><div class="admin-field-help">در حال بارگذاری…</div></div>
                <div class="fd-actions">
                    <button type="button" class="btn-primary" data-act="fd-save" data-scope="details"><?= melkinoSvgIcon('save') ?>  ذخیرهٔ تغییرات</button>
                    <button type="button" class="btn-secondary" data-act="fd-cancel" data-scope="details"><?= melkinoSvgIcon('x') ?> لغو</button>
                    <button type="button" class="btn-secondary" id="fdDetailsRestore" data-act="fd-restore" data-scope="details">↩ بازگردانی آخرین تنظیمات</button>
                    <span id="fdDetailsStatus" class="admin-status-msg"></span>
                </div>
            </div>
        </div>
        <div class="admin-card">
            <div class="card-header"><span class="card-title"><?= melkinoSvgIcon('eye') ?> پیش‌نمایش واقعی صفحهٔ جزئیات</span></div>
            <div style="padding:14px 16px;">
                <div class="fd-device-frame"><iframe id="fdDetailsPreview" title="پیش‌نمایش صفحهٔ جزئیات"></iframe></div>
            </div>
        </div>
    </div>

    <!-- ============ ساب‌تب: تنظیمات فعلی کارت‌های فهرست/VIP ============ -->
    <div class="fd-subpane" id="fdSubCards" style="display:none">
    <div class="admin-card">
        <div class="card-header">
            <span class="card-title"><?= melkinoSvgIcon('image') ?> نمایش کارت‌های آگهی</span>
            <button type="button" class="btn-secondary" style="padding:6px 14px;font-size:12px;" data-act="cd-init">↻ به‌روزرسانی</button>
        </div>
        <div style="padding:0 16px 16px;">
            <div class="admin-field-help" style="margin-bottom:12px;">
                برای <strong>هر فیلد آگهی</strong> (مشخصات عمومی + تمام فیلدهای تخصصی هر نوع ملک) حالت نمایش را انتخاب کنید:
                <strong>متن</strong> (چیپ کوچک زیر عنوان)، <strong>حباب</strong> (بج رنگی) یا <strong>مخفی</strong>.
                فیلدهای تخصصی فقط وقتی روی کارت می‌آیند که در آن آگهی <strong>پر شده باشند</strong>.
                این تب <strong>فقط کارت‌های «همه آگهی‌ها» و VIP</strong> را تنظیم می‌کند (جدا از کارت خانه).
                پیش‌نمایش پایین همزمان با هر کلیک به‌روز می‌شود؛ برای اعمال در سایت «ذخیره» را بزنید.
            </div>
            <div id="cardDisplayControls"><div class="admin-field-help">در حال بارگذاری…</div></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;align-items:center;">
                <button type="button" class="btn-primary" style="padding:8px 18px;font-size:13px;" data-act="cd-save"><?= melkinoSvgIcon('save') ?>  ذخیرهٔ تنظیمات نمایش</button>
                <button type="button" class="btn-secondary" style="padding:8px 18px;font-size:13px;" data-act="cd-reset">↺ بازنشانی به پیش‌فرض</button>
                <span id="cardDisplayStatus" class="admin-status-msg"></span>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header">
            <span class="card-title"><?= melkinoSvgIcon('eye') ?> پیش‌نمایش زندهٔ کارت‌ها</span>
        </div>
        <div style="padding:14px 16px;">
            <div id="cardDisplayPreview" class="cd-preview-wrap"></div>
        </div>
    </div>
    </div><!-- /fdSubCards -->
</div>
