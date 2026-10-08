<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — بازدیدها (ماژول مستقل دفتر، مرحله ۱۰)
 *--------------------------------------------------------------------------
 * جدول office_visits (افزایشی): لینک به فایل (ads) + لینک اختیاری به
 * مشتری + زمان بازدید + وضعیت (زمان‌بندی شد/انجام شد/لغو شد).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_requests.php';

if (!function_exists('office_visit_statuses')) {
    /** @return array<string,string> */
    function office_visit_statuses(): array
    {
        return [
            'scheduled' => 'زمان‌بندی شد',
            'done' => 'انجام شد',
            'cancelled' => 'لغو شد',
        ];
    }
}

if (!function_exists('office_visit_ad_title')) {
    /** عنوان فایل برای کد داده‌شده؛ null یعنی فایل وجود ندارد. */
    function office_visit_ad_title(PDO $pdo, string $adId): ?string
    {
        $adId = trim($adId);
        if ($adId === '') {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT title FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$adId]);
            $t = $st->fetchColumn();
            return $t === false ? null : (string)$t;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_visit_file_options')) {
    /** @return array<int,array{id:string,title:string}> تازه‌ترین فایل‌ها برای datalist */
    function office_visit_file_options(PDO $pdo, int $limit = 100): array
    {
        try {
            $limit = min(max(1, $limit), 200);
            $st = $pdo->query("SELECT id, title FROM ads ORDER BY created_at DESC, id DESC LIMIT $limit");
            return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_visit_parse_at')) {
    /** ترکیب date + time فرم به DATETIME؛ نامعتبر ← null. */
    function office_visit_parse_at(string $date, string $time): ?string
    {
        $date = trim($date);
        $time = trim($time);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return null;
        }
        if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return null;
        }
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            return null;
        }
        return $date . ' ' . $time . ':00';
    }
}

if (!function_exists('office_visit_get')) {
    /** @return array<string,mixed>|null */
    function office_visit_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('SELECT * FROM office_visits WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_visit_list')) {
    /**
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_visit_list(PDO $pdo, string $status, string $day, string $q, int $page, int $perPage = 25): array
    {
        office_ensure_tables();
        if ($status !== '' && !array_key_exists($status, office_visit_statuses())) {
            $status = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $day = '';
        }
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = 'v.status = ?';
            $params[] = $status;
        }
        if ($day !== '') {
            $where[] = 'DATE(v.visit_at) = ?';
            $params[] = $day;
        }
        if ($q !== '') {
            $where[] = '(v.ad_id LIKE ? OR v.name LIKE ? OR v.phone LIKE ? OR a.title LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM office_visits v LEFT JOIN ads a ON a.id = v.ad_id $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT v.*, a.title AS ad_title, c.name AS customer_name
                 FROM office_visits v
                 LEFT JOIN ads a ON a.id = v.ad_id
                 LEFT JOIN office_customers c ON c.id = v.customer_id
                 $whereSql ORDER BY v.visit_at IS NULL, v.visit_at DESC, v.id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_visit_save')) {
    /**
     * ثبت/ویرایش بازدید با اعتبارسنجی.
     * @return array{0: int|null, 1: string[]} [id, errors]
     */
    function office_visit_save(PDO $pdo, array $post, int $adminId, ?int $id = null): array
    {
        $errors = [];
        office_ensure_tables();
        $g = static fn(string $k): string => trim((string)($post[$k] ?? ''));

        $adId = mb_substr($g('ad_id'), 0, 64);
        $customerId = max(0, (int)($post['customer_id'] ?? 0));
        $customer = $customerId > 0 ? office_cust_get($pdo, $customerId) : null;
        if ($customerId > 0 && !$customer) {
            $customerId = 0;
        }

        $name = mb_substr($g('name'), 0, 120);
        $phone = office_cust_norm_phone($g('phone'));
        if ($customer) {
            if ($name === '') {
                $name = mb_substr((string)($customer['name'] ?? ''), 0, 120);
            }
            if ($phone === '') {
                $phone = office_cust_norm_phone((string)($customer['phone'] ?? ''));
            }
        }
        $visitAt = office_visit_parse_at($g('visit_date'), $g('visit_time'));
        $notes = mb_substr($g('notes'), 0, 2000);
        $status = $g('status');
        if (!array_key_exists($status, office_visit_statuses())) {
            if ($id !== null && $id > 0) {
                $old = office_visit_get($pdo, $id);
                $status = (string)($old['status'] ?? 'scheduled');
                if (!array_key_exists($status, office_visit_statuses())) {
                    $status = 'scheduled';
                }
            } else {
                $status = 'scheduled';
            }
        }

        if ($adId === '') {
            $errors[] = 'لطفاً کد فایل را وارد کنید.';
        } elseif (office_visit_ad_title($pdo, $adId) === null) {
            $errors[] = 'فایلی با این کد یافت نشد.';
        }
        if ($name === '') {
            $errors[] = 'لطفاً نام بازدیدکننده را وارد کنید.';
        }
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($phone === '' || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors[] = 'شماره تماس معتبر وارد کنید.';
        }
        if ($visitAt === null) {
            $errors[] = 'تاریخ و ساعت بازدید معتبر نیست.';
        }
        if ($errors) {
            return [null, $errors];
        }

        try {
            if ($id !== null && $id > 0) {
                $st = $pdo->prepare('UPDATE office_visits
                    SET ad_id = ?, customer_id = ?, name = ?, phone = ?, visit_at = ?, status = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?');
                $st->execute([$adId, $customerId, $name, $phone, $visitAt, $status, $notes, $id]);
                return [$id, []];
            }
            $st = $pdo->prepare('INSERT INTO office_visits
                (ad_id, customer_id, name, phone, visit_at, status, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $st->execute([$adId, $customerId, $name, $phone, $visitAt, $status, $notes, $adminId]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره بازدید ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_visit_delete')) {
    function office_visit_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('DELETE FROM office_visits WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
