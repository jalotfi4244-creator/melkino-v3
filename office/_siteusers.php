<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه کاربران سایت (مرحله ۲۵)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ تب کاربران پنل سایت (identity-sync.php):
 * فهرست + جست‌وجو، جزئیات (تاریخچه ورود + بازدیدها)، پاک‌سازی شماره
 * (با حسابرسی، عین سایت).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_su_cols')) {
    /** @return array<string,bool> ستون‌های موجود جدول users. */
    function office_su_cols(PDO $pdo): array
    {
        try {
            $out = [];
            foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $out[strtolower((string)$c['Field'])] = true;
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_su_stats')) {
    /** @return array{users:int,visits:int} */
    function office_su_stats(PDO $pdo): array
    {
        $out = ['users' => 0, 'visits' => 0];
        try {
            $out['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        } catch (Throwable $e) {
        }
        try {
            $out['visits'] = (int)$pdo->query('SELECT COUNT(*) FROM ad_views')->fetchColumn();
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_su_list')) {
    /** @return array<int,array> */
    function office_su_list(PDO $pdo, string $q = '', int $page = 1, int $perPage = 50): array
    {
        $cols = office_su_cols($pdo);
        $want = ['id', 'telegram_id', 'bale_id', 'username', 'name', 'phone', 'is_active',
            'first_login', 'last_login', 'login_count', 'created_at', 'last_ip', 'last_platform',
            'user_agent', 'photo_url', 'language_code', 'phone_verified', 'phone_locked'];
        $sel = array_values(array_filter($want, static fn($c) => isset($cols[$c])));
        if (!$sel || !isset($cols['id'])) {
            return [];
        }
        $where = '';
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $ors = [];
            foreach (['name', 'phone', 'username', 'telegram_id', 'bale_id'] as $c) {
                if (isset($cols[$c])) {
                    $ors[] = "`$c` LIKE ?";
                    $params[] = $like;
                }
            }
            if ($ors) {
                $where = 'WHERE (' . implode(' OR ', $ors) . ')';
            }
        }
        $order = isset($cols['last_login']) ? 'ORDER BY last_login DESC, id DESC' : 'ORDER BY id DESC';
        $off = max(0, ($page - 1) * $perPage);
        try {
            $st = $pdo->prepare('SELECT `' . implode('`,`', $sel) . "` FROM users $where $order LIMIT $perPage OFFSET $off");
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_su_count')) {
    function office_su_count(PDO $pdo, string $q = ''): int
    {
        $cols = office_su_cols($pdo);
        if (!isset($cols['id'])) {
            return 0;
        }
        $where = '';
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $ors = [];
            foreach (['name', 'phone', 'username', 'telegram_id', 'bale_id'] as $c) {
                if (isset($cols[$c])) {
                    $ors[] = "`$c` LIKE ?";
                    $params[] = $like;
                }
            }
            if ($ors) {
                $where = 'WHERE (' . implode(' OR ', $ors) . ')';
            }
        }
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM users $where");
            $st->execute($params);
            return max(0, (int)$st->fetchColumn());
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('office_su_get')) {
    function office_su_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_su_history')) {
    /** @return array<int,array> تاریخچه ورود (SELECT پویا عین سایت). */
    function office_su_history(PDO $pdo, int $uid): array
    {
        try {
            $leCols = [];
            foreach ($pdo->query('SHOW COLUMNS FROM login_events')->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $leCols[strtolower((string)$c['Field'])] = true;
            }
            $want = ['id', 'telegram_id', 'bale_id', 'username', 'name', 'ip_address', 'ip', 'user_agent', 'platform', 'language_code', 'created_at'];
            $sel = array_values(array_filter($want, static fn($c) => isset($leCols[$c])));
            if (!$sel) {
                $sel = ['id'];
            }
            if (!isset($leCols['user_id'])) {
                return [];
            }
            $st = $pdo->prepare('SELECT `' . implode('`,`', $sel) . '` FROM login_events WHERE user_id=? ORDER BY created_at DESC LIMIT 200');
            $st->execute([$uid]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_su_views')) {
    /** @return array<int,array> بازدیدهای آگهی کاربر. */
    function office_su_views(PDO $pdo, int $uid): array
    {
        try {
            $st = $pdo->prepare('SELECT ad_id, ad_title, viewed_at FROM ad_views WHERE user_id=? ORDER BY viewed_at DESC LIMIT 200');
            $st->execute([$uid]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_su_clear_phone')) {
    /** @return array{0:bool,1:string} */
    function office_su_clear_phone(PDO $pdo, int $uid, ?int $adminId, string $adminUsername): array
    {
        if ($uid <= 0) {
            return [false, 'user_id نامعتبر است.'];
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS admin_phone_audit (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT NULL,
                    admin_username VARCHAR(100) NULL,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    old_phone VARCHAR(30) NULL,
                    new_phone VARCHAR(30) NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_apa_user (user_id),
                    INDEX idx_apa_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $st = $pdo->prepare('SELECT id, phone, name, telegram_id FROM users WHERE id = ? LIMIT 1');
            $st->execute([$uid]);
            $target = $st->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                return [false, 'کاربر پیدا نشد.'];
            }
            $oldPhone = trim((string)($target['phone'] ?? ''));
            $cols = office_su_cols($pdo);
            $sets = ['phone = NULL'];
            if (isset($cols['phone_verified'])) {
                $sets[] = 'phone_verified = 0';
            }
            if (isset($cols['phone_locked'])) {
                $sets[] = 'phone_locked = 0';
            }
            if (isset($cols['updated_at'])) {
                $sets[] = 'updated_at = NOW()';
            }
            $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute([$uid]);
            $pdo->prepare(
                'INSERT INTO admin_phone_audit (admin_id, admin_username, user_id, action, old_phone, new_phone, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, NULL, ?, NOW())'
            )->execute([$adminId, $adminUsername, $uid, 'clear_phone', $oldPhone !== '' ? $oldPhone : null, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]);
            return [true, 'شماره تماس کاربر پاک شد.'];
        } catch (Throwable $e) {
            return [false, 'عملیات ناموفق بود.'];
        }
    }
}
