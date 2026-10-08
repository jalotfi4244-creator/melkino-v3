<?php
declare(strict_types=1);

namespace Melkino\Domain\Support;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — support tickets persistence (legacy schema preserved). */
final class SupportRepository
{
    /** @return array<int,array> */
    public static function mine(array $identity, int $limit = 50): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        $conds = [];
        $params = [];
        if (!empty($identity['user_id'])) {
            $conds[] = 'user_id = ?';
            $params[] = (int)$identity['user_id'];
        }
        if (!empty($identity['telegram_id'])) {
            $conds[] = 'telegram_id = ?';
            $params[] = (string)$identity['telegram_id'];
        }
        if (!empty($identity['phone'])) {
            $conds[] = 'phone = ?';
            $params[] = (string)$identity['phone'];
        }
        if (!$conds) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $st = $pdo->prepare('SELECT * FROM support_tickets WHERE (' . implode(' OR ', $conds) . ') ORDER BY updated_at DESC LIMIT ' . $limit);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array> */
    public static function messages(int $ticketId, int $limit = 200): array
    {
        $pdo = Database::pdo();
        if (!$pdo || $ticketId <= 0) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $st = $pdo->prepare('SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC LIMIT ' . $limit);
        $st->execute([$ticketId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function openCount(): int
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','pending','waiting')")->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
