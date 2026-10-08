<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — پین‌های نقشه دفتر (مرحله ۱۴ نهایی)
 *--------------------------------------------------------------------------
 * برخلاف نقشه عمومی سایت (حریم ~۵۰ متر)، نقشه دفتر مختصات دقیق دیتابیس
 * را نشان می‌دهد + همه وضعیت‌ها (نه فقط منتشرشده).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_mappins')) {
    /**
     * فایل‌های مختصات‌دار با مختصات دقیق + فیلتر.
     * @return array<int,array<string,mixed>>
     */
    function office_mappins(PDO $pdo, string $status, string $tx, string $q, int $limit = 500): array
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
                "SELECT id, title, property_type, transaction_type, location, area, status, latitude, longitude
                 FROM ads $whereSql ORDER BY created_at DESC, id DESC LIMIT $limit"
            );
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

if (!function_exists('office_mappin_colors')) {
    /** رنگ مارکر هر نوع ملک — همان تنظیمات سایت (تک‌منبع). */
    function office_mappin_colors(PDO $pdo): array
    {
        $defaults = [
            'آپارتمان' => '#3b82f6',
            'ویلایی' => '#22c55e',
            'باغ' => '#166534',
            'زمین' => '#92400e',
            'تجاری' => '#f97316',
            'اداری' => '#7c3aed',
        ];
        $f = dirname(__DIR__) . '/map-lib.php';
        if (is_file($f)) {
            require_once $f;
        }
if (!function_exists('melkinoMapPublicSettings')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/map-lib.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}
        if (function_exists('melkinoMapPublicSettings')) {
            try {
                $s = melkinoMapPublicSettings($pdo);
                if (is_array($s['marker_colors'] ?? null) && ($s['marker_colors'] ?? [])) {
                    return $s['marker_colors'];
                }
            } catch (Throwable $e) {
            }
        }
        return $defaults;
    }
}

if (!function_exists('office_mappin_tiles')) {
    /** @return array{url:string,attr:string} همان تایل سایت */
    function office_mappin_tiles(PDO $pdo): array
    {
        $f = dirname(__DIR__) . '/map-lib.php';
        if (is_file($f)) {
            require_once $f;
        }
if (!function_exists('melkinoMapPublicSettings')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/map-lib.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}
        if (function_exists('melkinoMapPublicSettings')) {
            try {
                $s = melkinoMapPublicSettings($pdo);
                return [
                    'url' => (string)($s['tile_url'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
                    'attr' => (string)($s['tile_attr'] ?? '© OpenStreetMap'),
                ];
            } catch (Throwable $e) {
            }
        }
        return ['url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'attr' => '© OpenStreetMap'];
    }
}
