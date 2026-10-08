<?php
/**
 * Melkino V2 — admin login (standalone; head/body VERBATIM, style extracted).
 * Vars: $error, $errorMessage.
 */
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>ملکینو - ورود ادمین</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="design-pro.css?v=<?= (int)@filemtime(MELKINO_ROOT . '/design-pro.css') ?>">
    
    <?= \Melkino\Support\Assets::css('assets/css/admin-login-legacy.css') ?>
</head>
<body>
    <div class="app-container" style="padding-bottom: 0;">
        <header class="topbar">
            <a href="profile.php" class="topbar-action"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
            <span class="topbar-title">ورود به پنل مدیریت</span>
            <div style="width:24px;"></div>
        </header>
        <div class="main-content">
            <div class="login-box">
                <div class="login-title"><?= melkinoSvgIcon('lock') ?> ملکینو</div>
                <div class="login-sub">برای دسترسی به تنظیمات، رمز عبور ادمین را وارد کنید.</div>
                
                <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($errorMessage !== '' ? $errorMessage : 'رمز عبور اشتباه است! لطفاً دوباره تلاش کنید.', ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(melkinoCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="password" class="login-input" name="password" placeholder="رمز عبور ادمین را وارد کنید" required>
                    <button type="submit" class="login-btn">ورود به پنل</button>
                </form>

                <a href="profile.php" class="login-back">بازگشت به پروفایل</a>
            </div>
        </div>
    </div>
</body>
</html>