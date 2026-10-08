<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه مشترک (بازسازی از نو، مرحله ۱)
 *--------------------------------------------------------------------------
 * - احراز هویت: عین لاگین پنل ادمین ملکینو (جدول admins، جدا از کاربران سایت)
 * - دیتابیس: همان دیتابیس ملکینو؛ فقط جدول‌های جدید office_* ساخته می‌شود
 *   و هیچ جدول موجودی دستکاری نمی‌شود.
 * - بدون هیچ منبع خارجی (فونت/CDN) تا روی هاست و PC مغازه همیشه کار کند.
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

$officeRoot = dirname(__DIR__);
foreach (['config.php', 'db-settings.php', 'security-lib.php'] as $lib) {
    $f = $officeRoot . '/' . $lib;
    if (is_file($f)) {
        require_once $f;
    }
}
if (is_file(__DIR__ . '/_version.php')) {
    require_once __DIR__ . '/_version.php';
}
require_once __DIR__ . '/_2fa.php';

if (!function_exists('office_db')) {
    /** @return PDO */
    function office_db(): PDO
    {
        global $pdo;
        if (function_exists('melkinoRequireDb')) {
            melkinoRequireDb();
        }
        if (!($pdo instanceof PDO)) {
            http_response_code(503);
            exit('دیتابیس در دسترس نیست.');
        }
        return $pdo;
    }
}

if (!function_exists('office_ensure_tables')) {
    /**
     * فقط جدول‌های جدید دفتر؛ هیچ جدول موجودی تغییر نمی‌کند.
     */
    function office_ensure_tables(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $pdo = office_db();
        $sql = [
            "CREATE TABLE IF NOT EXISTS office_customers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(120) NOT NULL DEFAULT '',
                phone VARCHAR(20) NOT NULL DEFAULT '',
                kind VARCHAR(20) NOT NULL DEFAULT 'buyer',
                budget BIGINT UNSIGNED NOT NULL DEFAULT 0,
                min_area INT UNSIGNED NOT NULL DEFAULT 0,
                neighborhood VARCHAR(120) NOT NULL DEFAULT '',
                notes TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_office_customers_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS office_requests (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_id INT UNSIGNED NOT NULL DEFAULT 0,
                name VARCHAR(120) NOT NULL DEFAULT '',
                phone VARCHAR(20) NOT NULL DEFAULT '',
                kind VARCHAR(10) NOT NULL DEFAULT 'buy',
                budget BIGINT UNSIGNED NOT NULL DEFAULT 0,
                min_area INT UNSIGNED NOT NULL DEFAULT 0,
                neighborhood VARCHAR(120) NOT NULL DEFAULT '',
                description TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'new',
                created_by INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_office_requests_phone (phone),
                KEY idx_office_requests_status (status),
                KEY idx_office_requests_customer (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS office_visits (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ad_id VARCHAR(64) NOT NULL DEFAULT '',
                customer_id INT UNSIGNED NOT NULL DEFAULT 0,
                name VARCHAR(120) NOT NULL DEFAULT '',
                phone VARCHAR(20) NOT NULL DEFAULT '',
                visit_at DATETIME NULL DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
                notes TEXT NOT NULL,
                created_by INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_office_visits_ad (ad_id),
                KEY idx_office_visits_status (status),
                KEY idx_office_visits_at (visit_at),
                KEY idx_office_visits_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS office_followups (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity VARCHAR(20) NOT NULL DEFAULT 'ad',
                entity_id VARCHAR(64) NOT NULL DEFAULT '',
                title VARCHAR(180) NOT NULL DEFAULT '',
                note TEXT NOT NULL,
                due_date DATE NULL DEFAULT NULL,
                done TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_office_followups_due (done, due_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS office_calls (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(120) NOT NULL DEFAULT '',
                phone VARCHAR(20) NOT NULL DEFAULT '',
                ad_id VARCHAR(64) NOT NULL DEFAULT '',
                direction VARCHAR(10) NOT NULL DEFAULT 'in',
                duration INT UNSIGNED NOT NULL DEFAULT 0,
                note TEXT NOT NULL,
                called_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_office_calls_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS office_settings (
                k VARCHAR(80) NOT NULL,
                v TEXT NOT NULL,
                PRIMARY KEY (k)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($sql as $q) {
            try {
                $pdo->exec($q);
            } catch (Throwable $e) {
                // اگر جدولی ساخته نشد، صفحه خراب نمی‌شود؛ همان بخش خطا می‌دهد
            }
        }
    }
}

if (!function_exists('office_is_logged_in')) {
    /**
     * ورود کامل = رمز + (نداشتن ثبت دوعاملی یا تأیید این سشن).
     * حالت نیمه‌تمام (رمز درست، کد نزده) فقط در خود صفحه
     * two-factor.php و logout.php مجاز است تا همه صفحات — حتی
     * POSTهای بالای صفحه که قبل از شل اجرا می‌شوند — بی‌نیاز از
     * هیچ تغییری، پشت گیت دوعاملی بمانند.
     */
    function office_is_logged_in(): bool
    {
        if (empty($_SESSION['is_admin'])) {
            return false;
        }
        try {
            if (!office_2fa_needs_check()) {
                return true;
            }
        } catch (Throwable $e) {
            return true;
        }
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $f) {
            $file = str_replace('\\', '/', (string)($f['file'] ?? ''));
            if (str_ends_with($file, '/two-factor.php') || str_ends_with($file, '/logout.php')) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('office_redirect')) {
    /**
     * ریدایرکت یکدست. در تست (OFFICE_NOEXIT) فقط هدر ثبت می‌شود و exit
     * نمی‌شود تا سناریو قابل assert باشد؛ در پروداکشن رفتار عین header+exit.
     */
    function office_redirect(string $url): void
    {
        if (defined('OFFICE_NOEXIT')) {
            // تست: به‌جای هدر واقعی (که در CLI بعد از اولین echo ممکن نیست)،
            // مقصد در حافظه ثبت می‌شود تا assert شود. پروداکشن دست‌نخورده.
            $GLOBALS['office_test_redirect'] = $url;
            return;
        }
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('office_require_login')) {
    function office_require_login(): void
    {
        // SECFIX(P3-session-lock): UA متفاوت = نشست ادمین نامعتبر.
        if (!empty($_SESSION['is_admin']) && function_exists('melkinoAdminFpCheck') && !melkinoAdminFpCheck()) {
            office_redirect('login.php?session=invalid');
            if (!defined('OFFICE_NOEXIT')) {
                exit;
            }
            return;
        }
        if (!office_is_logged_in()) {
            $to = 'login.php';
            try {
                if (office_2fa_password_ok() && office_2fa_needs_check()) {
                    $to = 'two-factor.php';
                }
            } catch (Throwable $e) {
            }
            office_redirect($to);
            if (!defined('OFFICE_NOEXIT')) {
                exit;
            }
        }
    }
}

if (!function_exists('office_user')) {
    /** @return array{id:int,username:string,display_name:string} */
    function office_user(): array
    {
        return [
            'id' => (int)($_SESSION['admin_id'] ?? 0),
            'username' => (string)($_SESSION['admin_username'] ?? 'admin'),
            'display_name' => (string)($_SESSION['admin_display_name'] ?? ''),
        ];
    }
}

if (!function_exists('office_h')) {
    function office_h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('office_fa')) {
    function office_fa(string $s): string
    {
        return strtr($s, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}

if (!function_exists('office_num')) {
    function office_num(int|float $n): string
    {
        return office_fa(number_format($n));
    }
}

if (!function_exists('office_theme')) {
    /** dark پیش‌فرض؛ light فقط اگر کوکی ذخیره شده باشد */
    function office_theme(): string
    {
        return (($_COOKIE['office_theme'] ?? '') === 'light') ? 'light' : 'dark';
    }
}

if (!function_exists('office_csrf')) {
    function office_csrf(): string
    {
        return function_exists('melkinoCsrfToken') ? (string)melkinoCsrfToken() : '';
    }
}

if (!function_exists('office_csrf_field')) {
    /** فیلد مخفی CSRF برای فرم‌های دفتر (افزایشی مرحله ۲). */
    function office_csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . office_h(office_csrf()) . '">';
    }
}

if (!function_exists('office_csrf_valid')) {
    /** بررسی توکن فرم بدون die (برای رندر مجدد با خطا). افزایشی مرحله ۲. */
    function office_csrf_valid(): bool
    {
        $sent = (string)($_POST['csrf_token'] ?? '');
        $expected = (string)($_SESSION['melkino_csrf'] ?? '');
        return $sent !== '' && $expected !== '' && hash_equals($expected, $sent);
    }
}

if (!function_exists('office_nav_items')) {
    /** @return array<int, array{href:string,label:string,icon:string,ready:bool}> */
    function office_nav_items(): array
    {
        return [
            ['href' => 'index.php', 'label' => 'داشبورد', 'icon' => '▦', 'ready' => true],
            ['href' => 'files.php', 'label' => 'فایل‌ها', 'icon' => '🗂', 'ready' => true],
            ['href' => 'register.php', 'label' => 'ثبت فایل', 'icon' => '＋', 'ready' => true],
            ['href' => 'partnerships.php', 'label' => 'مشارکت‌ها', 'icon' => '🤝', 'ready' => true],
            ['href' => 'customers.php', 'label' => 'مشتریان', 'icon' => '👥', 'ready' => true],
            ['href' => 'requests.php', 'label' => 'درخواست‌ها', 'icon' => '📝', 'ready' => true],
            ['href' => 'visits.php', 'label' => 'بازدیدها', 'icon' => '📅', 'ready' => true],
            ['href' => 'calls.php', 'label' => 'تماس‌ها', 'icon' => '📞', 'ready' => true],
            ['href' => 'followups.php', 'label' => 'پیگیری‌ها', 'icon' => '⏰', 'ready' => true],
            ['href' => 'reports.php', 'label' => 'گزارش‌ها', 'icon' => '📊', 'ready' => true],
            ['href' => 'map.php', 'label' => 'نقشه فایل‌ها', 'icon' => '🗺', 'ready' => true],
            ['href' => 'staff.php', 'label' => 'کاربران و دسترسی‌ها', 'icon' => '🛡', 'ready' => true],
            ['href' => 'promotions.php', 'label' => 'مدیریت سایت', 'icon' => '⚙', 'ready' => true],
            ['href' => 'settings.php', 'label' => 'تنظیمات', 'icon' => '🔧', 'ready' => true],
            ['href' => 'two-factor.php', 'label' => 'تأیید دومرحله‌ای', 'icon' => '🔐', 'ready' => true],
        ];
    }
}

if (!function_exists('office_shell_open')) {
    function office_shell_open(string $active, string $title): void
    {
        office_require_login();
        office_ensure_tables();
        $u = office_user();
        $theme = office_theme();
        $name = $u['display_name'] !== '' ? $u['display_name'] : $u['username'];
        $initial = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
        ?>
<!doctype html>
<html lang="fa" dir="rtl" data-theme="<?= $theme ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= office_h($title) ?> | دفتر ملکینو شهر</title>
<link rel="stylesheet" href="assets/office.css">
</head>
<body>
<div class="of-layout">
    <aside class="of-side" id="ofSide">
        <div class="of-brand">
            <span class="of-brand-logo">🏛</span>
            <span class="of-brand-name">ملکینو شهر<small>دفتر املاک</small></span>
        </div>
        <nav class="of-nav">
            <?php foreach (office_nav_items() as $it): ?>
                <?php if ($it['ready']): ?>
                    <a class="of-nav-item<?= $active === $it['href'] ? ' on' : '' ?>" href="<?= $it['href'] ?>">
                        <span class="of-nav-ico"><?= $it['icon'] ?></span><?= office_h($it['label']) ?>
                    </a>
                <?php else: ?>
                    <span class="of-nav-item off" title="به‌زودی">
                        <span class="of-nav-ico"><?= $it['icon'] ?></span><?= office_h($it['label']) ?>
                        <em class="of-soon">به‌زودی</em>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="of-side-foot">
            <a class="of-nav-item" href="../index.php">🌐 مشاهده سایت</a>
            <a class="of-nav-item" href="../admin-panel.php">🖥 پنل قدیمی</a>
            <a class="of-nav-item danger" href="logout.php">⎋ خروج</a>
        </div>
    </aside>
    <div class="of-main">
        <header class="of-top">
            <button class="of-burger" id="ofBurger" aria-label="منو">☰</button>
            <h1 class="of-title"><?= office_h($title) ?></h1>
            <span class="of-clock" id="ofClock">—</span>
            <button class="of-theme" id="ofTheme" aria-label="تغییر تم">🌙</button>
            <span class="of-user"><span class="of-avatar"><?= office_h($initial) ?></span><?= office_h($name) ?></span>
        </header>
        <main class="of-body">
        <?php
    }
}

if (!function_exists('office_shell_close')) {
    function office_shell_close(): void
    {
        ?>
        </main>
        <footer class="of-muted" style="padding:10px 16px;font-size:11.5px">دفتر ملکینو شهر — بیلد <?= office_h(defined('OFFICE_BUILD') ? OFFICE_BUILD : '?') ?></footer>
    </div>
</div>
<script src="assets/office.js"></script>
</body>
</html>
        <?php
    }
}

if (!function_exists('office_count_groups')) {
    /** شمارش گروهی امن از هر جدول (برای کارت‌های داشبورد) */
    function office_count_groups(string $table, string $column): array
    {
        $out = [];
        try {
            $pdo = office_db();
            $table = preg_replace('/[^a-z_]/', '', $table);
            $column = preg_replace('/[^a-z_]/', '', $column);
            $st = $pdo->query("SELECT `{$column}` AS k, COUNT(*) AS n FROM `{$table}` GROUP BY `{$column}`");
            if ($st) {
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $out[(string)($r['k'] ?? '')] = (int)$r['n'];
                }
            }
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_count_all')) {
    function office_count_all(string $table): int
    {
        try {
            $pdo = office_db();
            $table = preg_replace('/[^a-z_]/', '', $table);
            $n = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            return $n ? (int)$n->fetchColumn() : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('office_format_money')) {
    /**
     * نمایش مبلغ در دفتر: عدد کامل با جداکنندهٔ سه‌رقمی؛ فقط صفرهای بعد
     * از دات حذف می‌شوند (‎2800000000.00‎ ← ‎2,800,000,000‎).
     * ورودی فارسی/عربی/کما‌دار هم تحمل می‌شود؛ صفر/خالی ← «—».
     */
    function office_format_money(mixed $value): string
    {
        $s = trim((string)$value);
        if ($s === '') {
            return '—';
        }
        $s = strtr($s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $s = str_replace([',', '٬', '،', ' ', 'تومان'], '', $s);
        if ($s === '') {
            return '—';
        }
        // اعشار: فقط اگر همه‌اش صفر بود حذف می‌شود؛ اصل عدد دست‌نخورده
        $dec = '';
        if (str_contains($s, '.')) {
            $parts = explode('.', $s, 2);
            $s = $parts[0];
            $dec = preg_replace('/\D/', '', $parts[1] ?? '') ?? '';
            if ($dec === '' || trim($dec, '0') === '') {
                $dec = '';
            }
        }
        if (!preg_match('/^\d+$/', $s)) {
            return $s === '' ? '—' : $s;
        }
        $s = ltrim($s, '0');
        if ($s === '') {
            return $dec === '' ? '—' : '0.' . $dec;
        }
        return number_format((int)$s) . ($dec === '' ? '' : '.' . $dec);
    }
}

if (!function_exists('office_group_digits')) {
    /**
     * قانون مبالغ دفتر: فقط جداکنندهٔ سه‌رقمی (بدون حذف صفر) — برای اینپوت‌ها.
     * ورودی فارسی/کما‌دار هم تحمل می‌شود.
     */
    function office_group_digits(mixed $value): string
    {
        $s = trim((string)$value);
        if ($s === '') {
            return '';
        }
        $s = strtr($s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $s = preg_replace('/\..*$/', '', $s) ?? '';
        $s = preg_replace('/[^\d]/', '', $s) ?? '';
        $s = ltrim($s, '0');
        if ($s === '') {
            return '0';
        }
        return number_format((int)$s);
    }
}

if (!function_exists('office_ad_price')) {
    /**
     * سلول قیمت فایل (HTML امن): اجاره ← ودیعه + اجاره؛ رهن کامل؛
     * پیش‌فروش ← قیمت کل؛ فروش ← قیمت فروش.
     */
    function office_ad_price(array $row): string
    {
        $tx = trim((string)($row['transaction_type'] ?? ''));
        $num = static function (mixed $v): string {
            $s = preg_replace('/\D+/', '', (string)$v) ?? '';
            $s = ltrim($s, '0');
            return $s;
        };
        $cell = static function (string $label, mixed $v): string {
            return office_h($label) . ' <span dir="ltr">' . office_h(office_format_money($v)) . '</span>';
        };
        if (str_contains($tx, 'اجاره')) {
            if (($row['full_rent_enabled'] ?? '0') === '1' && $num($row['full_rent'] ?? '') !== '') {
                return $cell('رهن کامل', $row['full_rent']);
            }
            $parts = [];
            if ($num($row['deposit'] ?? '') !== '') {
                $parts[] = $cell('ودیعه', $row['deposit']);
            }
            if ($num($row['rent_monthly'] ?? '') !== '') {
                $parts[] = $cell('اجاره', $row['rent_monthly']);
            }
            return $parts ? implode(' / ', $parts) : '—';
        }
        if ($tx === 'پیش فروش') {
            return $num($row['total_price'] ?? '') !== '' ? $cell('قیمت کل', $row['total_price']) : '—';
        }
        foreach (['price_sell' => 'قیمت', 'total_price' => 'قیمت کل', 'deposit' => 'ودیعه', 'rent_monthly' => 'اجاره', 'full_rent' => 'رهن کامل'] as $k => $lb) {
            if ($num($row[$k] ?? '') !== '') {
                return $cell($lb, $row[$k]);
            }
        }
        return '—';
    }
}
