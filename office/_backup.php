<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه پشتیبان‌گیری و بازیابی (مرحله ۲۹)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-backup.php سایت: همان توابع خالص (کپی دقیق با
 * تغییر مسیر ریشه به پوشه سایت)، همان قراردادهای create/list/upload/
 * download/delete/restore، همان پوشه مشترک backups/ و همان نسخه ایمنی
 * اجباری پیش از بازیابی.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!defined('MELKINO_BACKUP_DIRNAME')) {
    define('MELKINO_BACKUP_DIRNAME', 'backups');
}

function office_bk_dir(): string
{
    $dir = dirname(__DIR__) . '/' . MELKINO_BACKUP_DIRNAME;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    office_bk_protect_dir($dir);
    return $dir;
}

/**
 * پوشه‌ی بکاپ‌ها حاوی خروجی کامل دیتابیس است و نباید از وب در دسترس باشد.
 * با .htaccess دسترسی مستقیم بسته می‌شود (دانلود فقط از طریق همین فایل و
 * برای ادمین لاگین‌کرده ممکن است) و index.html جلوی فهرست‌شدن را می‌گیرد.
 */
function office_bk_protect_dir(string $dir): void
{
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    $ix = $dir . '/index.html';
    if (!is_file($ix)) {
        @file_put_contents($ix, '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Forbidden</title></head><body>Forbidden</body></html>');
    }
}

/** فقط نام‌های امن (بدون مسیر) پذیرفته می‌شوند — جلوگیری از Path Traversal */
function office_bk_safe_name(?string $name): string
{
    $name = basename(trim((string)$name));
    if (!preg_match('/^melkino-backup-[\w.-]+\.(zip|tar|tar\.gz)$/', $name)) {
        return '';
    }
    return $name;
}

/**
 * اگر افزونه ZipArchive روی هاست فعال نباشد، از PharData استفاده
 * می‌شود (که به‌صورت پیش‌فرض همراه PHP است) و بایگانی به‌جای zip
 * با فرمت tar ساخته می‌شود.
 */
function office_bk_create_archive(string $targetPath, string $databaseSql = '', bool $withFiles = true, array $tables = [], array $extraMeta = []): array
{
    $root = dirname(__DIR__);
    $excludes = office_bk_excludes();
    $fileCount = 0;

    $addFiles = function ($addFile, $addDir) use ($root, $excludes, &$fileCount) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $path => $info) {
            $relative = substr($path, strlen($root) + 1);
            $parts = explode('/', str_replace('\\', '/', $relative));
            if (in_array($parts[0], $excludes, true)) {
                continue;
            }
            if ($info->isFile() && office_bk_is_secret($relative)) {
                continue;
            }
            if ($info->isDir()) {
                $addDir($relative);
            } elseif ($info->isFile()) {
                $addFile($path, $relative);
                $fileCount++;
            }
        }
    };

    $buildMeta = function () use ($withFiles, $databaseSql, $tables, &$fileCount, $extraMeta) {
        // راند ۳۳: متادیتای کامل‌تر (php_version + شمارش رکوردها + دامنه)
        return json_encode(array_merge([
            'version'    => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'with_files' => $withFiles,
            'with_db'    => $databaseSql !== '',
            'file_count' => $fileCount,
            'tables'     => array_values($tables),
            'generator'  => 'melkino-backup',
            'php_version' => PHP_VERSION,
        ], is_array($extraMeta) ? $extraMeta : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    };

    if (str_ends_with($targetPath, '.zip') && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('ساخت فایل زیپ ممکن نشد.');
        }

        if ($withFiles) {
            $addFiles(
                function ($path, $relative) use ($zip) { $zip->addFile($path, $relative); },
                function ($relative) use ($zip) { $zip->addEmptyDir($relative); }
            );
        }

        $zip->addFromString('meta.json', (string)$buildMeta());
        if ($databaseSql !== '') {
            $zip->addFromString('database.sql', $databaseSql);
        }
        $zip->close();

        return [$fileCount, true];
    }

    // مسیر جایگزین: tar
    $tarPath = preg_replace('/\.zip$/', '.tar', $targetPath);
    if (is_file($tarPath)) {
        @unlink($tarPath);
    }

    $tar = new PharData($tarPath);

    if ($withFiles) {
        $addFiles(
            function ($path, $relative) use ($tar) { $tar->addFile($path, $relative); },
            function ($relative) {}
        );
    }

    $tmpMeta = sys_get_temp_dir() . '/melkino-meta-' . bin2hex(random_bytes(4)) . '.json';
    file_put_contents($tmpMeta, (string)$buildMeta());
    $tar->addFile($tmpMeta, 'meta.json');
    @unlink($tmpMeta);

    if ($databaseSql !== '') {
        $tmpSql = sys_get_temp_dir() . '/melkino-database-' . bin2hex(random_bytes(4)) . '.sql';
        file_put_contents($tmpSql, $databaseSql);
        $tar->addFile($tmpSql, 'database.sql');
        @unlink($tmpSql);
    }

    return [$fileCount, true, $tarPath];
}

/**
 * راند ۳۳: بررسی سلامت آرشیو پشتیبان پس از ساخت — آرشیو باز می‌شود،
 * تعداد ورودی‌ها شمرده و وجود meta.json و سالم بودن database.sql تأیید
 * می‌گردد تا «پشتیبان خراب» هرگز در فهرست نماند.
 */
function office_bk_verify(string $path): array
{
    if (!is_file($path)) {
        return ['ok' => false, 'reason' => 'فایل آرشیو وجود ندارد', 'entries' => 0];
    }
    if (str_ends_with($path, '.zip') && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'reason' => 'آرشیو باز نمی‌شود (احتمالاً خراب است)', 'entries' => 0];
        }
        $entries = (int)$zip->numFiles;
        $hasMeta = $zip->locateName('meta.json') !== false;
        $sqlOk = true;
        if ($zip->locateName('database.sql') !== false) {
            $sqlSample = (string)$zip->getFromName('database.sql');
            $sqlOk = strpos($sqlSample, '-- ملکینو - پشتیبان دیتابیس') !== false;
        }
        $zip->close();
        $reason = !$hasMeta ? 'meta.json در آرشیو نیست' : (!$sqlOk ? 'database.sql ناقص است' : '');
        return ['ok' => $entries > 0 && $hasMeta && $sqlOk, 'reason' => $reason, 'entries' => $entries];
    }
    if (class_exists('PharData')) {
        try {
            $phar = new PharData($path);
            $entries = 0;
            $hasMeta = false;
            foreach (new RecursiveIteratorIterator($phar) as $item) {
                $entries++;
                if (basename($item->getPathname()) === 'meta.json') {
                    $hasMeta = true;
                }
            }
            return ['ok' => $entries > 0 && $hasMeta, 'reason' => $hasMeta ? '' : 'meta.json در آرشیو نیست', 'entries' => $entries];
        } catch (Throwable $e) {
            return ['ok' => false, 'reason' => 'آرشیو باز نمی‌شود: ' . $e->getMessage(), 'entries' => 0];
        }
    }
    return ['ok' => false, 'reason' => 'کتابخانه‌ی بررسی آرشیو روی سرور موجود نیست', 'entries' => 0];
}

/**
 * راند ۳۳: پاک‌سازی خودکار — فقط $keep پشتیبان معمولیِ آخر نگه داشته
 * می‌شود تا دیسک هاست پر نشود. نسخه‌های «ایمنی» (safety) هرگز حذف نمی‌شوند.
 */
function office_bk_prune(string $dir, int $keep = 10): int
{
    if ($keep < 1) {
        $keep = 1;
    }
    $files = [];
    foreach (array_merge(glob($dir . '/*.zip') ?: [], glob($dir . '/*.tar') ?: [], glob($dir . '/*.tar.gz') ?: []) as $file) {
        if (strpos(basename($file), 'safety') !== false) {
            continue;
        }
        $files[$file] = filemtime($file);
    }
    if (count($files) <= $keep) {
        return 0;
    }
    arsort($files);
    $deleted = 0;
    foreach (array_slice(array_keys($files), $keep) as $oldFile) {
        if (@unlink($oldFile)) {
            $deleted++;
        }
    }
    return $deleted;
}

/**
 * خواندن meta.json از داخل یک فایل پشتیبان (برای نمایش جزئیات در فهرست).
 * برای بکاپ‌های قدیمی که متا ندارند، null برمی‌گردد.
 */
function office_bk_read_meta(string $path): ?array
{
    try {
        if (str_ends_with($path, '.zip') && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                return null;
            }
            $raw = $zip->getFromName('meta.json');
            $zip->close();
            if (!is_string($raw) || $raw === '') {
                return null;
            }
            $meta = json_decode($raw, true);
            return is_array($meta) ? $meta : null;
        }

        if (class_exists('PharData')) {
            $phar = new PharData($path);
            if (!isset($phar['meta.json'])) {
                return null;
            }
            $meta = json_decode((string)file_get_contents($phar['meta.json']->getPathname()), true);
            return is_array($meta) ? $meta : null;
        }
    } catch (Throwable $e) {
        return null;
    }
    return null;
}

function office_bk_excludes(): array
{
    return [MELKINO_BACKUP_DIRNAME, '.git', 'node_modules', '.cache', 'config.secrets.php'];
}

/**
 * آیا این مسیر یک فایل محرمانه است که نباید در بکاپ (یا بازیابی) باشد؟
 * بکاپ‌ها خروجی کامل سایت هستند؛ اگر همراهشان config.secrets.php (رمز
 * دیتابیس و توکن‌های ربات‌ها) یا .env قرار گیرد، نشتِ هر بکاپ یعنی
 * نشتِ همه‌ی رازها.
 */
function office_bk_is_secret(string $relative): bool
{
    $base = strtolower(basename(str_replace('\\', '/', $relative)));
    if (str_contains($base, 'secrets')) {
        return true;
    }
    if ($base === '.env' || str_ends_with($base, '.env')) {
        return true;
    }
    return false;
}

/** خروجی SQL از دیتابیس با استفاده از PDO (نیازی به mysqldump ندارد) */
function office_bk_dump_db(PDO $pdo): string
{
    $out = "-- ملکینو - پشتیبان دیتابیس\n";
    $out .= "-- تاریخ: " . date('Y-m-d H:i:s') . "\n";
    $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
        $out .= 'DROP TABLE IF EXISTS `' . $table . "`;\n";
        $out .= $create[1] . ";\n\n";

        $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_map(fn($c) => '`' . $c . '`', array_keys($row));
            $values = array_map(function ($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote((string)$v);
            }, array_values($row));
            $out .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        $out .= "\n";
    }

    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $out;
}

/**
 * تفکیک یک اسکریپت SQL به دستورهای جداگانه.
 * نقطه‌ویرگول‌های داخل رشته‌ها (تک/دوکویتیشن)، بک‌تیک‌ها و کامنت‌ها
 * جدا کننده حساب نمی‌شوند.
 */
function office_bk_split_sql(string $sql): array
{
    $stmts = [];
    $buf = '';
    $len = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        $n = $i + 1 < $len ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($c === "\n") {
                $inLineComment = false;
                $buf .= $c;
            }
            continue;
        }
        if ($inBlockComment) {
            if ($c === '*' && $n === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }
        if ($inSingle) {
            $buf .= $c;
            if ($c === '\\' && $n !== '') {
                $buf .= $n;
                $i++;
            } elseif ($c === "'") {
                $inSingle = false;
            }
            continue;
        }
        if ($inDouble) {
            $buf .= $c;
            if ($c === '\\' && $n !== '') {
                $buf .= $n;
                $i++;
            } elseif ($c === '"') {
                $inDouble = false;
            }
            continue;
        }
        if ($inBacktick) {
            $buf .= $c;
            if ($c === '`') {
                $inBacktick = false;
            }
            continue;
        }
        if ($c === '-' && $n === '-' && ($i + 2 >= $len || strpos(" \t\n\r", $sql[$i + 2]) !== false)) {
            $inLineComment = true;
            $i++;
            continue;
        }
        if ($c === '#') {
            $inLineComment = true;
            continue;
        }
        if ($c === '/' && $n === '*') {
            $inBlockComment = true;
            $i++;
            continue;
        }
        if ($c === "'") {
            $inSingle = true;
            $buf .= $c;
            continue;
        }
        if ($c === '"') {
            $inDouble = true;
            $buf .= $c;
            continue;
        }
        if ($c === '`') {
            $inBacktick = true;
            $buf .= $c;
            continue;
        }
        if ($c === ';') {
            $t = trim($buf);
            if ($t !== '') {
                $stmts[] = $t;
            }
            $buf = '';
            continue;
        }
        $buf .= $c;
    }

    $t = trim($buf);
    if ($t !== '') {
        $stmts[] = $t;
    }
    return $stmts;
}

if (!function_exists('office_bk_available')) {
    function office_bk_available(): bool
    {
        return class_exists('ZipArchive') || class_exists('PharData');
    }
}

if (!function_exists('office_bk_list')) {
    /** @return array<int,array> */
    function office_bk_list(): array
    {
        $dir = office_bk_dir();
        $items = [];
        foreach (array_merge(glob($dir . '/*.zip') ?: [], glob($dir . '/*.tar') ?: [], glob($dir . '/*.tar.gz') ?: []) as $file) {
            $base = basename($file);
            $meta = office_bk_read_meta($file);
            $items[] = [
                'name' => $base,
                'size' => filesize($file),
                'size_human' => round(filesize($file) / 1024 / 1024, 2) . ' مگابایت',
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'is_safety' => strpos($base, 'safety') !== false,
                'age_days' => (int)floor((time() - filemtime($file)) / 86400),
                'meta' => $meta ? [
                    'with_files' => !empty($meta['with_files']),
                    'with_db' => !empty($meta['with_db']),
                    'file_count' => (int)($meta['file_count'] ?? 0),
                    'tables' => is_array($meta['tables'] ?? null) ? count($meta['tables']) : 0,
                    'rows_total' => (int)($meta['rows_total'] ?? (is_array($meta['table_rows'] ?? null) ? array_sum($meta['table_rows']) : 0)),
                    'php_version' => (string)($meta['php_version'] ?? ''),
                    'site' => (string)($meta['site'] ?? ''),
                ] : null,
            ];
        }
        usort($items, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $items;
    }
}

if (!function_exists('office_bk_create')) {
    /** @return array{0:bool,1:string,2:string} [ok,message,file] */
    function office_bk_create(PDO $pdo, bool $withDb, bool $withFiles): array
    {
        if (!office_bk_available()) {
            return [false, 'هیچ کتابخانه‌ی فشرده‌سازی روی سرور در دسترس نیست.', ''];
        }
        $dir = office_bk_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return [false, 'پوشه backups وجود ندارد یا قابل نوشتن نیست.', ''];
        }
        if (!$withDb && !$withFiles) {
            return [false, 'حداقل یکی از «فایل‌ها» یا «دیتابیس» باید انتخاب شود.', ''];
        }
        try {
            @set_time_limit(0);
            @ignore_user_abort(true);
        } catch (Throwable $e) {
        }
        $fileName = 'melkino-backup-' . date('Ymd-His') . '.zip';
        $target = $dir . '/' . $fileName;
        // نام یکتا: اگر در همان ثانیه فایلی با همین نام هست (دابل‌کلیک) یا
        // همین مسیر در همین پردازش استفاده شده (کش PharData)، پسوند می‌گیرد.
        static $usedTargets = [];
        $dup = 0;
        while (is_file($target) || is_file(preg_replace('/\.zip$/', '.tar', $target)) || isset($usedTargets[$target])) {
            $dup++;
            $fileName = 'melkino-backup-' . date('Ymd-His') . '-' . $dup . '.zip';
            $target = $dir . '/' . $fileName;
            if ($dup > 50) {
                break;
            }
        }
        $usedTargets[$target] = true;
        $databaseSql = '';
        $tables = [];
        $tableRows = [];
        if ($withDb) {
            try {
                $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                $databaseSql = office_bk_dump_db($pdo);
            } catch (Throwable $e) {
                $databaseSql = '';
                $tables = [];
            }
            foreach ($tables as $tbl) {
                try {
                    $tableRows[(string)$tbl] = (int)$pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '', (string)$tbl) . '`')->fetchColumn();
                } catch (Throwable $e) {
                    $tableRows[(string)$tbl] = 0;
                }
            }
        }
        try {
            $result = office_bk_create_archive($target, $databaseSql, $withFiles, $tables, [
                'table_rows' => $tableRows,
                'rows_total' => array_sum($tableRows),
                'site' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            ]);
        } catch (Throwable $e) {
            return [false, 'عملیات پشتیبان‌گیری انجام نشد.', ''];
        }
        $fileCount = (int)($result[0] ?? 0);
        $archivePath = (string)($result[2] ?? $target);
        $dbDone = $databaseSql !== '';
        if (!is_file($archivePath)) {
            return [false, 'ساخت فایل پشتیبان ناموفق بود.', ''];
        }
        $verify = office_bk_verify($archivePath);
        if (empty($verify['ok'])) {
            @unlink($archivePath);
            return [false, 'پشتیبان ساخته شد ولی بررسی سلامت ناموفق بود و فایل حذف شد: ' . (string)($verify['reason'] ?? 'نامشخص'), ''];
        }
        $pruned = office_bk_prune($dir, 10);
        $parts = [];
        if ($withFiles) {
            $parts[] = $fileCount . ' فایل';
        }
        if ($dbDone) {
            $parts[] = 'دیتابیس (' . count($tables) . ' جدول)';
        } elseif ($withDb) {
            $parts[] = 'بدون دیتابیس (خطا در خروجی)';
        }
        $msg = 'پشتیبان ساخته شد (' . implode(' + ', $parts) . ') · بررسی سلامت ✓ (' . (int)($verify['entries'] ?? 0) . ' ورودی)';
        if ($pruned > 0) {
            $msg .= ' · ' . $pruned . ' نسخه قدیمی خودکار پاک شد';
        }
        return [true, $msg, basename($archivePath)];
    }
}

if (!function_exists('office_bk_delete')) {
    /** @return array{0:bool,1:string} */
    function office_bk_delete(string $name): array
    {
        $safe = office_bk_safe_name($name);
        $path = office_bk_dir() . '/' . $safe;
        if ($safe === '' || !is_file($path)) {
            return [false, 'فایل پشتیبان پیدا نشد.'];
        }
        @unlink($path);
        return [true, 'فایل پشتیبان حذف شد.'];
    }
}

if (!function_exists('office_bk_validate_upload')) {
    /** @return array{0:bool,1:string,2:string} [ok,message,safeName] */
    function office_bk_validate_upload(?array $file): array
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $err = is_array($file) ? (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            $msg = 'آپلود فایل ناموفق بود.';
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'حجم فایل از سقف مجاز هاست بیشتر است.';
            } elseif ($err === UPLOAD_ERR_NO_FILE) {
                $msg = 'فایلی انتخاب نشده است.';
            }
            return [false, $msg, ''];
        }
        $origName = (string)($file['name'] ?? '');
        $lower = strtolower($origName);
        $ext = '';
        if (str_ends_with($lower, '.zip')) {
            $ext = '.zip';
        } elseif (str_ends_with($lower, '.tar.gz')) {
            $ext = '.tar.gz';
        } elseif (str_ends_with($lower, '.tar')) {
            $ext = '.tar';
        } else {
            return [false, 'فقط فایل‌های zip و tar مجاز هستند.', ''];
        }
        $safe = office_bk_safe_name($origName);
        if ($safe === '' || !str_ends_with(strtolower($safe), $ext)) {
            $safe = 'melkino-backup-uploaded-' . date('Ymd-His') . $ext;
        }
        return [true, '', $safe];
    }
}

if (!function_exists('office_bk_upload')) {
    /** @return array{0:bool,1:string} */
    function office_bk_upload(?array $file): array
    {
        $dir = office_bk_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return [false, 'پوشه backups وجود ندارد یا قابل نوشتن نیست.'];
        }
        [$ok, $msg, $safe] = office_bk_validate_upload($file);
        if (!$ok) {
            return [false, $msg];
        }
        $dest = $dir . '/' . $safe;
        $tmp = (string)$file['tmp_name'];
        if (!@move_uploaded_file($tmp, $dest)) {
            if (!is_file($tmp) || !@rename($tmp, $dest)) {
                return [false, 'ذخیره‌ی فایل آپلودشده ممکن نشد.'];
            }
        }
        return [true, 'فایل آپلود شد و آماده‌ی بازیابی است.'];
    }
}

if (!function_exists('office_bk_download_path')) {
    function office_bk_download_path(string $name): string
    {
        $safe = office_bk_safe_name($name);
        if ($safe === '') {
            return '';
        }
        $path = office_bk_dir() . '/' . $safe;
        return is_file($path) ? $path : '';
    }
}

if (!function_exists('office_bk_restore')) {
    /** @return array{0:bool,1:string} */
    function office_bk_restore(PDO $pdo, string $name, bool $restoreDb, bool $restoreFiles): array
    {
        if (!office_bk_available()) {
            return [false, 'هیچ کتابخانه‌ی فشرده‌سازی روی سرور در دسترس نیست.'];
        }
        try {
            @set_time_limit(0);
            @ignore_user_abort(true);
        } catch (Throwable $e) {
        }
        $dir = office_bk_dir();
        $safe = office_bk_safe_name($name);
        $path = $dir . '/' . $safe;
        if ($safe === '' || !is_file($path)) {
            return [false, 'فایل پشتیبان پیدا نشد.'];
        }
        if (!$restoreDb && !$restoreFiles) {
            return [false, 'حداقل یکی از «فایل‌ها» یا «دیتابیس» باید انتخاب شود.'];
        }
        $dbSql = '';
        $useZip = class_exists('ZipArchive') && str_ends_with($path, '.zip');
        if ($useZip) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                return [false, 'فایل پشتیبان قابل بازگشایی نیست.'];
            }
            $dbSql = (string)$zip->getFromName('database.sql');
            $zip->close();
        } elseif (class_exists('PharData')) {
            try {
                $phar = new PharData($path);
                if (isset($phar['database.sql'])) {
                    $dbSql = (string)file_get_contents($phar['database.sql']->getPathname());
                }
            } catch (Throwable $e) {
                return [false, 'فایل پشتیبان قابل بازگشایی نیست.'];
            }
        } else {
            return [false, 'هیچ کتابخانه‌ای برای باز کردن فایل پشتیبان در دسترس نیست.'];
        }
        if ($restoreDb && $dbSql === '') {
            return [false, 'دیتابیس داخل این فایل پشتیبان پیدا نشد؛ بازیابی انجام نشد.'];
        }
        // نسخه‌ی ایمنی از وضعیت فعلی (فایل‌ها + دیتابیس) — اجباری
        $safetyCreated = false;
        $safetyName = '';
        try {
            $safetySql = '';
            $safetyTables = [];
            try {
                $safetyTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                $safetySql = office_bk_dump_db($pdo);
            } catch (Throwable $e) {
                $safetySql = '';
            }
            $safetyTarget = $dir . '/melkino-backup-safety-' . date('Ymd-His') . '.zip';
            $safetyResult = office_bk_create_archive($safetyTarget, $safetySql, true, $safetyTables);
            $safetyPath = (string)($safetyResult[2] ?? $safetyTarget);
            if (is_file($safetyPath)) {
                $safetyCreated = true;
                $safetyName = basename($safetyPath);
            }
        } catch (Throwable $e) {
            $safetyCreated = false;
        }
        if (!$safetyCreated) {
            return [false, 'ساخت نسخه‌ی ایمنی ناموفق بود؛ برای امنیت، بازیابی انجام نشد.'];
        }
        // بازیابی فایل‌ها
        $restored = 0;
        $excludes = office_bk_excludes();
        $root = dirname(__DIR__);
        if ($restoreFiles) {
            $copyEntry = static function (string $relative, string $content) use (&$restored, $excludes, $root): void {
                if ($relative === 'database.sql' || $relative === 'meta.json') {
                    return;
                }
                if (strpos($relative, '..') !== false) {
                    return;
                }
                if (office_bk_is_secret($relative)) {
                    return;
                }
                $parts = explode('/', str_replace('\\', '/', $relative));
                if (in_array($parts[0], $excludes, true)) {
                    return;
                }
                $target = $root . '/' . $relative;
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }
                if (@file_put_contents($target, $content, LOCK_EX) !== false) {
                    $restored++;
                }
            };
            if ($useZip) {
                $zip = new ZipArchive();
                $zip->open($path);
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if ($entry === false || $entry === null) {
                        continue;
                    }
                    $content = $zip->getFromIndex($i);
                    if ($content === false) {
                        continue;
                    }
                    $copyEntry($entry, $content);
                }
                $zip->close();
            } else {
                $tmp = sys_get_temp_dir() . '/melkino-restore-' . bin2hex(random_bytes(4));
                @mkdir($tmp, 0755, true);
                $phar = new PharData($path);
                $phar->extractTo($tmp, null, true);
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $f) {
                    if (!$f->isFile()) {
                        continue;
                    }
                    $relative = substr($f->getPathname(), strlen($tmp) + 1);
                    $copyEntry($relative, (string)file_get_contents($f->getPathname()));
                }
            }
        }
        // بازیابی دیتابیس
        $dbStatements = 0;
        $dbErrors = [];
        if ($restoreDb && $dbSql !== '') {
            $shorten = static function (string $m): string {
                $m = trim((string)preg_replace('/\s+/', ' ', $m));
                return function_exists('mb_substr') ? mb_substr($m, 0, 160) : substr($m, 0, 160);
            };
            try {
                $stmts = office_bk_split_sql($dbSql);
                foreach ($stmts as $stmt) {
                    try {
                        $pdo->exec($stmt);
                        $dbStatements++;
                    } catch (Throwable $e) {
                        if (count($dbErrors) < 3) {
                            $dbErrors[] = $shorten($e->getMessage());
                        }
                    }
                }
            } catch (Throwable $e) {
                $dbErrors[] = $shorten($e->getMessage());
            }
        }
        $msgParts = [];
        if ($restoreFiles) {
            $msgParts[] = $restored . ' فایل بازیابی شد';
        }
        if ($restoreDb) {
            if ($dbStatements > 0 && !$dbErrors) {
                $msgParts[] = 'دیتابیس بازگردانده شد (' . $dbStatements . ' دستور)';
            } else {
                $msgParts[] = 'بازیابی دیتابیس ناقص ماند (' . $dbStatements . ' دستور موفق'
                    . ($dbErrors ? '؛ خطا: ' . implode(' / ', $dbErrors) : '') . ')';
            }
        }
        $msg = implode('؛ ', $msgParts) . '. یک نسخه‌ی ایمنی از وضعیت قبلی ساخته شد (' . $safetyName . ').';
        if ($restoreDb && !($dbStatements > 0 && !$dbErrors)) {
            return [false, $msg];
        }
        return [true, $msg];
    }
}
