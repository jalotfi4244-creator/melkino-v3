<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — پیگیری‌ها (ماژول مستقل دفتر، مرحله ۱۲)
 *--------------------------------------------------------------------------
 * جدول office_followups (از مرحله ۱، بدون تغییر): یادآور لینک‌دار به
 * فایل/مشتری/درخواست/بازدید/مشارکت یا متفرقه + سررسید + انجام/باز.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_calls.php';

if (!function_exists('office_follow_entities')) {
    /** @return array<string,string> */
    function office_follow_entities(): array
    {
        return [
            'ad' => 'فایل',
            'customer' => 'مشتری',
            'request' => 'درخواست',
            'visit' => 'بازدید',
            'partnership' => 'مشارکت',
            'other' => 'متفرقه',
        ];
    }
}

if (!function_exists('office_follow_entity_title')) {
    /**
     * برچسب نمایشی لینک؛ رشته خالی یعنی بدون لینک؛ null یعنی نامعتبر/ناموجود.
     */
    function office_follow_entity_title(PDO $pdo, string $entity, string $entityId): ?string
    {
        $entityId = trim($entityId);
        if ($entityId === '') {
            return '';
        }
        try {
            switch ($entity) {
                case 'ad':
                    $st = $pdo->prepare('SELECT title FROM ads WHERE id = ? LIMIT 1');
                    $st->execute([$entityId]);
                    break;
                case 'customer':
                    if (!ctype_digit($entityId)) {
                        return null;
                    }
                    $st = $pdo->prepare('SELECT name FROM office_customers WHERE id = ? LIMIT 1');
                    $st->execute([(int)$entityId]);
                    break;
                case 'request':
                    if (!ctype_digit($entityId)) {
                        return null;
                    }
                    $st = $pdo->prepare('SELECT name FROM office_requests WHERE id = ? LIMIT 1');
                    $st->execute([(int)$entityId]);
                    break;
                case 'visit':
                    if (!ctype_digit($entityId)) {
                        return null;
                    }
                    $st = $pdo->prepare('SELECT name FROM office_visits WHERE id = ? LIMIT 1');
                    $st->execute([(int)$entityId]);
                    break;
                case 'partnership':
                    if (!ctype_digit($entityId)) {
                        return null;
                    }
                    $st = $pdo->prepare('SELECT title FROM partnership_requests WHERE id = ? LIMIT 1');
                    $st->execute([(int)$entityId]);
                    break;
                case 'other':
                    return $entityId;
                default:
                    return null;
            }
            $t = $st->fetchColumn();
            return $t === false ? null : (string)$t;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_follow_get')) {
    /** @return array<string,mixed>|null */
    function office_follow_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('SELECT * FROM office_followups WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_follow_list')) {
    /**
     * @param string $doneFilter 'open'|'done'|''
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_follow_list(PDO $pdo, string $entity, string $doneFilter, bool $overdueOnly, string $q, int $page, int $perPage = 25): array
    {
        office_ensure_tables();
        if ($entity !== '' && !array_key_exists($entity, office_follow_entities())) {
            $entity = '';
        }
        if (!in_array($doneFilter, ['open', 'done', ''], true)) {
            $doneFilter = '';
        }
        $where = [];
        $params = [];
        if ($entity !== '') {
            $where[] = 'entity = ?';
            $params[] = $entity;
        }
        if ($doneFilter === 'open') {
            $where[] = 'done = 0';
        } elseif ($doneFilter === 'done') {
            $where[] = 'done = 1';
        }
        if ($overdueOnly) {
            $where[] = 'done = 0 AND due_date IS NOT NULL AND due_date < CURDATE()';
        }
        if ($q !== '') {
            $where[] = '(title LIKE ? OR note LIKE ? OR entity_id LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM office_followups $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT * FROM office_followups $whereSql
                 ORDER BY done ASC, due_date IS NULL, due_date ASC, id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_follow_save')) {
    /**
     * ثبت/ویرایش پیگیری با اعتبارسنجی.
     * @return array{0: int|null, 1: string[]} [id, errors]
     */
    function office_follow_save(PDO $pdo, array $post, int $adminId, ?int $id = null): array
    {
        $errors = [];
        office_ensure_tables();
        $g = static fn(string $k): string => trim((string)($post[$k] ?? ''));

        $title = mb_substr($g('title'), 0, 180);
        $entity = $g('entity');
        if (!array_key_exists($entity, office_follow_entities())) {
            $entity = 'other';
        }
        $entityId = mb_substr($g('entity_id'), 0, 64);
        $due = $g('due_date');
        $dueDate = null;
        if ($due !== '') {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $due, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                $dueDate = $due;
            } else {
                $errors[] = 'تاریخ سررسید معتبر نیست.';
            }
        }
        $note = mb_substr($g('note'), 0, 2000);

        if ($title === '') {
            $errors[] = 'لطفاً عنوان پیگیری را وارد کنید.';
        }
        if ($entity !== 'other' && $entityId === '') {
            $errors[] = 'لطفاً شناسه لینک را وارد کنید.';
        } elseif ($entityId !== '' && office_follow_entity_title($pdo, $entity, $entityId) === null) {
            $errors[] = 'لینک داده‌شده یافت نشد.';
        }
        if ($errors) {
            return [null, $errors];
        }

        try {
            if ($id !== null && $id > 0) {
                $st = $pdo->prepare('UPDATE office_followups
                    SET entity = ?, entity_id = ?, title = ?, note = ?, due_date = ?
                    WHERE id = ?');
                $st->execute([$entity, $entityId, $title, $note, $dueDate, $id]);
                return [$id, []];
            }
            $st = $pdo->prepare('INSERT INTO office_followups
                (entity, entity_id, title, note, due_date, done, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, 0, ?, NOW())');
            $st->execute([$entity, $entityId, $title, $note, $dueDate, $adminId]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره پیگیری ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_follow_toggle')) {
    /** @return int|null وضعیت جدید (0/1) یا null اگر یافت نشد */
    function office_follow_toggle(PDO $pdo, int $id): ?int
    {
        if ($id <= 0) {
            return null;
        }
        office_ensure_tables();
        $row = office_follow_get($pdo, $id);
        if (!$row) {
            return null;
        }
        $new = ((int)($row['done'] ?? 0)) === 1 ? 0 : 1;
        try {
            $st = $pdo->prepare('UPDATE office_followups SET done = ? WHERE id = ?');
            $st->execute([$new, $id]);
            return $new;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_follow_delete')) {
    function office_follow_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_ensure_tables();
        try {
            $st = $pdo->prepare('DELETE FROM office_followups WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_follow_is_overdue')) {
    function office_follow_is_overdue(array $row, string $today): bool
    {
        if (((int)($row['done'] ?? 0)) === 1) {
            return false;
        }
        $due = (string)($row['due_date'] ?? '');
        return $due !== '' && $due < $today;
    }
}
