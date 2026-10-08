<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کاربران و دسترسی‌ها (مرحله ۱۵)
 *--------------------------------------------------------------------------
 * مدیریت ردیف‌های جدول admins سایت (همان لاگین دفتر): ثبت، ویرایش نام/
 * رمز، فعال/غیرفعال، حذف. ساختار جدول دست‌نخورده؛ همان regex نام‌کاربری
 * و سیاست رمز tools/create-admin.php. حذف/غیرفعالِ خود و آخرین ادمین
 * فعال ممنوع است.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_staff_check_username')) {
    /** @return string|null متن خطا یا null */
    function office_staff_check_username(string $u): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,64}$/', $u)) {
            return 'نام کاربری نامعتبر است (۳ تا ۶۴ کاراکتر، حروف/عدد/._-).';
        }
        return null;
    }
}

if (!function_exists('office_staff_check_password')) {
    /** @return string|null متن خطا یا null */
    function office_staff_check_password(string $p): ?string
    {
        if (strlen($p) < 12) {
            return 'رمز باید حداقل ۱۲ کاراکتر باشد.';
        }
        if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
            return 'رمز باید حداقل یک حرف و یک رقم داشته باشد.';
        }
        return null;
    }
}

if (!function_exists('office_staff_list')) {
    /** @return array<int,array<string,mixed>> */
    function office_staff_list(PDO $pdo): array
    {
        try {
            $st = $pdo->query(
                'SELECT a.id, a.username, a.display_name, a.is_active, a.updated_at,
                        (SELECT MAX(l.created_at) FROM admin_login_attempts l
                         WHERE l.username = a.username AND l.success = 1) AS last_login
                 FROM admins a ORDER BY a.id ASC'
            );
            return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_staff_get')) {
    /** @return array<string,mixed>|null */
    function office_staff_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT id, username, display_name, is_active, updated_at FROM admins WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_staff_active_count')) {
    function office_staff_active_count(PDO $pdo): int
    {
        try {
            return (int)$pdo->query('SELECT COUNT(*) FROM admins WHERE is_active = 1')->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('office_staff_create')) {
    /** @return array{0: int|null, 1: string[]} [id, errors] */
    function office_staff_create(PDO $pdo, string $username, string $password, string $displayName): array
    {
        $username = trim($username);
        $displayName = mb_substr(trim($displayName), 0, 120);
        if (($e = office_staff_check_username($username)) !== null) {
            return [null, [$e]];
        }
        if (($e = office_staff_check_password($password)) !== null) {
            return [null, [$e]];
        }
        try {
            $st = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
            $st->execute([$username]);
            if ($st->fetchColumn()) {
                return [null, ['این نام کاربری قبلاً ثبت شده است.']];
            }
            $st = $pdo->prepare('INSERT INTO admins (username, password_hash, display_name, is_active) VALUES (?, ?, ?, 1)');
            $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), $displayName !== '' ? $displayName : null]);
            return [(int)$pdo->lastInsertId(), []];
        } catch (Throwable $e) {
            return [null, ['ذخیره ناموفق بود؛ لطفاً دوباره تلاش کنید.']];
        }
    }
}

if (!function_exists('office_staff_update')) {
    /**
     * ویرایش نام نمایشی + رمز اختیاری (خالی = حفظ).
     * @return string[] errors
     */
    function office_staff_update(PDO $pdo, int $id, string $displayName, string $newPassword): array
    {
        $row = office_staff_get($pdo, $id);
        if (!$row) {
            return ['کاربر یافت نشد.'];
        }
        $displayName = mb_substr(trim($displayName), 0, 120);
        try {
            if ($newPassword !== '') {
                if (($e = office_staff_check_password($newPassword)) !== null) {
                    return [$e];
                }
                $st = $pdo->prepare('UPDATE admins SET display_name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?');
                $st->execute([$displayName !== '' ? $displayName : null, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
            } else {
                $st = $pdo->prepare('UPDATE admins SET display_name = ?, updated_at = NOW() WHERE id = ?');
                $st->execute([$displayName !== '' ? $displayName : null, $id]);
            }
            return [];
        } catch (Throwable $e) {
            return ['ذخیره ناموفق بود؛ لطفاً دوباره تلاش کنید.'];
        }
    }
}

if (!function_exists('office_staff_set_active')) {
    /** @return string|null متن خطا یا null */
    function office_staff_set_active(PDO $pdo, int $id, bool $active, int $selfId): ?string
    {
        $row = office_staff_get($pdo, $id);
        if (!$row) {
            return 'کاربر یافت نشد.';
        }
        if (!$active && $id === $selfId) {
            return 'نمی‌توانید خودتان را غیرفعال کنید.';
        }
        if (!$active && (int)$row['is_active'] === 1 && office_staff_active_count($pdo) <= 1) {
            return 'آخرین ادمین فعال را نمی‌توان غیرفعال کرد.';
        }
        try {
            $pdo->prepare('UPDATE admins SET is_active = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$active ? 1 : 0, $id]);
            return null;
        } catch (Throwable $e) {
            return 'تغییر وضعیت ناموفق بود.';
        }
    }
}

if (!function_exists('office_staff_delete')) {
    /** @return string|null متن خطا یا null */
    function office_staff_delete(PDO $pdo, int $id, int $selfId): ?string
    {
        $row = office_staff_get($pdo, $id);
        if (!$row) {
            return 'کاربر یافت نشد.';
        }
        if ($id === $selfId) {
            return 'نمی‌توانید خودتان را حذف کنید.';
        }
        if ((int)$row['is_active'] === 1 && office_staff_active_count($pdo) <= 1) {
            return 'آخرین ادمین فعال را نمی‌توان حذف کرد.';
        }
        try {
            $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            return null;
        } catch (Throwable $e) {
            return 'حذف ناموفق بود.';
        }
    }
}
