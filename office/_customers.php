<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — مشتریان (ماژول مستقل دفتر، مرحله ۸)
 *--------------------------------------------------------------------------
 * جدول office_customers (از مرحله ۱، بدون تغییر):
 * لیست (جست‌وجو + فیلتر نوع) + ثبت + ویرایش + حذف + فعال/غیرفعال.
 * بودجه مشمول قانون مبالغ دفتر است (نمایش گروه‌بندی‌شده، ذخیره رقم خام).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_cust_kinds')) {
    /** @return array<string,string> کد => برچسب فارسی */
    function office_cust_kinds(): array
    {
        return [
            'buyer' => 'خریدار',
            'seller' => 'فروشنده / مالک',
            'renter' => 'مستأجر',
            'investor' => 'سرمایه‌گذار',
        ];
    }
}

if (!function_exists('office_cust_norm_phone')) {
    /** نرمالایز موبایل/تلفن: ارقام فارسی/عربی ← لاتین، حذف فاصله/خط‌تیره. */
    function office_cust_norm_phone(string $v): string
    {
        $v = trim($v);
        $v = strtr($v, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $v = str_replace([' ', ' ', '-', '(', ')'], '', $v);
        return mb_substr($v, 0, 20);
    }
}

if (!function_exists('office_cust_norm_int')) {
    /** نرمالایز عدد صحیح از ورودی گروه‌بندی‌شده؛ نامعتبر ← null. */
    function office_cust_norm_int(string $v): ?int
    {
        $v = strtr(trim($v), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $v = str_replace([',', '٬', '،', ' ', 'تومان'], '', $v);
        if ($v === '') {
            return 0;
        }
        if (!preg_match('/^\d{1,18}$/', $v)) {
            return null;
        }
        return (int)$v;
    }
}

if (!function_exists('office_cust_get')) {
    /** @return array<string,mixed>|null */
    function office_cust_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('SELECT * FROM office_customers WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_cust_list')) {
    /**
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_cust_list(PDO $pdo, string $kind, string $q, int $page, int $perPage = 25): array
    {
        office_ensure_tables();
        $kinds = office_cust_kinds();
        if ($kind !== '' && !array_key_exists($kind, $kinds)) {
            $kind = '';
        }
        $where = [];
        $params = [];
        if ($kind !== '') {
            $where[] = 'kind = ?';
            $params[] = $kind;
        }
        if ($q !== '') {
            $where[] = '(name LIKE ? OR phone LIKE ? OR neighborhood LIKE ? OR notes LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM office_customers $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT * FROM office_customers $whereSql ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_cust_save')) {
    /**
     * ثبت/ویرایش مشتری با اعتبارسنجی.
     * @return array{0: int|null, 1: string[]} [id, errors]
     */
    function office_cust_save(PDO $pdo, array $post, int $adminId, ?int $id = null): array
    {
        $errors = [];
        office_ensure_tables();
        $g = static fn(string $k): string => trim((string)($post[$k] ?? ''));

        $name = mb_substr($g('name'), 0, 120);
        $phone = office_cust_norm_phone($g('phone'));
        $kind = $g('kind');
        $kinds = office_cust_kinds();
        if (!array_key_exists($kind, $kinds)) {
            $kind = 'buyer';
        }
        $budget = office_cust_norm_int($g('budget'));
        $minArea = office_cust_norm_int($g('min_area'));
        $neighborhood = mb_substr($g('neighborhood'), 0, 120);
        $notes = mb_substr($g('notes'), 0, 2000);

        if ($name === '') {
            $errors[] = 'لطفاً نام مشتری را وارد کنید.';
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
                $st = $pdo->prepare('UPDATE office_customers
                    SET name = ?, phone = ?, kind = ?, budget = ?, min_area = ?, neighborhood = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?');
                $st->execute([$name, $phone, $kind, $budget, $minArea, $neighborhood, $notes, $id]);
                return [$id, []];
            }
            $st = $pdo->prepare('INSERT INTO office_customers
                (name, phone, kind, budget, min_area, neighborhood, notes, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())');
            $st->execute([$name, $phone, $kind, $budget, $minArea, $neighborhood, $notes, $adminId]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره مشتری ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_cust_delete')) {
    function office_cust_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('DELETE FROM office_customers WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_cust_set_active')) {
    function office_cust_set_active(PDO $pdo, int $id, bool $active): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('UPDATE office_customers SET is_active = ?, updated_at = NOW() WHERE id = ?');
            return $st->execute([$active ? 1 : 0, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
