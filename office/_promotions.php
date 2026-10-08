<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه مدیریت سایت (مرحله ۱۷)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ مدیریت تبلیغات پنل سایت (admin-promotions.php):
 * همان جدول promotions، همان اعتبارسنجی، همان قوانین آپلود
 * (۵ مگ، تصویر، uploads/promotions/promo_...‎).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

// توجه: promotions.php سایت عمداً لود نمی‌شود — نسخه مستقر روی هاست هنگام
// لود در کانتکست دفتر بی‌صدا exit می‌کند (ERR_EMPTY_RESPONSE). آماده‌سازی
// جدول با همان DDL سایت، دفتر-ساید انجام می‌شود (افزایشی، IF NOT EXISTS).

if (!function_exists('office_promo_boot')) {
    function office_promo_boot(PDO $pdo): void
    {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS promotions (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL DEFAULT \'\',
                    image_url VARCHAR(500) NOT NULL DEFAULT \'\',
                    link_url VARCHAR(500) NOT NULL DEFAULT \'\',
                    button_text VARCHAR(100) NOT NULL DEFAULT \'مشاهده\',
                    description VARCHAR(1000) NOT NULL DEFAULT \'\',
                    placement VARCHAR(50) NOT NULL DEFAULT \'all\',
                    position_after INT UNSIGNED NOT NULL DEFAULT 3,
                    repeat_every INT UNSIGNED NOT NULL DEFAULT 0,
                    start_date DATETIME NULL,
                    end_date DATETIME NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    views INT UNSIGNED NOT NULL DEFAULT 0,
                    clicks INT UNSIGNED NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_promotions_active (is_active),
                    KEY idx_promotions_placement (placement)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_promo_placements')) {
    /** @return array<string,string> */
    function office_promo_placements(): array
    {
        return ['all' => 'همه', 'home' => 'خانه', 'properties' => 'ملک‌ها', 'search' => 'جست‌وجو', 'vip' => 'ویژه'];
    }
}

if (!function_exists('office_promo_list')) {
    /** @return array<int,array> */
    function office_promo_list(PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT * FROM promotions ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_promo_get')) {
    function office_promo_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM promotions WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_promo_stats')) {
    /** @return array{total:int,views:int,clicks:int} */
    function office_promo_stats(PDO $pdo): array
    {
        try {
            $row = $pdo->query('SELECT COUNT(*) AS total, COALESCE(SUM(views),0) AS views, COALESCE(SUM(clicks),0) AS clicks FROM promotions')->fetch(PDO::FETCH_ASSOC);
            return [
                'total' => (int)($row['total'] ?? 0),
                'views' => (int)($row['views'] ?? 0),
                'clicks' => (int)($row['clicks'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'views' => 0, 'clicks' => 0];
        }
    }
}

if (!function_exists('office_promo_clean')) {
    /** @return array{0:array<string,mixed>,1:string[]} پاک‌سازی عین منطق سایت + خطاها. */
    function office_promo_clean(array $in): array
    {
        $dt = static function (string $v): string|false|null {
            $v = trim($v);
            if ($v === '') {
                return null;
            }
            $v = str_replace('T', ' ', $v);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $v)) {
                return false;
            }
            return strlen($v) === 16 ? $v . ':00' : $v;
        };
        $fields = [
            'title' => trim((string)($in['title'] ?? '')),
            'image_url' => trim((string)($in['image_url'] ?? '')),
            'link_url' => trim((string)($in['link_url'] ?? '')),
            'button_text' => '',
            'description' => '',
            'placement' => trim((string)($in['placement'] ?? 'all')),
            'position_after' => max(1, (int)($in['position_after'] ?? 3)),
            'repeat_every' => max(0, (int)($in['repeat_every'] ?? 0)),
            'start_date' => $dt((string)($in['start_date'] ?? '')),
            'end_date' => $dt((string)($in['end_date'] ?? '')),
            'is_active' => !empty($in['is_active']) ? 1 : 0,
        ];
        $errors = [];
        if (!array_key_exists($fields['placement'], office_promo_placements())) {
            $fields['placement'] = 'all';
        }
        if ($fields['image_url'] === '') {
            $errors[] = 'تصویر تبلیغ الزامی است.';
        }
        if ($fields['link_url'] !== '' && !preg_match('#^https?://#i', $fields['link_url'])) {
            $errors[] = 'لینک باید با http:// یا https:// شروع شود.';
        }
        if ($fields['start_date'] === false || $fields['end_date'] === false) {
            $errors[] = 'قالب تاریخ نامعتبر است.';
        }
        return [$fields, $errors];
    }
}

if (!function_exists('office_promo_save')) {
    /** @return array{0:?int,1:string[]} */
    function office_promo_save(PDO $pdo, ?int $id, array $in): array
    {
        [$fields, $errors] = office_promo_clean($in);
        if ($errors) {
            return [null, $errors];
        }
        try {
            if ($id !== null && $id > 0) {
                $sql = 'UPDATE promotions SET title=?, image_url=?, link_url=?, button_text=?, description=?,
                        placement=?, position_after=?, repeat_every=?, start_date=?, end_date=?, is_active=? WHERE id=?';
                $params = array_values($fields);
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                return [$id, []];
            }
            $sql = 'INSERT INTO promotions (title, image_url, link_url, button_text, description,
                    placement, position_after, repeat_every, start_date, end_date, is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute(array_values($fields));
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['خطا در ذخیره‌سازی تبلیغ.']];
        }
    }
}

if (!function_exists('office_promo_toggle')) {
    function office_promo_toggle(PDO $pdo, int $id, bool $active): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            return $pdo->prepare('UPDATE promotions SET is_active=? WHERE id=?')->execute([$active ? 1 : 0, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_promo_delete')) {
    function office_promo_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            return $pdo->prepare('DELETE FROM promotions WHERE id=?')->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_promo_upload')) {
    /** @param array<string,mixed> $file @return array{0:?string,1:?string} آدرس نسبی یا خطا. */
    function office_promo_upload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, 'فایلی دریافت نشد.'];
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return [null, 'خطا در آپلود یا حجم بیش از ۵ مگابایت.'];
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            return [null, 'فایلی دریافت نشد.'];
        }
        $mime = '';
        if (function_exists('finfo_open')) {
            try {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) {
                    $mime = (string)finfo_file($fi, $tmp);
                    finfo_close($fi);
                }
            } catch (Throwable $e) {
            }
        }
        $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)
            || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return [null, 'فرمت تصویر مجاز نیست.'];
        }
        $dir = dirname(__DIR__) . '/uploads/promotions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            return [null, 'پوشه آپلود در دسترس نیست.'];
        }
        try {
            $name = 'promo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        } catch (Throwable $e) {
            $name = 'promo_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        }
        $dest = $dir . '/' . $name;
        $moved = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $dest) : false;
        if (!$moved && PHP_SAPI === 'cli' && is_file($tmp)) {
            $moved = @rename($tmp, $dest); // فقط مسیر تست CLI؛ روی وب همان move_uploaded_file سایت.
        }
        if (!$moved) {
            return [null, 'ذخیره فایل ناموفق بود.'];
        }
        return ['uploads/promotions/' . $name, null];
    }
}
