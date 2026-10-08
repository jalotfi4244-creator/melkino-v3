<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bale.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/bot-settings.php';

// اگر ادمین ورود با بله را غیرفعال کرده باشد، حتی درخواست مستقیم
// به این اندپوینت هم رد می‌شود (غیرفعال‌سازی فقط ظاهری نیست).
if (!melkinoLoginMethodEnabled('bale')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'ورود با بله توسط مدیر سایت غیرفعال شده است.',
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

$melkinoBaleToken = function_exists('melkinoBaleToken') ? melkinoBaleToken() : (defined('BALE_BOT_TOKEN') ? (string)BALE_BOT_TOKEN : '');
$melkinoBaleCheck = function_exists('melkinoVerifyMiniAppInitDataEx')
    ? melkinoVerifyMiniAppInitDataEx($initData, $melkinoBaleToken)
    : ['user' => baleVerifyInitData($initData), 'error' => null];
$verified = $melkinoBaleCheck['user'] ?? null;
if ($verified === null) {
    http_response_code(401);
    $melkinoBaleErr = (string)($melkinoBaleCheck['error'] ?? '');
    if ($melkinoBaleErr === 'expired' || $melkinoBaleErr === 'future') {
        $melkinoBaleMsg = 'نشست بله منقضی شده؛ مینی‌اپ را کامل ببند و دوباره از دکمهٔ منوی ربات باز کن.';
    } else {
        $melkinoBaleMsg = 'امضای بله معتبر نیست. رباتی که مینی‌اپ را از آن باز کردی با توکن ذخیره‌شده در سایت یکی نیست — نام کاربری ربات را در تب «ربات و کانال» بررسی کن.';
    }
    echo json_encode(['success' => false, 'code' => $melkinoBaleErr !== '' ? $melkinoBaleErr : 'bad_hash', 'message' => $melkinoBaleMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim($verified['first_name'] . ' ' . $verified['last_name']);
$identity = defined('MELKINO_UNIFIED_LOGIN')
    ? melkinoMessengerUpsert('bale', $verified)
    : melkinoUpsertUser('', '', $name, $verified['username'], null, $verified['id']);

// ثبت کامل اطلاعات این ورود (IP، مرورگر، پلتفرم) برای پنل ادمین
melkinoRecordLoginInfo($identity['id'] ?? null, 'bale', [
    'bale_id'  => $verified['id'],
    'username' => $verified['username'],
    'name'     => $name,
]);

// راند ۳۰: همگام‌سازی فیلدهای هویتی بله (عکس/زبان/auth_date/last_init_data)
melkinoSyncTelegramIdentity($identity['id'] ?? null, $verified, 'bale', defined('MELKINO_UNIFIED_LOGIN') ? '' : $initData);

if (empty($identity['trusted'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ثبت هویت ناموفق بود.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (defined('MELKINO_UNIFIED_LOGIN')) {
    echo json_encode(melkinoMessengerEstablish($pdo, (int)$identity['id'], 'bale'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

session_regenerate_id(true);
melkinoSyncSessionFromUser((int)$identity['id']);

$_SESSION['reg_bale_id'] = $verified['id'];
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
    ? melkinoMintLoginToken($identity['id'] ?? null, null, $verified['id'])
    : '';

echo json_encode([
    'success' => true,
    'user_id' => $identity['id'],
    'name' => $name,
    'phone' => $phone,
    'login_token' => $loginToken,
], JSON_UNESCAPED_UNICODE);
