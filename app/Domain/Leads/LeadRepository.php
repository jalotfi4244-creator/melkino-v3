<?php
declare(strict_types=1);

namespace Melkino\Domain\Leads;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — light CRM on comm_contacts (spec §39). Statuses: new/contacted/visit/negotiation/won/lost. */
final class LeadRepository
{
    public const STATUSES = ['new', 'contacted', 'visit', 'negotiation', 'won', 'lost'];
    public const STATUS_FA = [
        'new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'visit' => 'بازدید',
        'negotiation' => 'مذاکره', 'won' => 'موفق', 'lost' => 'از دست رفته',
    ];

    /** @return array{items:array,total:int,page:int,per_page:int} */
    public static function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }
        $w = [];
        $p = [];
        if (!empty($filters['status'])) {
            $w[] = 'status = ?';
            $p[] = (string)$filters['status'];
        }
        if (!empty($filters['q'])) {
            $w[] = '(phone LIKE ? OR name LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            array_push($p, $q, $q, $q, $q);
        }
        $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM comm_contacts {$where}");
            $st->execute($p);
            $total = (int)$st->fetchColumn();
            $page = max(1, $page);
            $perPage = min(100, max(1, $perPage));
            $st = $pdo->prepare("SELECT * FROM comm_contacts {$where} ORDER BY last_activity DESC, id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage));
            $st->execute($p);
            return ['items' => $st->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }
    }

    public static function setStatus(int $id, string $status): bool
    {
        $pdo = Database::pdo();
        if (!$pdo || $id <= 0 || !in_array($status, self::STATUSES, true)) {
            return false;
        }
        try {
            $st = $pdo->prepare('UPDATE comm_contacts SET status = ?, updated_at = NOW() WHERE id = ?');
            return $st->execute([$status, $id]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,int> */
    public static function statusCounts(): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query('SELECT status, COUNT(*) c FROM comm_contacts GROUP BY status')->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[(string)$r['status']] = (int)$r['c'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
