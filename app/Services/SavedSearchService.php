<?php
declare(strict_types=1);

namespace Melkino\Services;

use Melkino\Core\Database;
use Melkino\Domain\Notifications\NotificationService;
use PDO;

/** Melkino V2 — saved searches: first-class feature (spec §14, §149). */
final class SavedSearchService
{
    /** @return array<int,array> */
    public static function mine(array $identity): array
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
        if (!$conds) {
            return [];
        }
        $st = $pdo->prepare('SELECT * FROM saved_searches WHERE (' . implode(' OR ', $conds) . ') ORDER BY created_at DESC');
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{ok:bool,id:int} */
    public static function save(array $identity, string $title, array $filters, bool $notify = true): array
    {
        $pdo = Database::pdo();
        if (!$pdo || (empty($identity['user_id']) && empty($identity['phone']))) {
            return ['ok' => false, 'id' => 0];
        }
        $f = SearchService::canonicalize($filters);
        try {
            $st = $pdo->prepare(
                'INSERT INTO saved_searches (user_id, phone, title, tx, property_type, district,
                 min_price, max_price, min_area, max_area, rooms, notify, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $st->execute([
                $identity['user_id'] ?? null, $identity['phone'] ?? null,
                mb_substr(trim($title) !== '' ? trim($title) : 'جستجوی ذخیره‌شده', 0, 120),
                $f['tx'] ?? null, $f['property_type'] ?? null, $f['district'] ?? null,
                $f['min_price'] ?? null, $f['max_price'] ?? null, $f['min_area'] ?? null,
                $f['max_area'] ?? null, $f['rooms'] ?? null, $notify ? 1 : 0,
            ]);
            return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'id' => 0];
        }
    }

    public static function delete(int $id, array $identity): bool
    {
        $pdo = Database::pdo();
        if (!$pdo || $id <= 0) {
            return false;
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
        if (!$conds) {
            return false;
        }
        try {
            $st = $pdo->prepare('DELETE FROM saved_searches WHERE id = ? AND (' . implode(' OR ', $conds) . ') LIMIT 1');
            $st->execute([$id, ...$params]);
            return $st->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Match a newly published ad against saved searches and notify (deduped per search+ad).
     * Called from the publish flow. Returns notified count.
     */
    public static function notifyForAd(array $ad): int
    {
        $pdo = Database::pdo();
        if (!$pdo || ($ad['status'] ?? '') !== 'published') {
            return 0;
        }
        try {
            $rows = $pdo->query('SELECT * FROM saved_searches WHERE notify = 1')->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return 0;
        }
        $n = 0;
        foreach ($rows as $s) {
            if (!self::matches($s, $ad)) {
                continue;
            }
            $id = NotificationService::emit('saved_search.matched', [
                'user_id' => $s['user_id'], 'ad' => $ad, 'search_title' => $s['title'],
            ], ['dedupe' => 'savedsearch:' . (int)$s['id'] . ':' . (int)$ad['id']]);
            if ($id) {
                $n++;
                try {
                    $pdo->prepare('UPDATE saved_searches SET last_notified_at = NOW() WHERE id = ?')->execute([(int)$s['id']]);
                } catch (\Throwable $ignored) {
                }
            }
        }
        return $n;
    }

    private static function matches(array $s, array $ad): bool
    {
        if (!empty($s['tx']) && (string)$ad['transaction_type'] !== (string)$s['tx']) {
            return false;
        }
        if (!empty($s['property_type']) && (string)$ad['property_type'] !== (string)$s['property_type']) {
            return false;
        }
        if (!empty($s['district']) && stripos((string)($ad['location'] ?? '') . ' ' . ($ad['address'] ?? ''), (string)$s['district']) === false) {
            return false;
        }
        $price = (float)($ad['price_sell'] ?? $ad['total_price'] ?? 0);
        if (!empty($s['min_price']) && $price > 0 && $price < (float)$s['min_price']) {
            return false;
        }
        if (!empty($s['max_price']) && $price > 0 && $price > (float)$s['max_price']) {
            return false;
        }
        $area = (float)($ad['area'] ?? $ad['built_area'] ?? 0);
        if (!empty($s['min_area']) && $area > 0 && $area < (float)$s['min_area']) {
            return false;
        }
        if (!empty($s['max_area']) && $area > 0 && $area > (float)$s['max_area']) {
            return false;
        }
        if (!empty($s['rooms']) && (int)($ad['rooms'] ?? 0) !== (int)$s['rooms']) {
            return false;
        }
        return true;
    }
}
