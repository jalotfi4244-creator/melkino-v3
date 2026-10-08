<?php
/**
 * Melkino V2 — admin 2FA (TOTP): setup / verify / manage.
 * Standalone page, no inline scripts. Reuses the admin-login look.
 * Vars: $mode, $error, $success, $secret, $uri, $username, $redirect, $csrf.
 */
$mode = (string)($mode ?? 'verify');
$titles = [
    'setup' => 'فعال‌سازی ورود دومرحله‌ای',
    'verify' => 'تأیید دومرحله‌ای',
    'manage' => 'ورود دومرحله‌ای فعال است',
    'unavailable' => 'ورود دومرحله‌ای',
];
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>ملکینو - <?= e($titles[$mode] ?? 'ورود دومرحله‌ای') ?></title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="design-pro.css?v=<?= (int)@filemtime(MELKINO_ROOT . '/design-pro.css') ?>">
    <?= \Melkino\Support\Assets::css('assets/css/admin-login-legacy.css') ?>
</head>
<body>
    <div class="app-container" style="padding-bottom: 0;">
        <header class="topbar">
            <a href="admin.php" class="topbar-action"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
            <span class="topbar-title"><?= e($titles[$mode] ?? 'ورود دومرحله‌ای') ?></span>
            <div style="width:24px;"></div>
        </header>
        <div class="main-content">
            <div class="login-box">
                <div class="login-title"><?= melkinoSvgIcon('lock') ?> ملکینو</div>

                <?php if (($error ?? '') !== ''): ?>
                <div class="login-error"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if (($success ?? '') !== ''): ?>
                <div class="login-error" style="background:#e8f7ee;border-color:#bfe6cd;color:#14663a;"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($mode === 'setup'): ?>
                <div class="login-sub">۱. در اپ احراز هویت (Google Authenticator و مشابه)، «ورود دستی» را بزنید و این کلید را وارد کنید:</div>
                <div dir="ltr" style="font-family:monospace;font-size:15px;font-weight:700;letter-spacing:1px;text-align:center;background:#f3f4ef;border:1px dashed #9aa5a1;border-radius:10px;padding:12px 8px;margin:10px 0;word-break:break-all;user-select:all;"><?= e(chunk_split($secret, 4, ' ')) ?></div>
                <div class="login-sub">۲. سپس کد ۶رقمی نمایش‌داده‌شده در اپ را زیر وارد کنید تا فعال‌سازی کامل شود.</div>
                <form method="POST">
                    <?= $csrf ?>
                    <input type="hidden" name="action" value="setup_confirm">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                    <input type="text" class="login-input" name="code" placeholder="کد ۶رقمی اپ" required inputmode="numeric" autocomplete="one-time-code" dir="ltr" style="text-align:center;letter-spacing:4px;font-size:18px;">
                    <button type="submit" class="login-btn">تأیید و فعال‌سازی</button>
                </form>
                <div class="login-sub" style="margin-top:12px;">رشته‌ی استاندارد (برای انتقال دستی به اپ دیگر):</div>
                <div dir="ltr" style="font-family:monospace;font-size:10px;color:#5b6f6c;word-break:break-all;background:#fafbf8;border:1px solid #e3e7e2;border-radius:8px;padding:8px;user-select:all;"><?= e($uri) ?></div>

                <?php elseif ($mode === 'verify'): ?>
                <div class="login-sub">برای «<?= e($username) ?>» ورود دومرحله‌ای فعال است؛ کد ۶رقمی فعلی اپ را وارد کنید.</div>
                <form method="POST">
                    <?= $csrf ?>
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                    <input type="text" class="login-input" name="code" placeholder="کد ۶رقمی اپ" required inputmode="numeric" autocomplete="one-time-code" dir="ltr" style="text-align:center;letter-spacing:4px;font-size:18px;" autofocus>
                    <button type="submit" class="login-btn">تأیید و ورود</button>
                </form>

                <?php elseif ($mode === 'manage'): ?>
                <div class="login-sub">حساب «<?= e($username) ?>» با دوعاملی محافظت می‌شود و این نشست تأیید شده است.</div>
                <a href="<?= e($redirect) ?>" class="login-btn" style="display:block;text-align:center;text-decoration:none;">ادامه به پنل مدیریت</a>
                <div class="login-sub" style="margin-top:14px;">غیرفعال‌سازی (نیازمند کد فعلی اپ):</div>
                <form method="POST">
                    <?= $csrf ?>
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                    <input type="text" class="login-input" name="code" placeholder="کد ۶رقمی فعلی" required inputmode="numeric" autocomplete="one-time-code" dir="ltr" style="text-align:center;letter-spacing:4px;">
                    <button type="submit" class="login-btn" style="background:#b00020;">غیرفعال‌سازی دوعاملی</button>
                </form>

                <?php else: ?>
                <div class="login-sub">سرویس دوعاملی موقتاً در دسترس نیست.</div>
                <a href="admin.php" class="login-back">بازگشت به پنل</a>
                <?php endif; ?>

                <?php if ($mode !== 'manage'): ?>
                <a href="admin.php" class="login-back">بازگشت به پنل</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
