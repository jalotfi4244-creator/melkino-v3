<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه اعلان همگانی (مرحله ۱۸)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ تب اعلان‌های پنل سایت (admin-notifications.php):
 * آمار، ارسال همگانی (عین fan-out سایت)، فهرست، حذف،
 * آخرین اعلان‌های کاربران + حذف تکی.
 * آماده‌سازی جدول‌ها عین DDL سایت است (افزایشی، IF NOT EXISTS).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

// فقط رجیستری رویدادها (تعریف خالص، امن). توجه: db_helpers.php عمداً لود
// نمی‌شود چون روی وب melkino-require-login.php را بالا می‌کشد.
$__ofBcF = dirname(__DIR__) . '/notification-events.php';
if (is_file($__ofBcF)) {
    require_once $__ofBcF;
}
unset($__ofBcF);
if (!function_exists('melkinoNotificationEventDefs')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/notification-events.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_bc_boot')) {
    function office_bc_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
        office_bc_ensure($pdo);
    }
}

if (!function_exists('office_bc_ensure')) {
    function office_bc_ensure(PDO $pdo): void
    {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS notification_broadcasts (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    url VARCHAR(500) NULL,
                    sent_count INT NOT NULL DEFAULT 0,
                    created_by INT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
            $cols = [];
            foreach ($pdo->query('SHOW COLUMNS FROM notifications')->fetchAll(PDO::FETCH_ASSOC) as $col) {
                $cols[strtolower((string)$col['Field'])] = true;
            }
            if (!isset($cols['broadcast_id'])) {
                $pdo->exec('ALTER TABLE notifications ADD COLUMN broadcast_id INT UNSIGNED NULL, ADD KEY idx_broadcast (broadcast_id)');
            }
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_bc_stats')) {
    /** @return array{users:int,total:int,unread:int,broadcasts:int} */
    function office_bc_stats(PDO $pdo): array
    {
        $out = ['users' => 0, 'total' => 0, 'unread' => 0, 'broadcasts' => 0];
        try {
            $out['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $out['total'] = (int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
            $out['unread'] = (int)$pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
            $out['broadcasts'] = (int)$pdo->query('SELECT COUNT(*) FROM notification_broadcasts')->fetchColumn();
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_bc_send')) {
    /** @return array{0:?int,1:string} شناسه + پیام موفقیت، یا null + متن خطا. */
    function office_bc_send(PDO $pdo, string $title, string $message, string $url, ?int $adminId): array
    {
        $title = trim($title);
        $message = trim($message);
        $url = trim($url);
        if ($title === '' || $message === '') {
            return [null, 'عنوان و متن اعلان الزامی است.'];
        }
        if ($url !== '' && !preg_match('#^(https?://|home\.php|properties\.php|property-details\.php|requests\.php|my-properties\.php|my-request-matches\.php|favorites\.php|notifications\.php|contact\.php|profile\.php)#', $url)) {
            return [null, 'لینک معتبر نیست (آدرس داخلی سایت یا https).'];
        }
        try {
            $ins = $pdo->prepare('INSERT INTO notification_broadcasts (title, message, url, created_by) VALUES (?, ?, ?, ?)');
            $ins->execute([$title, $message, $url !== '' ? $url : null, $adminId]);
            $bid = (int)$pdo->lastInsertId();
            $fanout = $pdo->prepare(
                "INSERT INTO notifications (user_id, telegram_id, type, title, message, url, broadcast_id, is_read, created_at)
                 SELECT id, NULLIF(telegram_id, ''), 'broadcast', ?, ?, ?, ?, 0, NOW() FROM users"
            );
            $fanout->execute([$title, $message, $url !== '' ? $url : null, $bid]);
            $sent = (int)$fanout->rowCount();
            $pdo->prepare('UPDATE notification_broadcasts SET sent_count = ? WHERE id = ?')->execute([$sent, $bid]);
            return [$bid, 'اعلان عمومی برای ' . $sent . ' کاربر ارسال شد.'];
        } catch (Throwable $e) {
            return [null, 'ارسال ناموفق بود.'];
        }
    }
}

if (!function_exists('office_bc_list')) {
    /** @return array<int,array> */
    function office_bc_list(PDO $pdo): array
    {
        try {
            return $pdo->query(
                'SELECT id, title, message, url, sent_count, created_at,
                        (SELECT COUNT(*) FROM notifications n WHERE n.broadcast_id = notification_broadcasts.id AND n.is_read = 0) AS unread_count
                 FROM notification_broadcasts ORDER BY id DESC LIMIT 50'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_bc_delete')) {
    function office_bc_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            $pdo->prepare('DELETE FROM notifications WHERE broadcast_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM notification_broadcasts WHERE id = ?')->execute([$id]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_bc_recent')) {
    /** @return array<int,array> */
    function office_bc_recent(PDO $pdo): array
    {
        try {
            return $pdo->query(
                'SELECT n.id, n.type, n.title, n.message, n.url, n.is_read, n.created_at,
                        n.user_id, n.telegram_id, u.name AS user_name, u.phone AS user_phone
                 FROM notifications n
                 LEFT JOIN users u ON u.id = n.user_id
                 ORDER BY n.id DESC LIMIT 50'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_bc_notif_delete')) {
    function office_bc_notif_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            return $pdo->prepare('DELETE FROM notifications WHERE id = ?')->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_bc_events_master')) {
    /** سوییچ اصلی enable_notifications — عین منطق melkinoEventsEnabled. */
    function office_bc_events_master(PDO $pdo): bool
    {
        try {
            if (!function_exists('dbSettingGet')) {
                return true;
            }
            return (bool)dbSettingGet($pdo, 'global', 'enable_notifications', true);
        } catch (Throwable $e) {
            return true;
        }
    }
}

if (!function_exists('office_bc_events')) {
    /** @return array{defs:array<string,array>,state:array<string,bool>} */
    function office_bc_events(): array
    {
        $defs = [];
        $state = [];
        try {
            if (function_exists('melkinoNotificationEventDefs')) {
                $defs = melkinoNotificationEventDefs();
            }
            if (function_exists('melkinoNotificationEventsState')) {
                $state = melkinoNotificationEventsState();
            }
        } catch (Throwable $e) {
        }
        foreach ($defs as $id => $def) {
            if (!array_key_exists($id, $state)) {
                $state[$id] = true;
            }
        }
        return ['defs' => $defs, 'state' => $state];
    }
}

if (!function_exists('office_bc_events_save')) {
    /** @param array<string,mixed> $map */
    function office_bc_events_save(array $map): bool
    {
        if (!function_exists('melkinoSaveNotificationEvents')) {
            return false;
        }
        try {
            return (bool)melkinoSaveNotificationEvents($map);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_bc_notif_types')) {
    /** @return string[] عین فهرست مجاز سایت. */
    function office_bc_notif_types(): array
    {
        return ['welcome', 'match', 'property_match', 'broadcast', 'ad_submitted',
            'ad_published', 'ad_rejected', 'ad_revision_approved', 'ad_revision_rejected', 'ad_status', 'request_submitted',
            'request_status', 'system', 'price_condition'];
    }
}

if (!function_exists('office_bc_notif_delete_bulk')) {
    /** @param mixed[] $rawIds @return int تعداد حذف‌شده. */
    function office_bc_notif_delete_bulk(PDO $pdo, array $rawIds): int
    {
        $ids = [];
        foreach ($rawIds as $rawId) {
            $intId = (int)$rawId;
            if ($intId > 0) {
                $ids[$intId] = true;
            }
        }
        $ids = array_keys($ids);
        if ($ids === []) {
            return 0;
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $st = $pdo->prepare('DELETE FROM notifications WHERE id IN (' . $placeholders . ')');
            $st->execute($ids);
            return $st->rowCount();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('office_bc_notif_delete_filtered')) {
    /** @return array{0:bool,1:string} */
    function office_bc_notif_delete_filtered(PDO $pdo, string $type): array
    {
        $type = trim($type);
        try {
            if ($type === '' || $type === 'all') {
                $pdo->exec('DELETE FROM notifications');
                return [true, 'همهٔ اعلان‌های کاربران حذف شد.'];
            }
            if (!in_array($type, office_bc_notif_types(), true)) {
                return [false, 'نوع اعلان معتبر نیست.'];
            }
            $pdo->prepare('DELETE FROM notifications WHERE type = ?')->execute([$type]);
            return [true, 'اعلان‌های نوع انتخاب‌شده حذف شد.'];
        } catch (Throwable $e) {
            return [false, 'حذف ناموفق بود.'];
        }
    }
}
