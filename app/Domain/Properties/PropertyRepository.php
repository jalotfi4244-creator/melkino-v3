<?php
declare(strict_types=1);

namespace Melkino\Domain\Properties;

use Melkino\Core\Database;
use PDO;

/**
 * Melkino V2 — property persistence (spec §7).
 * All SQL for ads/images/amenities lives here; pages/services never touch PDO directly.
 * Only needed columns are selected (no SELECT * on hot paths — spec §143).
 */
final class PropertyRepository
{
    public const LIST_COLUMNS = 'a.id,a.ad_code,a.title,a.status,a.transaction_type,a.property_type,'
        . 'a.location,a.address,a.area,a.land_area,a.built_area,a.rooms,a.floor,a.year,'
        . 'a.price_sell,a.deposit,a.rent_monthly,a.full_rent,a.total_price,a.price_hidden,'
        . 'a.is_vip,a.views,a.created_at,a.published_at,a.default_image_no,a.description';

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findPublished(int $id): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        $st = $pdo->prepare("SELECT * FROM ads WHERE id = ? AND status = 'published' LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Server-side paginated listing (spec §76).
     * @param array<string,mixed> $filters tx,property_type,district,min_price,max_price,min_area,max_area,rooms,sort
     * @return array{items:array,total:int,page:int,per_page:int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }
        $page = max(1, $page);
        $perPage = min(60, max(1, $perPage));
        [$where, $params] = self::filterSql($filters);

        $st = $pdo->prepare("SELECT COUNT(*) FROM ads a {$where}");
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        $sort = self::sortSql((string)($filters['sort'] ?? 'newest'));
        $offset = ($page - 1) * $perPage;
        $st = $pdo->prepare("SELECT " . self::LIST_COLUMNS . " FROM ads a {$where} {$sort} LIMIT {$perPage} OFFSET {$offset}");
        $st->execute($params);
        $items = $st->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            $images = self::primaryImages(array_column($items, 'id'));
            foreach ($items as &$it) {
                $it['primary_image'] = $images[(int)$it['id']] ?? null;
            }
            unset($it);
        }
        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    /** @return array{string,array} */
    private static function filterSql(array $f): array
    {
        $w = ["a.status = 'published'"];
        $p = [];
        if (!empty($f['tx'])) {
            $w[] = 'a.transaction_type = ?';
            $p[] = (string)$f['tx'];
        }
        if (!empty($f['property_type'])) {
            $w[] = 'a.property_type = ?';
            $p[] = (string)$f['property_type'];
        }
        if (!empty($f['district'])) {
            $w[] = '(a.location LIKE ? OR a.address LIKE ?)';
            $p[] = '%' . $f['district'] . '%';
            $p[] = '%' . $f['district'] . '%';
        }
        if (!empty($f['q'])) {
            $w[] = '(a.title LIKE ? OR a.location LIKE ? OR a.address LIKE ? OR a.ad_code LIKE ?)';
            $q = '%' . $f['q'] . '%';
            array_push($p, $q, $q, $q, $q);
        }
        foreach (['min_price' => '>=', 'max_price' => '<='] as $k => $op) {
            if (isset($f[$k]) && is_numeric($f[$k]) && (float)$f[$k] > 0) {
                $w[] = "COALESCE(NULLIF(a.price_sell,0), NULLIF(a.total_price,0), NULLIF(a.deposit,0)) {$op} ?";
                $p[] = (float)$f[$k];
            }
        }
        foreach (['min_area' => '>=', 'max_area' => '<='] as $k => $op) {
            if (isset($f[$k]) && is_numeric($f[$k]) && (float)$f[$k] > 0) {
                $w[] = "COALESCE(NULLIF(a.area,0), NULLIF(a.built_area,0), NULLIF(a.land_area,0)) {$op} ?";
                $p[] = (float)$f[$k];
            }
        }
        if (!empty($f['rooms']) && is_numeric($f['rooms'])) {
            $w[] = 'a.rooms = ?';
            $p[] = (int)$f['rooms'];
        }
        return ['WHERE ' . implode(' AND ', $w), $p];
    }

    private static function sortSql(string $sort): string
    {
        return match ($sort) {
            'cheapest' => 'ORDER BY COALESCE(NULLIF(a.price_sell,0),NULLIF(a.total_price,0),999999999999) ASC',
            'expensive' => 'ORDER BY COALESCE(NULLIF(a.price_sell,0),NULLIF(a.total_price,0),0) DESC',
            'area_desc' => 'ORDER BY COALESCE(NULLIF(a.area,0),0) DESC',
            'views' => 'ORDER BY a.views DESC',
            default => 'ORDER BY a.is_vip DESC, COALESCE(a.published_at, a.created_at) DESC',
        };
    }

    /** @return array<int,?string> ad_id => filename (single query, no N+1) */
    public static function primaryImages(array $adIds): array
    {
        $pdo = Database::pdo();
        $adIds = array_values(array_unique(array_map('intval', $adIds)));
        $out = [];
        foreach ($adIds as $id) {
            $out[$id] = null;
        }
        if (!$pdo || !$adIds) {
            return $out;
        }
        $in = implode(',', array_fill(0, count($adIds), '?'));
        $st = $pdo->prepare(
            "SELECT ad_id, filename FROM images WHERE ad_id IN ({$in})
             ORDER BY ad_id, is_primary DESC, is_selected DESC, sort_order ASC, id ASC"
        );
        $st->execute($adIds);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int)$row['ad_id'];
            if ($out[$id] === null) {
                $out[$id] = $row['filename'];
            }
        }
        return $out;
    }

    /** @return array<int,array{filename:string,is_primary:bool}> */
    public static function gallery(int $adId, bool $publicOnly = true): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        $sql = 'SELECT filename, storage_path, is_primary, is_selected, publish_publicly, sort_order
                FROM images WHERE ad_id = ?';
        if ($publicOnly) {
            $sql .= ' AND (publish_publicly = 1 OR publish_publicly IS NULL)';
        }
        $sql .= ' ORDER BY is_primary DESC, is_selected DESC, sort_order ASC, id ASC';
        $st = $pdo->prepare($sql);
        $st->execute([$adId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return string[] amenity names for an ad */
    public static function amenities(int $adId): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $st = $pdo->prepare(
                'SELECT m.name FROM amenities m INNER JOIN ad_amenities x ON x.amenity_id = m.id
                 WHERE x.ad_id = ? ORDER BY m.sort_order ASC, m.name ASC'
            );
            $st->execute([$adId]);
            return array_column($st->fetchAll(PDO::FETCH_ASSOC), 'name');
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function incrementViews(int $adId): void
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $pdo->prepare('UPDATE ads SET views = views + 1 WHERE id = ?')->execute([$adId]);
        } catch (\Throwable $ignored) {
        }
    }

    /** @return array<int,array> similar published ads (same type/tx, closest area) */
    public static function similar(array $ad, int $limit = 6): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        $st = $pdo->prepare(
            'SELECT ' . self::LIST_COLUMNS . ' FROM ads a
             WHERE a.status = \'published\' AND a.id <> ?
               AND a.property_type = ? AND a.transaction_type = ?
             ORDER BY ABS(COALESCE(NULLIF(a.area,0),0) - ?) ASC, a.created_at DESC LIMIT ' . max(1, min(12, $limit))
        );
        $st->execute([(int)$ad['id'], (string)($ad['property_type'] ?? ''), (string)($ad['transaction_type'] ?? ''), (float)($ad['area'] ?? 0)]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($items) {
            $images = self::primaryImages(array_column($items, 'id'));
            foreach ($items as &$it) {
                $it['primary_image'] = $images[(int)$it['id']] ?? null;
            }
            unset($it);
        }
        return $items;
    }

    public static function setStatus(int $adId, string $status, array $extra = []): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        $allowed = ['pending', 'published', 'rejected', 'sold', 'suspended', 'archived'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $sets = ['status = ?', 'updated_at = NOW()'];
        $params = [$status];
        if ($status === 'published') {
            $sets[] = 'published_at = COALESCE(published_at, NOW())';
        }
        foreach ($extra as $col => $val) {
            if (preg_match('/^[a-z_]+$/', (string)$col)) {
                $sets[] = "`{$col}` = ?";
                $params[] = $val;
            }
        }
        $params[] = $adId;
        try {
            $st = $pdo->prepare('UPDATE ads SET ' . implode(', ', $sets) . ' WHERE id = ?');
            return $st->execute($params);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,int> status counts for admin filters */
    public static function statusCounts(): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query('SELECT status, COUNT(*) c FROM ads GROUP BY status')->fetchAll(PDO::FETCH_ASSOC);
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
