<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه تصاویر آگهی‌ها (مرحله ۲۶)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ مدیریت تصاویر پنل سایت (admin-images.php):
 * فهرست+فیلتر، حذف تکی (با چک‌باکس حذف فایل)، حذف دسته‌ای هر آگهی،
 * و پاک‌سازی فایل‌های یتیم پوشه uploads. مسیرها عین سایت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_img_root')) {
    function office_img_root(): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('office_img_abs')) {
    /** مسیر فیزیکی تصویر عین melkinoImageAbsolutePath سایت. */
    function office_img_abs(array $row): string
    {
        $raw = trim((string)($row['storage_path'] ?? ''));
        if ($raw === '') {
            $raw = trim((string)($row['filename'] ?? ''));
        }
        if ($raw === '') {
            return '';
        }
        $raw = str_replace('\\', '/', $raw);
        if (strpos($raw, '/') === 0 && is_file($raw)) {
            return $raw;
        }
        if (preg_match('#^https?://#i', $raw)) {
            return '';
        }
        $root = office_img_root();
        $candidates = [
            $root . '/' . ltrim($raw, '/'),
            $root . '/uploads/' . ltrim($raw, '/'),
            $root . '/uploads/' . basename($raw),
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return '';
    }
}

if (!function_exists('office_img_web')) {
    /** آدرس وب نمایش تصویر (نسبت به office/). */
    function office_img_web(array $row): string
    {
        $raw = trim((string)($row['filename'] ?? ''));
        if ($raw === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $raw)) {
            return $raw;
        }
        $raw = str_replace('\\', '/', $raw);
        $rel = strpos($raw, 'uploads/') === 0 ? $raw : 'uploads/' . ltrim($raw, '/');
        return '../' . $rel;
    }
}

if (!function_exists('office_img_list')) {
    /** @return array<int,array> حداکثر ۳۰۰ ردیف، جدیدترین اول (عین سایت). */
    function office_img_list(PDO $pdo, string $q = '', string $adId = '', bool $onlyMissing = false): array
    {
        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(i.filename LIKE ? OR a.title LIKE ? OR i.ad_id LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($adId !== '') {
            $where[] = 'i.ad_id = ?';
            $params[] = $adId;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $stmt = $pdo->prepare(
                "SELECT i.id, i.ad_id, i.filename, i.storage_path, i.is_primary,
                        i.is_selected, i.publish_publicly, i.created_at,
                        a.title AS ad_title
                   FROM images i
                   LEFT JOIN ads a ON a.id = i.ad_id
                   $whereSql
                  ORDER BY i.id DESC
                  LIMIT 300"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            try {
                $rows = $pdo->query(
                    'SELECT id, ad_id, filename, storage_path, is_primary, is_selected, publish_publicly
                       FROM images ORDER BY id DESC LIMIT 300'
                )->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {
                return [];
            }
        }
        $items = [];
        foreach ($rows as $row) {
            $abs = office_img_abs($row);
            $exists = $abs !== '' && is_file($abs);
            if ($onlyMissing && $exists) {
                continue;
            }
            $row['exists'] = $exists;
            $row['size'] = $exists ? (int)filesize($abs) : 0;
            $items[] = $row;
        }
        return $items;
    }
}

if (!function_exists('office_img_promote')) {
    /** اگر آگهی تصویر اصلی ندارد، بعدی را اصلی می‌کند (عین سایت). */
    function office_img_promote(PDO $pdo, string $adId): void
    {
        if ($adId === '') {
            return;
        }
        try {
            $c = $pdo->prepare('SELECT COUNT(*) FROM images WHERE ad_id = ? AND is_primary = 1');
            $c->execute([$adId]);
            if ((int)$c->fetchColumn() !== 0) {
                return;
            }
            $next = $pdo->prepare('SELECT id FROM images WHERE ad_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
            $next->execute([$adId]);
            $nextId = $next->fetchColumn();
            if ($nextId) {
                $pdo->prepare('UPDATE images SET is_primary = 1 WHERE id = ?')->execute([$nextId]);
            }
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_img_delete')) {
    /** @return array{0:bool,1:string} */
    function office_img_delete(PDO $pdo, int $id, bool $removeFile = true): array
    {
        if ($id <= 0) {
            return [false, 'شناسه نامعتبر است.'];
        }
        try {
            $st = $pdo->prepare('SELECT id, ad_id, filename, storage_path, is_primary FROM images WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return [false, 'تصویری با این شناسه پیدا نشد.'];
            }
            $fileDeleted = false;
            if ($removeFile) {
                $abs = office_img_abs($row);
                if ($abs !== '' && is_file($abs) && @unlink($abs)) {
                    $fileDeleted = true;
                }
            }
            $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$id]);
            if (!empty($row['is_primary'])) {
                office_img_promote($pdo, (string)$row['ad_id']);
            }
            return [true, $fileDeleted ? 'تصویر و فایل آن حذف شد.' : 'ردیف تصویر حذف شد.'];
        } catch (Throwable $e) {
            return [false, 'حذف تصویر ناموفق بود.'];
        }
    }
}

if (!function_exists('office_img_bulk')) {
    /** حذف دسته‌ای تصاویر یک آگهی (فایل‌ها + ردیف‌ها، عین سایت). @return array{0:bool,1:string} */
    function office_img_bulk(PDO $pdo, string $adId, array $filenames): array
    {
        $files = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $filenames
        ), static fn($v) => $v !== '')));
        if ($adId === '' || !$files) {
            return [false, 'شناسه‌ی آگهی یا فهرست تصاویر نامعتبر است.'];
        }
        $deletedRows = 0;
        $deletedFiles = 0;
        try {
            $sel = $pdo->prepare('SELECT id, storage_path, filename FROM images WHERE ad_id = ? AND filename = ? LIMIT 1');
            $del = $pdo->prepare('DELETE FROM images WHERE id = ?');
            foreach ($files as $file) {
                try {
                    $sel->execute([$adId, $file]);
                    $row = $sel->fetch(PDO::FETCH_ASSOC);
                    if (!$row) {
                        continue;
                    }
                    $abs = office_img_abs($row);
                    if ($abs !== '' && is_file($abs) && @unlink($abs)) {
                        $deletedFiles++;
                    }
                    $del->execute([$row['id']]);
                    $deletedRows++;
                } catch (Throwable $e) {
                }
            }
            office_img_promote($pdo, $adId);
        } catch (Throwable $e) {
            return [false, 'حذف دسته‌ای ناموفق بود.'];
        }
        return [true, "$deletedRows ردیف و $deletedFiles فایل حذف شد."];
    }
}

if (!function_exists('office_img_orphans')) {
    /** @return array<int,array{name:string,size:int}> فایل‌های تصویری uploads بدون ردیف دیتابیس. */
    function office_img_orphans(PDO $pdo): array
    {
        $dir = office_img_root() . '/uploads';
        if (!is_dir($dir)) {
            return [];
        }
        $exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $out = [];
        try {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM images WHERE filename LIKE ? OR storage_path LIKE ?');
        } catch (Throwable $e) {
            return [];
        }
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (strpos($f, 'onboarding-logo') === 0) {
                continue;
            }
            if (!in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $exts, true)) {
                continue;
            }
            $path = $dir . '/' . $f;
            if (!is_file($path)) {
                continue;
            }
            try {
                $like = '%' . $f;
                $chk->execute([$like, $like]);
                if ((int)$chk->fetchColumn() !== 0) {
                    continue;
                }
            } catch (Throwable $e) {
                continue;
            }
            $out[] = ['name' => $f, 'size' => (int)filesize($path)];
        }
        return $out;
    }
}

if (!function_exists('office_img_delete_orphans')) {
    /** @return array{0:bool,1:string} */
    function office_img_delete_orphans(PDO $pdo, array $names): array
    {
        if (!$names) {
            return [false, 'فایلی انتخاب نشده است.'];
        }
        $deleted = 0;
        $failed = 0;
        $freed = 0;
        $dir = office_img_root() . '/uploads';
        foreach ($names as $name) {
            $safe = basename((string)$name);
            if ($safe === '' || $safe !== (string)$name) {
                $failed++;
                continue;
            }
            if (strpos($safe, 'onboarding-logo') === 0) {
                $failed++;
                continue;
            }
            if (!in_array(strtolower(pathinfo($safe, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $failed++;
                continue;
            }
            $path = $dir . '/' . $safe;
            if (!is_file($path)) {
                $failed++;
                continue;
            }
            $size = (int)filesize($path);
            if (@unlink($path)) {
                $deleted++;
                $freed += $size;
            } else {
                $failed++;
            }
        }
        $mb = round($freed / 1024 / 1024, 2);
        return [true, "$deleted فایل حذف شد ($mb مگابایت آزاد شد)."];
    }
}
