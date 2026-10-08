<?php
declare(strict_types=1);

namespace Melkino\Domain\Promotions;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — promotions feed (placements preserved from legacy promotions.php). */
final class PromotionRepository
{
    /** @return array<int,array> active promos for a placement */
    public static function active(string $placement, int $limit = 10): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $st = $pdo->prepare(
                'SELECT * FROM promotions WHERE is_active = 1 AND placement = ?
                 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())
                 ORDER BY id DESC LIMIT ' . max(1, min(20, $limit))
            );
            $st->execute([$placement]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function trackView(int $id): void
    {
        $pdo = Database::pdo();
        if (!$pdo || $id <= 0) {
            return;
        }
        try {
            $pdo->prepare('UPDATE promotions SET views = views + 1 WHERE id = ?')->execute([$id]);
        } catch (\Throwable $ignored) {
        }
    }

    public static function trackClick(int $id): void
    {
        $pdo = Database::pdo();
        if (!$pdo || $id <= 0) {
            return;
        }
        try {
            $pdo->prepare('UPDATE promotions SET clicks = clicks + 1 WHERE id = ?')->execute([$id]);
        } catch (\Throwable $ignored) {
        }
    }
}
