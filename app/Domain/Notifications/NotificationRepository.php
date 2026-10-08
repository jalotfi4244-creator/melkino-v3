<?php
declare(strict_types=1);

namespace Melkino\Domain\Notifications;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — notifications persistence (+ dedupe_key / type columns ensured by migration). */
final class NotificationRepository
{
    public static function create(array $row): ?int
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        $cols = ['user_id', 'telegram_id', 'request_id', 'ad_id', 'title', 'message', 'type', 'url', 'broadcast_id', 'match_percent', 'dedupe_key'];
        $data = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $row)) {
                $data[$c] = $row[$c];
            }
        }
        if (empty($data['message'])) {
            return null;
        }
        // Dedupe: same key => skip (spec §66, §149).
        if (!empty($data['dedupe_key']) && Database::columnExists('notifications', 'dedupe_key')) {
            try {
                $st = $pdo->prepare('SELECT id FROM notifications WHERE dedupe_key = ? LIMIT 1');
                $st->execute([(string)$data['dedupe_key']]);
                if ($st->fetchColumn()) {
                    return null;
                }
            } catch (\Throwable $ignored) {
            }
        } else {
            unset($data['dedupe_key']);
        }
        $keys = array_keys($data);
        $marks = implode(',', array_fill(0, count($keys), '?'));
        try {
            $st = $pdo->prepare('INSERT INTO notifications (`' . implode('`,`', $keys) . '`, created_at) VALUES (' . $marks . ', NOW())');
            $st->execute(array_values($data));
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array{items:array,total:int,page:int,per_page:int,unread:int} */
    public static function paginate(array $identity, ?string $tab = null, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'unread' => 0];
        }
        [$where, $params] = self::ownerSql($identity, 'n');
        if ($where === '') {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'unread' => 0];
        }
        if ($tab && in_array($tab, ['property', 'request', 'account'], true)) {
            $where .= ' AND n.type LIKE ?';
            $params[] = $tab . '%';
        }
        $st = $pdo->prepare("SELECT COUNT(*) FROM notifications n WHERE {$where}");
        $st->execute($params);
        $total = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM notifications n WHERE {$where} AND n.is_read = 0");
        $st->execute($params);
        $unread = (int)$st->fetchColumn();

        $page = max(1, $page);
        $perPage = min(60, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $st = $pdo->prepare(
            "SELECT n.* FROM notifications n WHERE {$where} ORDER BY n.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $st->execute($params);
        return ['items' => $st->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'unread' => $unread];
    }

    public static function unreadCount(array $identity): int
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return 0;
        }
        [$where, $params] = self::ownerSql($identity, 'n');
        if ($where === '') {
            return 0;
        }
        $st = $pdo->prepare("SELECT COUNT(*) FROM notifications n WHERE {$where} AND n.is_read = 0");
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public static function markRead(int $id, array $identity): bool
    {
        $pdo = Database::pdo();
        if (!$pdo || $id <= 0) {
            return false;
        }
        [$where, $params] = self::ownerSql($identity, '');
        if ($where === '') {
            return false;
        }
        $st = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND ({$where})");
        $st->execute([$id, ...$params]);
        return $st->rowCount() > 0;
    }

    public static function markAllRead(array $identity): int
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return 0;
        }
        [$where, $params] = self::ownerSql($identity, '');
        if ($where === '') {
            return 0;
        }
        $st = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0 AND ({$where})");
        $st->execute($params);
        return $st->rowCount();
    }

    /** @return array{string,array} */
    private static function ownerSql(array $identity, string $alias): array
    {
        $p = $alias !== '' ? $alias . '.' : '';
        $conds = [];
        $params = [];
        if (!empty($identity['user_id'])) {
            $conds[] = "{$p}user_id = ?";
            $params[] = (int)$identity['user_id'];
        }
        if (!empty($identity['telegram_id'])) {
            $conds[] = "{$p}telegram_id = ?";
            $params[] = (string)$identity['telegram_id'];
        }
        if (!$conds) {
            return ['', []];
        }
        return ['(' . implode(' OR ', $conds) . ')', $params];
    }
}
