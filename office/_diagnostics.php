<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه عیب‌یابی (مرحله ۳۱)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-diagnostics.php سایت: همان ۷ گروه چک (محیط
 * سرور، دیتابیس، فایل‌ها، ربات و کانال، احراز هویت، محتوا، امنیت) با
 * همان منطق؛ فقط خواندنی، بدون هیچ تغییری در دیتابیس یا فایل‌ها.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofDgB = dirname(__DIR__) . '/bot-settings.php';
if (is_file($__ofDgB)) {
    require_once $__ofDgB;
}
unset($__ofDgB);
if (!function_exists('melkinoBotSettings')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/bot-settings.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}
$__ofDgD = dirname(__DIR__) . '/db-settings.php';
if (is_file($__ofDgD)) {
    require_once $__ofDgD;
}
unset($__ofDgD);
if (!function_exists('dbSettingGet')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/db-settings.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}


/* =========================================================
   ابزارها
   ========================================================= */

if (!function_exists('office_dg_item')) {
    function office_dg_item(string $group, string $name, string $status, string $message, string $hint = ''): array
    {
        return [
            'group'   => $group,
            'name'    => $name,
            'status'  => $status,   // ok | warn | fail
            'message' => $message,
            'hint'    => $hint,
        ];
    }
}

if (!function_exists('office_dg_pdo')) {
    function office_dg_pdo(): ?PDO
    {
        global $pdo;
        return ($pdo instanceof PDO) ? $pdo : null;
    }
}

/** شمارش امنِ ردیف‌های یک جدول (در صورت نبود جدول یا خطا: null) */
if (!function_exists('office_dg_count')) {
    function office_dg_count(?PDO $pdo, string $table): ?int
    {
        if (!$pdo) {
            return null;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return null;
        }
        try {
            return (int)$pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_dg_columns')) {
    function office_dg_columns(?PDO $pdo, string $table): array
    {
        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[strtolower((string)$r['Field'])] = $r;
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_dg_indexes')) {
    function office_dg_indexes(?PDO $pdo, string $table): array
    {
        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query('SHOW INDEX FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $key = strtolower((string)$r['Key_name']);
                $out[$key]['unique'] = ((int)$r['Non_unique'] === 0);
                $out[$key]['columns'][] = strtolower((string)$r['Column_name']);
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }
}

/** آیا یک ایندکسِ یکتا روی این ستون(ها) وجود دارد؟ */
if (!function_exists('office_dg_has_unique_on')) {
    function office_dg_has_unique_on(?PDO $pdo, string $table, array $columns): bool
    {
        foreach (office_dg_indexes($pdo, $table) as $idx) {
            if (empty($idx['unique'])) {
                continue;
            }
            $cols = array_map('strtolower', $columns);
            $have = array_map('strtolower', $idx['columns'] ?? []);
            sort($cols);
            sort($have);
            if ($cols === $have) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('office_dg_api_call')) {
    /**
     * یک فراخوانی سریع به API پیام‌رسان‌ها با مهلت کوتاه (۵ ثانیه)
     * تا اجرای کل عیب‌یاب بیش از حد طولانی نشود. اگر بودجهٔ زمانی
     * کل اجرا تمام شده باشد، اصلاً تلاش نمی‌کند (هاست‌های رایگان
     * اسکریپت طولانی را می‌کُشند و ۵۰۲ می‌دهند).
     */
    function office_dg_api_call(string $url): array
    {
        $t0 = (float)($GLOBALS['office_dg_t0'] ?? 0);
        if ($t0 > 0 && (microtime(true) - $t0) > 10) {
            return ['ok' => false, 'description' => 'رد شد تا صفحه قفل نشود (بودجه زمانی)؛ با «اجرای دوباره» تکرار کنید.'];
        }
        // تلاش اول: curl با مهلت کوتاه
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => '',
            ]);

            $diagProxy = function_exists('melkinoProxy') ? trim((string)melkinoProxy()) : '';
            if ($diagProxy !== '' && defined('CURLOPT_PROXY')) {
                curl_setopt($ch, CURLOPT_PROXY, $diagProxy);
                $lower = strtolower($diagProxy);
                if (strpos($lower, 'socks5h://') === 0 && defined('CURLPROXY_SOCKS5_HOSTNAME')) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
                } elseif (strpos($lower, 'socks5://') === 0 && defined('CURLPROXY_SOCKS5')) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } elseif (strpos($lower, 'socks4://') === 0 && defined('CURLPROXY_SOCKS4')) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                }
            }

            $raw = curl_exec($ch);
            curl_close($ch);

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    if (empty($decoded['ok'])) {
                        return ['ok' => false, 'description' => (string)($decoded['description'] ?? 'خطای نامشخص')];
                    }
                    return ['ok' => true, 'result' => $decoded['result'] ?? []];
                }
            }
            // curl بود ولی جواب نگرفتیم: دیگر با فالبک ۲۰ثانیه‌ای وقت تلف
            // نمی‌کنیم (هاست اسکریپت طولانی را ۵۰۲ می‌کند) — همان خطا.
            return ['ok' => false, 'description' => 'ارتباط برقرار نشد (خروجی سرور یا فیلترینگ)'];
        }

        // بدون curl: تنها راه، melkinoHttpPost است.
        $response = function_exists('melkinoHttpPost') ? melkinoHttpPost($url, '') : null;
        if ($response === null || $response === '') {
            return ['ok' => false, 'description' => 'ارتباط برقرار نشد (خروجی سرور یا فیلترینگ)'];
        }
        $data = json_decode((string)$response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'description' => 'پاسخ نامعتبر از سرور'];
        }
        if (empty($data['ok'])) {
            return ['ok' => false, 'description' => (string)($data['description'] ?? 'خطای نامشخص')];
        }
        return ['ok' => true, 'result' => $data['result'] ?? []];
    }
}

/* =========================================================
   اجرای همه‌ی بررسی‌ها
   ========================================================= */

if (!function_exists('office_dg_run')) {
    function office_dg_run(): array
    {
        $GLOBALS['office_dg_t0'] = microtime(true);
        $checks = [];
        $pdo = office_dg_pdo();
        $root = dirname(__DIR__);

        /* ---------------- ۱. محیط سرور ---------------- */
        $g = '۱. محیط سرور';

        $phpVersion = PHP_VERSION;
        $versionOk = version_compare($phpVersion, '7.4.0', '>=');
        $checks[] = office_dg_item(
            $g,
            'نسخه PHP',
            $versionOk ? 'ok' : 'fail',
            $phpVersion . ($versionOk ? ' — پشتیبانی می‌شود' : ' — بسیار قدیمی است'),
            $versionOk ? '' : 'نسخه‌ی PHP باید حداقل ۷.۴ باشد. از هاست بخواهید آن را به‌روزرسانی کند.'
        );

        $requiredExt = [
            'pdo'       => ['fail', 'بدون آن هیچ ارتباطی با پایگاه داده برقرار نمی‌شود.'],
            'pdo_mysql' => ['fail', 'درایور MySQL برای PDO نصب نیست؛ سایت اصلاً کار نمی‌کند.'],
            'curl'      => ['warn', 'بدون آن ارسال پیام به تلگرام/بله با روش پشتیبان انجام می‌شود (کندتر و کم‌دوام‌تر).'],
            'json'      => ['fail', 'بدون آن بیشتر بخش‌های سایت از کار می‌افتند.'],
            'mbstring'  => ['warn', 'برای پردازش دقیق متن‌های فارسی توصیه می‌شود.'],
            'gd'        => ['warn', 'بدون آن تغییر اندازه و فشرده‌سازی تصاویر انجام نمی‌شود.'],
            'openssl'   => ['warn', 'بدون آن برخی ارتباط‌های امن (https) ممکن است ناموفق شود.'],
        ];
        foreach ($requiredExt as $ext => [$severity, $hint]) {
            $loaded = extension_loaded($ext);
            $checks[] = office_dg_item(
                $g,
                'افزونه ' . $ext,
                $loaded ? 'ok' : $severity,
                $loaded ? 'نصب شده' : 'نصب نیست',
                $loaded ? '' : $hint
            );
        }

        $zipOk = class_exists('ZipArchive');
        $checks[] = office_dg_item(
            $g,
            'افزونه zip (برای پشتیبان‌گیری)',
            $zipOk ? 'ok' : 'warn',
            $zipOk ? 'موجود — پشتیبان‌ها به صورت zip ذخیره می‌شوند' : 'موجود نیست — پشتیبان‌ها به صورت tar ذخیره می‌شوند',
            $zipOk ? '' : 'امکانات سایت محدود نمی‌شود؛ فقط فرمت فایل پشتیبان متفاوت است.'
        );

        $displayErrors = (bool)(int)ini_get('display_errors');
        $checks[] = office_dg_item(
            $g,
            'نمایش خطاها در صفحه',
            $displayErrors ? 'warn' : 'ok',
            $displayErrors ? 'روشن است' : 'خاموش است (صحیح)',
            $displayErrors ? 'نمایش خطاها در سایت اصلی باید خاموش باشد؛ در غیر این صورت خطاها به کاربران نشان داده می‌شود.' : ''
        );

        $uploadsOn = (bool)(int)ini_get('file_uploads');
        $checks[] = office_dg_item(
            $g,
            'امکان بارگذاری فایل',
            $uploadsOn ? 'ok' : 'fail',
            $uploadsOn ? 'فعال' : 'غیرفعال',
            $uploadsOn ? '' : 'بارگذاری تصاویر آگهی غیرممکن است. از هاست بخواهید file_uploads را فعال کند.'
        );

        $maxUpload = (string)ini_get('upload_max_filesize');
        $maxPost = (string)ini_get('post_max_size');
        $checks[] = office_dg_item(
            $g,
            'محدودیت حجم بارگذاری',
            'ok',
            'هر فایل: ' . $maxUpload . ' — کل درخواست: ' . $maxPost,
            'اگر تصاویر بزرگ آپلود نمی‌شوند، این مقادیر را در هاست افزایش دهید.'
        );

        $timeLimit = (int)ini_get('max_execution_time');
        $checks[] = office_dg_item(
            $g,
            'مهلت اجرای اسکریپت',
            $timeLimit > 0 && $timeLimit < 30 ? 'warn' : 'ok',
            $timeLimit > 0 ? $timeLimit . ' ثانیه' : 'بدون محدودیت',
            $timeLimit > 0 && $timeLimit < 30 ? 'برای پشتیبان‌گیری و ارسال تصویر به تلگرام ممکن است کم باشد.' : ''
        );

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $checks[] = office_dg_item(
            $g,
            'پروتکل HTTPS',
            $isHttps ? 'ok' : 'fail',
            $isHttps ? 'فعال' : 'غیرفعال — سایت با http باز شده است',
            $isHttps ? '' : 'مینی‌اپ تلگرام و بله فقط روی HTTPS کار می‌کند؛ نصب گواهی SSL الزامی است.'
        );

        $free = @disk_free_space($root);
        if ($free !== false && $free !== null) {
            $freeMb = (int)floor($free / 1048576);
            $checks[] = office_dg_item(
                $g,
                'فضای آزاد دیسک',
                $freeMb < 100 ? 'warn' : 'ok',
                number_format($freeMb) . ' مگابایت',
                $freeMb < 100 ? 'فضا کم است؛ آپلود تصویر و پشتیبان‌گیری ممکن است ناموفق شود.' : ''
            );
        }

        /* ---------------- ۲. پایگاه داده ---------------- */
        $g = '۲. پایگاه داده';

        if (!$pdo) {
            $checks[] = office_dg_item($g, 'اتصال به پایگاه داده', 'fail', 'برقرار نیست', 'اطلاعات اتصال در config.secrets.php را بررسی کنید (هاست، نام دیتابیس، کاربر، رمز).');
        } else {
            $checks[] = office_dg_item($g, 'اتصال به پایگاه داده', 'ok', 'برقرار است');

            try {
                $version = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
                $checks[] = office_dg_item($g, 'مشخصات پایگاه داده', 'ok', 'MySQL/MariaDB ' . $version . ' — پایگاه داده: ' . $dbName);
            } catch (Throwable $e) {
                $checks[] = office_dg_item($g, 'مشخصات پایگاه داده', 'warn', 'دریافت نشد');
            }

            try {
                $row = $pdo->query("SELECT @@character_set_database AS cs, @@collation_database AS cl")->fetch(PDO::FETCH_ASSOC);
                $cs = strtolower((string)($row['cs'] ?? ''));
                $checks[] = office_dg_item(
                    $g,
                    'رمزگذاری پایگاه داده (charset)',
                    strpos($cs, 'utf8mb4') === 0 ? 'ok' : 'warn',
                    (string)($row['cs'] ?? '?') . ' / ' . (string)($row['cl'] ?? '?'),
                    strpos($cs, 'utf8mb4') === 0 ? '' : 'برای ذخیره‌ی درست متن‌های فارسی و ایموجی، utf8mb4 توصیه می‌شود.'
                );
            } catch (Throwable $e) {
                // نادیده گرفته می‌شود
            }

            // جداول
            $coreTables = ['users' => 'کاربران', 'ads' => 'آگهی‌ها', 'images' => 'تصاویر', 'settings' => 'تنظیمات'];

            try {
                $existing = [];
                foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
                    $existing[strtolower((string)$t)] = true;
                }

                foreach ($coreTables as $table => $label) {
                    $has = isset($existing[$table]);
                    $count = $has ? office_dg_count($pdo, $table) : null;
                    $checks[] = office_dg_item(
                        $g,
                        'جدول «' . $label . '» (' . $table . ')',
                        $has ? 'ok' : 'fail',
                        $has ? ('موجود — ' . number_format((int)$count) . ' ردیف') : 'موجود نیست',
                        $has ? '' : 'این جدول برای کارکرد سایت حیاتی است.'
                    );
                }

                $importantTables = [
                    'property_requests'  => 'درخواست‌های ملک',
                    'request_matches'    => 'تطبیق درخواست‌ها',
                    'support_tickets'    => 'تیکت‌های پشتیبانی',
                    'support_messages'   => 'پیام‌های پشتیبانی',
                    'promotions'         => 'تبلیغات',
                    'notifications'      => 'اعلان‌ها',
                    'favorites'          => 'علاقه‌مندی‌ها',
                    'login_events'       => 'رویدادهای ورود',
                    'login_tokens'       => 'توکن‌های ورود',
                    'amenities'          => 'امکانات ملک',
                    'ad_amenities'       => 'امکانات آگهی',
                    'admins'             => 'مدیران',
                ];
                $missingImportant = [];
                foreach ($importantTables as $table => $label) {
                    if (!isset($existing[$table])) {
                        $missingImportant[] = $label . ' (' . $table . ')';
                    }
                }
                $checks[] = office_dg_item(
                    $g,
                    'جداولِ بخش‌های دیگر',
                    $missingImportant ? 'warn' : 'ok',
                    $missingImportant ? ('ناقص: ' . implode('، ', $missingImportant)) : 'همه موجود هستند',
                    $missingImportant ? 'این جدول‌ها معمولاً هنگام استفاده از هر بخش ساخته می‌شوند؛ اگر آن بخش کار نمی‌کند، اینجا را بررسی کنید.' : ''
                );
            } catch (Throwable $e) {
                $checks[] = office_dg_item($g, 'فهرست جدول‌ها', 'fail', 'دریافت نشد: ' . $e->getMessage());
            }

            // ستون‌های حساس
            $userCols = office_dg_columns($pdo, 'users');
            if ($userCols) {
                $needed = ['telegram_id' => 'آیدی تلگرام', 'bale_id' => 'آیدی بله', 'phone' => 'شماره تماس', 'name' => 'نام'];
                $missing = [];
                foreach ($needed as $col => $label) {
                    if (!isset($userCols[$col])) {
                        $missing[] = $label . ' (' . $col . ')';
                    }
                }
                $checks[] = office_dg_item(
                    $g,
                    'ستون‌های جدول کاربران',
                    $missing ? 'fail' : 'ok',
                    $missing ? ('ناقص: ' . implode('، ', $missing)) : 'کامل است',
                    $missing ? 'بدون این ستون‌ها ورود و ذخیره‌ی پروفایل کاربر انجام نمی‌شود.' : ''
                );

                $uniqTg = office_dg_has_unique_on($pdo, 'users', ['telegram_id']);
                $checks[] = office_dg_item(
                    $g,
                    'ایندکس یکتا روی telegram_id',
                    $uniqTg ? 'ok' : 'fail',
                    $uniqTg ? 'وجود دارد' : 'وجود ندارد',
                    $uniqTg ? '' : 'بدون این ایندکس، ذخیره‌ی کاربر به‌جای به‌روزرسانی، ردیف تکراری می‌سازد و ورود خراب می‌شود.'
                );
            }

            $settingsIdx = office_dg_indexes($pdo, 'settings');
            $settingsUniq = false;
            foreach ($settingsIdx as $idx) {
                if (empty($idx['unique'])) {
                    continue;
                }
                $have = array_map('strtolower', $idx['columns'] ?? []);
                sort($have);
                if ($have === ['setting_group', 'setting_key']) {
                    $settingsUniq = true;
                }
            }
            $checks[] = office_dg_item(
                $g,
                'ایندکس یکتا روی تنظیمات (group, key)',
                $settingsUniq ? 'ok' : 'fail',
                $settingsUniq ? 'وجود دارد' : 'وجود ندارد',
                $settingsUniq ? '' : 'بدون آن، ذخیره‌ی توکن ربات ردیف تکراری می‌سازد و تنظیمات پاک می‌شود.'
            );

            $adCols = office_dg_columns($pdo, 'ads');
            if ($adCols) {
                $missing = [];
                foreach (['telegram_message_id' => 'شناسه پیام تلگرام', 'bale_message_id' => 'شناسه پیام بله'] as $col => $label) {
                    if (!isset($adCols[$col])) {
                        $missing[] = $label;
                    }
                }
                $checks[] = office_dg_item(
                    $g,
                    'ستون‌های انتشار در پیام‌رسان‌ها',
                    $missing ? 'warn' : 'ok',
                    $missing ? ('ناقص: ' . implode('، ', $missing)) : 'کامل است',
                    $missing ? 'این ستون‌ها هنگام اولین انتشار ساخته می‌شوند؛ اگر انتشار انجام نمی‌شود آن را بررسی کنید.' : ''
                );
            }
        }

        /* ---------------- ۳. فایل‌های پروژه ---------------- */
        $g = '۳. فایل‌های پروژه';

        $criticalFiles = [
            'config.php'           => 'تنظیمات اصلی',
            'config.secrets.php'   => 'رمزها و توکن‌ها',
            'db_helpers.php'       => 'توابع پایگاه داده',
            'db-settings.php'      => 'ذخیره‌ی تنظیمات',
            'bot-settings.php'     => 'تنظیمات ربات',
            'telegram.php'         => 'ارتباط با تلگرام',
            'auth.php'             => 'احراز هویت',
            'admin-guard.php'      => 'محافظ پنل ادمین',
            'admin-panel.php'      => 'پنل ادمین',
            'header.php'           => 'هدر مشترک',
            'index.php'            => 'صفحه اصلی',
            'login.php'            => 'صفحه ورود',
            'style.css'            => 'شیوه‌نامه اصلی',
            'design-pro.css'       => 'شیوه‌نامه لوکس',
        ];
        $missingFiles = [];
        foreach ($criticalFiles as $file => $label) {
            if (!is_file($root . '/' . $file)) {
                $missingFiles[] = $label . ' (' . $file . ')';
            }
        }
        $checks[] = office_dg_item(
            $g,
            'فایل‌های حیاتی',
            $missingFiles ? 'fail' : 'ok',
            $missingFiles ? ('مفقود: ' . implode('، ', $missingFiles)) : 'همه‌ی ' . count($criticalFiles) . ' فایل موجود هستند',
            $missingFiles ? 'فایل‌های مفقود را دوباره از زیپ پروژه آپلود کنید.' : ''
        );

        // بررسی نسخه: آیا اصلاحاتِ مهم آپلود شده‌اند؟
        $versionMarkers = [
            ['db-settings.php', 'melkinoEnsureSettingsTable', 'fail', 'رفع اشکالِ «پاک شدن توکن ربات» هنوز آپلود نشده است.', 'فایل db-settings.php را از زیپ جدید آپلود کنید.'],
            ['bot-settings.php', 'melkinoProxy', 'warn', 'پشتیبانی از پروکسی هنوز آپلود نشده است.', 'اگر هاست به api.telegram.org دسترسی ندارد، این فایل را آپلود کنید.'],
            ['db_helpers.php', 'melkinoProfilePrefill', 'warn', 'پر کردن خودکار فرم‌ها هنوز آپلود نشده است.', 'فایل db_helpers.php را آپلود کنید.'],
            ['header.php', 'MELKINO_PROFILE', 'warn', 'اطلاعات پروفایل در صفحات در دسترس نیست.', 'فایل header.php را آپلود کنید.'],
            ['telegram.php', 'CURLOPT_PROXY', 'warn', 'پشتیبانی از پروکسی در ارسال درخواست‌ها فعال نیست.', 'فایل telegram.php را آپلود کنید.'],
        ];
        foreach ($versionMarkers as [$file, $marker, $severity, $msg, $hint]) {
            $path = $root . '/' . $file;
            $has = is_file($path) && strpos((string)@file_get_contents($path), $marker) !== false;
            $checks[] = office_dg_item(
                $g,
                'نسخه‌ی ' . $file,
                $has ? 'ok' : $severity,
                $has ? 'به‌روز است' : $msg,
                $has ? '' : $hint
            );
        }

        // نشانگرهای جاوااسکریپت
        $jsMarkers = [
            ['admin-ads.js', 'publishToBale', 'دکمه‌ی انتشار در بله'],
            ['admin-panel.php', 'tab-diagnostics', 'اتصال تب عیب‌یاب به پنل'],
        ];
        foreach ($jsMarkers as [$file, $marker, $label]) {
            $path = $root . '/' . $file;
            $has = is_file($path) && strpos((string)@file_get_contents($path), $marker) !== false;
            $checks[] = office_dg_item(
                $g,
                $label . ' (' . $file . ')',
                $has ? 'ok' : 'warn',
                $has ? 'موجود' : 'یافت نشد',
                $has ? '' : 'این فایل را از زیپ جدید آپلود کنید.'
            );
        }

        // اسکریپتِ مسدودکننده (عامل صفحه‌ی سفید)
        $headerPath = $root . '/header.php';
        $headerContent = is_file($headerPath) ? (string)@file_get_contents($headerPath) : '';
        $blocking = (strpos($headerContent, 'src="https://telegram.org/js/telegram-web-app.js"') !== false)
            || (strpos($headerContent, 'src="https://tapi.bale.ai/miniapp.js') !== false);
        $checks[] = office_dg_item(
            $g,
            'لودِ اسکریپت‌های مینی‌اپ',
            $blocking ? 'fail' : 'ok',
            $blocking ? 'اسکریپت‌ها به‌صورت مسدودکننده لود می‌شوند (عامل صفحه‌ی سفید)' : 'به‌صورت غیرِ مسدودکننده لود می‌شوند',
            $blocking ? 'نسخه‌ی جدید header.php را آپلود کنید؛ در غیر این صورت در شبکه‌هایی که telegram.org در دسترس نیست صفحه سفید می‌ماند.' : ''
        );

        // پوشه‌ها و مجوزها
        $uploadsDir = $root . '/uploads';
        if (!is_dir($uploadsDir)) {
            $checks[] = office_dg_item($g, 'پوشه‌ی uploads', 'fail', 'وجود ندارد', 'این پوشه را بسازید و مجوز نوشتن (۷۵۵ یا ۷۷۵) به آن بدهید.');
        } else {
            $writable = is_writable($uploadsDir);
            $checks[] = office_dg_item(
                $g,
                'دسترسی نوشتن در uploads',
                $writable ? 'ok' : 'fail',
                $writable ? 'قابل نوشتن' : 'غیرقابل نوشتن',
                $writable ? '' : 'آپلود تصویر انجام نمی‌شود. مجوز پوشه را روی ۷۵۵ یا ۷۷۵ تنظیم کنید.'
            );
        }

        foreach (['settings', 'backups'] as $dir) {
            $path = $root . '/' . $dir;
            if (is_dir($path)) {
                $writable = is_writable($path);
                $checks[] = office_dg_item(
                    $g,
                    'دسترسی نوشتن در پوشه‌ی ' . $dir,
                    $writable ? 'ok' : 'warn',
                    $writable ? 'قابل نوشتن' : 'غیرقابل نوشتن',
                    $writable ? '' : 'ذخیره‌ی تنظیمات یا پشتیبان‌گیری ممکن است ناموفق شود.'
                );
            }
        }

        /* ---------------- ۴. ربات و کانال ---------------- */
        $g = '۴. ربات و کانال';

        $tgToken = function_exists('melkinoTelegramToken') ? (string)melkinoTelegramToken() : '';
        $tgTokenOk = ($tgToken !== '' && $tgToken !== 'توکن_ربات_تلگرام');
        $checks[] = office_dg_item(
            $g,
            'توکن ربات تلگرام',
            $tgTokenOk ? 'ok' : 'fail',
            $tgTokenOk ? ('تنظیم شده (' . substr($tgToken, 0, 6) . '••••' . substr($tgToken, -4) . ')') : 'تنظیم نشده',
            $tgTokenOk ? '' : 'به تب «ربات و کانال» بروید، توکن را وارد و ذخیره کنید. بدون آن ورود کاربران و انتشار آگهی کار نمی‌کند.'
        );

        if ($tgTokenOk) {
            $r = office_dg_api_call('https://api.telegram.org/bot' . $tgToken . '/getMe');
            $checks[] = office_dg_item(
                $g,
                'اتصال زنده به تلگرام (getMe)',
                $r['ok'] ? 'ok' : 'warn',
                $r['ok'] ? ('ربات: @' . (string)($r['result']['username'] ?? '')) : ('' . $r['description']),
                $r['ok'] ? '' : ('سرورِ هاست نتوانست به api.telegram.org وصل شود. بعضی هاست‌ها (مانند InfinityFree) ارتباط خروجی با تلگرام را مسدود کرده‌اند؛ در این صورت این هشدار طبیعی است. بررسیِ «ارتباط مستقیم از مرورگر» در همین گروه تعیین‌کننده است: اگر آن سبز باشد، توکن شما سالم است و انتشار آگهی از مرورگرِ ادمین انجام می‌شود. در غیر این صورت توکن را بررسی کنید یا در تب «ربات و کانال» یک پروکسی ثبت کنید.')
            );
        }

        $tgChannel = function_exists('melkinoChannelId') ? (string)melkinoChannelId() : '';
        $checks[] = office_dg_item(
            $g,
            'شناسه کانال تلگرام',
            $tgChannel !== '' ? 'ok' : 'warn',
            $tgChannel !== '' ? $tgChannel : 'تنظیم نشده',
            $tgChannel !== '' ? '' : 'برای انتشار آگهی در کانال، شناسه را در تب «ربات و کانال» وارد کنید.'
        );

        if ($tgTokenOk && $tgChannel !== '' && $tgChannel !== '@آیدی_کانال') {
            $r = office_dg_api_call('https://api.telegram.org/bot' . $tgToken . '/getChat?chat_id=' . urlencode($tgChannel));
            $tgChatFail = !$r['ok'];
            $tgFiltered = $tgChatFail && (strpos((string)($r['description'] ?? ''), 'فیلتر') !== false || strpos((string)($r['description'] ?? ''), 'ارتباط برقرار نشد') !== false);
            $checks[] = office_dg_item(
                $g,
                'دسترسی به کانال تلگرام',
                $r['ok'] ? 'ok' : ($tgFiltered ? 'warn' : 'fail'),
                $r['ok'] ? ('' . (string)($r['result']['title'] ?? $tgChannel)) : ('' . $r['description']),
                $r['ok'] ? '' : ($tgFiltered
                    ? 'هاست به api.telegram.org وصل نشد (در InfinityFree طبیعی است). اگر «ارتباط مستقیم از مرورگر» سبز باشد، انتشار از پنل کار می‌کند.'
                    : 'ربات را در کانال به‌عنوان ادمین اضافه کنید و شناسه را درست وارد کنید.')
            );
        }

        $baleToken = function_exists('melkinoBaleToken') ? (string)melkinoBaleToken() : '';
        $baleTokenOk = ($baleToken !== '' && $baleToken !== 'توکن_ربات_بله');
        $checks[] = office_dg_item(
            $g,
            'توکن ربات بله',
            $baleTokenOk ? 'ok' : 'warn',
            $baleTokenOk ? ('تنظیم شده (' . substr($baleToken, 0, 6) . '••••' . substr($baleToken, -4) . ')') : 'تنظیم نشده',
            $baleTokenOk ? '' : 'برای ورود کاربران از بله و انتشار در کانال بله لازم است.'
        );

        if ($baleTokenOk) {
            $r = office_dg_api_call('https://tapi.bale.ai/bot' . $baleToken . '/getMe');
            $checks[] = office_dg_item(
                $g,
                'اتصال زنده به بله (getMe)',
                $r['ok'] ? 'ok' : 'warn',
                $r['ok'] ? ('ربات: @' . (string)($r['result']['username'] ?? '')) : ('' . $r['description']),
                $r['ok'] ? '' : ('سرورِ هاست نتوانست به tapi.bale.ai وصل شود. این می‌تواند به‌دلیل محدودیتِ خروجیِ هاست یا فیلترینگ باشد. بررسیِ «ارتباط مستقیم از مرورگر» در همین گروه تعیین‌کننده است؛ اگر آن سبز باشد توکن سالم است و انتشار از مرورگرِ ادمین انجام می‌شود.')
            );
        }

        $baleChannel = function_exists('melkinoBotSetting') ? melkinoBotSetting('bale_channel') : '';
        if ($baleChannel !== '') {
            $r = office_dg_api_call('https://tapi.bale.ai/bot' . $baleToken . '/getChat?chat_id=' . urlencode($baleChannel));
            $checks[] = office_dg_item(
                $g,
                'دسترسی به کانال بله',
                $r['ok'] ? 'ok' : 'warn',
                $r['ok'] ? ('' . (string)($r['result']['title'] ?? $baleChannel)) : ('' . $r['description']),
                $r['ok'] ? '' : 'ربات را در کانال بله ادمین کنید و شناسه را بررسی کنید.'
            );
        }

        $proxy = function_exists('melkinoProxy') ? (string)melkinoProxy() : '';
        $checks[] = office_dg_item(
            $g,
            'پروکسی ارتباط',
            'ok',
            $proxy !== '' ? ('تنظیم شده: ' . $proxy) : 'تنظیم نشده (ارتباط مستقیم)',
            'اگر تست اتصال تلگرام ناموفق است و هاست در ایران قرار دارد، یک پروکسی ثبت کنید.'
        );

        /* ---------------- ۵. احراز هویت و کاربران ---------------- */
        $g = '۵. احراز هویت و کاربران';

        $settingsOk = false;
        if ($pdo) {
            try {
                $pdo->query('SELECT 1 FROM settings LIMIT 1');
                $settingsOk = true;
            } catch (Throwable $e) {
                $settingsOk = false;
            }
        }
        $checks[] = office_dg_item(
            $g,
            'ذخیره‌سازی تنظیمات (جدول settings)',
            $settingsOk ? 'ok' : 'fail',
            $settingsOk ? 'سالم — توکن‌ها و تنظیمات ذخیره می‌شوند' : 'کار نمی‌کند',
            $settingsOk ? '' : 'این مهم‌ترین بخش است: بدون آن توکن ربات بعد از هر بار خروج از پنل پاک می‌شود.'
        );

        if ($pdo) {
            $users = office_dg_count($pdo, 'users');
            $checks[] = office_dg_item(
                $g,
                'تعداد کاربران',
                'ok',
                $users === null ? 'نامشخص' : (number_format($users) . ' کاربر'),
                ''
            );

            try {
                $tgUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL AND telegram_id <> ''")->fetchColumn();
                $baleUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE bale_id IS NOT NULL AND bale_id <> ''")->fetchColumn();
                $checks[] = office_dg_item(
                    $g,
                    'کاربران بر پایه‌ی پلتفرم',
                    ($tgUsers + $baleUsers) > 0 ? 'ok' : 'warn',
                    'تلگرام: ' . number_format($tgUsers) . ' — بله: ' . number_format($baleUsers),
                    ($tgUsers + $baleUsers) > 0 ? '' : 'هیچ کاربری از طریق مینی‌اپ وارد نشده است. توکن، آدرس مینی‌اپ و تنظیمات ربات را بررسی کنید.'
                );
            } catch (Throwable $e) {
                // نادیده گرفته می‌شود
            }

            try {
                $last = $pdo->query("SELECT MAX(last_login) FROM users")->fetchColumn();
                if ($last) {
                    $days = (int)floor((time() - strtotime((string)$last)) / 86400);
                    $checks[] = office_dg_item(
                        $g,
                        'آخرین ورود کاربر',
                        $days <= 7 ? 'ok' : 'warn',
                        (string)$last . ' (' . $days . ' روز پیش)',
                        $days <= 7 ? '' : 'مدتی است کسی وارد نشده است؛ اگر انتظار دارید کاربران فعال باشند، بخش ربات و مینی‌اپ را بررسی کنید.'
                    );
                }
            } catch (Throwable $e) {
                // نادیده گرفته می‌شود
            }

            try {
                $logins = office_dg_count($pdo, 'login_events');
                if ($logins !== null) {
                    $checks[] = office_dg_item($g, 'رویدادهای ورود ثبت‌شده', 'ok', number_format($logins) . ' مورد');
                }
            } catch (Throwable $e) {
                // نادیده گرفته می‌شود
            }
        }

        /* ---------------- ۶. محتوا ---------------- */
        $g = '۶. محتوای سایت';

        if ($pdo) {
            $ads = office_dg_count($pdo, 'ads');
            if ($ads !== null) {
                $checks[] = office_dg_item($g, 'تعداد آگهی‌ها', 'ok', number_format($ads) . ' آگهی');
            }

            $imgs = office_dg_count($pdo, 'images');
            if ($imgs !== null) {
                $checks[] = office_dg_item($g, 'تعداد تصاویر ثبت‌شده', 'ok', number_format($imgs) . ' تصویر');
            }

            $reqs = office_dg_count($pdo, 'property_requests');
            if ($reqs !== null) {
                $checks[] = office_dg_item($g, 'تعداد درخواست‌ها', 'ok', number_format($reqs) . ' درخواست');
            }

            // تصاویر یتیم
            try {
                $used = [];
                foreach ($pdo->query("SELECT filename FROM images")->fetchAll(PDO::FETCH_COLUMN) as $f) {
                    $used[basename((string)$f)] = true;
                }
                $orphan = 0;
                $scanned = 0;
                if (is_dir($root . '/uploads')) {
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/uploads', FilesystemIterator::SKIP_DOTS));
                    foreach ($it as $file) {
                        if (!$file->isFile()) {
                            continue;
                        }
                        $scanned++;
                        if ($scanned > 2000) {
                            break;
                        }
                        if (!isset($used[$file->getFilename()])) {
                            $orphan++;
                        }
                    }
                }
                $checks[] = office_dg_item(
                    $g,
                    'تصاویر بدون استفاده (یتیم)',
                    $orphan > 50 ? 'warn' : 'ok',
                    $orphan . ' فایل از ' . $scanned . ' فایل بررسی‌شده',
                    $orphan > 50 ? 'از تب «تصاویر» می‌توانید این فایل‌ها را پاک کنید تا فضای هاست آزاد شود.' : ''
                );
            } catch (Throwable $e) {
                // نادیده گرفته می‌شود
            }
        }

        /* ---------------- ۷. امنیت ---------------- */
        $g = '۷. امنیت';

        $htaccess = $root . '/.htaccess';
        $hasHtaccess = is_file($htaccess);
        $htTxt = $hasHtaccess ? (string) @file_get_contents($htaccess) : '';
        $protects = $hasHtaccess && (strpos($htTxt, 'config.secrets') !== false || strpos($htTxt, 'config\\.secrets') !== false);
        $checks[] = office_dg_item(
            $g,
            'محافظت از فایل‌های حساس (.htaccess)',
            $protects ? 'ok' : 'warn',
            $protects ? 'قانون مسدودسازی وجود دارد' : ($hasHtaccess ? 'فایل هست ولی قانون مسدودسازی config.secrets.php در آن نیست' : 'فایل .htaccess وجود ندارد'),
            $protects ? '' : 'فایل .htaccess را از زیپ پروژه آپلود کنید تا دسترسی مستقیم به فایل‌های حساس بسته شود.'
        );

        $checks[] = office_dg_item(
            $g,
            'پروتکل امن (تکرار برای اطمینان)',
            $isHttps ? 'ok' : 'fail',
            $isHttps ? 'HTTPS فعال است' : 'HTTPS فعال نیست',
            $isHttps ? '' : 'بدون HTTPS مینی‌اپ تلگرام و بله کار نمی‌کند.'
        );

        try {
            $maintenance = $pdo ? (bool)dbSettingGet($pdo, 'global', 'maintenance_mode', false) : false;
            $checks[] = office_dg_item(
                $g,
                'حالت تعمیرات',
                $maintenance ? 'warn' : 'ok',
                $maintenance ? 'فعال — سایت برای کاربران بسته است' : 'غیرفعال',
                $maintenance ? 'اگر نمی‌خواهید سایت بسته باشد، از تب «تنظیمات عمومی» آن را خاموش کنید.' : ''
            );
        } catch (Throwable $e) {
            // نادیده گرفته می‌شود
        }

        $legacyPass = defined('MELKINO_LEGACY_ADMIN_PASSWORD');
        $checks[] = office_dg_item(
            $g,
            'رمز پیش‌فرض ادمین',
            $legacyPass ? 'warn' : 'ok',
            $legacyPass ? 'یک رمز پشتیبان در config.secrets.php تعریف شده است' : 'تعریف نشده',
            $legacyPass ? 'حتماً رمز عبور ادمین را به یک رمز قوی تغییر دهید.' : ''
        );

        /* =====================================================
           ۸. سلامت قابلیت‌های اضافه‌شده (راندهای ۶ تا ۱۶)
           ===================================================== */
        $g = '۸. سلامت قابلیت‌های جدید';

        // ۸-۱) نسخه‌های آپلودشده: نشانگرهای کدِ راندهای اخیر
        $roundMarkers = [
            ['compare-page.php', 'cmp-card', 'fail', 'صفحهٔ مستقل مقایسه آپلود نشده (خطای ۴۰۴ کاربران).', 'compare-page.php را از زیپ جدید آپلود کنید.'],
            ['compare-widget.js', 'bottom-nav-height', 'warn', 'نسخهٔ جدید ویجت مقایسه (حباب بالای نوار پایین) آپلود نشده.', 'compare-widget.js را آپلود کنید.'],
            ['profile.php', 'editProfilePhone', 'warn', 'فیلد قابل‌ویرایش شماره موبایل در پروفایل آپلود نشده.', 'profile.php را آپلود کنید.'],
            ['login.php', 'LOGIN_FLAGS', 'warn', 'کنترل روش‌های ورود در صفحهٔ ورود آپلود نشده.', 'login.php را آپلود کنید.'],
            ['admin-notifications.php', 'notif_delete_bulk', 'warn', 'حذف گروهی اعلان‌ها آپلود نشده.', 'admin-notifications.php را آپلود کنید.'],
            ['admin-new-tabs.js', 'deleteSelectedNotifs', 'warn', 'نوار ابزار حذف اعلان‌ها در پنل آپلود نشده.', 'admin-new-tabs.js را آپلود کنید.'],
            ['bot-settings.php', 'ad_link', 'warn', 'فیلد «لینک آگهی» در محتوای انتشار آپلود نشده.', 'bot-settings.php را آپلود کنید.'],
            ['.htaccess', 'mod_deflate', 'warn', 'فشرده‌سازی gzip در .htaccess فعال نیست؛ صفحه‌ها (به‌ویژه پنل) کندتر بارگذاری می‌شوند.', '.htaccess جدید را آپلود کنید.'],
            // راند ۱۲: وام + انتشار به تفکیک نوع ملک/معامله
            ['db_helpers.php', 'melkinoLoanInfo', 'fail', 'تابع محاسبهٔ وام وجود ندارد؛ قیمت دوم (نقد + وام) و پیام انتشارِ وام کار نمی‌کنند.', 'db_helpers.php را از زیپ جدید آپلود کنید.'],
            ['register-apartment.php', 'loanSection', 'warn', 'فرم ثبت ملک، بخش وام را ندارد.', 'register-apartment.php را آپلود کنید.'],
            ['bot-settings.php', 'melkinoPublishMatrix', 'warn', 'تنظیم محتوای انتشار به تفکیک «نوع ملک × نوع معامله» آپلود نشده.', 'bot-settings.php را آپلود کنید.'],
            ['admin-new-tabs.js', 'resetPublishCombo', 'warn', 'رابط انتشار تفکیکی در پنل آپلود نشده.', 'admin-new-tabs.js را آپلود کنید.'],
            ['ad-cards.js', 'price-loan-sub', 'warn', 'نمایش دو قیمت در کارت‌های لیست آپلود نشده.', 'ad-cards.js را آپلود کنید.'],
            // راند ۱۴: گام «نوع سند» + چک‌باکس‌های معاوضه + حباب معاوضه
            ['register-apartment.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت آپارتمان آپلود نشده.', 'register-apartment.php را آپلود کنید.'],
            ['register-villa.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت ویلا آپلود نشده.', 'register-villa.php را آپلود کنید.'],
            ['register-land.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت زمین آپلود نشده.', 'register-land.php را آپلود کنید.'],
            ['register-garden.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت باغ آپلود نشده.', 'register-garden.php را آپلود کنید.'],
            ['register-office.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت اداری آپلود نشده.', 'register-office.php را آپلود کنید.'],
            ['register-commercial.php', 'deed_type', 'warn', 'گام «نوع سند» در فرم ثبت تجاری آپلود نشده.', 'register-commercial.php را آپلود کنید.'],
            ['property-db-helper.php', 'deed_type', 'warn', 'ذخیرهٔ سند/معاوضه در هستهٔ ثبت ملک آپلود نشده.', 'property-db-helper.php را آپلود کنید.'],
            ['field-display.php', 'توضیحات سند', 'warn', 'نمایش سند در صفحهٔ جزئیات آپلود نشده.', 'field-display.php را آپلود کنید.'],
            ['property-details.php', '--pd-bottom-nav-h', 'warn', 'فیکس نوار «تماس با مشاور» (ماندن بالای فوتر با safe-area) آپلود نشده.', 'property-details.php را آپلود کنید.'],
            ['home.php', 'mkRenderAdCards', 'warn', 'رندر مشترک کارت‌های صفحهٔ اصلی آپلود نشده.', 'home.php را آپلود کنید.'],
            ['ad-cards.js', 'CD_SPECIAL', 'warn', 'رندر پویای کارت‌های لیست آپلود نشده.', 'ad-cards.js را آپلود کنید.'],
            // راند ۱۵: رفع پیازِ escape (صفحهٔ همهٔ آگهی‌ها)
            ['db_helpers.php', 'melkinoPeelJsonValue', 'fail', 'تابع بازکردن escape تودرتو وجود ندارد؛ با یک ذخیرهٔ ادمین، صفحهٔ «همهٔ آگهی‌ها» دوباره می‌شکند.', 'db_helpers.php را از زیپ جدید آپلود کنید.'],
            ['properties-data.php', 'melkinoNormalizeJsonColumn', 'fail', 'پاک‌سازی خروجی آگهی‌ها آپلود نشده؛ صفحهٔ «همهٔ آگهی‌ها» با رکورد آلوده می‌شکند.', 'properties-data.php را آپلود کنید.'],
            ['admin-panel.php', 'melkinoNormalizeJsonColumn', 'fail', 'ذخیرهٔ تمیز پنل ادمین آپلود نشده؛ هر ذخیره یک لایه escape اضافه می‌کند.', 'admin-panel.php را آپلود کنید.'],
            // راند ۱۶: فیلدهای انتشار به تفکیک نوع ملک + سند/معاوضه در پیام کانال
            ['bot-settings.php', 'publish_defs_v3', 'warn', 'فیلدهای انتشارِ نوع‌محور (شامل فیلدهای اختصاصی فرم هر نوع ملک) آپلود نشده — با تغییر نوع ملک، فیلدهای مرتبط لود نمی‌شوند.', 'bot-settings.php را آپلود کنید.'],
            ['admin-bots.php', 'melkinoPublishFieldDefs($ptype', 'warn', 'گیرندهٔ فیلدهای نوع‌محور در پنل آپلود نشده.', 'admin-bots.php را آپلود کنید.'],
            ['db_helpers.php', "on('deed')", 'warn', 'خطوط سند/معاوضه در پیام کانال آپلود نشده.', 'db_helpers.php را آپلود کنید.'],
            // راند ۱۷: ذخیرهٔ فیلدهای جاماندهٔ فرم‌ها + pd.* در پیام کانال
            ['register-apartment.php', "'total_units' =>", 'warn', '«تعداد کل واحدها» در فرم آپارتمان ذخیره نمی‌شود (نسخهٔ قدیمی آپلود شده).', 'register-apartment.php را آپلود کنید.'],
            ['register-commercial.php', 'location_features', 'warn', 'فیلدهای موقعیت/مشاغل در فرم تجاری ذخیره نمی‌شوند (نسخهٔ قدیمی آپلود شده).', 'register-commercial.php را آپلود کنید.'],
            ['db_helpers.php', "'pd.'", 'warn', 'فیلدهای اختصاصی هر نوع ملک در پیام کانال پشتیبانی نمی‌شود.', 'db_helpers.php را آپلود کنید.'],
            ['field-display.php', 'ویژگی موقعیت', 'warn', 'کاتالوگ فیلدهای تجاری/زمین/باغ (منتقل‌شده به field-display.php) کامل نیست.', 'field-display.php را آپلود کنید.'],
            // راند ۱۸: فیلدهای رهن/اجاره + فیلتر نوع معامله + لاگ/نشانگر انتشار + حذف کارت تماس
            ['bot-settings.php', 'publish_defs_v4', 'warn', 'فیلدهای رهن/اجاره و فیلتر نوع معامله در تنظیمات انتشار آپلود نشده.', 'bot-settings.php را آپلود کنید.'],
            ['bot-settings.php', 'publish_defs_v5', 'warn', 'فیلدهای «قیمت هر متر» و «پیش‌پرداخت پیش‌فروش» در تنظیمات انتشار آپلود نشده.', 'bot-settings.php را آپلود کنید.'],
            ['db_helpers.php', 'melkinoPriceToNum', 'warn', 'نرمال‌سازی مبالغ و قیمت هر متر در متن انتشار آپلود نشده (باگ «۰ تومان» پیش‌فروش).', 'db_helpers.php را آپلود کنید.'],
            ['compare-engine.php', 'melkinoCompareEngine', 'warn', 'موتور جدید مقایسهٔ ملک‌ها (راند ۲۴) آپلود نشده.', 'compare-engine.php را آپلود کنید.'],
            ['compare-widget.js', 'melkinoCompareToggle', 'warn', 'ویجت جدید مقایسه (toggle دکمه + مسدودسازی مهمان) آپلود نشده.', 'compare-widget.js را آپلود کنید.'],
            ['db_helpers.php', 'rent_monthly', 'warn', 'خطوط جداگانهٔ رهن/اجاره در پیام کانال آپلود نشده.', 'db_helpers.php را آپلود کنید.'],
            ['admin-ads.js', 'showPublishLogs', 'warn', 'نشانگر و سابقهٔ انتشار در پنل آگهی‌ها آپلود نشده.', 'admin-ads.js را آپلود کنید.'],
            ['telegram-relay.php', 'melkinoLogChannelPublish', 'warn', 'لاگ انتشار (مسیر مرورگر ادمین) آپلود نشده.', 'telegram-relay.php را آپلود کنید.'],
            ['publish-to-telegram.php', 'melkinoLogChannelPublish', 'warn', 'لاگ انتشار (مسیر سرور) آپلود نشده.', 'publish-to-telegram.php را آپلود کنید.'],
            ['admin-publish-logs.php', 'channel_publish_logs', 'warn', 'endpoint سابقهٔ انتشار آپلود نشده — دکمهٔ «سابقهٔ انتشار» در پنل آگهی‌ها کار نمی‌کند.', 'admin-publish-logs.php را آپلود کنید.'],
            ['register-apartment.php', 'راند ۱۸', 'warn', 'کارت «تکمیل اطلاعات تماس» هنوز از فرم‌های ثبت حذف نشده.', 'فرم‌های register-*.php را آپلود کنید.'],
            // راند ۱۹: سند/معاوضه در مودال ویرایش + bulk_sync + صفحهٔ جزئیات
            ['admin-ads.js', 'editAdDeedType', 'warn', 'فیلدهای سند/معاوضه در مودال ویرایش آگهی آپلود نشده.', 'admin-ads.js را آپلود کنید.'],
            ['admin-panel.php', 'deed_type=?', 'warn', 'ذخیرهٔ سند/معاوضه در bulk_sync پنل ادمین آپلود نشده — ویرایش‌ها ذخیره نمی‌شوند.', 'admin-panel.php را آپلود کنید.'],
            ['property-details.php', 'مایل به معاوضه', 'warn', 'نمایش معاوضه در صفحهٔ جزئیات آپلود نشده.', 'property-details.php را آپلود کنید.'],
            // راند ۲۰: مدیریت رویدادهای اعلان + تب نمایش کارت‌ها
            ['notification-events.php', 'melkinoNotificationEventDefs', 'warn', 'رجیستری رویدادهای اعلان آپلود نشده — مدیریت اعلان‌ها کار نمی‌کند.', 'notification-events.php را آپلود کنید.'],
            ['db_helpers.php', 'melkinoNotificationEventEnabled', 'warn', 'گیت فعال/غیرفعال اعلان‌ها در هسته آپلود نشده.', 'db_helpers.php را آپلود کنید.'],
            ['admin-notifications.php', 'events_get', 'warn', 'بخش «رویدادهای اعلان سیستمی» در تب اعلان‌ها آپلود نشده.', 'admin-notifications.php را آپلود کنید.'],
            ['property-request.php', '__matchNotifOn', 'warn', 'گیت اعلان «فایل مناسب» آپلود نشده.', 'property-request.php را آپلود کنید.'],
            ['card-display.php', 'melkinoCardDisplayDefs', 'warn', 'هستهٔ تنظیمات نمایش کارت‌ها آپلود نشده.', 'card-display.php را آپلود کنید.'],
            ['admin-card-display.php', 'melkinoSaveCardDisplay', 'warn', 'endpoint تب «نمایش» آپلود نشده.', 'admin-card-display.php را آپلود کنید.'],
            ['admin-panel.php', 'melkinoInitDisplayTab', 'warn', 'تب «نمایش» در پنل ادمین آپلود نشده.', 'admin-panel.php را آپلود کنید.'],
            ['admin-new-tabs.js', 'melkinoInitDisplayTab', 'warn', 'اسکریپت تب «نمایش» آپلود نشده.', 'admin-new-tabs.js را آپلود کنید.'],
            ['ad-cards-bootstrap.php', 'MELKINO_CARD_DISPLAY', 'warn', 'کارت‌های صفحهٔ اصلی از تنظیمات نمایش پیروی نمی‌کنند.', 'ad-cards-bootstrap.php را آپلود کنید.'],
            ['field-display.php', 'melkinoFdHomeRender', 'fail', 'فایل سیستم مدیریت فیلدها (field-display.php) آپلود نشده؛ صفحهٔ اصلی و جزئیات با خطای بحرانی مواجه می‌شوند.', 'field-display.php را از زیپ آپلود کنید.'],
            ['admin-field-display.php', 'melkinoFdSave', 'warn', 'endpoint مدیریت فیلدهای نمایشی آپلود نشده؛ تب «نمایش» پنل کار نمی‌کند.', 'admin-field-display.php را آپلود کنید.'],
            ['admin-field-display.js', 'melkinoInitFieldDisplay', 'warn', 'رابط مدیریت فیلدها در پنل آپلود نشده.', 'admin-field-display.js را آپلود کنید.'],
            ['property-details.php', 'melkinoFdSpecLabelOrder', 'warn', 'مدیریت بخش‌ها/مشخصات صفحهٔ جزئیات (راند ۲۹) آپلود نشده.', 'property-details.php را آپلود کنید.'],
            ['ad-cards.js', 'MELKINO_CARD_DISPLAY', 'warn', 'کارت‌های فهرست آگهی‌ها از تنظیمات نمایش پیروی نمی‌کنند.', 'ad-cards.js را آپلود کنید.'],
            // راند ۳۰: احراز هویت مینی‌اپ تلگرام + شمارهٔ تماس قفل‌شونده
            ['auth.php', 'melkinoSyncTelegramIdentity', 'fail', 'تابع همگام‌سازی هویت تلگرام (راند ۳۰) آپلود نشده؛ فیلدهای telegram_* پر نمی‌شوند.', 'auth.php را از زیپ جدید آپلود کنید.'],
            ['auth.php', 'phone_locked', 'fail', 'ستون‌های قفل شماره در فهرست مهاجرت کاربران نیست (راند ۳۰).', 'auth.php را آپلود کنید و یک‌بار melkino-migrate.php را اجرا کنید.'],
            ['db_helpers.php', 'phone_verified', 'warn', 'هویت/پیش‌پرکردن فرم‌ها به وضعیت قفل شماره به‌روز نشده (راند ۳۰).', 'db_helpers.php را آپلود کنید.'],
            ['profile-sync.php', 'set_phone', 'fail', 'endpoint ثبت شمارهٔ تماس با تأیید نهایی (راند ۳۰) آپلود نشده؛ ثبت شماره کار نمی‌کند.', 'profile-sync.php را آپلود کنید.'],
            ['identity-sync.php', 'clear_phone', 'fail', 'endpoint حذف شمارهٔ تماس توسط ادمین (راند ۳۰) آپلود نشده.', 'identity-sync.php را آپلود کنید.'],
            ['identity-sync.php', 'admin_phone_audit', 'warn', 'لاگ حسابرسی حذف شمارهٔ تماس (راند ۳۰) فعال نیست.', 'identity-sync.php را آپلود کنید.'],
            ['profile.php', 'phoneRegisterModal', 'warn', 'مودال ثبت شمارهٔ تماس با مرحلهٔ تأیید (راند ۳۰) آپلود نشده.', 'profile.php را آپلود کنید.'],
            ['register-apartment.php', '__verified30', 'fail', 'کنترل شمارهٔ قفل‌شده در فرم ثبت آپارتمان (راند ۳۰) آپلود نشده؛ شمارهٔ دلخواه از فرم پذیرفته می‌شود.', 'register-apartment.php را آپلود کنید.'],
            ['register-villa.php', '__verified30', 'fail', 'کنترل شمارهٔ قفل‌شده در فرم ثبت ویلا (راند ۳۰) آپلود نشده.', 'register-villa.php را آپلود کنید.'],
            ['register-land.php', '__verified30', 'warn', 'کنترل شمارهٔ قفل‌شده در فرم ثبت زمین (راند ۳۰) آپلود نشده.', 'register-land.php را آپلود کنید.'],
            ['register-garden.php', '__verified30', 'warn', 'کنترل شمارهٔ قفل‌شده در فرم ثبت باغ (راند ۳۰) آپلود نشده.', 'register-garden.php را آپلود کنید.'],
            ['register-commercial.php', '__verified30', 'warn', 'کنترل شمارهٔ قفل‌شده در فرم ثبت تجاری (راند ۳۰) آپلود نشده.', 'register-commercial.php را آپلود کنید.'],
            ['register-office.php', '__verified30', 'warn', 'کنترل شمارهٔ قفل‌شده در فرم ثبت دفتر کار (راند ۳۰) آپلود نشده.', 'register-office.php را آپلود کنید.'],
            ['property-request.php', '__verified30', 'fail', 'کنترل شمارهٔ قفل‌شده در فرم درخواست (راند ۳۰) آپلود نشده.', 'property-request.php را آپلود کنید.'],
            ['property-db-helper.php', 'created_by_telegram_id', 'warn', 'ثبت هویت ثبت‌کننده روی رکورد ملک (راند ۳۰) آپلود نشده.', 'property-db-helper.php را آپلود کنید.'],
            ['admin-panel.php', 'clearUserPhone', 'warn', 'دکمهٔ حذف شمارهٔ تماس در تب کاربران (راند ۳۰) آپلود نشده.', 'admin-panel.php را آپلود کنید.'],
            // راند ۳۱: ثبت نام فارسی + قفل نام + یکتایی سراسری شماره
            ['profile-sync.php', 'set_profile', 'fail', 'endpoint تکمیل اطلاعات حساب (نام فارسی + شمارهٔ یکتا، راند ۳۱) آپلود نشده.', 'profile-sync.php را آپلود کنید.'],
            ['auth.php', 'name_locked', 'fail', 'ستون‌های قفل نام (راند ۳۱) در فهرست مهاجرت نیست.', 'auth.php را آپلود کنید و یک‌بار melkino-migrate.php را اجرا کنید.'],
            ['db_helpers.php', 'r31NameGuard', 'warn', 'محافظ بازنویسی نام در ورود تلگرام (راند ۳۱) آپلود نشده؛ نام کاربر با نام تلگرام بازنویسی می‌شود.', 'db_helpers.php را آپلود کنید.'],
            ['profile.php', 'openProfileComplete', 'warn', 'مودال تکمیل نام/شماره (راند ۳۱) آپلود نشده.', 'profile.php را آپلود کنید.'],
            // راند ۳۳: پیش‌نمایش تب نمایش + پشتیبان/تاریخچه/آیکون کامل‌تر
            ['config.php', 'frame-ancestors', 'fail', 'هدر محافظ قاب (frame-ancestors) در config.php نیست؛ پیش‌نمایش زنده در تب نمایش پنل ادمین یا مینی‌اپ بله می‌شکند.', 'config.php را آپلود کنید.'],
            ['admin-backup.php', 'melkinoVerifyBackupArchive', 'warn', 'بررسی سلامت آرشیو و نگهداری خودکار ۱۰ نسخه (راند ۳۳) آپلود نشده.', 'admin-backup.php را آپلود کنید.'],
            ['admin-panel.php', 'راند ۳۳: همهٔ شناسه‌های هر رخداد', 'warn', 'نمایش کامل شناسه‌ها در تاریخچهٔ ورود کاربران (راند ۳۳) آپلود نشده.', 'admin-panel.php را آپلود کنید.'],
            ['admin-new-tabs.js', 'age_days', 'warn', 'هشدار تازگی پشتیبان در فهرست پشتیبان‌ها (راند ۳۳) آپلود نشده.', 'admin-new-tabs.js را آپلود کنید.'],
            ['profile.php', 'طبق درخواست حذف شد', 'warn', 'دکمهٔ «ویرایش نام» هنوز از پروفایل حذف نشده است (راند ۳۳).', 'profile.php را آپلود کنید.'],
            ['onboarding-tour.php', 'melkinoProductTourDefaults', 'warn', 'سیستم راهنمای تعاملی (Product Tour) آپلود نشده.', 'onboarding-tour.php را آپلود کنید.'],
            ['onboarding-tour.js', 'MelkinoTour', 'warn', 'موتور راهنمای تعاملی آپلود نشده.', 'onboarding-tour.js را آپلود کنید.'],
            ['footer.php', 'melkinoProductTourBoot', 'warn', 'اتصال راهنما به فوتر سایت آپلود نشده.', 'footer.php را آپلود کنید.'],
            ['admin-panel.php', 'melkinoProductTourAdminCard', 'warn', 'مدیریت راهنمای تعاملی در تب صفحات هدایت آپلود نشده.', 'admin-panel.php را آپلود کنید.'],
            ['admin-panel.php', "const txt=function(id, fb)", 'fail', 'دکمهٔ ذخیرهٔ تنظیمات عمومی هنوز فیلد اختیاری ماشین‌حساب را اجباری می‌کند و ذخیره را قطع می‌کند.', 'admin-panel.php را آپلود کنید.'],
            ['profile.php', 'mkTourReplayBtn', 'warn', 'دکمهٔ اجرای دوبارهٔ راهنما در پروفایل آپلود نشده.', 'profile.php را آپلود کنید.'],
            ['save_global_settings.php', 'empty($_SESSION[\'is_admin\'])', 'fail', 'ذخیرهٔ تنظیمات عمومی هنوز با CSRF سخت می‌شکند.', 'save_global_settings.php را آپلود کنید.'],
            ['db_helpers.php', 'melkinoRunMaintenanceGate', 'fail', 'گیت حالت تعمیرات در هسته آپلود نشده؛ سوییچ تعمیرات بی‌اثر است.', 'db_helpers.php را آپلود کنید.'],
            ['db_helpers.php', 'findConsultantForAd', 'fail', 'شمارهٔ مشاور در پیش‌نمایش کانال هنوز از تنظیمات قدیمی خوانده می‌شود نه لیست مشاوران.', 'db_helpers.php را آپلود کنید.'],
            ['admin-visits.php', 'vrMoney', 'warn', 'جداکنندهٔ هزارگان رهن/اجاره در کارت بازدید آپلود نشده.', 'admin-visits.php را آپلود کنید.'],
            ['admin-visits.php', 'i < curIdx', 'warn', 'رنگ مرحله‌ای دکمه‌های وضعیت بازدید آپلود نشده.', 'admin-visits.php را آپلود کنید.'],
            ['identity-sync.php', 'ad_views', 'warn', 'تاریخچهٔ آگهی‌های دیده‌شده در تب کاربران آپلود نشده.', 'identity-sync.php را آپلود کنید.'],
            ['property-details.php', 'melkinoRecordAdView', 'warn', 'ثبت بازدید آگهی توسط کاربر آپلود نشده.', 'property-details.php را آپلود کنید.'],
            ['admin-panel.php', 'uploadBaleLogo', 'warn', 'آپلود لوگوی کانال بله در تب تماس آپلود نشده.', 'admin-panel.php را آپلود کنید.'],
            ['admin-panel.php', 'addCustomCard', 'warn', 'کارت‌های سفارشی هنوز ۵ کارت خالی اجباری دارند.', 'admin-panel.php را آپلود کنید.'],
            ['contact.php', 'baleChannelLogo', 'warn', 'نمایش لوگوی بله در صفحهٔ ارتباط با ما آپلود نشده.', 'contact.php را آپلود کنید.'],
            ['db_helpers.php', 'melkinoClaimPhoneOnUser', 'warn', 'ادغام اکانت بله و تلگرام با یک شماره آپلود نشده.', 'db_helpers.php را آپلود کنید.'],
            ['admin-dashboard.php', 'loadAdminDashboard', 'warn', 'داشبورد فرمان پنل ادمین آپلود نشده.', 'admin-dashboard.php و admin-dashboard-api.php را آپلود کنید.'],
            ['admin-ads.js', 'openPublishComposer', 'fail', 'پیش‌نمایش واقعی انتشار تلگرام/بله آپلود نشده.', 'admin-ads.js را آپلود کنید.'],
            ['telegram-relay.php', 'preview_only', 'warn', 'آماده‌سازی پیش‌نمایش انتشار با انتخاب فیلد آپلود نشده.', 'telegram-relay.php را آپلود کنید.'],
        ];
        foreach ($roundMarkers as [$file, $marker, $severity, $msg, $hint]) {
            $path = dirname(__DIR__) . '/' . $file;
            $rootTxt = is_file($path) ? (string)@file_get_contents($path) : '';
            $has = $rootTxt !== '' && strpos($rootTxt, $marker) !== false;
            if (!$has && $rootTxt !== '' && substr($file, -4) === '.php'
                && strpos($rootTxt, 'views/legacy/') !== false && strpos($rootTxt, '-legacy.php') !== false) {
                // ورودیِ نازک V2: نشانگر در کپیِ legacy است، نه در همین فایل.
                $legacyPath = dirname(__DIR__) . '/views/legacy/' . substr($file, 0, -4) . '-legacy.php';
                if (is_file($legacyPath)) {
                    $has = strpos((string)@file_get_contents($legacyPath), $marker) !== false;
                }
            }
            $checks[] = office_dg_item(
                $g,
                'نسخهٔ ' . $file,
                $has ? 'ok' : $severity,
                $has ? 'به‌روز است' : $msg,
                $has ? '' : $hint
            );
        }

        $profSrc = is_file(dirname(__DIR__) . '/profile.php') ? (string) @file_get_contents(dirname(__DIR__) . '/profile.php') : '';
        $profLeak = $profSrc !== '' && (strpos($profSrc, '?>;') !== false || substr_count($profSrc, "require_once dirname(__DIR__) . '/footer.php';") > 2);
        $checks[] = office_dg_item(
            $g,
            'انتهای تمیز صفحهٔ پروفایل',
            $profLeak ? 'fail' : ($profSrc === '' ? 'fail' : 'ok'),
            $profLeak ? 'کد جاوااسکریپت بعد از بسته شدن PHP در انتهای profile.php نشت می‌کند و در صفحه دیده می‌شود.' : 'انتهای فایل تمیز است',
            $profLeak ? 'profile.php را از زیپ جدید جایگزین کنید.' : ''
        );
        $dupMenu = $profSrc !== '' && strpos($profSrc, '<h2>') !== false && strpos($profSrc, 'فعالیت‌های من') !== false;
        $checks[] = office_dg_item(
            $g,
            'حذف منوی تکراری پروفایل',
            $dupMenu ? 'warn' : 'ok',
            $dupMenu ? 'بخش «فعالیت‌های من» هنوز در پروفایل هست و با کارت‌های بالا تکراری است.' : 'منوی تکراری حذف شده است',
            $dupMenu ? 'profile.php را آپلود کنید.' : ''
        );

        $tourFiles = ['onboarding-tour.php', 'onboarding-tour.js', 'onboarding-tour.css', 'onboarding-tour-save.php'];
        $missTour = [];
        foreach ($tourFiles as $tf) {
            if (!is_file(dirname(__DIR__) . '/' . $tf)) {
                $missTour[] = $tf;
            }
        }
        $checks[] = office_dg_item(
            $g,
            'فایل‌های راهنمای تعاملی',
            $missTour ? 'fail' : 'ok',
            $missTour ? ('مفقود: ' . implode('، ', $missTour)) : 'هر چهار فایل راهنما موجود است',
            $missTour ? 'فایل‌های onboarding-tour.* را آپلود کنید.' : ''
        );

        // ۸-۲) فایل‌های نصب که باید بعد از استفاده حذف شوند
        $leftovers = [];
        foreach (['melkino-install.php', '__test_session.php'] as $lf) {
            if (is_file(dirname(__DIR__) . '/' . $lf)) {
                $leftovers[] = $lf;
            }
        }
        $checks[] = office_dg_item(
            $g,
            'فایل‌های موقت نصب/تست',
            $leftovers ? 'fail' : 'ok',
            $leftovers ? ('این فایل‌ها هنوز روی هاست هستند: ' . implode('، ', $leftovers)) : 'فایل نصب/تست اضافه باقی نمانده',
            $leftovers ? 'ملف melkino-install.php و هر فایل تستی را همین حالا حذف کنید (ریسک امنیتی).' : ''
        );

        // ۸-۳) ستون‌ها و جداول موردنیاز قابلیت‌های جدید
        $pdoD = office_dg_pdo();
        $neededColumns = [
            ['users', 'access_token', 'ورود با پیامک (توکن دسترسی)'],
            ['settings', 'value_type', 'خواندن تنظیمات (صفحهٔ جزئیات ملک)'],
            ['settings', 'setting_group', 'گروه‌بندی تنظیمات'],
            ['amenities', 'sort_order', 'ترتیب امکانات در جزئیات ملک'],
            // فیلدهای وام (راند ۱۲)
            ['ads', 'has_loan', 'ذخیرهٔ وضعیت «وام دارد»'],
            ['ads', 'loan_amount', 'ذخیرهٔ مبلغ وام و نمایش دو قیمت'],
            ['ads', 'loan_installment', 'ذخیرهٔ مبلغ قسط وام'],
            // سند و معاوضه (راند ۱۴)
            ['ads', 'deed_type', 'ذخیرهٔ نوع سند ملک'],
            ['ads', 'deed_notes', 'ذخیرهٔ توضیحات سند'],
            ['ads', 'exchange_types', 'ذخیرهٔ گزینه‌های معاوضه'],
        ];
        foreach ($neededColumns as [$tbl, $col, $why]) {
            $cols = office_dg_columns($pdoD, $tbl);
            // office_dg_columns یک نگاشت «نام ستون => ردیف» برمی‌گرداند
            $has = $cols === [] ? null : in_array(strtolower($col), array_keys($cols), true);
            $checks[] = office_dg_item(
                $g,
                'ستون ' . $tbl . '.' . $col,
                $has === null ? 'warn' : ($has ? 'ok' : 'fail'),
                $has === null ? ('جدول ' . $tbl . ' بررسی نشد') : ($has ? 'وجود دارد' : 'مفقود است — ' . $why),
                $has ? '' : 'melkino-migrate.php را اجرا کنید تا ستون ساخته شود.'
            );
        }
        // ۸-۳ب) ستون‌های JSON آلوده به escape تودرتو (باگ راند ۱۵)
        // هر ذخیرهٔ پنل ادمین یک لایه json_encode اضافه می‌کرد و این
        // ستون‌ها نمایی رشد می‌کردند تا خروجی properties-data.php بریده
        // شود و صفحهٔ «همهٔ آگهی‌ها» خطای اتصال دیتابیس بگیرد.
        $jsonBloatCount = null;
        $pdoJ = office_dg_pdo();
        if ($pdoJ instanceof PDO) {
            try {
                $jsonBloatCount = (int)$pdoJ->query(
                    'SELECT COUNT(*) FROM ads WHERE LENGTH(tags) > 2000 OR LENGTH(custom_fields) > 2000 OR LENGTH(property_details) > 20000'
                )->fetchColumn();
            } catch (Throwable $e) {
                $jsonBloatCount = null;
            }
        }
        $checks[] = office_dg_item(
            $g,
            'ستون‌های JSON سالم (tags/custom_fields/property_details)',
            $jsonBloatCount === null ? 'warn' : ($jsonBloatCount > 0 ? 'fail' : 'ok'),
            $jsonBloatCount === null
                ? 'بررسی نشد'
                : ($jsonBloatCount > 0
                    ? ($jsonBloatCount . ' آگهی با escape تودرتوی بیش‌ازحد — همین باگ صفحهٔ «همهٔ آگهی‌ها» را می‌شکند')
                    : 'هیچ رکورد آلوده‌ای پیدا نشد'),
            $jsonBloatCount > 0 ? 'فایل melkino-fix-json.php را (بعد از ورود به پنل ادمین) یک‌بار باز کنید و سپس حذفش کنید.' : ''
        );

        $otpTables = ['otp_codes', 'compare_items', 'compare_groups'];
        foreach ($otpTables as $tbl) {
            $count = office_dg_count($pdoD, $tbl);
            $checks[] = office_dg_item(
                $g,
                'جدول ' . $tbl,
                $count === null ? 'fail' : 'ok',
                $count === null ? 'وجود ندارد' : ('وجود دارد (' . $count . ' ردیف)'),
                $count === null ? 'melkino-migrate.php یا melkino-database.sql را اجرا کنید.' : ''
            );
        }

        // ۸-۴) یکدستی collation (باگ ۱۲۶۶/۱۲۶۷ محیط واقعی)
        if ($pdoD instanceof PDO) {
            try {
                $db = $pdoD->query('SELECT DATABASE()')->fetchColumn();
                $colls = $pdoD->query(
                    'SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = ' . $pdoD->quote((string)$db) . '
                       AND TABLE_COLLATION IS NOT NULL'
                )->fetchAll(PDO::FETCH_ASSOC);
                $distinct = [];
                foreach ($colls as $row) {
                    $distinct[(string)$row['TABLE_COLLATION']][] = (string)$row['TABLE_NAME'];
                }
                $mixed = count($distinct) > 1;
                $summary = implode(' | ', array_map(
                    fn($c) => $c . ': ' . count($distinct[$c]) . ' جدول',
                    array_keys($distinct)
                ));
                $checks[] = office_dg_item(
                    $g,
                    'یکدستی collation جداول',
                    $mixed ? 'warn' : 'ok',
                    $mixed ? ('چند collation متفاوت: ' . $summary) : ('همه یکسان: ' . $summary),
                    $mixed ? 'melkino-migrate.php بخش یکسان‌سازی collation را اجرا کنید تا خطای ۱۲۶۷ در JOIN ها نیاید.' : ''
                );
            } catch (Throwable $e) {
                $checks[] = office_dg_item($g, 'یکدستی collation جداول', 'warn', 'بررسی نشد: ' . $e->getMessage());
            }
        }

        // ۸-۵) روش‌های ورود فعال (تنظیم پنل)
        if (function_exists('melkinoLoginMethodEnabled')) {
            $methods = [];
            foreach (['telegram' => 'تلگرام', 'bale' => 'بله', 'sms' => 'پیامک'] as $key => $fa) {
                $methods[] = $fa . ': ' . (melkinoLoginMethodEnabled($key) ? 'فعال' : 'غیرفعال');
            }
            $checks[] = office_dg_item(
                $g,
                'روش‌های ورود کاربران',
                'ok',
                implode(' · ', $methods),
                'از تب «ربات و کانال» → «روش‌های ورود» قابل تغییر است.'
            );
        }

        // ۸-۶) فیلد لینک آگهی در انتشار
        if (function_exists('melkinoPublishFieldDefs')) {
            $defs = melkinoPublishFieldDefs();
            $hasLink = isset($defs['ad_link']);
            $checks[] = office_dg_item(
                $g,
                'فیلد «لینک آگهی» در انتشار',
                $hasLink ? 'ok' : 'warn',
                $hasLink ? 'در فهرست فیلدهای انتشار موجود است' : 'در bot-settings.php تعریف نشده',
                $hasLink ? '' : 'bot-settings.php را آپلود کنید.'
            );
        }

        /* ---------------- ۹. سلامت ساختاری و پشتیبان (راند ۳۳) ---------------- */
        $g = '۹. سلامت ساختاری و پشتیبان';

        // ۹-۱) محافظت وبی پوشه backups
        $backupDir33 = $root . '/backups';
        $dirOk33 = is_dir($backupDir33);
        $htOk33 = $dirOk33 && is_file($backupDir33 . '/.htaccess');
        $checks[] = office_dg_item(
            $g,
            'محافظت وبی پوشه backups',
            $htOk33 ? 'ok' : ($dirOk33 ? 'warn' : 'fail'),
            $htOk33 ? '.htaccess محافظ موجود است (فایل‌های پشتیبان مستقیم دانلودشدنی نیستند)' : ($dirOk33 ? 'پوشه موجود است ولی .htaccess محافظ ندارد' : 'پوشه backups وجود ندارد'),
            $htOk33 ? '' : 'یک پشتیبان از تب «پشتیبان‌گیری» بسازید؛ .htaccess خودکار ایجاد می‌شود.'
        );

        // ۹-۲) تازگی پشتیبان‌ها
        $count33 = 0;
        $age33 = null;
        if ($dirOk33) {
            $files33 = array_merge(
                glob($backupDir33 . '/*.zip') ?: [],
                glob($backupDir33 . '/*.tar') ?: [],
                glob($backupDir33 . '/*.tar.gz') ?: []
            );
            $count33 = count($files33);
            if ($count33 > 0) {
                $mtimes33 = array_map('filemtime', $files33);
                $age33 = (int)floor((time() - max($mtimes33)) / 86400);
            }
        }
        $fresh33 = ($count33 === 0) ? 'warn' : (($age33 !== null && $age33 >= 7) ? 'warn' : 'ok');
        $checks[] = office_dg_item(
            $g,
            'تازگی پشتیبان‌ها',
            $fresh33,
            $count33 === 0 ? 'هیچ نسخهٔ پشتیبانی ساخته نشده است' : ($count33 . ' نسخه موجود · آخرین پشتیبان ' . $age33 . ' روز پیش'),
            $fresh33 === 'ok' ? '' : 'از تب «پشتیبان‌گیری» یک پشتیبان کامل بسازید. فقط ۱۰ نسخه آخر نگه داشته می‌شوند.'
        );

        // ۹-۳) فضای آزاد دیسک
        $free33 = @disk_free_space($root);
        $checks[] = office_dg_item(
            $g,
            'فضای آزاد دیسک',
            ($free33 !== false && $free33 > 104857600) ? 'ok' : 'warn',
            $free33 !== false ? (round($free33 / 1048576) . ' مگابایت آزاد') : 'قابل تشخیص نیست',
            ($free33 !== false && $free33 > 104857600) ? '' : 'فضای کم ممکن است پشتیبان‌گیری یا آپلود تصویر را مختل کند.'
        );

        // ۹-۴) محدودیت‌های آپلود و حافظه PHP
        $checks[] = office_dg_item(
            $g,
            'محدودیت‌های آپلود و حافظه',
            'ok',
            'upload_max_filesize=' . ini_get('upload_max_filesize') . ' · post_max_size=' . ini_get('post_max_size') . ' · memory_limit=' . ini_get('memory_limit') . ' · max_execution_time=' . ini_get('max_execution_time'),
            'برای آپلود/بازیابی پشتیبان‌های بزرگ، این مقادیر را در هاست افزایش دهید.'
        );

        // ۹-۵) ستون‌ها و ایندکس‌های کلیدی هویت (راندهای ۳۰/۳۱)
        $userCols33 = office_dg_columns($pdo, 'users');
        foreach (['phone' => 'شماره تماس قفل‌شونده', 'name_locked' => 'قفل نام کاربر'] as $col33 => $faName33) {
            $has33 = isset($userCols33[$col33]);
            $checks[] = office_dg_item(
                $g,
                'ستون users.' . $col33 . ' (' . $faName33 . ')',
                $has33 ? 'ok' : 'fail',
                $has33 ? 'موجود است' : 'در جدول users یافت نشد',
                $has33 ? '' : 'melkino-database.sql کامل را در phpMyAdmin وارد کنید یا melkino-migrate.php را اجرا کنید.'
            );
        }
        $uniq33 = office_dg_has_unique_on($pdo, 'users', ['phone']);
        $checks[] = office_dg_item(
            $g,
            'ایندکس یکتای users.phone (uniq_phone)',
            $uniq33 ? 'ok' : 'warn',
            $uniq33 ? 'ایندکس UNIQUE روی phone فعال است — یک شماره فقط به یک حساب تعلق می‌گیرد' : 'ایندکس UNIQUE روی phone یافت نشد',
            $uniq33 ? '' : 'بدون این ایندکس ممکن است دو حساب یک شماره ثبت کنند. در phpMyAdmin: ALTER TABLE users ADD UNIQUE uniq_phone (phone);'
        );

        /* ---------------- ۱۰. لایه امنیت و نقشه (به‌روزرسانی) ---------------- */
        $g = '۱۰. امنیت جدید، نقشه و استودیو';

        $secLib = $root . '/security-lib.php';
        $checks[] = office_dg_item(
            $g,
            'لایه امنیت مشترک (security-lib.php)',
            is_file($secLib) ? 'ok' : 'fail',
            is_file($secLib) ? 'نصب است' : 'یافت نشد',
            is_file($secLib) ? '' : 'فایل security-lib.php را در ریشه آپلود کنید؛ CSRF، محدودیت نرخ و لاگ حسابرسی به آن وابسته‌اند.'
        );

        foreach (['melkinoSafeError' => 'پنهان‌سازی خطاهای فنی', 'melkinoAudit' => 'ثبت حسابرسی', 'melkinoRateLimitHit' => 'محدودیت نرخ درخواست', 'melkinoReencodeImage' => 'رمزگذاری مجدد تصویر'] as $fn => $fa) {
            $checks[] = office_dg_item(
                $g,
                $fa,
                function_exists($fn) ? 'ok' : 'warn',
                function_exists($fn) ? 'فعال' : 'تابع ' . $fn . ' در دسترس نیست',
                function_exists($fn) ? '' : 'security-lib.php را به‌روز آپلود کنید.'
            );
        }

        $gdOk = function_exists('imagecreatefromstring') && function_exists('imagejpeg');
        $checks[] = office_dg_item(
            $g,
            'افزونه GD (پاک‌سازی تصویر آپلودی)',
            $gdOk ? 'ok' : 'warn',
            $gdOk ? 'فعال است؛ تصاویر آپلودی دوباره رمزگذاری می‌شوند' : 'غیرفعال است',
            $gdOk ? '' : 'بدون GD تصویر فقط اعتبارسنجی می‌شود و متادیتای آن پاک نمی‌شود. از هاست بخواهید GD را فعال کند.'
        );

        foreach (['uploads', 'uploads/cards', 'uploads/contact'] as $updir) {
            $abs = $root . '/' . $updir;
            if (!is_dir($abs)) {
                continue;
            }
            $ht = is_file($abs . '/.htaccess');
            $checks[] = office_dg_item(
                $g,
                'محافظت پوشه ' . $updir,
                $ht ? 'ok' : 'warn',
                $ht ? 'فایل .htaccess ضد اجرای اسکریپت موجود است' : '.htaccess ندارد',
                $ht ? '' : 'یک‌بار از پنل تصویری آپلود کنید تا فایل محافظ ساخته شود، یا دستی .htaccess با php_flag engine off بگذارید.'
            );
        }

        if ($pdo instanceof PDO) {
            foreach (['melkino_audit_log' => 'جدول لاگ حسابرسی', 'melkino_rate_limits' => 'جدول محدودیت نرخ'] as $tb => $fa) {
                $exists = office_dg_count($pdo, $tb) !== null;
                $checks[] = office_dg_item(
                    $g,
                    $fa,
                    $exists ? 'ok' : 'warn',
                    $exists ? 'ساخته شده است' : 'هنوز ساخته نشده',
                    $exists ? '' : 'این جدول در اولین استفاده به‌صورت خودکار ساخته می‌شود؛ نیازی به اجرای دستی SQL نیست.'
                );
            }

            try {
                $otpFallback = function_exists('dbSettingGet') ? (bool) dbSettingGet($pdo, 'security', 'otp_screen_fallback', false) : false;
            } catch (Throwable $e) {
                $otpFallback = false;
            }
            $checks[] = office_dg_item(
                $g,
                'نمایش کد ورود روی صفحه',
                $otpFallback ? 'fail' : 'ok',
                $otpFallback ? 'روشن است — کد ورود در پاسخ برگردانده می‌شود' : 'خاموش است (امن)',
                $otpFallback ? 'این گزینه فقط برای تست است. در سایت واقعی باید خاموش باشد وگرنه هر کسی می‌تواند وارد حساب دیگری شود.' : ''
            );
        }

        $mapFiles = ['map-markers.php' => 'کتابخانه شکل مارکر', 'map-markers.js' => 'هِلپر مارکر سمت مرورگر', 'design-studio-lib.php' => 'هسته استودیو طراحی'];
        foreach ($mapFiles as $f => $fa) {
            $checks[] = office_dg_item(
                $g,
                $fa,
                is_file($root . '/' . $f) ? 'ok' : 'fail',
                is_file($root . '/' . $f) ? 'موجود است' : $f . ' آپلود نشده',
                is_file($root . '/' . $f) ? '' : 'این فایل برای انتخاب شکل مارکر و طراحی کارت نقشه لازم است.'
            );
        }

        if ($pdo instanceof PDO && function_exists('dbSettingGet') && function_exists('melkinoStudioSanitize')) {
            try {
                $rawT = dbSettingGet($pdo, 'theme', 'studio_draft', null) ?: dbSettingGet($pdo, 'theme', 'studio_published', null);
                $th = is_array($rawT) ? melkinoStudioSanitize($rawT) : null;
                if ($th) {
                    $shape = (string) ($th['marker']['shape'] ?? 'pin');
                    $checks[] = office_dg_item($g, 'تم نقشه', 'ok', 'مارکر فعلی: ' . $shape . ' — عرض کارت نقشه: ' . ($th['mapCard']['width'] ?? '—') . 'px');
                } else {
                    $checks[] = office_dg_item($g, 'تم نقشه', 'warn', 'هنوز تمی ذخیره نشده؛ مقدار پیش‌فرض استفاده می‌شود.');
                }
            } catch (Throwable $e) {
                $checks[] = office_dg_item($g, 'تم نقشه', 'warn', 'خوانده نشد');
            }
        }

        $checks[] = office_dg_item(
            $g,
            'فایل دور زدن ورود (__test_session.php)',
            is_file($root . '/__test_session.php') ? 'fail' : 'ok',
            is_file($root . '/__test_session.php') ? 'روی سرور موجود است!' : 'وجود ندارد',
            is_file($root . '/__test_session.php') ? 'این فایل باید فوراً از سرور حذف شود؛ اجازه ورود بدون رمز می‌دهد.' : ''
        );

        return $checks;
    }
}

if (!function_exists('office_dg_boot')) {
    function office_dg_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
    }
}

if (!function_exists('office_dg_summary')) {
    /** @return array{ok:int,warn:int,fail:int} */
    function office_dg_summary(array $checks): array
    {
        $s = ['ok' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($checks as $c) {
            $st = (string)($c['status'] ?? '');
            if (isset($s[$st])) {
                $s[$st]++;
            }
        }
        return $s;
    }
}
