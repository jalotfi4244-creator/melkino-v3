<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — درخواست‌ها (ماژول مستقل دفتر، مرحله ۹)
 *--------------------------------------------------------------------------
 * جدول office_requests (افزایشی، بدون دست‌زدن به جدول‌های موجود):
 * لیست (جست‌وجو + فیلتر نوع/وضعیت) + ثبت + ویرایش + حذف.
 * لینک اختیاری به مشتری؛ بودجه مشمول قانون مبالغ دفتر است.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_customers.php';

if (!function_exists('office_req_kinds')) {
    /** @return array<string,string> */
    function office_req_kinds(): array
    {
        return ['buy' => 'خرید', 'rent' => 'اجاره'];
    }
}

if (!function_exists('office_req_statuses')) {
    /** @return array<string,string> */
    function office_req_statuses(): array
    {
        return [
            'new' => 'جدید',
            'contacted' => 'در حال پیگیری',
            'done' => 'انجام شد',
            'cancelled' => 'لغو شد',
        ];
    }
}

if (!function_exists('office_req_get')) {
    /** @return array<string,mixed>|null */
    function office_req_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('SELECT * FROM office_requests WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_req_list')) {
    /**
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_req_list(PDO $pdo, string $kind, string $status, string $q, int $page, int $perPage = 25): array
    {
        office_ensure_tables();
        if ($kind !== '' && !array_key_exists($kind, office_req_kinds())) {
            $kind = '';
        }
        if ($status !== '' && !array_key_exists($status, office_req_statuses())) {
            $status = '';
        }
        $where = [];
        $params = [];
        if ($kind !== '') {
            $where[] = 'r.kind = ?';
            $params[] = $kind;
        }
        if ($status !== '') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(r.name LIKE ? OR r.phone LIKE ? OR r.neighborhood LIKE ? OR r.description LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM office_requests r $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT r.*, c.name AS customer_name FROM office_requests r
                 LEFT JOIN office_customers c ON c.id = r.customer_id
                 $whereSql ORDER BY r.created_at DESC, r.id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_req_customer_options')) {
    /** @return array<int,array{id:int,name:string,phone:string}> مشتریان فعال برای لینک */
    function office_req_customer_options(PDO $pdo): array
    {
        office_ensure_tables();
        try {
            $st = $pdo->query("SELECT id, name, phone FROM office_customers WHERE is_active = 1 ORDER BY name LIMIT 200");
            return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_req_save')) {
    /**
     * ثبت/ویرایش درخواست با اعتبارسنجی.
     * اگر مشتری لینک شده و نام/تماس خالی باشد، از کارت مشتری کپی می‌شود.
     * @return array{0: int|null, 1: string[]} [id, errors]
     */
    function office_req_save(PDO $pdo, array $post, int $adminId, ?int $id = null): array
    {
        $errors = [];
        office_ensure_tables();
        $g = static fn(string $k): string => trim((string)($post[$k] ?? ''));

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
        $kind = $g('kind');
        if (!array_key_exists($kind, office_req_kinds())) {
            $kind = 'buy';
        }
        $budget = office_cust_norm_int($g('budget'));
        $minArea = office_cust_norm_int($g('min_area'));
        $neighborhood = mb_substr($g('neighborhood'), 0, 120);
        $description = mb_substr($g('description'), 0, 2000);
        $status = $g('status');
        if (!array_key_exists($status, office_req_statuses())) {
            if ($id !== null && $id > 0) {
                $old = office_req_get($pdo, $id);
                $status = (string)($old['status'] ?? 'new');
                if (!array_key_exists($status, office_req_statuses())) {
                    $status = 'new';
                }
            } else {
                $status = 'new';
            }
        }

        if ($name === '') {
            $errors[] = 'لطفاً نام متقاضی را وارد کنید.';
        }
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($phone === '' || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors[] = 'شماره تماس معتبر وارد کنید.';
        }
        if ($budget === null) {
            $errors[] = 'بودجه باید عدد باشد.';
        }
        if ($minArea === null) {
            $errors[] = 'حداقل متراژ باید عدد باشد.';
        }
        if ($errors) {
            return [null, $errors];
        }

        try {
            if ($id !== null && $id > 0) {
                $st = $pdo->prepare('UPDATE office_requests
                    SET customer_id = ?, name = ?, phone = ?, kind = ?, budget = ?, min_area = ?,
                        neighborhood = ?, description = ?, status = ?, updated_at = NOW()
                    WHERE id = ?');
                $st->execute([$customerId, $name, $phone, $kind, $budget, $minArea, $neighborhood, $description, $status, $id]);
                return [$id, []];
            }
            $st = $pdo->prepare('INSERT INTO office_requests
                (customer_id, name, phone, kind, budget, min_area, neighborhood, description, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $st->execute([$customerId, $name, $phone, $kind, $budget, $minArea, $neighborhood, $description, $status, $adminId]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره درخواست ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_req_delete')) {
    function office_req_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('DELETE FROM office_requests WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
