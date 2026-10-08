<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Csrf;
use Melkino\Core\Database;
use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Http\Gate;

/**
 * Melkino V2 — support tickets (spec: identical POST JSON contract as legacy support.php:
 * create_ticket / send_message / close_ticket, same SQL, same messages).
 */
final class SupportController
{
    public function handle(): string
    {
        Gate::check('support.php');
        foreach (['db_helpers.php', 'security-lib.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        $pdo = Database::pdo();
        if (!$pdo) {
            http_response_code(500);
            exit('اتصال به دیتابیس برقرار نیست.');
        }
        $identity = function_exists('melkinoCurrentIdentity') ? (array)melkinoCurrentIdentity() : [];
        $userId = !empty($identity['user_id']) ? trim((string)$identity['user_id']) : '';
        $telegramId = !empty($identity['telegram_id']) ? trim((string)$identity['telegram_id']) : '';
        $userName = !empty($identity['user']['name']) ? trim((string)$identity['user']['name'])
            : trim((string)($_SESSION['user_name'] ?? ''));
        $userPhone = !empty($identity['phone']) ? trim((string)$identity['phone'])
            : trim((string)($_SESSION['user_phone'] ?? ''));
        if ($userName === '') {
            $userName = 'کاربر ملکینو';
        }
        if ($userId === '' && $telegramId === '' && $userPhone === '') {
            http_response_code(401);
            exit('برای استفاده از پشتیبانی ابتدا وارد حساب کاربری شوید.');
        }
        if (function_exists('melkinoEnsureSupportTables') && !melkinoEnsureSupportTables()) {
            http_response_code(500);
            exit('ارتباط با دیتابیس برقرار نشد یا جدول‌های پشتیبانی ساخته نشدند.');
        }

        if (Request::method() === 'POST') {
            $this->post($pdo, $userId, $telegramId, $userName, $userPhone);
        }

        [$where, $params] = self::userWhere($userId, $telegramId, $userPhone);
        $st = $pdo->prepare(
            "SELECT t.*, (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) AS message_count
             FROM support_tickets t WHERE $where ORDER BY t.updated_at DESC, t.id DESC"
        );
        $st->execute($params);
        $tickets = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $activeId = Request::int('ticket', 0);
        $active = null;
        $messages = [];
        if ($activeId > 0) {
            $active = self::belongsToUser($pdo, $activeId, $userId, $telegramId, $userPhone);
            if ($active) {
                $st = $pdo->prepare('SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY id ASC');
                $st->execute([$activeId]);
                $messages = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }
        }

        return melkinoView('layouts/public.php', [
            'title' => 'پشتیبانی | ملکینو',
            'description' => 'گفتگو با پشتیبانی ملکینو',
            'active_nav' => 'profile',
            'scripts' => ['support'],
            'content_view' => 'pages/support.php',
            'content_data' => ['tickets' => $tickets, 'active' => $active, 'messages' => $messages],
        ]);
    }

    /** @return array{0:string,1:array} */
    public static function userWhere(string $userId, string $telegramId, string $userPhone): array
    {
        $parts = [];
        $params = [];
        if ($userId !== '') {
            $parts[] = 'user_id = ?';
            $params[] = $userId;
        }
        if ($telegramId !== '') {
            $parts[] = 'telegram_id = ?';
            $params[] = $telegramId;
        }
        if ($userPhone !== '') {
            $normalized = preg_replace('/[\s\-\(\)]/', '', $userPhone);
            $parts[] = "REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') = ?";
            $params[] = $normalized;
        }
        if (!$parts) {
            $parts[] = '1 = 0';
        }
        return ['(' . implode(' OR ', $parts) . ')', $params];
    }

    public static function belongsToUser(\PDO $pdo, int $ticketId, string $userId, string $telegramId, string $userPhone): ?array
    {
        [$where, $params] = self::userWhere($userId, $telegramId, $userPhone);
        $params[] = $ticketId;
        $st = $pdo->prepare("SELECT * FROM support_tickets WHERE $where AND id = ? LIMIT 1");
        $st->execute($params);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'answered' => 'پاسخ داده شده',
            'pending' => 'در حال پیگیری',
            'closed' => 'بسته شده',
            default => 'در انتظار بررسی',
        };
    }

    /** Flat legacy JSON shape (same-page endpoint — byte-identical to V1). */
    private static function flat(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function post(\PDO $pdo, string $userId, string $telegramId, string $userName, string $userPhone): void
    {
        Csrf::check();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $action = Request::string('action');
            $senderId = $userId !== '' ? $userId : ($telegramId !== '' ? $telegramId : $userPhone);

            if ($action === 'create_ticket') {
                $subject = Request::string('subject');
                $message = Request::string('message');
                if ($subject === '') {
                    throw new \RuntimeException('لطفاً موضوع درخواست را وارد کنید.');
                }
                if (mb_strlen($subject) > 255) {
                    throw new \RuntimeException('موضوع درخواست بیش از حد طولانی است.');
                }
                if ($message === '') {
                    throw new \RuntimeException('لطفاً مشکل یا سوال خود را بنویسید.');
                }
                if (mb_strlen($message) > 10000) {
                    throw new \RuntimeException('متن پیام بیش از حد طولانی است.');
                }
                $pdo->beginTransaction();
                $st = $pdo->prepare('INSERT INTO support_tickets (user_id, telegram_id, phone, name, subject, status) VALUES (?, ?, ?, ?, ?, \'open\')');
                $st->execute([
                    $userId !== '' ? $userId : null, $telegramId !== '' ? $telegramId : null,
                    $userPhone !== '' ? $userPhone : null, $userName, $subject,
                ]);
                $ticketId = (int)$pdo->lastInsertId();
                $st = $pdo->prepare('INSERT INTO support_messages (ticket_id, sender_type, sender_id, sender_name, message, is_read) VALUES (?, \'user\', ?, ?, ?, 0)');
                $st->execute([$ticketId, $senderId !== '' ? $senderId : null, $userName, $message]);
                $pdo->commit();
                self::flat(['success' => true, 'message' => 'درخواست شما با موفقیت برای پشتیبانی ارسال شد.', 'ticket_id' => $ticketId]);
            }

            if ($action === 'send_message') {
                $ticketId = Request::int('ticket_id', 0);
                $message = Request::string('message');
                if ($ticketId <= 0) {
                    throw new \RuntimeException('شناسه درخواست نامعتبر است.');
                }
                if ($message === '') {
                    throw new \RuntimeException('لطفاً پیام خود را وارد کنید.');
                }
                if (mb_strlen($message) > 10000) {
                    throw new \RuntimeException('متن پیام بیش از حد طولانی است.');
                }
                $ticket = self::belongsToUser($pdo, $ticketId, $userId, $telegramId, $userPhone);
                if (!$ticket) {
                    throw new \RuntimeException('این درخواست پشتیبانی متعلق به شما نیست.');
                }
                if ($ticket['status'] === 'closed') {
                    throw new \RuntimeException('این درخواست بسته شده است. برای مشکل جدید یک درخواست جدید ایجاد کنید.');
                }
                $pdo->beginTransaction();
                $st = $pdo->prepare('INSERT INTO support_messages (ticket_id, sender_type, sender_id, sender_name, message, is_read) VALUES (?, \'user\', ?, ?, ?, 0)');
                $st->execute([$ticketId, $senderId !== '' ? $senderId : null, $userName, $message]);
                $pdo->prepare('UPDATE support_tickets SET status = \'open\', updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$ticketId]);
                $pdo->commit();
                self::flat(['success' => true, 'message' => 'پیام شما ارسال شد.']);
            }

            if ($action === 'close_ticket') {
                $ticketId = Request::int('ticket_id', 0);
                if ($ticketId <= 0) {
                    throw new \RuntimeException('شناسه درخواست نامعتبر است.');
                }
                if (!self::belongsToUser($pdo, $ticketId, $userId, $telegramId, $userPhone)) {
                    throw new \RuntimeException('درخواست پیدا نشد.');
                }
                $pdo->prepare('UPDATE support_tickets SET status = \'closed\', updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$ticketId]);
                self::flat(['success' => true, 'message' => 'درخواست پشتیبانی بسته شد.']);
            }

            throw new \RuntimeException('عملیات نامعتبر است.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = function_exists('melkinoSafeError')
                ? melkinoSafeError($e, 'support.page', 'درخواست انجام نشد.')
                : $e->getMessage();
            self::flat(['success' => false, 'message' => $e instanceof \RuntimeException ? $e->getMessage() : $msg]);
        }
    }
}
