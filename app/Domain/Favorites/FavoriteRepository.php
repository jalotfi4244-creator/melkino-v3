<?php
declare(strict_types=1);

namespace Melkino\Domain\Favorites;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — favorites persistence (user_id + telegram_id identity, legacy-compatible). */
final class FavoriteRepository
{
    /** @return array{col:string,val:mixed}|null */
    private static function owner(array $identity): ?array
    {
        if (!empty($identity['user_id'])) {
            return ['col' => 'user_id', 'val' => (int)$identity['user_id']];
        }
        if (!empty($identity['telegram_id'])) {
            return ['col' => 'telegram_id', 'val' => (string)$identity['telegram_id']];
        }
        return null;
    }

    public static function isFavorite(int $adId, array $identity): bool
    {
        $pdo = Database::pdo();
        $o = self::owner($identity);
        if (!$pdo || $adId <= 0 || !$o) {
            return false;
        }
        $st = $pdo->prepare("SELECT 1 FROM favorites WHERE ad_id = ? AND `{$o['col']}` = ? LIMIT 1");
        $st->execute([$adId, $o['val']]);
        return (bool)$st->fetchColumn();
    }

    /** @return array{ok:bool,saved:bool} toggle with optimistic-UI support (spec §20). */
    public static function toggle(int $adId, array $identity): array
    {
        $pdo = Database::pdo();
        $o = self::owner($identity);
        if (!$pdo || $adId <= 0 || !$o) {
            return ['ok' => false, 'saved' => false];
        }
        try {
            if (self::isFavorite($adId, $identity)) {
                $st = $pdo->prepare("DELETE FROM favorites WHERE ad_id = ? AND `{$o['col']}` = ? LIMIT 1");
                $st->execute([$adId, $o['val']]);
                return ['ok' => true, 'saved' => false];
            }
            $userId = $o['col'] === 'user_id' ? $o['val'] : ($identity['user_id'] ?? null);
            $tg = $o['col'] === 'telegram_id' ? $o['val'] : ($identity['telegram_id'] ?? null);
            $st = $pdo->prepare('INSERT INTO favorites (user_id, telegram_id, ad_id, created_at) VALUES (?,?,?,NOW())');
            $st->execute([$userId ?: null, $tg ?: null, $adId]);
            return ['ok' => true, 'saved' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'saved' => false];
        }
    }

    /** @return array<int,array> favorite ads with card columns */
    public static function list(array $identity, ?string $tx = null, int $limit = 100): array
    {
        $pdo = Database::pdo();
        $o = self::owner($identity);
        if (!$pdo || !$o) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $sql = 'SELECT a.* FROM favorites f INNER JOIN ads a ON a.id = f.ad_id
                WHERE f.`' . $o['col'] . '` = ?';
        $params = [$o['val']];
        if ($tx !== null && $tx !== '') {
            $sql .= ' AND a.transaction_type = ?';
            $params[] = $tx;
        }
        $sql .= ' ORDER BY f.created_at DESC LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,bool> ad_id => true (batch state for grids, no N+1) */
    public static function states(array $adIds, array $identity): array
    {
        $pdo = Database::pdo();
        $o = self::owner($identity);
        $adIds = array_values(array_unique(array_map('intval', $adIds)));
        if (!$pdo || !$o || !$adIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($adIds), '?'));
        $st = $pdo->prepare("SELECT ad_id FROM favorites WHERE ad_id IN ({$in}) AND `{$o['col']}` = ?");
        $st->execute([...$adIds, $o['val']]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(int)$r['ad_id']] = true;
        }
        return $out;
    }

    public static function count(array $identity): int
    {
        $pdo = Database::pdo();
        $o = self::owner($identity);
        if (!$pdo || !$o) {
            return 0;
        }
        $st = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE `{$o['col']}` = ?");
        $st->execute([$o['val']]);
        return (int)$st->fetchColumn();
    }
}
