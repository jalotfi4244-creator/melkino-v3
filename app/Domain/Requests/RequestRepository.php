<?php
declare(strict_types=1);

namespace Melkino\Domain\Requests;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — property_requests persistence. */
final class RequestRepository
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM property_requests WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo || trim($code) === '') {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM property_requests WHERE tracking_code = ? LIMIT 1');
        $st->execute([trim($code)]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array<int,array> active requests of current identity (phone/messenger). */
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
        if (!empty($identity['phone'])) {
            $conds[] = 'phone = ?';
            $params[] = (string)$identity['phone'];
        }
        if (!empty($identity['telegram_id'])) {
            $conds[] = 'telegram_id = ?';
            $params[] = (string)$identity['telegram_id'];
        }
        if (!$conds) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $st = $pdo->prepare(
            'SELECT * FROM property_requests WHERE (' . implode(' OR ', $conds) . ')
             ORDER BY created_at DESC LIMIT ' . $limit
        );
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function setStatus(int $id, string $status): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $st = $pdo->prepare('UPDATE property_requests SET status = ?, updated_at = NOW() WHERE id = ?');
            return $st->execute([substr($status, 0, 32), $id]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{items:array,total:int,page:int,per_page:int} */
    public static function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }
        $w = [];
        $p = [];
        foreach (['status' => 'status', 'transaction_type' => 'transaction_type', 'property_type' => 'property_type'] as $k => $col) {
            if (!empty($filters[$k])) {
                $w[] = "{$col} = ?";
                $p[] = (string)$filters[$k];
            }
        }
        if (!empty($filters['q'])) {
            $w[] = '(tracking_code LIKE ? OR phone LIKE ? OR location LIKE ? OR last_name LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            array_push($p, $q, $q, $q, $q);
        }
        $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
        $st = $pdo->prepare("SELECT COUNT(*) FROM property_requests {$where}");
        $st->execute($p);
        $total = (int)$st->fetchColumn();
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $st = $pdo->prepare("SELECT * FROM property_requests {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $st->execute($p);
        return ['items' => $st->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
