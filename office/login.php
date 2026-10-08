<?php
/**
 *--------------------------------------------------------------------------
 * ورود به دفتر ملکینو شهر — عین لاگین پنل ادمین ملکینو
 *--------------------------------------------------------------------------
 * - همان جدول admins، همان password_verify، همان قفل بعد از ۵ تلاش ناموفق،
 *   همان لاگ admin_login_attempts و همان کلیدهای سشن (is_admin و ...)
 * - کاملاً جدا از لاگین کاربران سایت (مشتری‌ها)
 * - تنها تفاوت ظاهری با لاگین پنل: نام‌کاربری هم گرفته می‌شود (به‌جای admin
 *   ثابت) تا بعداً حساب مشاورها هم همین‌جا وارد شوند.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once dirname(__DIR__) . '/security-lib.php';

if (office_is_logged_in()) {
    office_redirect('index.php');
    return;
}
try {
    if (office_2fa_password_ok() && office_2fa_needs_check()) {
        office_redirect('two-factor.php?redirect=index.php');
        return;
    }
} catch (Throwable $e) {
}

$pdo = office_db();
melkinoRequireDb();

$security = [];
foreach (['lockout', 'admin_login_log'] as $k) {
    $security[$k] = function_exists('dbSettingGet')
        ? (bool)dbSettingGet($pdo, 'security', $k, true)
        : true;
}
/* SECFIX(M1): قفل سراسری حذف شد؛ per-user/per-IP پایین‌تر اعمال می‌شود. */

$error = '';
$okMsg = '';
if (($_GET['out'] ?? '') === '1') {
    $okMsg = 'با موفقیت خارج شدید.';
}

$username = trim((string)($_POST['username'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $lockPre = ['locked' => false, 'remaining' => 0, 'scope' => ''];
    if (!empty($security['lockout']) && function_exists('melkinoAdminLockoutCheck')) {
        $lockPre = melkinoAdminLockoutCheck(($pdo instanceof PDO) ? $pdo : null, $username, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
    }
    if (!empty($lockPre['locked'])) {
        $remaining = max(1, (int)($lockPre['remaining'] ?? 1));
        $error = 'به‌دلیل چند ورود ناموفق، ورود موقتاً قفل شده است. حدود ' . $remaining . ' دقیقه دیگر دوباره تلاش کنید.';
    } elseif ((string)($_POST['csrf_token'] ?? '') === '' || !hash_equals(office_csrf(), (string)$_POST['csrf_token'])) {
        $error = 'درخواست نامعتبر است؛ صفحه را دوباره بارگذاری کنید.';
    } else {
        $inputPass = (string)($_POST['password'] ?? '');
        // SECFIX(H2): نام‌کاربری خالی دیگر به 'admin' برنمی‌گردد.
        $loginName = $username;

        $admin = null;
        try {
            $st = $pdo->prepare('SELECT id, username, password_hash, is_active, display_name FROM admins WHERE username = :u LIMIT 1');
            $st->execute(['u' => $loginName]);
            $admin = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $admin = null;
        }

        $valid = $loginName !== '' && $admin && !empty($admin['is_active']) && $inputPass !== ''
            && password_verify($inputPass, (string)$admin['password_hash']);

        if ($valid) {
            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['is_admin'] = true;
            // SECFIX(P3-roles): نقش واقعی از settings؛ پیش‌فرض admin.
            $_SESSION['user_role'] = function_exists('melkinoAdminRole') ? melkinoAdminRole($pdo, (int)$admin['id']) : 'admin';
            $_SESSION['admin_id'] = (int)($admin['id'] ?? 0);
            $_SESSION['admin_username'] = (string)($admin['username'] ?? $loginName);
            $_SESSION['admin_display_name'] = (string)($admin['display_name'] ?? '');
            $_SESSION['admin_login_at'] = time();
            $_SESSION['melkino_session_started_at'] = time();
            $_SESSION['admin_last_activity'] = time();

            if (function_exists('melkinoAdminLockoutReset')) {
                melkinoAdminLockoutReset(($pdo instanceof PDO) ? $pdo : null, $loginName);
            }
            if ($security['admin_login_log']) {
                try {
                    $lg = $pdo->prepare('INSERT INTO admin_login_attempts (username, success, ip, created_at) VALUES (:u, 1, :ip, NOW())');
                    $lg->execute(['u' => $loginName, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                } catch (Throwable $e) {
                }
            }
            try {
                $needs2fa = office_2fa_enrolled($pdo, (int)($_SESSION['admin_id'] ?? 0));
            } catch (Throwable $e) {
                $needs2fa = false;
            }
            office_redirect($needs2fa ? 'two-factor.php?redirect=index.php' : 'index.php');
            return;
        }

        $lockHit = ['locked' => false, 'remaining' => 0, 'scope' => ''];
        if (!empty($security['lockout']) && function_exists('melkinoAdminLockoutFail')) {
            $lockHit = melkinoAdminLockoutFail(($pdo instanceof PDO) ? $pdo : null, $loginName, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
        }
        if ($security['admin_login_log']) {
            try {
                $lg = $pdo->prepare('INSERT INTO admin_login_attempts (username, success, ip, created_at) VALUES (:u, 0, :ip, NOW())');
                $lg->execute(['u' => $loginName, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
            } catch (Throwable $e) {
            }
        }
        $error = !empty($lockHit['locked'])
            ? 'به‌دلیل چند ورود ناموفق، ورود موقتاً قفل شده است. حدود ' . max(1, (int)($lockHit['remaining'] ?? 1)) . ' دقیقه دیگر دوباره تلاش کنید.'
            : 'نام‌کاربری یا رمز اشتباه است.';
    }
}

$theme = office_theme();
?>
<!doctype html>
<html lang="fa" dir="rtl" data-theme="<?= $theme ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ورود به دفتر | ملکینو شهر</title>
<link rel="stylesheet" href="assets/office.css">
</head>
<body>
<div class="of-login-wrap">
    <div class="of-login-box">
        <span class="of-brand-logo">🏛</span>
        <h1>ملکینو شهر</h1>
        <p class="sub">ورود ادمین و مشاوران دفتر املاک</p>
        <?php if ($error !== ''): ?>
            <div class="of-alert err"><?= office_h($error) ?></div>
        <?php endif; ?>
        <?php if ($okMsg !== ''): ?>
            <div class="of-alert ok"><?= office_h($okMsg) ?></div>
        <?php endif; ?>
        <form class="of-form" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= office_h(office_csrf()) ?>">
            <label for="lg-u">نام‌کاربری</label>
            <input id="lg-u" name="username" dir="ltr" value="<?= office_h($username) ?>" required autofocus>
            <label for="lg-p">رمز عبور</label>
            <input id="lg-p" type="password" name="password" dir="ltr" required>
            <button class="of-btn">ورود به دفتر</button>
        </form>
        <p class="of-muted" style="margin-top:14px">همین حساب پنل ادمین ملکینو — جدا از کاربران سایت</p>
    </div>
</div>
<script src="assets/office.js"></script>
</body>
</html>
