<?php
/** Melkino V2 — admin bots tab. Fragment VERBATIM from admin-bots.php (after API branch);
 * wrapper from panel. Root file untouched (still serves API). */
?>
<div
    role="tabpanel"
    class="tab-content"
    id="tab-bots"
>
<?php require_once dirname(__DIR__,4) . '/admin-bot-controls-view.php'; ?>
<!-- =========================================================
     روش‌های ورود کاربران
     =========================================================
     ادمین انتخاب می‌کند کاربران با کدام روش‌ها بتوانند وارد شوند.
     روشِ غیرفعال، هم از صفحه‌ی ورود حذف می‌شود و هم اندپوینتِ
     احراز هویتِ مربوطه در سمت سرور ۴۰۳ برمی‌گرداند. -->
<div class="admin-card" data-bot-section="methods">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('lock') ?> روش‌های ورود کاربران</span>
    </div>

    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:10px;">
            روش‌های غیرفعال، دکمه‌شان در صفحهٔ ورود (<span dir="ltr">login.php</span>) نمایش داده نمی‌شود
            و تلاش برای ورود از آن مسیر هم در سمت سرور رد می‌شود.
            <b>حداقل یکی از روش‌ها باید فعال بماند.</b>
        </div>

        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e2e2;border-radius:10px;margin-bottom:8px;cursor:pointer;">
            <input disabled type="checkbox" id="loginTelegramEnabled" style="width:18px;height:18px;accent-color:var(--primary);">
            <span style="font-size:14px;"><?= melkinoSvgIcon('send') ?> ورود با تلگرام <span style="color:var(--text-muted);font-size:12px;">(مینی‌اپ ربات تلگرام)</span></span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e2e2;border-radius:10px;margin-bottom:8px;cursor:pointer;">
            <input disabled type="checkbox" id="loginBaleEnabled" style="width:18px;height:18px;accent-color:var(--primary);">
            <span style="font-size:14px;"><?= melkinoSvgIcon('chat') ?> ورود با بله <span style="color:var(--text-muted);font-size:12px;">(مینی‌اپ ربات بله)</span></span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e2e2;border-radius:10px;margin-bottom:8px;cursor:pointer;">
            <input disabled type="checkbox" id="loginEitaaEnabled" style="width:18px;height:18px;accent-color:var(--primary);">
            <span style="font-size:14px;">ورود با ایتا <span style="color:var(--text-muted);font-size:12px;">(برنامک رسمی؛ هویت امضاشده، بدون نیاز به شماره برای ورود)</span></span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e2e2;border-radius:10px;cursor:pointer;">
            <input disabled type="checkbox" id="loginSmsEnabled" style="width:18px;height:18px;accent-color:var(--primary);">
            <span style="font-size:14px;"><?= melkinoSvgIcon('phone') ?> ورود با پیامک <span style="color:var(--text-muted);font-size:12px;">(شماره موبایل + کد یک‌بارمصرف)</span></span>
        </label>

        <div class="admin-field-help" style="margin-top:8px;">
            برای ورود پیامکی، کد ابتدا از طریق ربات تلگرام/بله (اگر شماره قبلاً شناخته شود)،
            بعد از پنل پیامک ارسال می‌شود؛ در نبود درگاه، ارسال ناموفق گزارش می‌شود.
            برای اعمال تیک‌ها، دکمهٔ «ذخیره روش‌های ورود» در همین کارت را بزنید؛ ذخیرهٔ هر ربات هم تیک تغییرکردهٔ خودش را ثبت می‌کند.
        </div>
    </div>
    <?php melkinoBotSectionControls('methods'); ?>
</div>

<div class="admin-card" data-bot-section="telegram">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('bot') ?> ربات تلگرام</span>
        
    </div>

    <div style="padding:0 16px 16px;">
        <label class="admin-field-label">توکن ربات تلگرام</label>
        <input disabled type="password" id="botTelegramToken" class="admin-input" dir="ltr" placeholder="123456789:AAE..." autocomplete="off">
        <div class="admin-field-help">از @BotFather دریافت می‌شود. پس از ذخیره، امضای ورود کاربران با این توکن بررسی می‌شود.</div>

        <label class="admin-field-label">شناسه کانال</label>
        <input disabled type="text" id="botTelegramChannel" class="admin-input" dir="ltr" placeholder="@melkino_shahrood">
        <div class="admin-field-help">آگهی‌های منتشرشده می‌توانند به این کانال ارسال شوند.</div>

        <label class="admin-field-label">نام کاربری ربات (بدون @)</label>
        <input disabled type="text" id="botTelegramUsername" class="admin-input" dir="ltr" placeholder="melkino_bot">
        <label class="admin-field-label" for="botTelegramMiniappUrl">پیوند اجرای مستقیم برنامک تلگرام (اختیاری)</label>
        <input disabled type="url" id="botTelegramMiniappUrl" class="admin-input" dir="ltr" placeholder="https://t.me/your_bot?startapp">
        <div class="admin-field-help">پیوند رسمی را از تنظیمات بازو/ربات کپی کنید. بدون این مقدار، پیوند مینی‌اپ اصلی و صفحهٔ ربات بر اساس نام کاربری ساخته می‌شوند.</div>
        <label class="admin-field-label" for="botTelegramEntryUrl">آدرس مقصد برای ثبت در تنظیمات مینی‌اپ تلگرام</label>
        <input disabled type="url" id="botTelegramEntryUrl" class="admin-input" dir="ltr" readonly value="telegram-app.php">
        <div class="admin-field-help">همین نشانی نهایی HTTPS را ثبت کنید؛ ورود همهٔ پیام‌رسان‌ها ظاهر و منطق شروع یکسان دارد و پیش از دریافت هویت ریدایرکت نمی‌کند.</div>

        <div class="admin-field-help">برای ساخت دکمه‌ی «ورود از طریق تلگرام» در مرورگر معمولی استفاده می‌شود.</div>

        
    </div>
    <?php melkinoBotSectionControls('telegram'); ?>
</div>

<div class="admin-card" data-bot-section="bale">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('chat') ?> ربات بله</span>
        
    </div>

    <div style="padding:0 16px 16px;">
        <label class="admin-field-label">توکن ربات بله</label>
        <input disabled type="password" id="botBaleToken" class="admin-input" dir="ltr" placeholder="387417012:..." autocomplete="off">
        <div class="admin-field-help">از پنل توسعه‌دهندگان بله (یا @botfather_bale) دریافت می‌شود. خالی بگذار تا مقدار قبلی حفظ شود؛ برای پاک‌کردن «-» وارد کن.</div>

        <label class="admin-field-label">شناسه کانال بله</label>
        <input disabled type="text" id="botBaleChannel" class="admin-input" dir="ltr" placeholder="@melkino">
        <div class="admin-field-help">
            می‌تواند با @ (مانند <span dir="ltr">@melkino</span>) یا شناسه‌ی عددی کانال باشد.
            ربات باید در کانال، ادمین با اجازه‌ی ارسال باشد.
        </div>

        <label class="admin-field-label">نام کاربری ربات (بدون @)</label>
        <input disabled type="text" id="botBaleUsername" class="admin-input" dir="ltr" placeholder="melkino_bot">
        <label class="admin-field-label" for="botBaleMiniappUrl">پیوند اجرای مستقیم برنامک بله (اختیاری)</label>
        <input disabled type="url" id="botBaleMiniappUrl" class="admin-input" dir="ltr" placeholder="https://ble.ir/your_bot?startapp">
        <div class="admin-field-help">پیوند رسمی را از تنظیمات بازو/ربات کپی کنید. بدون این مقدار، پیوند مینی‌اپ اصلی و صفحهٔ ربات بر اساس نام کاربری ساخته می‌شوند.</div>
        <label class="admin-field-label" for="botBaleEntryUrl">آدرس مقصد برای ثبت در تنظیمات مینی‌اپ بله</label>
        <input disabled type="url" id="botBaleEntryUrl" class="admin-input" dir="ltr" readonly value="bale-app.php">
        <div class="admin-field-help">همین نشانی نهایی HTTPS را ثبت کنید؛ ورود همهٔ پیام‌رسان‌ها ظاهر و منطق شروع یکسان دارد و پیش از دریافت هویت ریدایرکت نمی‌کند.</div>


        
        <div class="admin-field-help" style="margin-top:6px">
            اگر هنگامِ انتشار خطای
            <span dir="ltr">Unauthorized</span>
            می‌بینی، یعنی خودِ توکن پذیرفته نشده است (نه شناسه‌ی کانال). با
            این دکمه مشخص می‌شود بله این توکن را قبول دارد یا نه، و کدام
            ربات به آن وصل است.
        </div>

        <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:13px;margin-top:8px;" data-act="bots-bale-find">
            <?= melkinoSvgIcon('search') ?> یافتن شناسه‌ی کانال (از پیام‌های اخیرِ ربات)
        </button>
        <div class="admin-field-help" style="margin-top:6px">
            اگر هنگامِ انتشار خطای
            <span dir="ltr">no such group or user</span>
            می‌بینی، یعنی ربات این شناسه را نمی‌شناسد. با این دکمه فهرستِ
            گفتگوهایی که ربات اخیراً دیده را ببین و شناسه‌ی <b>عددیِ</b> کانال را
            انتخاب کن؛ شناسه‌ی عددی از آیدیِ @ بسیار مطمئن‌تر است.
        </div>
        <div id="baleChatsBox" style="display:none;margin-top:10px;padding:10px;border:1px solid #e2e2e2;border-radius:10px;background:#fafafa"></div>

        
    </div>
    <?php melkinoBotSectionControls('bale'); ?>
</div>

<div class="admin-card" data-bot-section="eitaa">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('bot') ?> برنامه / برنامک ایتا</span>
        
    </div>

    <div style="padding:0 16px 16px;">
        <label class="admin-field-label">توکن برنامه ایتا</label>
        <input disabled type="password" id="botEitaaToken" class="admin-input" dir="ltr" placeholder="123456789:AAE..." autocomplete="off">
        <div class="admin-field-help">
            از پنل <b>ایتایار</b> برای همان برنامه‌ای که برنامک ملکینو را باز می‌کند بگیر.
            این توکن برای اعتبارسنجی <span dir="ltr">initData</span> سمت سرور لازم است.
            خالی بگذار تا مقدار قبلی حفظ شود؛ برای پاک‌کردن «-» وارد کن.
        </div>

        <label class="admin-field-label">نام کاربری برنامه ایتا (بدون @)</label>
        <input disabled type="text" id="botEitaaUsername" class="admin-input" dir="ltr" placeholder="melkino_bot">
        <label class="admin-field-label" for="botEitaaMiniappUrl">پیوند مستقیم برنامک در ایتا</label>
        <input disabled type="url" id="botEitaaMiniappUrl" class="admin-input" dir="ltr" placeholder="https://eitaa.com/your_app/your_miniapp" autocomplete="off">
        <div class="admin-field-help">پیوند برنامک را از ایتایار کپی کنید. این پیوند با آدرس تارنمای شما متفاوت است.</div>
        <label class="admin-field-label" for="botEitaaEntryUrl">آدرس تارنما / مقصد برای ثبت در ایتایار</label>
        <input disabled type="url" id="botEitaaEntryUrl" class="admin-input" dir="ltr" readonly value="eitaa-app.php">
        <div class="admin-field-help">نشانی کامل بالا را با HTTPS در ایتایار ثبت کنید. این صفحه پیش از ورود ریدایرکت نمی‌کند و فقط SDK رسمی ایتا را بارگذاری می‌کند.</div>
        <div class="admin-field-help">
            برای لینک «باز کردن در ایتا» وقتی کاربر صفحهٔ ورود را در مرورگر معمولی باز کند.
            آدرس برنامک را در ایتایار روی همین سایت تنظیم کن و از داخل برنامه بازش کن تا ورود خودکار شود.
        </div>
    </div>
    <?php melkinoBotSectionControls('eitaa'); ?>
</div>

<div class="admin-card" data-bot-section="eitaa_channel">
    <div class="card-header"><span class="card-title">کانال ایتا — API ایتایار</span></div>
    <div style="padding:0 16px 16px;">
        <p class="admin-field-help">این بخش مخصوص <b>انتشار در کانال</b> است؛ توکن API ایتایار با توکن ورود برنامک یکی نیست. کانال را در پنل ایتایار اضافه کنید و برنامهٔ <span dir="ltr">@sender</span> را طبق راهنمای ایتایار مدیر کانال کنید.</p>
        <label class="admin-field-label" for="botEitaaChannelToken">توکن API ارسال به کانال</label>
        <input disabled type="password" id="botEitaaChannelToken" class="admin-input" dir="ltr" autocomplete="new-password" placeholder="توکن کامل از بخش API ایتایار">
        <div class="admin-field-help">توکن کامل را بدون تغییر پیشوند کپی کنید. خالی یعنی حفظ توکن ذخیره‌شده؛ برای پاک‌کردن مقدار دیتابیس «-» وارد کنید.</div>
        <label class="admin-field-label" for="botEitaaChannel">شناسهٔ کانال در ایتایار یا نام کانال</label>
        <input disabled type="text" id="botEitaaChannel" class="admin-input" dir="ltr" placeholder="1404 یا @your_channel" autocomplete="off">
        <div class="admin-field-help">تست اتصال، اعتبار API را بررسی می‌کند؛ آزمون ارسال فقط با تأیید شما یک پیام واقعی در همین کانال می‌فرستد. انتشار آگهی از تب «آگهی‌ها» و پس از پیش‌نمایش انجام می‌شود.</div>
    </div>
    <?php melkinoBotSectionControls('eitaa_channel'); ?>
</div>


<div class="admin-card" data-bot-section="proxy">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('globe') ?> پروکسی ارتباط با پیام‌رسان‌ها</span>
    </div>

    <div style="padding:0 16px 16px;">
        <label class="admin-field-label">نشانی پروکسی (اختیاری)</label>
        <input disabled type="text" id="botProxy" class="admin-input" dir="ltr" placeholder="http://user:pass@1.2.3.4:8080" autocomplete="off">
        <div class="admin-field-help">
            اگر هاست شما به <span dir="ltr">api.telegram.org</span> دسترسی ندارد
            (برای هاست‌های داخل ایران معمول است)، نشانی یک پروکسی را اینجا وارد کنید تا
            تستِ اتصال و انتشارِ آگهی از طریق آن انجام شود. از
            <span dir="ltr">http://</span> و <span dir="ltr">socks5://</span>
            پشتیبانی می‌شود. در غیر این صورت این فیلد را خالی بگذارید.
        </div>
    </div>
    <?php melkinoBotSectionControls('proxy'); ?>
</div>

<div class="admin-card" data-bot-section="sms">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('phone') ?> پنل پیامک</span>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
            <input disabled type="checkbox" id="smsEnabled" style="width:18px;height:18px;accent-color:var(--primary);">
            فعال باشد
        </label>
    </div>

    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:10px;">
            برای ارسال پیامک واقعی، پنل و اطلاعات اتصال باید معتبر باشند؛ بدون درگاه ارسال، موفقیت یا تحویل پیامک فرض نمی‌شود.
        </div>

        <label class="admin-field-label">سرویس‌دهنده</label>
        <select disabled id="smsProvider" class="admin-input" style="max-width:260px;">
            <option value="melipayamak">ملی‌پیامک (rest.payamak-panel.com)</option>
            <option value="generic">سرویس عمومی دیگر (api_key + api_url)</option>
        </select>
        <div class="admin-field-help">اتصال برنامهٔ پیامک ملکینو برای ملی‌پیامک تنظیم شده است.</div>

        <label class="admin-field-label">نشانی API سرویس پیامک</label>
        <input disabled type="text" id="smsApiUrl" class="admin-input" dir="ltr" placeholder="https://rest.payamak-panel.com/api/SendSMS/SendSMS" autocomplete="off">
        <div class="admin-field-help">برای ملی‌پیامک خالی بگذار (آدرس رسمی خودکار استفاده می‌شود).</div>

        <label class="admin-field-label">نام کاربری سامانه پیامک</label>
        <input disabled type="text" id="smsApiKey" class="admin-input" dir="ltr" placeholder="..." autocomplete="off">
        <div class="admin-field-help">نام کاربری پنل ملی‌پیامک. خالی = حفظ مقدار قبلی؛ پاک‌کردن: «-».</div>

        <label class="admin-field-label">رمز عبور سامانه پیامک</label>
        <input disabled type="password" id="smsApiPassword" class="admin-input" dir="ltr" placeholder="..." autocomplete="new-password">
        <div class="admin-field-help">رمز پنل ملی‌پیامک. خالی = حفظ مقدار قبلی؛ پاک‌کردن: «-».</div>

        <label class="admin-field-label">خط خدماتی (OTP و اطلاع‌رسانی)</label>
        <input disabled type="text" id="smsSenderLine" class="admin-input" dir="ltr" placeholder="مثلاً 5000...">
        <div class="admin-field-help">خط اصلی ارسال: کد ورود، جستجوی ذخیره‌شده، انطباق درخواست‌ها و هشدارها.</div>

        <label class="admin-field-label">خط OTP (اختیاری)</label>
        <input disabled type="text" id="smsOtpLine" class="admin-input" dir="ltr" placeholder="خالی = همان خط خدماتی">
        <div class="admin-field-help">اگر برای کد ورود خط جداگانه داری؛ در غیر این صورت خالی بگذار.</div>

        <label class="admin-field-label">کد الگوی OTP — bodyId (اختیاری)</label>
        <input disabled type="text" id="smsOtpBodyId" class="admin-input" dir="ltr" placeholder="مثلاً 100024...">
        <div class="admin-field-help">اگر در ملی‌پیامک پترن «کد تایید» ساختی، کدش را بگذار تا ارسال با الگوی تأییدشده انجام شود. در این حالت متن از سامانه می‌آید و قالب زیر استفاده نمی‌شود.</div>

        <label class="admin-field-label">متن پیامک کد ورود</label>
        <textarea disabled id="smsOtpTemplate" class="admin-input" rows="2" placeholder="کد ورود ملکینو: {code} (اعتبار ۲ دقیقه)"></textarea>
        <div class="admin-field-help">متغیر {code} جای کد می‌نشیند. خالی = متن پیش‌فرض.</div>

        <label class="admin-field-label">خط تبلیغاتی (کمپین‌ها)</label>
        <input disabled type="text" id="smsPromoLine" class="admin-input" dir="ltr" placeholder="مثلاً 3000...">
        <div class="admin-field-help">طبق مقررات، کمپین‌های تبلیغاتی فقط از خط تبلیغاتی و فقط ۸ صبح تا ۲۲ شب ارسال می‌شوند.</div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center;">
            <input disabled type="text" id="smsTestPhone" class="admin-input" dir="ltr" placeholder="09123456789" style="max-width:170px;">
            
        </div>
        <div class="admin-field-help" style="margin-top:6px">ابتدا تنظیمات همین بخش را ذخیره کنید؛ آزمون پیامک از تنظیمات ذخیره‌شده استفاده می‌کند.</div>
    </div>
    <?php melkinoBotSectionControls('sms'); ?>
</div>

<div class="admin-field-help" style="padding:12px 16px;">ذخیره و تست هر بخش مستقل است؛ خطای ربات دیگر مانع ذخیرهٔ این بخش نمی‌شود. توکن‌ها در لاگ‌ها نمایش داده نمی‌شوند.<span id="botSettingsStatus" hidden></span></div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('edit') ?> محتوای انتشار در کانال تلگرام</span>
    </div>

    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:8px;">
            انتخاب کن کدام فیلدهای آگهی در پیام کانال تلگرام بیاید.
            می‌توانی برای هر <b>نوع ملک</b> و <b>نوع معامله</b> تنظیمِ جداگانه ذخیره کنی؛
            با انتخاب هر نوع ملک، <b>فقط فیلدهای مرتبط با همان نوع</b> فهرست می‌شوند
            (مثلاً «تعداد اتاق» و «طبقه» برای زمین نمایش داده نمی‌شوند).
            ترکیبی که تنظیم اختصاصی ندارد از «تنظیمات عمومی» استفاده می‌کند:
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
            <label style="font-size:13px;"><?= melkinoSvgIcon('tag', 'mk-icon mk-icon--sm') ?> نوع ملک:
                <select id="publishTypeTelegram" class="admin-input" onchange="melkinoReloadPublish('telegram')" style="padding:6px 10px;font-size:13px;min-width:140px;"></select>
            </label>
            <label style="font-size:13px;"><?= melkinoSvgIcon('pin', 'mk-icon mk-icon--sm') ?> نوع معامله:
                <select id="publishTransTelegram" class="admin-input" onchange="melkinoReloadPublish('telegram')" style="padding:6px 10px;font-size:13px;min-width:140px;"></select>
            </label>
            <button type="button" class="btn-secondary" id="publishResetTelegram" data-act="bots-pub-reset" data-pf="telegram" style="display:none;padding:6px 12px;font-size:12px;" title="تنظیم اختصاصی این ترکیب را حذف می‌کند و به تنظیمات عمومی برمی‌گرداند"><?= melkinoSvgIcon('trash') ?> حذف تنظیم این ترکیب</button>
            <span id="publishComboStatusTelegram" style="font-size:12px;color:var(--text-muted);"></span>
        </div>
        <div id="publishFieldsTelegram" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-bottom:12px;">
            <span style="font-size:12px;color:var(--text-muted);">در حال بارگذاری…</span>
        </div>

        <label class="admin-field-label">متن ثابت بالای آگهی‌ها (اختیاری)</label>
        <textarea id="publishHeaderTelegram" rows="2" class="admin-input" style="width:100%;box-sizing:border-box;padding:10px;font-family:inherit;font-size:13px;resize:vertical;" placeholder="این متن بالای همه‌ی آگهی‌های کانال نمایش داده می‌شود"></textarea>

        <label class="admin-field-label" style="margin-top:10px;display:block;">متن ثابت پایین آگهی‌ها (اختیاری)</label>
        <textarea id="publishFooterTelegram" rows="2" class="admin-input" style="width:100%;box-sizing:border-box;padding:10px;font-family:inherit;font-size:13px;resize:vertical;" placeholder="این متن پایین همه‌ی آگهی‌های کانال نمایش داده می‌شود"></textarea>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center;">
            <button type="button" class="btn-primary" style="padding:8px 16px;font-size:13px;" data-act="bots-pub-save" data-pf="telegram"><?= melkinoSvgIcon('save') ?> ذخیره</button>
            <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:13px;" data-act="bots-pub-prev" data-pf="telegram"><?= melkinoSvgIcon('eye') ?> پیش‌نمایش</button>
            <span id="publishStatusTelegram" class="admin-status-msg"></span>
        </div>
        <pre id="publishPreviewTelegram" dir="auto" style="display:none;white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:12px;line-height:2;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;margin-top:10px;max-height:320px;overflow:auto;"></pre>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('edit') ?> محتوای انتشار در کانال بله</span>
    </div>

    <div style="padding:0 16px 16px;">
        <div class="admin-field-help" style="margin-bottom:8px;">
            انتخاب کن کدام فیلدهای آگهی در پیام کانال بله بیاید.
            می‌توانی برای هر <b>نوع ملک</b> و <b>نوع معامله</b> تنظیمِ جداگانه ذخیره کنی؛
            با انتخاب هر نوع ملک، <b>فقط فیلدهای مرتبط با همان نوع</b> فهرست می‌شوند
            (مثلاً «تعداد اتاق» و «طبقه» برای زمین نمایش داده نمی‌شوند).
            ترکیبی که تنظیم اختصاصی ندارد از «تنظیمات عمومی» استفاده می‌کند:
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
            <label style="font-size:13px;"><?= melkinoSvgIcon('tag', 'mk-icon mk-icon--sm') ?> نوع ملک:
                <select id="publishTypeBale" class="admin-input" onchange="melkinoReloadPublish('bale')" style="padding:6px 10px;font-size:13px;min-width:140px;"></select>
            </label>
            <label style="font-size:13px;"><?= melkinoSvgIcon('pin', 'mk-icon mk-icon--sm') ?> نوع معامله:
                <select id="publishTransBale" class="admin-input" onchange="melkinoReloadPublish('bale')" style="padding:6px 10px;font-size:13px;min-width:140px;"></select>
            </label>
            <button type="button" class="btn-secondary" id="publishResetBale" data-act="bots-pub-reset" data-pf="bale" style="display:none;padding:6px 12px;font-size:12px;" title="تنظیم اختصاصی این ترکیب را حذف می‌کند و به تنظیمات عمومی برمی‌گرداند"><?= melkinoSvgIcon('trash') ?> حذف تنظیم این ترکیب</button>
            <span id="publishComboStatusBale" style="font-size:12px;color:var(--text-muted);"></span>
        </div>
        <div id="publishFieldsBale" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-bottom:12px;">
            <span style="font-size:12px;color:var(--text-muted);">در حال بارگذاری…</span>
        </div>

        <label class="admin-field-label">متن ثابت بالای آگهی‌ها (اختیاری)</label>
        <textarea id="publishHeaderBale" rows="2" class="admin-input" style="width:100%;box-sizing:border-box;padding:10px;font-family:inherit;font-size:13px;resize:vertical;" placeholder="این متن بالای همه‌ی آگهی‌های کانال نمایش داده می‌شود"></textarea>

        <label class="admin-field-label" style="margin-top:10px;display:block;">متن ثابت پایین آگهی‌ها (اختیاری)</label>
        <textarea id="publishFooterBale" rows="2" class="admin-input" style="width:100%;box-sizing:border-box;padding:10px;font-family:inherit;font-size:13px;resize:vertical;" placeholder="این متن پایین همه‌ی آگهی‌های کانال نمایش داده می‌شود"></textarea>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center;">
            <button type="button" class="btn-primary" style="padding:8px 16px;font-size:13px;" data-act="bots-pub-save" data-pf="bale"><?= melkinoSvgIcon('save') ?> ذخیره</button>
            <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:13px;" data-act="bots-pub-prev" data-pf="bale"><?= melkinoSvgIcon('eye') ?> پیش‌نمایش</button>
            <span id="publishStatusBale" class="admin-status-msg"></span>
        </div>
        <pre id="publishPreviewBale" dir="auto" style="display:none;white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:12px;line-height:2;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;margin-top:10px;max-height:320px;overflow:auto;"></pre>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('compass') ?> راهنمای هاست‌هایی که دسترسیِ خروجی ندارند</span>
    </div>
    <div style="padding:14px 16px;color:var(--text-muted);font-size:13px;line-height:2;">
        <p style="margin:0 0 10px">
            بعضی هاست‌ها — از جمله <b>InfinityFree</b> — ارتباطِ خروجیِ سرور با
            <span dir="ltr">api.telegram.org</span> را به‌طور کامل مسدود کرده‌اند.
            در این حالت هر کاری که «سرور» انجام دهد با خطا مواجه می‌شود،
            <b>حتی وقتی توکن کاملاً سالم است</b>.
        </p>
        <p style="margin:0 0 10px">
            برای حل این مشکل، سامانه به‌صورت خودکار ابتدا از
            <b>مرورگرِ خودِ شما</b> با تلگرام/بله ارتباط برقرار می‌کند و فقط اگر
            مرورگر هم موفق نشد، از سرور امتحان می‌کند. بنابراین:
        </p>
        <ul style="margin:0 18px 10px;padding:0">
            <li>اگر مرورگر شما به تلگرام دسترسی دارد (مثلاً فیلترشکن روشن است)
                → <b>همه چیز کار می‌کند</b>: تست اتصال و انتشار آگهی.</li>
            <li>اگر مرورگر شما هم به تلگرام دسترسی ندارد
                → یک پروکسی در کارتِ بالا ثبت کنید، یا سایت را به هاستی منتقل کنید
                که ارتباطِ خروجیِ آزاد داشته باشد.</li>
        </ul>
        <p style="margin:0">
            برای این‌که بفهمید دقیقاً کدام حالت برقرار است، به تب
            <b>«عیب‌یاب»</b> بروید و دکمهٔ <b>«شروع تست»</b> را بزنید. در گروه
            «ربات و کانال»، موردِ <b>«ارتباط مستقیم از مرورگر»</b> تعیین‌کننده است.
        </p>
    </div>
</div>

</div>
