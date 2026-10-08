<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تأیید دومرحله‌ای (مرحله ۳۶)
 *--------------------------------------------------------------------------
 * آینهٔ Admin2faController سایت با همان جدول مشترک admin_totp:
 * فعال‌سازی (نمایش سکرت + ورود دستی در اپ)، تأیید کد هر سشن، و
 * غیرفعال‌سازی. این صفحه حالت نیمه‌تمام (رمز درست، کد نزده) را
 * می‌پذیرد؛ بقیه صفحات دفتر پشت گیت office_is_logged_in هستند.
 * بدون هیچ منبع خارجی (آفلاین): ورود دستی سکرت، بدون QR آنلاین.
 */

declare(strict_types=1);

require_once __DIR__ . '/_2fa.php';

if (!office_2fa_password_ok()) {
    office_redirect('login.php');
    return;
}
$pdo = office_db();
office_2fa_boot($pdo);

$adminId = office_2fa_admin_id();
$username = (string)($_SESSION['admin_username'] ?? 'admin');
$redirect = office_2fa_safe_redirect((string)(($_POST['redirect'] ?? '') !== '' ? $_POST['redirect'] : ($_GET['redirect'] ?? '')));

$available = office_2fa_available() && $adminId > 0;
$enrolled = $available && office_2fa_enrolled($pdo, $adminId);
$satisfied = office_2fa_guard_satisfied();
$error = '';
$success = '';

if ($available && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
    if (!office_csrf_valid()) {
        $error = 'توکن امنیتی نامعتبر است؛ صفحه را تازه‌سازی کنید.';
    } elseif (office_2fa_throttled()) {
        $error = 'به‌دلیل تلاش‌های ناموفق پیاپی، چند دقیقه دیگر دوباره تلاش کنید.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $code = (string)($_POST['code'] ?? '');
        if ($action === 'setup_confirm' && !$enrolled) {
            $pending = (string)($_SESSION['admin_2fa_pending'] ?? '');
            if ($pending !== '' && office_2fa_verify($pending, $code)) {
                office_2fa_activate($pdo, $adminId, $pending);
                office_2fa_clear_pending();
                office_2fa_reset_throttle();
                office_2fa_mark_satisfied();
                office_2fa_audit('admin.2fa_enrolled', $adminId);
                office_redirect($redirect);
                return;
            }
            office_2fa_hit_throttle();
            office_2fa_audit('admin.2fa_failed', $adminId);
            $error = 'کد واردشده درست نیست؛ ساعت گوشی و کد جدید را بررسی کنید.';
        } elseif ($action === 'verify' && $enrolled) {
            $secret = office_2fa_secret_for($pdo, $adminId);
            if ($secret !== null && office_2fa_verify($secret, $code)) {
                office_2fa_reset_throttle();
                office_2fa_mark_satisfied();
                office_2fa_audit('admin.2fa_verified', $adminId);
                office_redirect($redirect);
                return;
            }
            office_2fa_hit_throttle();
            office_2fa_audit('admin.2fa_failed', $adminId);
            $error = 'کد واردشده درست نیست؛ کد جدید اپ را وارد کنید.';
        } elseif ($action === 'revoke' && $enrolled) {
            $secret = office_2fa_secret_for($pdo, $adminId);
            if ($secret !== null && office_2fa_verify($secret, $code)) {
                office_2fa_revoke($pdo, $adminId);
                unset($_SESSION['admin_2fa_ok']);
                office_2fa_reset_throttle();
                office_2fa_audit('admin.2fa_revoked', $adminId);
                $enrolled = false;
                $satisfied = false;
                $success = 'دوعاملی غیرفعال شد. در صورت نیاز دوباره فعال‌سازی کنید.';
            } else {
                office_2fa_hit_throttle();
                $error = 'برای غیرفعال‌سازی، کد صحیح فعلی را وارد کنید.';
            }
        } else {
            $error = 'درخواست نامعتبر است.';
        }
    }
}

$secret = '';
$uri = '';
if ($available && !$enrolled) {
    $secret = office_2fa_pending();
    $uri = $secret !== '' ? office_2fa_uri($secret, $username) : '';
}
$mode = !$available ? 'unavailable' : (!$enrolled ? 'setup' : ($satisfied ? 'manage' : 'verify'));

// حالت نیمه‌تمام اجازه رندر شل را هم دارد (گیت two-factor.php را مستثنا می‌کند).
office_shell_open('two-factor.php', 'تأیید دومرحله‌ای');
?>
<?php if ($error !== '') : ?><p class="of-alert err"><?= office_h($error) ?></p><?php endif; ?>
<?php if ($success !== '') : ?><p class="of-alert ok"><?= office_h($success) ?></p><?php endif; ?>

<?php if ($mode === 'unavailable') : ?>
<p class="of-alert err">سرویس دوعاملی در دسترس نیست (اتصال پایگاه داده یا موتور TOTP).</p>

<?php elseif ($mode === 'setup') : ?>
<div class="of-card">
<h3>فعال‌سازی تأیید دومرحله‌ای</h3>
<p class="of-muted">۱) در اپ احرازهویت گوشی (Google Authenticator و مشابه)، «ورود دستی» را بزنید و این کلید را وارد کنید:</p>
<p dir="ltr" style="font-size:22px;letter-spacing:3px;font-family:monospace;text-align:center;user-select:all"><?= office_h($secret) ?></p>
<p class="of-muted">۲) یا این نشانی را در اپ سازگار وارد کنید:</p>
<p dir="ltr" style="font-size:11px;word-break:break-all;font-family:monospace" class="of-muted"><?= office_h($uri) ?></p>
<p class="of-muted">۳) سپس کد ۶ رقمی نمایش‌داده‌شده در اپ را زیر وارد کنید تا فعال‌سازی کامل شود.</p>
<form method="post" class="of-form" autocomplete="off">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="setup_confirm">
<input type="hidden" name="redirect" value="<?= office_h($redirect) ?>">
<label for="tfc">کد ۶ رقمی اپ</label>
<input id="tfc" name="code" inputmode="numeric" dir="ltr" maxlength="8" required autofocus>
<button class="of-btn">تأیید و فعال‌سازی</button>
</form>
</div>

<?php elseif ($mode === 'verify') : ?>
<div class="of-card">
<h3>کد تأیید دومرحله‌ای</h3>
<p class="of-muted">برای این حساب دوعاملی فعال است؛ کد ۶ رقمی فعلی اپ احرازهویت را وارد کنید.</p>
<form method="post" class="of-form" autocomplete="off">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="verify">
<input type="hidden" name="redirect" value="<?= office_h($redirect) ?>">
<label for="tfc">کد ۶ رقمی</label>
<input id="tfc" name="code" inputmode="numeric" dir="ltr" maxlength="8" required autofocus>
<button class="of-btn">ورود</button>
</form>
</div>

<?php elseif ($mode === 'manage') : ?>
<div class="of-card">
<h3>دوعاملی فعال است ✅</h3>
<p class="of-muted">این سشن تأیید شده است (حساب <?= office_h($username) ?>). هر ورود تازه، کد تازه می‌خواهد.</p>
<form method="post" class="of-form" autocomplete="off" onsubmit="return confirm('دوعاملی غیرفعال شود؟');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="revoke">
<input type="hidden" name="redirect" value="<?= office_h($redirect) ?>">
<label for="tfc">برای غیرفعال‌سازی، کد فعلی اپ را وارد کنید</label>
<input id="tfc" name="code" inputmode="numeric" dir="ltr" maxlength="8" required>
<button class="of-btn danger" type="submit">غیرفعال‌سازی دوعاملی</button>
</form>
</div>
<?php endif; ?>
<?php office_shell_close(); ?>
