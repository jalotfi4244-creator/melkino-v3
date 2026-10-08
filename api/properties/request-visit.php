<?php
declare(strict_types=1);

/**
 * Melkino V2 — canonical visit request (same validation chain as visit-request-api.php?action=create:
 * requester gate + slot allow-list + 7-day window + capacity + ad snapshot), canonical envelope.
 * Accepts both JSON (fetch) and classic form POST (progressive enhancement fallback).
 */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Database;
use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Core\Session;

@require_once MELKINO_ROOT . '/db_helpers.php';
@require_once MELKINO_ROOT . '/visit-request-lib.php';

$wantsJson = stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
    || !empty($_POST['__json'])
    || (string)($_SERVER['CONTENT_TYPE'] ?? '') !== '' && stripos((string)$_SERVER['CONTENT_TYPE'], 'application/json') !== false;

$done = static function (bool $ok, string $message, int $code, int $adId, array $extra = []) use ($wantsJson): void {
    if (!$wantsJson) {
        Session::flash($ok ? 'success' : 'error', $message);
        Response::redirect('property-details.php?id=' . $adId);
    }
    Response::json($extra, $message, $ok, $ok ? 200 : $code);
};

$pdo = Database::pdo();
if (!$pdo) {
    $done(false, 'اتصال دیتابیس برقرار نیست.', 500, Request::int('ad_id', 0));
}
try {
    if (function_exists('melkinoEnsureVisitRequestSchema')) {
        melkinoEnsureVisitRequestSchema();
    }
} catch (\Throwable $ignored) {
}

$identity = function_exists('melkinoCurrentIdentity') ? melkinoCurrentIdentity() : ['user_id' => null, 'phone' => ''];
$gate = function_exists('melkinoVisitRequesterGate')
    ? melkinoVisitRequesterGate($identity)
    : ['ok' => false, 'login' => true, 'message' => 'برای درخواست بازدید ابتدا وارد حساب شوید.'];
if (empty($gate['ok'])) {
    $done(false, (string)($gate['message'] ?? 'امکان ثبت درخواست بازدید نیست.'), !empty($gate['login']) ? 401 : 403, Request::int('ad_id', 0));
}

$adId = Request::int('ad_id', 0, 1);
$date = function_exists('melkinoVisitNormalizeDigits')
    ? melkinoVisitNormalizeDigits(Request::string('preferred_date'))
    : Request::string('preferred_date');
if ($date !== '' && function_exists('melkinoVisitToGregorianDate')) {
    $asG = melkinoVisitToGregorianDate($date);
    if ($asG !== '') {
        $date = $asG;
    }
}
$slot = Request::string('time_slot');
$slots = function_exists('melkinoVisitSlots') ? melkinoVisitSlots() : ['morning' => 'صبح', 'evening' => 'عصر'];
if ($adId <= 0) {
    $done(false, 'شناسه آگهی نامعتبر است.', 422, $adId);
}
if (!isset($slots[$slot])) {
    $done(false, 'بازه زمانی را انتخاب کنید (صبح یا عصر).', 422, $adId);
}
$allowed = [];
if (function_exists('melkinoVisitNextDays')) {
    foreach (melkinoVisitNextDays(7) as $dayRow) {
        $allowed[(string)$dayRow['date']] = $dayRow;
    }
}
if (!isset($allowed[$date])) {
    $done(false, 'روز انتخاب‌شده معتبر نیست. از فردا تا یک هفته بعد را انتخاب کنید.', 422, $adId);
}
$day = $allowed[$date];
if (!empty($day['closed']) || (isset($day['selectable']) && !$day['selectable'])) {
    $done(false, 'این روز تعطیل است و برای بازدید قابل انتخاب نیست.', 422, $adId);
}
if (function_exists('melkinoVisitCapacity') && function_exists('melkinoVisitDayCounts')) {
    $cap = melkinoVisitCapacity();
    $limit = (int)($cap[$slot] ?? 0);
    if ($limit > 0) {
        $counts = melkinoVisitDayCounts([$date]);
        if ((int)($counts[$date][$slot] ?? 0) >= $limit) {
            $done(false, 'برنامه بازدیدهای این روز کامل است؛ روز دیگری را انتخاب کنید.', 422, $adId);
        }
    }
}

$st = $pdo->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
$st->execute([$adId]);
$ad = $st->fetch(PDO::FETCH_ASSOC);
if (!$ad) {
    $done(false, 'آگهی پیدا نشد.', 404, $adId);
}
$name = function_exists('melkinoVisitRequesterName') ? melkinoVisitRequesterName($identity) : (string)($identity['user']['name'] ?? '');
$phone = function_exists('melkinoVisitNormalizePhone') ? melkinoVisitNormalizePhone((string)($identity['phone'] ?? '')) : '';
$snapshot = function_exists('melkinoVisitAdSnapshot') ? melkinoVisitAdSnapshot($ad) : [];
$tracking = '';
try {
    $ins = $pdo->prepare('INSERT INTO visit_requests
        (ad_id, ad_title, user_id, telegram_id, phone, name, preferred_date, preferred_date_fa, weekday, time_slot, alternative_datetime, advertiser_last_name, advertiser_phone, ad_snapshot, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\', NOW())');
    $ok = $ins->execute([
        $adId, mb_substr(trim((string)($ad['title'] ?? '')), 0, 255) ?: null,
        !empty($identity['user_id']) ? (int)$identity['user_id'] : null,
        trim((string)($identity['telegram_id'] ?? '')) !== '' ? trim((string)$identity['telegram_id']) : null,
        $phone, $name, $date,
        mb_substr(Request::string('preferred_date_fa', (string)($day['label'] ?? '')), 0, 64),
        mb_substr((string)($day['weekday'] ?? ''), 0, 32), $slot,
        mb_substr(Request::string('alternative_datetime'), 0, 500) ?: null,
        mb_substr(trim((string)($ad['last_name'] ?? '')), 0, 120) ?: null,
        function_exists('melkinoVisitNormalizePhone') ? melkinoVisitNormalizePhone((string)($ad['phone'] ?? '')) : null,
        $snapshot ? json_encode($snapshot, JSON_UNESCAPED_UNICODE) : null,
    ]);
    if ($ok && function_exists('melkinoVisitEnsureTracking')) {
        $tracking = (string)@melkinoVisitEnsureTracking((int)$pdo->lastInsertId());
    }
    if ($ok && function_exists('melkinoVisitNotifyForStatus')) {
        try {
            @melkinoVisitNotifyForStatus((int)$pdo->lastInsertId(), 'new');
        } catch (\Throwable $ignored) {
        }
    }
} catch (\Throwable $e) {
    $ok = false;
}
$message = $ok && function_exists('melkinoVisitSuccessMessage')
    ? (string)@melkinoVisitSuccessMessage($date, $slot)
    : ($ok ? 'درخواست بازدید ثبت شد. با شما تماس می‌گیریم.' : 'ثبت درخواست ناموفق بود.');
$done((bool)$ok, $message !== '' ? $message : 'درخواست بازدید ثبت شد.', $ok ? 200 : 500, $adId, $tracking !== '' ? ['tracking' => $tracking] : []);
