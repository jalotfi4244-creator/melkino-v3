<?php
/** Melkino V2 — admin contact tab. Fragment VERBATIM from admin-panel.php
 * (contact section incl. its own <style>); inline JS region moved to
 * admin-tab-contact.js. 8 fragment onclick -> data-act + delegation. */
?>
<!-- =========================================================
     CONTACT
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-contact"
>

    <div class="admin-card">

        <div class="card-header">

            <div>

                <span class="card-title">
                    <?= melkinoSvgIcon('phone') ?> اطلاعات تماس ملکینو
                </span>

                <div
                    style="
                        font-size:12px;
                        color:var(--text-secondary);
                        margin-top:5px;
                    "
                >
                    اطلاعات این بخش مستقیماً در صفحه «ارتباط با ما» نمایش داده می‌شود.
                </div>

            </div>


            <span
                id="contactSaveState"
                style="
                    font-size:12px;
                    color:var(--text-secondary);
                "
            >
                آماده ویرایش
            </span>

        </div>


        <div id="contactContainer">
<style>
/* =========================================================
   تنظیمات مشاور
   ========================================================= */
.consultant-admin-card{
    margin-top:18px;
    padding:16px;
    border-radius:14px;
    border:1px solid rgba(212,175,55,.22);
    background:
        linear-gradient(145deg,rgba(212,175,55,.055),rgba(6,78,78,.025));
}
.consultant-admin-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.consultant-admin-title{
    font-size:15px;
    font-weight:900;
    color:var(--text-primary);
}
.consultant-admin-help{
    margin-top:4px;
    font-size:11px;
    line-height:1.8;
    color:var(--text-secondary);
}
.consultant-admin-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:11px;
}
.consultant-admin-field{
    display:flex;
    flex-direction:column;
    gap:6px;
}
.consultant-admin-field.full{
    grid-column:1/-1;
}
.consultant-admin-field label{
    font-size:12px;
    color:var(--text-secondary);
    font-weight:700;
}
.consultant-admin-field input{
    width:100%;
    box-sizing:border-box;
    padding:10px 11px;
    border:1px solid var(--border);
    border-radius:9px;
    background:var(--bg);
    color:var(--text-primary);
    font-family:'Vazirmatn',sans-serif;
    font-size:13px;
    outline:none;
}
.consultant-admin-field input:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 2px rgba(6,78,78,.08);
}
.consultant-admin-preview{
    margin-top:14px;
    padding:12px;
    border-radius:11px;
    border:1px solid var(--border);
    background:var(--bg);
}
.consultant-admin-preview-title{
    font-size:12px;
    font-weight:800;
    color:var(--text-primary);
    margin-bottom:9px;
}
.consultant-preview-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
}
.consultant-preview-item{
    padding:9px 10px;
    border-radius:9px;
    background:var(--surface);
    border:1px solid var(--border);
}
.consultant-preview-item span{
    display:block;
    font-size:10px;
    color:var(--text-secondary);
    margin-bottom:3px;
}
.consultant-preview-item strong{
    display:block;
    font-size:12px;
    color:var(--text-primary);
    word-break:break-word;
}
#consultantSaveState{
    font-size:11px;
    color:var(--text-secondary);
}
@media(max-width:700px){
    .consultant-admin-grid,
    .consultant-preview-grid{
        grid-template-columns:1fr;
    }
}


/* =========================================================
   MELKINO COMMAND CENTER — VISIBLE REDESIGN
   ========================================================= */
.admin-command-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin:0 0 14px;
    padding:14px 18px;
    border-radius:20px;
    background:linear-gradient(135deg,#043b3b 0%,#064e4e 55%,#0a6661 100%);
    border:1px solid rgba(212,175,55,.22);
    box-shadow:0 16px 38px rgba(6,78,78,.18);
    color:#fff;
}
.admin-command-brand{display:flex;align-items:center;gap:11px;min-width:0}
.admin-command-mark{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(145deg,#f0d878,#c89d32);color:#173131;font-size:22px;font-weight:950;box-shadow:0 8px 18px rgba(212,175,55,.22)}
.admin-command-kicker{font-size:9px;letter-spacing:1.4px;opacity:.62;font-weight:900}
.admin-command-title{font-size:17px;font-weight:950;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.admin-command-meta{display:flex;align-items:center;gap:7px;flex:0 0 auto;font-size:10px;font-weight:800;color:rgba(255,255,255,.74)}
.admin-command-date{padding:6px 9px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10)}
.admin-live-dot{width:7px;height:7px;border-radius:50%;background:#67e8a5;box-shadow:0 0 12px rgba(103,232,165,.7)}
.tabs-container{
    position:sticky !important;
    top:0 !important;
    z-index:8000 !important;
    gap:7px !important;
    padding:8px !important;
    border:1px solid var(--border) !important;
    border-radius:18px !important;
    background:color-mix(in srgb,var(--surface) 96%,transparent) !important;
    box-shadow:0 10px 25px rgba(0,0,0,.06) !important;
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
}
.tab-btn{
    min-height:42px !important;
    padding:0 14px !important;
    border:1px solid transparent !important;
    border-bottom:none !important;
    border-radius:12px !important;
    background:transparent !important;
    color:var(--text-secondary) !important;
    font-size:12px !important;
}
.tab-btn:hover{background:var(--bg-secondary) !important;color:var(--primary) !important}
.tab-btn.active{
    background:linear-gradient(135deg,var(--primary),var(--primary-dark)) !important;
    color:#fff !important;
    border-color:transparent !important;
    box-shadow:0 7px 18px rgba(6,78,78,.18) !important;
}
.support-filter-btn.active{background:var(--primary) !important;color:#fff !important;border-color:var(--primary) !important}
.support-status-badge.status-open{background:#FEF3C7 !important;color:#92400E !important}
.support-status-badge.status-answered{background:#DCFCE7 !important;color:#166534 !important}
.support-status-badge.status-pending{background:#DBEAFE !important;color:#1E40AF !important}
.support-status-badge.status-closed{background:#F3F4F6 !important;color:#6B7280 !important}
.admin-static-security{padding:2px 0}
.admin-security-summary{min-height:50px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--bg);display:flex;flex-direction:column;justify-content:center;gap:4px}
.admin-security-summary strong{color:var(--primary);font-size:13px}
.admin-security-summary span{color:var(--text-secondary);font-size:10px}
.admin-security-note{margin-top:12px;padding:11px 13px;border-radius:12px;background:var(--gold-bg);color:var(--text-secondary);font-size:10px;line-height:1.9}
@media(max-width:680px){
    .admin-command-header{align-items:flex-start;flex-direction:column}
    .admin-command-title{font-size:15px}
    .admin-command-meta{width:100%;justify-content:space-between}
    .tabs-container{overflow-x:auto;scrollbar-width:none}
    .tabs-container::-webkit-scrollbar{display:none}
    .tab-btn{flex:0 0 auto}
}
[data-theme="dark"] .admin-command-header{box-shadow:0 16px 38px rgba(0,0,0,.32)}
</style>


            <div class="contact-admin-grid">

                <!-- نام مجموعه -->

                <div class="contact-admin-field full">

                    <label for="adminContactAgencyName">
                        نام مجموعه
                    </label>

                    <input
                        type="text"
                        id="adminContactAgencyName"
                        placeholder="مثلاً املاک ملکینو شاهرود"
                    >

                </div>


                <!-- آدرس -->

                <div class="contact-admin-field full">

                    <label for="adminContactAddress">
                        آدرس دفتر
                    </label>

                    <textarea
                        id="adminContactAddress"
                        placeholder="آدرس کامل دفتر ملکینو"
                    ></textarea>

                </div>

                <!-- راند ۷۴: نقشهٔ زنده برای انتخاب موقعیت دفتر -->
                <div class="contact-admin-field full">
                    <label>موقعیت دفتر روی نقشه</label>
                    <div class="contact-admin-help" style="margin-bottom:8px;">
                        روی نقشه بزنید یا نشان را بکشید تا موقعیت دفتر انتخاب شود.
                        همین نقطه در صفحهٔ «ارتباط با ما» روی نقشهٔ زنده دیده می‌شود.
                    </div>
                    <div id="adminOfficeMap" style="height:280px;width:100%;border-radius:12px;border:1px solid var(--border);background:var(--bg-secondary);z-index:1;"></div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;align-items:flex-end;">
                        <div class="contact-admin-field" style="flex:1;min-width:140px;margin:0;">
                            <label for="adminOfficeLat">عرض جغرافیایی</label>
                            <input type="text" id="adminOfficeLat" dir="ltr" placeholder="36.4181" autocomplete="off">
                        </div>
                        <div class="contact-admin-field" style="flex:1;min-width:140px;margin:0;">
                            <label for="adminOfficeLng">طول جغرافیایی</label>
                            <input type="text" id="adminOfficeLng" dir="ltr" placeholder="54.9763" autocomplete="off">
                        </div>
                        <input type="hidden" id="adminOfficeZoom" value="15">
                        <button type="button" class="btn-secondary" data-act="ct-loc">موقعیت فعلی من</button>
                    </div>
                </div>


                <!-- تلفن -->

                <div class="contact-admin-field">

                    <label for="adminContactPhone">
                        شماره تماس
                    </label>

                    <input
                        type="text"
                        id="adminContactPhone"
                        placeholder="مثلاً ۰۲۳-۳۲۲۲۲۲۲۲"
                        dir="ltr"
                    >

                </div>


                <!-- ایمیل -->

                <div class="contact-admin-field">

                    <label for="adminContactEmail">
                        ایمیل پشتیبانی
                    </label>

                    <input
                        type="email"
                        id="adminContactEmail"
                        placeholder="info@melkino.ir"
                        dir="ltr"
                    >

                </div>


                <!-- ساعت کاری -->

                <div class="contact-admin-field full">

                    <label for="adminContactWorkingHours">
                        ساعت کاری
                    </label>

                    <input
                        type="text"
                        id="adminContactWorkingHours"
                        placeholder="شنبه تا پنجشنبه، ۹ صبح تا ۸ شب"
                    >

                </div>


                <!-- واتساپ -->

                <div class="contact-admin-field">

                    <label for="adminContactWhatsapp">
                        واتساپ
                    </label>

                    <input
                        type="text"
                        id="adminContactWhatsapp"
                        placeholder="شماره یا لینک واتساپ"
                        dir="ltr"
                    >

                </div>


                <!-- تلگرام -->

                <div class="contact-admin-field">

                    <label for="adminContactTelegram">
                        لینک کانال تلگرام
                    </label>

                    <input
                        type="text" inputmode="url"
                        id="adminContactTelegram"
                        placeholder="https://t.me/..."
                        dir="ltr"
                    >

                    <div class="contact-admin-help">
                        مثال:
                        https://t.me/melkino
                    </div>

                    <label for="adminContactTelegramColor" style="margin-top:10px">رنگ کارت تلگرام</label>
                    <input type="color" id="adminContactTelegramColor" value="#174D46">

                    <label for="adminContactTelegramDesc" style="margin-top:10px">خط سوم کارت تلگرام</label>
                    <input type="text" id="adminContactTelegramDesc" placeholder="مثلاً مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو">

                </div>


                <!-- اینستاگرام -->

                <div class="contact-admin-field">

                    <label for="adminContactInstagram">
                        لینک اینستاگرام
                    </label>

                    <input
                        type="text" inputmode="url"
                        id="adminContactInstagram"
                        placeholder="https://instagram.com/..."
                        dir="ltr"
                    >

                    <div class="contact-admin-help">
                        مثال:
                        https://instagram.com/melkino
                    </div>

                    <label for="adminContactInstagramColor" style="margin-top:10px">رنگ کارت اینستاگرام</label>
                    <input type="color" id="adminContactInstagramColor" value="#4B3D32">

                    <label for="adminContactInstagramDesc" style="margin-top:10px">خط سوم کارت اینستاگرام</label>
                    <input type="text" id="adminContactInstagramDesc" placeholder="مثلاً تصاویر، فایل‌ها و محتوای اختصاصی ملکینو">

                </div>

                <!-- بله -->

                <div class="contact-admin-field">

                    <label for="adminContactBale">
                        لینک کانال بله
                    </label>

                    <input
                        type="text" inputmode="url"
                        id="adminContactBale"
                        placeholder="https://ble.ir/..."
                        dir="ltr"
                    >

                    <div class="contact-admin-help">
                        مثال:
                        https://ble.ir/melkino
                    </div>

                    <label for="adminContactBaleColor" style="margin-top:10px">رنگ کارت بله</label>
                    <input type="color" id="adminContactBaleColor" value="#4AB06A">

                    <label for="adminContactBaleDesc" style="margin-top:10px">خط سوم کارت بله</label>
                    <input type="text" id="adminContactBaleDesc" placeholder="مثلاً مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو">

                    <label style="margin-top:10px">لوگوی کانال بله</label>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:6px;">
                        <img id="adminBaleLogoPreview" alt="" style="display:none;width:48px;height:48px;object-fit:contain;border-radius:10px;border:1px solid var(--border);background:#fff;">
                        <input type="file" id="adminBaleLogoFile" accept="image/png,image/jpeg,image/webp,image/gif" style="font-size:12px;max-width:180px;">
                        <button type="button" class="btn-icon-sm primary" data-act="ct-bale-up">آپلود لوگو</button>
                        <button type="button" class="btn-secondary" data-act="ct-bale-rm">حذف</button>
                    </div>
                    <div class="contact-admin-help">همین لوگو روی کارت بله در صفحهٔ ارتباط با ما نمایش داده می‌شود.</div>

                </div>



            </div>


            <!-- =====================================================
                 کارت‌های سفارشیِ صفحه‌ی «ارتباط با ما»
                 ===================================================== -->

            <div class="consultant-admin-card" style="border-style:solid;">

                <div class="consultants-toolbar">

                    <div>

                        <div class="consultant-admin-title">
                            <?= melkinoSvgIcon('puzzle') ?> کارت‌های سفارشیِ «ارتباط با ما»
                        </div>

                        <div class="consultant-admin-help">
                            کارت خالی نشان داده نمی‌شود. با دکمهٔ + کارت جدید بسازید؛
                            برای هر کارت متن دکمه، نام پیام‌رسان، آدرس کانال و آیکون را تنظیم کنید.
                        </div>

                    </div>

                </div>

                <div
                    id="customCardsManager"
                    class="consultant-manager-list"
                ></div>

            </div>



            <!-- =====================================================
                 مدیریت حرفه‌ای مشاوران
                 ===================================================== -->
            <div class="consultant-admin-card" style="border-style:solid;">
                <div class="consultants-toolbar">
                    <div>
                        <div class="consultant-admin-title"><?= melkinoSvgIcon('users') ?> مدیریت مشاوران</div>
                        <div class="consultant-admin-help">حداکثر ۱۰ مشاور، برای هر مشاور حداکثر ۱۰ تخصص؛ هر تخصص با نوع ملک + نوع معامله تعریف می‌شود. اگر چند مشاور یک تخصص را داشته باشند، اولویت کمتر برنده است و در نبود مشاور تخصصی، مشاور پیش‌فرض استفاده می‌شود.</div>
                    </div>
                    <button type="button" class="btn-icon-sm gold" data-act="ct-add-con">＋ افزودن مشاور</button>
                </div>
                <div id="consultantsManager" class="consultant-manager-list"></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
                    <button type="button" class="btn-icon-sm primary" data-act="ct-save-con" style="min-width:210px;height:46px;font-size:14px;"><?= melkinoSvgIcon('save') ?>  ذخیره همه مشاوران</button>
                    <button type="button" class="btn-secondary" data-act="ct-load-con" style="min-height:46px;">↻ بازیابی</button>
                    <span id="consultantsSaveState" style="align-self:center;font-size:11px;color:var(--text-secondary);"></span>
                </div>
            </div>

            <!-- Preview -->

            <div class="contact-admin-preview">

                <div class="contact-admin-preview-title">
                    پیش‌نمایش اطلاعات تماس
                </div>


                <div class="contact-preview-grid">

                    <div>

                        <span>
                            نام مجموعه
                        </span>

                        <strong id="previewAgencyName">
                            -
                        </strong>

                    </div>


                    <div>

                        <span>
                            تلفن
                        </span>

                        <strong id="previewPhone">
                            -
                        </strong>

                    </div>


                    <div>

                        <span>
                            تلگرام
                        </span>

                        <strong id="previewTelegram">
                            ثبت نشده
                        </strong>

                    </div>


                    <div>

                        <span>
                            اینستاگرام
                        </span>

                        <strong id="previewInstagram">
                            ثبت نشده
                        </strong>

                    </div>

                </div>

            </div>


            <div class="contact-save-note">

                تغییرات این بخش با دکمه زیر ذخیره می‌شوند و صفحه «ارتباط با ما»
                به صورت خودکار اطلاعات ذخیره‌شده را نمایش می‌دهد.

            </div>


            <div
                style="
                    display:flex;
                    gap:10px;
                    flex-wrap:wrap;
                    margin-top:18px;
                "
            >

                <button
                    type="button"
                    class="btn-icon-sm primary"
                    data-act="ct-save"
                    style="
                        min-width:190px;
                        height:46px;
                        font-size:14px;
                    "
                >
                    <?= melkinoSvgIcon('save') ?>  ذخیره اطلاعات تماس
                </button>


                <button
                    type="button"
                    class="btn-secondary"
                    data-act="ct-load"
                    style="min-height:46px;"
                >
                    ↻ بازیابی اطلاعات ذخیره‌شده
                </button>

            </div>

        </div>

    </div>

</div>

