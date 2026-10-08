<?php
/** Eitaa-only, signed initData -> real PDO identity -> rotated user session. */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bot-settings.php';
require_once __DIR__ . '/security-lib.php';
require_once __DIR__ . '/eitaa-auth-lib.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$reply = static function (array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $reply(['success' => false, 'code' => 'method', 'message' => 'این مسیر فقط POST می‌پذیرد.'], 405);
}
if (!melkinoLoginMethodEnabled('eitaa')) {
    $reply(['success' => false, 'code' => 'disabled', 'message' => 'ورود با ایتا توسط مدیر سایت غیرفعال شده است.'], 403);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) {
    $reply(['success' => false, 'code' => 'too_large', 'message' => 'دادهٔ ورود بیش از حد بزرگ است.'], 413);
}
melkinoCsrfCheck();
if (function_exists('melkinoRateLimitHit') && !melkinoRateLimitHit('eitaa-login:' . (string)($_SERVER['REMOTE_ADDR'] ?? ''), 120, 60, true)) {
    header('Retry-After: 60');
    $reply(['success' => false, 'code' => 'rate_limit', 'message' => 'درخواست‌های ورود زیاد است؛ یک دقیقه بعد دوباره تلاش کنید.'], 429);
}
$token = melkinoEitaaToken();
if ($token === '' || $token === 'توکن_برنامه_ایتا') {
    $reply(['success' => false, 'code' => 'not_configured', 'message' => 'توکن همان برنامهٔ ایتا باید در تب «ربات و کانال» تنظیم شود.'], 503);
}
$raw = file_get_contents('php://input', false, null, 0, 65537);
if ($raw === false || strlen($raw) > 65536) {
    $reply(['success' => false, 'code' => 'too_large', 'message' => 'دادهٔ ورود نامعتبر است.'], 413);
}
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST;
$initData = is_string($body['init_data'] ?? null) ? $body['init_data'] : '';
if ($initData === '') {
    $reply(['success' => false, 'code' => 'missing_init_data', 'message' => 'دادهٔ امضاشدهٔ ایتا دریافت نشد. برنامک را از داخل ایتا باز کنید.'], 422);
}
$proof = melkinoEitaaVerifyInitDataEx($initData, $token);
if (!$proof['user']) {
    $expired = in_array($proof['error'], ['expired','future','bad_date'], true);
    $reply(['success' => false, 'code' => $proof['error'], 'message' => $expired
        ? 'دادهٔ ورود ایتا منقضی شده یا ساعت سرور نادرست است؛ برنامک را ببندید و دوباره از ایتا باز کنید.'
        : 'داده یا امضای ایتا معتبر نیست. توکن باید متعلق به همان برنامه‌ای باشد که این برنامک را باز می‌کند.'], 401);
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $reply(['success' => false, 'code' => 'database', 'message' => 'پایگاه داده در دسترس نیست.'], 503);
}
if (empty($_SESSION['melkino_eitaa_binding'])) $_SESSION['melkino_eitaa_binding'] = bin2hex(random_bytes(32));
$binding = (string)$_SESSION['melkino_eitaa_binding'];
try {
    $result = melkinoEitaaExchange($pdo, $proof, $binding, (int)($_SESSION['melkino_user_id'] ?? 0));
    $user = $result['user'];
    if (!$result['reused']) {
        $csrf = melkinoCsrfToken();
        // Never inherit another user's phone/TG/Bale ID or any admin/office privilege.
        $unifiedPending = $_SESSION['mk_login_pending'] ?? null;
        $unifiedProbe = $_SESSION['mk_login_probe'] ?? null;
        $_SESSION = ['melkino_csrf' => $csrf, 'melkino_eitaa_binding' => $binding];
        if (defined('MELKINO_UNIFIED_LOGIN')) {
            if (is_array($unifiedPending)) $_SESSION['mk_login_pending'] = $unifiedPending;
            if (is_string($unifiedProbe)) $_SESSION['mk_login_probe'] = $unifiedProbe;
        }
        if (!session_regenerate_id(true)) throw new RuntimeException('session', 503);
        $_SESSION['user_id'] = $_SESSION['melkino_user_id'] = (int)$user['id'];
        $_SESSION['reg_eitaa_id'] = (string)$user['eitaa_id'];
        foreach (['telegram_id' => 'reg_telegram_id', 'bale_id' => 'reg_bale_id'] as $column => $key) {
            if (!empty($user[$column])) $_SESSION[$key] = (string)$user[$column];
        }
        $_SESSION['user_name'] = (string)($user['name'] ?? '');
        if (!empty($user['phone'])) $_SESSION['user_phone'] = (string)$user['phone'];
        $_SESSION['melkino_eitaa_context'] = true;
        $_SESSION['melkino_session_started_at'] = time();
        $_SESSION['melkino_login_recorded'] = time();
        // Remove only the old browser credential; do not revoke unrelated devices.
        setcookie('melkino_access_token', '', ['expires' => time() - 3600, 'path' => '/',
            'secure' => !empty($melkinoIsHttps), 'httponly' => true, 'samesite' => 'Lax']);
        unset($_COOKIE['melkino_access_token']);
    }
    // SameSite=None only for the Eitaa HTTPS flow, for supported iframe clients.
    // Native/localhost tests keep the standard Lax cookie; third-party-cookie bans still apply.
    if (!empty($melkinoIsHttps)) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), session_id(), ['expires' => 0, 'path' => $cookie['path'] ?: '/',
            'domain' => $cookie['domain'] ?? '', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);
    }
    if (defined('MELKINO_UNIFIED_LOGIN')) melkinoMessengerMarkSuccess('eitaa', (int)$user['id']);
    $reply(['success' => true, 'platform' => 'eitaa', 'user_id' => (int)$user['id'],
        'eitaa_id' => (string)$user['eitaa_id'], 'name' => (string)($user['name'] ?? ''),
        'phone' => (string)($user['phone'] ?? ''), 'reused' => $result['reused']]);
} catch (Throwable $e) {
    $messages = [
        'replayed' => 'این دادهٔ ورود قبلاً استفاده شده است؛ برنامک را ببندید و دوباره از داخل ایتا باز کنید.',
        'account_disabled' => 'حساب کاربری شما غیرفعال است. با پشتیبانی تماس بگیرید.',
        'duplicate_identity' => 'چند حساب با همین آیدی ایتا وجود دارد؛ مدیر باید رکوردهای تکراری را بررسی کند.',
        'session' => 'نشست ورود ساخته نشد. دسترسی به کوکی و تنظیمات نشست سرور را بررسی کنید.',
    ];
    $known = isset($messages[$e->getMessage()]) && !($e instanceof PDOException);
    if (!$known) error_log('[melkino][eitaa] Authentication storage failed; code=' . $e->getCode());
    $reply(['success' => false, 'code' => $known ? $e->getMessage() : 'storage',
        'message' => $known ? $messages[$e->getMessage()] : 'ذخیرهٔ ورود انجام نشد؛ اتصال دیتابیس و جدول‌های SQL ایتا را بررسی کنید.'],
        $known ? (int)$e->getCode() : 503);
}
