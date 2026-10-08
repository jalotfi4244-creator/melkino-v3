<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — نقشه فایل‌ها (آفلاین، مرحله ۱۴)
 *--------------------------------------------------------------------------
 * بدون هیچ منبع خارجی: موقعیت نسبی فایل‌های مختصات‌دار روی SVG خودکشیده
 * + جدول + لینک «نمایش در نقشه» (ناوبری کاربر، نه امبد).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_map_points')) {
    /**
     * فایل‌های مختصات‌دار با فیلتر.
     * @return array<int,array<string,mixed>>
     */
    function office_map_points(PDO $pdo, string $status, string $tx, string $q, int $limit = 500): array
    {
        $where = ["latitude IS NOT NULL AND latitude <> ''", "longitude IS NOT NULL AND longitude <> ''"];
        $params = [];
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($tx !== '') {
            $where[] = 'transaction_type = ?';
            $params[] = $tx;
        }
        if ($q !== '') {
            $where[] = '(id LIKE ? OR title LIKE ? OR location LIKE ? OR phone LIKE ? OR address LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        try {
            $limit = min(max(1, $limit), 1000);
            $st = $pdo->prepare(
                "SELECT id, title, property_type, transaction_type, location, phone, status, latitude, longitude
                 FROM ads $whereSql ORDER BY created_at DESC, id DESC LIMIT $limit"
            );
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            // فقط مختصات عددی معتبر
            return array_values(array_filter($rows, static function ($r): bool {
                if (!is_numeric($r['latitude'] ?? null) || !is_numeric($r['longitude'] ?? null)) {
                    return false;
                }
                $lat = (float)$r['latitude'];
                $lng = (float)$r['longitude'];
                return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && ($lat != 0.0 || $lng != 0.0);
            }));
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_map_bounds')) {
    /**
     * کران مختصات + حاشیه ۵٪.
     * @return array{minLat:float,maxLat:float,minLng:float,maxLng:float}|null
     */
    function office_map_bounds(array $rows): ?array
    {
        if (!$rows) {
            return null;
        }
        $lats = array_map(static fn($r) => (float)$r['latitude'], $rows);
        $lngs = array_map(static fn($r) => (float)$r['longitude'], $rows);
        $minLat = min($lats);
        $maxLat = max($lats);
        $minLng = min($lngs);
        $maxLng = max($lngs);
        $padLat = max(($maxLat - $minLat) * 0.05, 0.0005);
        $padLng = max(($maxLng - $minLng) * 0.05, 0.0005);
        return [
            'minLat' => $minLat - $padLat,
            'maxLat' => $maxLat + $padLat,
            'minLng' => $minLng - $padLng,
            'maxLng' => $maxLng + $padLng,
        ];
    }
}

if (!function_exists('office_map_xy')) {
    /** @return array{0:float,1:float} [x, y] در viewBox (y وارونه). */
    function office_map_xy(float $lat, float $lng, array $bounds, int $w = 1000, int $h = 600): array
    {
        $spanLat = max($bounds['maxLat'] - $bounds['minLat'], 1e-9);
        $spanLng = max($bounds['maxLng'] - $bounds['minLng'], 1e-9);
        $x = ($lng - $bounds['minLng']) / $spanLng * $w;
        $y = (1 - ($lat - $bounds['minLat']) / $spanLat) * $h;
        return [round(min(max($x, 8), $w - 8), 1), round(min(max($y, 8), $h - 8), 1)];
    }
}

if (!function_exists('office_map_tx_color')) {
    function office_map_tx_color(string $tx): string
    {
        if (str_contains($tx, 'فروش') && !str_contains($tx, 'پیش')) {
            return '#16A34A';
        }
        if (str_contains($tx, 'اجاره') || str_contains($tx, 'رهن')) {
            return '#2563EB';
        }
        if (str_contains($tx, 'پیش')) {
            return '#EA580C';
        }
        if (str_contains($tx, 'مشارکت')) {
            return '#9333EA';
        }
        return '#6B7280';
    }
}

if (!function_exists('office_map_counts')) {
    /** @return array{with_coords:int, total:int} */
    function office_map_counts(PDO $pdo): array
    {
        try {
            $total = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
            $with = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE latitude IS NOT NULL AND latitude <> '' AND longitude IS NOT NULL AND longitude <> ''")->fetchColumn();
            return ['with_coords' => $with, 'total' => $total];
        } catch (Throwable $e) {
            return ['with_coords' => 0, 'total' => 0];
        }
    }
}
