<?php
/** One preload/login document for Telegram, Bale, Eitaa, ordinary web and V2. */
declare(strict_types=1);
require_once __DIR__.'/messenger-login-lib.php';
melkinoMessengerSessionStart();
require_once __DIR__.'/config.php';
require_once __DIR__.'/bot-settings.php';
global $pdo;
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header_remove('X-Frame-Options');
$nonce=base64_encode(random_bytes(18));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://tapi.bale.ai https://developer.eitaa.com; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self' https://web.telegram.org https://*.telegram.org https://bale.ai https://*.bale.ai https://ble.ir https://eitaa.com https://*.eitaa.com");
melkinoMessengerSessionCookie();
if(empty($_SESSION['mk_login_probe']))$_SESSION['mk_login_probe']=bin2hex(random_bytes(16));
$hint=isset($melkinoForcedMessenger)&&isset(melkinoMessengerNames()[$melkinoForcedMessenger])?$melkinoForcedMessenger:melkinoMessengerHint();
$next=melkinoMessengerTarget($melkinoLoginNext??$_GET['redirect']??'home.php');
$providers=[];
foreach(melkinoMessengerNames()as$p=>$label){
    $links=melkinoMessengerLinks($p);
    $providers[$p]=['label'=>$label,'enabled'=>melkinoLoginMethodEnabled($p),'sdk'=>melkinoMessengerSdk($p),
        'launch'=>$links['launch'],'bot'=>$links['bot'],'entry'=>$p.'-app.php'];
}
$cfg=['hint'=>$hint,'next'=>$next,'providers'=>$providers,'csrf'=>melkinoCsrfToken(),
    'sessionProbe'=>$_SESSION['mk_login_probe'],'endpoint'=>'messenger-auth.php','sms'=>melkinoLoginMethodEnabled('sms'),
    'version'=>'login-v3'];
$esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-login-version="3">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="<?= $esc($cfg['csrf']) ?>">
<meta name="theme-color" content="#0b5d59">
<title>ورود به ملکینو</title>
<script nonce="<?= $esc($nonce) ?>">
// Capture before any vendor SDK can consume/change the fragment. Memory only.
window.__mkLaunch = { hash: location.hash || '', search: location.search || '' };
try { var t=localStorage.getItem('melkino_theme'); if(t==='dark')document.documentElement.dataset.theme='dark'; } catch(e){}
</script>
<link rel="stylesheet" href="assets/css/messenger-login.css?v=<?= (int)filemtime(__DIR__.'/assets/css/messenger-login.css') ?>">
<script id="mkLoginConfig" type="application/json"><?= json_encode($cfg,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script src="assets/js/messenger-bridge.js?v=<?= (int)filemtime(__DIR__.'/assets/js/messenger-bridge.js') ?>" defer></script>
<script src="assets/js/messenger-login.js?v=<?= (int)filemtime(__DIR__.'/assets/js/messenger-login.js') ?>" defer></script>
</head>
<body>
<main class="mk-login-shell">
    <section class="mk-login-card" aria-labelledby="mkLoginTitle">
        <header class="mk-login-brand"><span class="mk-login-mark" aria-hidden="true">م</span><div><strong>ملکینو</strong><small>انتخابی فراتر از یک ملک</small></div><span class="mk-login-secure">ورود امن</span></header>
        <h1 id="mkLoginTitle">به ملکینو خوش آمدید</h1>
        <p class="mk-login-intro">یک مسیر ورود، برای همهٔ پیام‌رسان‌ها</p>
        <ol class="mk-login-steps" aria-label="مراحل ورود">
            <li data-stage="detect">۱. پیام‌رسان</li><li data-stage="verify">۲. تأیید هویت</li><li data-stage="session">۳. ورود</li>
        </ol>
        <div class="mk-login-progress">
            <span class="mk-login-spinner" id="mkLoginSpinner" aria-hidden="true"></span>
            <div><span id="mkProviderLabel" class="mk-login-badge">در حال بررسی</span><p id="mkLoginStatus" role="status" aria-live="polite">در حال آماده‌سازی ورود…</p></div>
        </div>
        <section id="mkProviderChoice" class="mk-login-choice" aria-labelledby="mkChooseTitle" hidden>
            <h2 id="mkChooseTitle">از کدام برنامه وارد شده‌اید؟</h2>
            <div class="mk-provider-grid">
                <?php foreach($providers as$p=>$provider): if(!$provider['enabled'])continue; ?>
                <button type="button" data-provider="<?= $esc($p) ?>" aria-pressed="false"><?= $esc($provider['label']) ?><span>انتخاب</span></button>
                <?php endforeach; ?>
            </div>
            <p id="mkReopenHelp" class="mk-login-help">اگر هویت دریافت نشد، <strong>برنامک را کاملاً ببندید و از دکمهٔ اجرای آن در ربات دوباره باز کنید.</strong> باز کردن لینک معمولی سایت، الزاماً ورود مینی‌اپ نیست.</p>
        </section>
        <div class="mk-login-actions">
            <button type="button" id="mkLoginRetry" class="mk-primary" hidden>تلاش دوباره در همین صفحه</button>
            <a id="mkOpenApp" class="mk-primary" href="#" rel="noopener noreferrer" hidden>باز کردن برنامک</a>
            <a id="mkOpenBot" class="mk-secondary" href="#" rel="noopener noreferrer" hidden>باز کردن صفحهٔ ربات</a>
            <button type="button" id="mkCloseApp" class="mk-secondary" hidden>بستن برنامک</button>
            <button type="button" id="mkContinueSession" class="mk-secondary" hidden>ادامه با نشست فعلی سایت</button>
        </div>
        <?php if($cfg['sms']): ?>
        <details class="mk-login-sms" id="mkSmsPanel">
            <summary>ورود با شماره موبایل</summary>
            <p class="mk-login-help">این روش جدا از هویت پیام‌رسان است.</p>
            <label for="smsPhone">شماره موبایل</label>
            <div class="mk-sms-row"><input id="smsPhone" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="09123456789" maxlength="16"><button id="smsSendBtn" type="button" class="mk-secondary">دریافت کد</button></div>
            <div id="mkSmsCodeRow" hidden><label for="smsCode">کد ورود</label><div class="mk-sms-row"><input id="smsCode" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="6"><button id="smsVerifyBtn" type="button" class="mk-primary">تأیید و ورود</button></div></div>
            <p id="smsMsg" role="status" aria-live="polite"></p>
        </details>
        <?php endif; ?>
        <details class="mk-login-diag"><summary>راهنمای رفع مشکل</summary><p>فقط کد وضعیت زیر را برای پشتیبانی بفرستید؛ نیازی به توکن یا اطلاعات حساب نیست.</p><output id="mkLoginDiagnostic" dir="ltr">START</output></details>
        <noscript><p class="mk-login-help">برای ورود، JavaScript باید فعال باشد.</p></noscript>
    </section>
    <p class="mk-login-foot">هویت فقط پس از بررسی امضای پیام‌رسان در سرور تأیید می‌شود.</p>
</main>
</body>
</html>
