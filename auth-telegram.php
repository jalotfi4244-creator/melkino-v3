<?php
/*
|--------------------------------------------------------------------------
| ورود با مینی‌اپ تلگرام
|--------------------------------------------------------------------------
| کلاینت، initData خام (window.Telegram.WebApp.initData) رو می‌فرسته.
| هیچ کد یا رمزی لازم نیست چون خودِ امضای تلگرام هویت رو تضمین می‌کنه.
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/bot-settings.php';

// اگر ادمین ورود با تلگرام را غیرفعال کرده باشد، حتی درخواست مستقیم
// به این اندپوینت هم رد می‌شود (غیرفعال‌سازی فقط ظاهری نیست).
if (!melkinoLoginMethodEnabled('telegram')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'ورود با تلگرام توسط مدیر سایت غیرفعال شده است.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'روش مجاز نیست'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$initData = (string)($body['init_data'] ?? '');
if ($initData === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'initData ارسال نشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$melkinoTgToken = function_exists('melkinoTelegramToken') ? melkinoTelegramToken() : (defined('BOT_TOKEN') ? (string)BOT_TOKEN : '');
$melkinoTgCheck = function_exists('melkinoVerifyMiniAppInitDataEx')
    ? melkinoVerifyMiniAppInitDataEx($initData, $melkinoTgToken)
    : ['user' => telegramVerifyInitData($initData), 'error' => null];
$verified = $melkinoTgCheck['user'] ?? null;
if ($verified === null) {
    http_response_code(401);
    $melkinoTgErr = (string)($melkinoTgCheck['error'] ?? '');
    if ($melkinoTgErr === 'expired' || $melkinoTgErr === 'future') {
        $melkinoTgMsg = 'نشست تلگرام منقضی شده؛ مینی‌اپ را کامل ببند و دوباره از دکمهٔ منوی ربات باز کن.';
    } else {
        $melkinoTgMsg = 'امضای تلگرام معتبر نیست. رباتی که مینی‌اپ را از آن باز کردی با توکن ذخیره‌شده در سایت یکی نیست — نام کاربری ربات را در تب «ربات و کانال» بررسی کن.';
    }
    echo json_encode(['success' => false, 'code' => $melkinoTgErr !== '' ? $melkinoTgErr : 'bad_hash', 'message' => $melkinoTgMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim($verified['first_name'] . ' ' . $verified['last_name']);
$identity = defined('MELKINO_UNIFIED_LOGIN')
    ? melkinoMessengerUpsert('telegram', $verified)
    : melkinoUpsertUser($verified['id'], '', $name, $verified['username']);

// ثبت کامل اطلاعات این ورود (IP، مرورگر، پلتفرم) برای پنل ادمین
melkinoRecordLoginInfo($identity['id'] ?? null, 'telegram', [
    'telegram_id' => $verified['id'],
    'username'    => $verified['username'],
    'name'        => $name,
]);

// راند ۳۰: همگام‌سازی فیلدهای هویتی تلگرام (نام/یوزرنیم/زبان/پریمیوم/
// عکس/auth_date/last_init_data) در هر ورودِ معتبر. به ستون‌های شماره
// (phone / phone_verified / phone_locked) هرگز دست نمی‌زند.
melkinoSyncTelegramIdentity($identity['id'] ?? null, $verified, 'telegram', defined('MELKINO_UNIFIED_LOGIN') ? '' : $initData);

if (empty($identity['trusted'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ثبت هویت ناموفق بود.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// نکته‌ی امنیتی: هر بار که یک نشست ورودِ جدید و معتبر برقرار می‌شود،
// شناسه‌ی نشست (session id) از نو تولید می‌شود تا از حملات تثبیت
// نشست (Session Fixation) جلوگیری شود.
if (defined('MELKINO_UNIFIED_LOGIN')) {
    echo json_encode(melkinoMessengerEstablish($pdo, (int)$identity['id'], 'telegram'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

session_regenerate_id(true);
melkinoSyncSessionFromUser((int)$identity['id']);

$_SESSION['reg_telegram_id'] = $verified['id'];
if ($name !== '') {
    $_SESSION['user_name'] = $name;
}

$phoneRow = $pdo->prepare('SELECT phone FROM users WHERE id = ?');
$phoneRow->execute([$identity['id']]);
$phone = trim((string)$phoneRow->fetchColumn());
if ($phone !== '') {
    $_SESSION['user_phone'] = $phone;
}

// توکنِ پشتیبانِ ورود: برای مرورگرهای داخلی که کوکیِ نشست را نگه نمی‌دارند
$loginToken = function_exists('melkinoMintLoginToken')
    ? melkinoMintLoginToken($identity['id'] ?? null, $verified['id'], null)
    : '';

echo json_encode([
    'success' => true,
    'user_id' => $identity['id'],
    'name' => $name,
    'phone' => $phone,
    'login_token' => $loginToken,
], JSON_UNESCAPED_UNICODE);
