<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تماس‌ها (ماژول مستقل دفتر، مرحله ۱۱)
 *--------------------------------------------------------------------------
 * جدول office_calls (از مرحله ۱، بدون تغییر): دفترچه ثبت تماس‌های ورودی/
 * خروجی + لینک اختیاری به فایل. واحد مدت‌زمان: دقیقه.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_customers.php';

if (!function_exists('office_call_dirs')) {
    /** @return array<string,string> */
    function office_call_dirs(): array
    {
        return ['in' => 'ورودی', 'out' => 'خروجی'];
    }
}

if (!function_exists('office_call_parse_at')) {
    /** ترکیب date + time فرم به DATETIME؛ نامعتبر ← null. */
    function office_call_parse_at(string $date, string $time): ?string
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

if (!function_exists('office_call_ad_title')) {
    /** عنوان فایل؛ خالی ← '' (لینک اختیاری)؛ ناموجود ← null. */
    function office_call_ad_title(PDO $pdo, string $adId): ?string
    {
        $adId = trim($adId);
        if ($adId === '') {
            return '';
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

if (!function_exists('office_call_get')) {
    /** @return array<string,mixed>|null */
    function office_call_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('SELECT * FROM office_calls WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_call_list')) {
    /**
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_call_list(PDO $pdo, string $dir, string $day, string $q, int $page, int $perPage = 25): array
    {
        office_ensure_tables();
        if ($dir !== '' && !array_key_exists($dir, office_call_dirs())) {
            $dir = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $day = '';
        }
        $where = [];
        $params = [];
        if ($dir !== '') {
            $where[] = 'c.direction = ?';
            $params[] = $dir;
        }
        if ($day !== '') {
            $where[] = 'DATE(c.called_at) = ?';
            $params[] = $day;
        }
        if ($q !== '') {
            $where[] = '(c.ad_id LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR c.note LIKE ? OR a.title LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM office_calls c LEFT JOIN ads a ON a.id = c.ad_id $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT c.*, a.title AS ad_title
                 FROM office_calls c
                 LEFT JOIN ads a ON a.id = c.ad_id
                 $whereSql ORDER BY c.called_at DESC, c.id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_call_save')) {
    /**
     * ثبت/ویرایش تماس با اعتبارسنجی.
     * @return array{0: int|null, 1: string[]} [id, errors]
     */
    function office_call_save(PDO $pdo, array $post, int $adminId, ?int $id = null): array
    {
        $errors = [];
        office_ensure_tables();
        $g = static fn(string $k): string => trim((string)($post[$k] ?? ''));

        $name = mb_substr($g('name'), 0, 120);
        $phone = office_cust_norm_phone($g('phone'));
        $adId = mb_substr($g('ad_id'), 0, 64);
        $dir = $g('direction');
        if (!array_key_exists($dir, office_call_dirs())) {
            $dir = 'in';
        }
        $duration = office_cust_norm_int($g('duration'));
        $calledAt = office_call_parse_at($g('call_date'), $g('call_time'));
        $note = mb_substr($g('note'), 0, 2000);

        if ($name === '') {
            $errors[] = 'لطفاً نام تماس‌گیرنده را وارد کنید.';
        }
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($phone === '' || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors[] = 'شماره تماس معتبر وارد کنید.';
        }
        if ($adId !== '' && office_call_ad_title($pdo, $adId) === null) {
            $errors[] = 'فایلی با این کد یافت نشد.';
        }
        if ($duration === null) {
            $errors[] = 'مدت تماس باید عدد باشد.';
        }
        if ($calledAt === null) {
            $errors[] = 'تاریخ و ساعت تماس معتبر نیست.';
        }
        if ($errors) {
            return [null, $errors];
        }

        try {
            if ($id !== null && $id > 0) {
                $st = $pdo->prepare('UPDATE office_calls
                    SET name = ?, phone = ?, ad_id = ?, direction = ?, duration = ?, note = ?, called_at = ?
                    WHERE id = ?');
                $st->execute([$name, $phone, $adId, $dir, $duration, $note, $calledAt, $id]);
                return [$id, []];
            }
            $st = $pdo->prepare('INSERT INTO office_calls
                (name, phone, ad_id, direction, duration, note, called_at, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $st->execute([$name, $phone, $adId, $dir, $duration, $note, $calledAt, $adminId]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره تماس ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_call_delete')) {
    function office_call_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('DELETE FROM office_calls WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
