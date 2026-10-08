<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه ویرایش‌های در انتظار تأیید (مرحله ۲۱)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-property-revisions.php: فهرست pending، تأیید/رد با
 * عین SQL سایت + اعلان به مالک (تکرار دفتر-سایدِ melkinoNotifyAdOwner، چون
 * db_helpers روی وب گارد لاگین سایت را بالا می‌کشد).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofRvF = dirname(__DIR__) . '/notification-events.php';
if (is_file($__ofRvF)) {
    require_once $__ofRvF;
}
unset($__ofRvF);
if (!function_exists('melkinoNotificationEventDefs')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/notification-events.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_rev_boot')) {
    function office_rev_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
    }
}

if (!function_exists('office_rev_decode')) {
    /** @return array<string,mixed>|null */
    function office_rev_decode(mixed $snapshot): ?array
    {
        $s = json_decode((string)$snapshot, true);
        return is_array($s) ? $s : null;
    }
}

if (!function_exists('office_rev_pending')) {
    /** @return array<int,array> */
    function office_rev_pending(PDO $pdo): array
    {
        try {
            $rows = $pdo->query('SELECT r.id,r.ad_id,r.snapshot,r.change_note,r.created_at,a.title,a.phone,a.status FROM ad_revisions r JOIN ads a ON a.id=r.ad_id ORDER BY r.created_at DESC,r.id DESC')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $s = office_rev_decode($r['snapshot'] ?? null);
            if ($s === null || ($s['review_status'] ?? 'pending') !== 'pending') {
                continue;
            }
            $r['snapshot'] = $s;
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('office_rev_get')) {
    function office_rev_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM ad_revisions WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_rev_send_notification')) {
    /** تکرار sendNotification (با گیت رویداد). */
    function office_rev_send_notification(PDO $pdo, ?int $userId, ?string $tg, string $type, string $title, string $message, ?string $url, ?string $adId): bool
    {
        if (($userId === null || $userId <= 0) && ($tg === null || $tg === '')) {
            return false;
        }
        try {
            if (function_exists('dbSettingGet') && !(bool)dbSettingGet($pdo, 'global', 'enable_notifications', true)) {
                return false;
            }
            if (function_exists('melkinoNotificationEventEnabled') && !melkinoNotificationEventEnabled($type)) {
                return false;
            }
            return $pdo->prepare(
                'INSERT INTO notifications (user_id, telegram_id, type, title, message, url, ad_id, request_id, match_percent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([$userId, $tg, $type, $title, $message, $url, $adId, null, null]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_rev_notify_owner')) {
    /** تکرار melkinoNotifyAdOwner + melkinoNotifyByPhone. */
    function office_rev_notify_owner(PDO $pdo, string $adId, string $type, string $title, string $message, ?string $url = 'my-properties.php'): bool
    {
        if ($adId === '') {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT owner_user_id, user_id, telegram_id, phone FROM ads WHERE id=? LIMIT 1');
            $st->execute([$adId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            $userId = (int)($row['owner_user_id'] ?: $row['user_id'] ?: 0);
            $tg = trim((string)($row['telegram_id'] ?? ''));
            if ($userId > 0 || $tg !== '') {
                return office_rev_send_notification($pdo, $userId > 0 ? $userId : null, $tg !== '' ? $tg : null, $type, $title, $message, $url, $adId);
            }
            $phone = function_exists('melkinoNormalizePhone') ? melkinoNormalizePhone((string)($row['phone'] ?? '')) : trim((string)($row['phone'] ?? ''));
            if ($phone === '') {
                return false;
            }
            $candidates = [$phone];
            if (strpos($phone, '0') === 0 && strlen($phone) > 1) {
                $candidates[] = substr($phone, 1);
                $candidates[] = '98' . substr($phone, 1);
            } elseif (strpos($phone, '98') === 0) {
                $candidates[] = '0' . substr($phone, 2);
            } else {
                $candidates[] = '0' . $phone;
            }
            $candidates = array_values(array_unique($candidates));
            $placeholders = implode(',', array_fill(0, count($candidates), '?'));
            $st = $pdo->prepare("SELECT id, telegram_id FROM users WHERE phone IN ($placeholders) ORDER BY id DESC LIMIT 1");
            $st->execute($candidates);
            $u = $st->fetch(PDO::FETCH_ASSOC);
            if (!$u) {
                return false;
            }
            return office_rev_send_notification($pdo, (int)$u['id'], !empty($u['telegram_id']) ? (string)$u['telegram_id'] : null, $type, $title, $message, $url, $adId);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_rev_approve')) {
    /** @return array{0:bool,1:string} */
    function office_rev_approve(PDO $pdo, int $rid): array
    {
        $rev = office_rev_get($pdo, $rid);
        if (!$rev) {
            return [false, 'ویرایش پیدا نشد.'];
        }
        $s = office_rev_decode($rev['snapshot'] ?? null);
        if ($s === null || ($s['review_status'] ?? 'pending') !== 'pending') {
            return [false, 'این ویرایش قبلاً بررسی شده است.'];
        }
        try {
            $a = $s['after'] ?? [];
            if (!is_array($a)) {
                $a = [];
            }
            $pdo->prepare("UPDATE ads SET title=?,area=?,price_sell=?,deposit=?,rent_monthly=?,description=?,status='published',updated_at=NOW(),published_at=COALESCE(published_at,NOW()) WHERE id=?")
                ->execute([$a['title'] ?? null, $a['area'] ?? null, $a['price_sell'] ?? null, $a['deposit'] ?? null, $a['rent_monthly'] ?? null, $a['description'] ?? null, $rev['ad_id']]);
            $s['review_status'] = 'approved';
            $s['reviewed_at'] = date('Y-m-d H:i:s');
            $pdo->prepare('UPDATE ad_revisions SET changed_by_admin_id=NULL,snapshot=? WHERE id=?')
                ->execute([json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $rid]);
            office_rev_notify_owner($pdo, (string)$rev['ad_id'], 'ad_revision_approved', 'ویرایش آگهی شما تأیید شد', 'ویرایش آگهی «' . ($a['title'] ?? '') . '» تأیید شد و آگهی دوباره منتشر شد.', 'my-properties.php');
            return [true, 'ویرایش تأیید و آگهی دوباره منتشر شد.'];
        } catch (Throwable $e) {
            return [false, 'تأیید ناموفق بود.'];
        }
    }
}

if (!function_exists('office_rev_reject')) {
    /** @return array{0:bool,1:string} */
    function office_rev_reject(PDO $pdo, int $rid): array
    {
        $rev = office_rev_get($pdo, $rid);
        if (!$rev) {
            return [false, 'ویرایش پیدا نشد.'];
        }
        $s = office_rev_decode($rev['snapshot'] ?? null);
        if ($s === null || ($s['review_status'] ?? 'pending') !== 'pending') {
            return [false, 'این ویرایش قبلاً بررسی شده است.'];
        }
        try {
            $s['review_status'] = 'rejected';
            $s['reviewed_at'] = date('Y-m-d H:i:s');
            $pdo->prepare('UPDATE ad_revisions SET changed_by_admin_id=NULL,snapshot=? WHERE id=?')
                ->execute([json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $rid]);
            $a = $s['before'] ?? [];
            if (!is_array($a)) {
                $a = [];
            }
            $status = $a['status'] ?? 'published';
            $pdo->prepare('UPDATE ads SET status=?,updated_at=NOW() WHERE id=?')->execute([$status, $rev['ad_id']]);
            office_rev_notify_owner($pdo, (string)$rev['ad_id'], 'ad_revision_rejected', 'ویرایش آگهی شما رد شد', 'ویرایش پیشنهادی شما برای آگهی «' . (($s['before'] ?? [])['title'] ?? '') . '» تأیید نشد؛ اطلاعات قبلی آگهی حفظ شد.', 'my-properties.php');
            return [true, 'ویرایش رد شد و اطلاعات قبلی حفظ شد.'];
        } catch (Throwable $e) {
            return [false, 'رد ناموفق بود.'];
        }
    }
}
