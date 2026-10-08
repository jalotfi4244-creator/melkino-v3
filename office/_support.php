<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه پشتیبانی (مرحله ۲۳)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ بخش ادمین support-api.php: فهرست تیکت‌ها، گفتگو،
 * پاسخ، بستن/بازگشایی + اعلان به صاحب تیکت (تکرار دفتر-سایدِ
 * melkinoNotifySupportOwner، چون db_helpers روی وب گارد لاگین بالا می‌کشد).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_revisions.php'; // office_rev_boot + office_rev_send_notification

if (!function_exists('office_sup_statuses')) {
    /** @return array<string,string> عین supportApiStatusLabel. */
    function office_sup_statuses(): array
    {
        return ['open' => 'در انتظار بررسی', 'answered' => 'پاسخ داده شده', 'pending' => 'در حال پیگیری', 'closed' => 'بسته شده'];
    }
}

if (!function_exists('office_sup_list')) {
    /** @return array<int,array> */
    function office_sup_list(PDO $pdo, string $status = ''): array
    {
        $where = '1 = 1';
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $where = 't.status = ?';
            $params[] = $status;
        }
        try {
            $st = $pdo->prepare(
                "SELECT t.id, t.user_id, t.telegram_id, t.phone, t.name AS user_name, t.subject, t.status, t.created_at, t.updated_at,
                    (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) AS message_count,
                    (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id AND sm.sender_type = 'user' AND sm.is_read = 0) AS unread_count,
                    (SELECT sm.message FROM support_messages sm WHERE sm.ticket_id = t.id ORDER BY sm.id DESC LIMIT 1) AS last_message
                 FROM support_tickets t
                 WHERE $where
                 ORDER BY t.updated_at DESC, t.id DESC"
            );
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_sup_get')) {
    function office_sup_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM support_tickets WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }
            if (!array_key_exists('user_name', $row)) {
                $row['user_name'] = $row['name'] ?? '';
            }
            return $row;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_sup_messages')) {
    /** @return array<int,array> */
    function office_sup_messages(PDO $pdo, int $id): array
    {
        try {
            $st = $pdo->prepare(
                'SELECT id, ticket_id, sender_type, sender_id, sender_name, message, is_read, created_at
                 FROM support_messages
                 WHERE ticket_id = ?
                 ORDER BY id ASC'
            );
            $st->execute([$id]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_sup_mark_read')) {
    function office_sup_mark_read(PDO $pdo, int $id): void
    {
        try {
            $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE ticket_id = ? AND sender_type = 'user' AND is_read = 0")
                ->execute([$id]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_sup_notify_owner')) {
    /** تکرار melkinoNotifySupportOwner (url همیشه support.php). */
    function office_sup_notify_owner(PDO $pdo, int $ticketId, string $type, string $title, string $template): bool
    {
        if ($ticketId <= 0) {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT user_id, telegram_id, phone, subject FROM support_tickets WHERE id=? LIMIT 1');
            $st->execute([$ticketId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            $message = str_replace('%s', (string)($row['subject'] ?? ''), $template);
            $userId = (int)($row['user_id'] ?? 0);
            $tg = trim((string)($row['telegram_id'] ?? ''));
            if ($userId > 0 || $tg !== '') {
                return office_rev_send_notification($pdo, $userId > 0 ? $userId : null, $tg !== '' ? $tg : null, $type, $title, $message, 'support.php', null);
            }
            $phone = trim((string)($row['phone'] ?? ''));
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
            return office_rev_send_notification($pdo, (int)$u['id'], !empty($u['telegram_id']) ? (string)$u['telegram_id'] : null, $type, $title, $message, 'support.php', null);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_sup_reply')) {
    /** @return array{0:bool,1:string} */
    function office_sup_reply(PDO $pdo, int $ticketId, string $message, ?int $adminId, string $adminName): array
    {
        $message = trim($message);
        if ($ticketId <= 0) {
            return [false, 'شناسه تیکت نامعتبر است.'];
        }
        if ($message === '') {
            return [false, 'لطفاً متن پاسخ را وارد کنید.'];
        }
        $len = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
        if ($len > 10000) {
            return [false, 'متن پیام بیش از حد طولانی است.'];
        }
        try {
            $st = $pdo->prepare('SELECT id FROM support_tickets WHERE id = ? LIMIT 1');
            $st->execute([$ticketId]);
            if (!$st->fetch(PDO::FETCH_ASSOC)) {
                return [false, 'تیکت پیدا نشد.'];
            }
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, sender_id, sender_name, message, is_read, created_at) VALUES (?, 'admin', ?, ?, ?, 1, NOW())")
                ->execute([$ticketId, $adminId, $adminName, $message]);
            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")->execute([$ticketId]);
            $pdo->commit();
            office_sup_notify_owner($pdo, $ticketId, 'support_reply', 'پاسخ پشتیبانی', 'به تیکت شما با موضوع «%s» پاسخ داده شد. برای مشاهده گفتگو کلیک کنید.');
            return [true, 'پاسخ ارسال شد.'];
        } catch (Throwable $e) {
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (Throwable $e2) {
            }
            return [false, 'خطا در ارسال پاسخ.'];
        }
    }
}

if (!function_exists('office_sup_set_status')) {
    /** @return array{0:bool,1:string} */
    function office_sup_set_status(PDO $pdo, int $ticketId, string $status): array
    {
        if ($ticketId <= 0) {
            return [false, 'شناسه تیکت نامعتبر است.'];
        }
        if (!array_key_exists($status, office_sup_statuses())) {
            return [false, 'وضعیت نامعتبر است.'];
        }
        try {
            $pdo->prepare('UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $ticketId]);
            if ($status === 'closed') {
                office_sup_notify_owner($pdo, $ticketId, 'support_closed', 'بسته شدن تیکت پشتیبانی', 'تیکت شما با موضوع «%s» بسته شد. اگر همچنان مشکل برقرار است، تیکت جدید ثبت کنید.');
            }
            return [true, 'وضعیت تیکت به‌روزرسانی شد.'];
        } catch (Throwable $e) {
            return [false, 'خطا در به‌روزرسانی وضعیت تیکت.'];
        }
    }
}
