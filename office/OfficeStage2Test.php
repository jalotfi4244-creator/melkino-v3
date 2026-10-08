<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-2 tests: files list + register.
 *
 * SAFETY: same guard as stage 1 — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage2
 * (seed first: .../qa/seed-test.php)
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
if (!defined('OFFICE_TEST_UPLOAD_DIR')) {
    define('OFFICE_TEST_UPLOAD_DIR', sys_get_temp_dir() . '/office-stage2-uploads');
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_forms.php';

if (!function_exists('office2_login_session')) {
    function office2_login_session(PDO $pdo): int
    {
        try {
            $id = (int)($pdo->query("SELECT id FROM admins WHERE username = 'qa_admin'")->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $id = 0;
        }
        if ($id <= 0) {
            $id = 1;
        }
        $_SESSION['is_admin'] = 1;
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_username'] = 'qa_admin';
        return $id;
    }
}

if (!function_exists('office2_clean_fixtures')) {
    /** حذف ردیف‌های OF2 اجراهای قبلی (تست قابل تکرار). */
    function office2_clean_fixtures(PDO $pdo): void
    {
        foreach (['09000000001', '09000000002', '09000000003'] as $ph) {
            try {
                $ids = $pdo->query("SELECT id FROM ads WHERE phone = " . $pdo->quote($ph))->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($ids as $id) {
                    $pdo->prepare('DELETE FROM ad_amenities WHERE ad_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM images WHERE ad_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([$id]);
                }
            } catch (Throwable $e) {
            }
        }
        $dir = (string)OFFICE_TEST_UPLOAD_DIR;
        foreach ((array)glob($dir . '/*') as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }
}

if (!function_exists('office2_sale_fixture')) {
    /** @return array<string,mixed> */
    function office2_sale_fixture(): array
    {
        $rooms = office_combo_items('rooms');
        $deeds = office_combo_items('deed_type');
        if ($deeds === []) {
            $deeds = ['طلق'];
        }
        return [
            'csrf_token' => office_csrf(),
            'transaction_type' => 'فروش',
            'status' => 'published',
            'gender' => 'آقا',
            'last_name' => 'OF2 مالک فروش',
            'phone' => '09000000001',
            'title' => 'OF2 آپارتمان تستی فروش',
            'location' => 'OF2 تهران',
            'address' => 'OF2 خیابان تست',
            'map_lat' => '35.700000',
            'map_lng' => '51.400000',
            'area_apt' => '85',
            'floor' => '3',
            'rooms_apt' => (string)($rooms[1] ?? $rooms[0] ?? '2'),
            'year_apt' => '1402',
            'apartment_type' => 'فلت',
            'units_per_floor' => '2',
            'total_units' => '20',
            'amenities_apt' => ['آسانسور', 'پارکینگ'],
            'price_sell' => '2800000000',
            'price_condition' => 'negotiable',
            'deed_type' => (string)$deeds[0],
            'publish_photos' => 'yes',
            'full_description' => 'OF2 شرح تستی',
        ];
    }
}

if (!function_exists('office2_png_file')) {
    /** @return array{name:string[],type:string[],tmp_name:string[],error:int[],size:int[]} */
    function office2_png_file(string $name): array
    {
        // PNG واقعی 1x1 (بدون نیاز به GD برای ساخت فیکسچر)
        $raw = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $tmp = tempnam(sys_get_temp_dir(), 'of2img');
        file_put_contents($tmp, $raw);
        return ['name' => [$name], 'type' => ['image/png'], 'tmp_name' => [$tmp], 'error' => [UPLOAD_ERR_OK], 'size' => [filesize($tmp)]];
    }
}

if (!function_exists('office2_register_post')) {
    /**
     * @return array{output:string,redirect:string}
     * $keepCsrf=true فقط برای تست توکن نامعتبر؛ در غیر این صورت توکنِ تازه
     * در سشنِ تازه ضرب می‌شود (توکن فیکسچرِ قبل از reset کهنه است).
     */
    function office2_register_post(PDO $pdo, array $post, mixed $files = null, bool $keepCsrf = false): array
    {
        office_test_reset();
        $_FILES = [];
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'apartment'];
        $_POST = $post;
        if (!$keepCsrf) {
            $_POST['csrf_token'] = office_csrf();
        }
        if ($files !== null) {
            $_FILES = ['images' => $files];
        }
        return office_test_run_page('register.php');
    }
}

return [
    'stage2 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'files requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $r = office_test_run_page('files.php');
        t_eq('login.php', $r['redirect'], 'unauth files must redirect to login');
    },

    'files lists seeded ads' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $r = office_test_run_page('files.php');
        t_eq('', $r['redirect'], 'no redirect when logged in');
        foreach (['QA-AD-1', 'QA-AD-2', 'QA-AD-6'] as $code) {
            t_ok(str_contains($r['output'], $code), "files list contains $code");
        }
        t_ok(str_contains($r['output'], 'فایل یافت شد'), 'count line rendered');
    },

    'files search filters rows' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['q' => 'ویلا'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-2'), 'search ویلا finds QA-AD-2');
        t_ok(!str_contains($r['output'], 'QA-AD-1'), 'search ویلا excludes QA-AD-1');
        t_ok(!str_contains($r['output'], 'QA-AD-3'), 'search ویلا excludes QA-AD-3');
    },

    'files empty search shows empty state' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['q' => '__no_such_ad__'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'فایلی با این مشخصات یافت نشد'), 'empty state shown');
        t_ok(!str_contains($r['output'], 'QA-AD-1'), 'no rows on empty search');
    },

    'files status filter works' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['status' => 'pending'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-4'), 'pending shows QA-AD-4');
        t_ok(str_contains($r['output'], 'QA-AD-5'), 'pending shows QA-AD-5');
        t_ok(!str_contains($r['output'], 'QA-AD-1'), 'pending excludes QA-AD-1');
        t_ok(!str_contains($r['output'], 'QA-AD-6'), 'pending excludes QA-AD-6');
    },

    'files tx and ptype filters work' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['tx' => 'فروش'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-1'), 'tx فروش shows QA-AD-1');
        t_ok(!str_contains($r['output'], 'QA-AD-3'), 'tx فروش excludes رهن و اجاره row');
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['ptype' => 'ویلا'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-2'), 'ptype ویلا shows QA-AD-2');
        t_ok(!str_contains($r['output'], 'QA-AD-1'), 'ptype ویلا excludes QA-AD-1');
    },

    'files invalid filter values are ignored' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['status' => '__nope__', 'tx' => '__nope__', 'ptype' => '__nope__'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-1'), 'invalid filters ignored, all rows shown');
    },

    'files search is injection-safe' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['q' => "' OR '1'='1"];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'فایلی با این مشخصات یافت نشد'), 'injection string matches nothing');
        t_ok(!str_contains($r['output'], 'QA-AD-1'), 'injection does not dump rows');
        $n = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_ok($n >= 6, 'ads table intact after injection attempt');
    },

    'files pagination is robust' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['page' => 'abc'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-1'), 'non-numeric page falls back to page 1');
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['page' => '999'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'QA-AD-1'), 'overflow page clamps to last page');
    },

    'register requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $_GET = ['type' => 'apartment'];
        $r = office_test_run_page('register.php');
        t_eq('login.php', $r['redirect'], 'unauth register GET redirects');
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'apartment'];
        $_POST = office2_sale_fixture();
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $r = office_test_run_page('register.php');
        t_eq('login.php', $r['redirect'], 'unauth register POST redirects');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before, $after, 'unauth POST inserts nothing');
    },

    'register GET renders same field names as site form' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/register-apartment-legacy.php');
        preg_match_all('/name="([^"]+)"/', $legacy, $m);
        $legacyNames = array_values(array_unique($m[1]));
        // استثناهای موجه: آپلود آژاکس‌ی ویزارد سایت (در دفتر همان images[] چندتایی است)
        // + اینپوت‌های ساخته‌شده با JS داخل رشته‌ها (حاوی + و کوتیشن).
        $legacyNames = array_values(array_filter(
            array_diff($legacyNames, ['uploaded_images']),
            static fn($n) => !str_contains((string)$n, '+') && !str_contains((string)$n, "'")
        ));
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'apartment'];
        $r = office_test_run_page('register.php');
        $missing = [];
        foreach ($legacyNames as $nm) {
            $bare = rtrim($nm, '[]');
            if (!str_contains($r['output'], 'name="' . $nm . '"') && !str_contains($r['output'], 'name="' . $bare . '[]"') && !str_contains($r['output'], 'name="' . $bare . '"')) {
                $missing[] = $nm;
            }
        }
        t_eq([], $missing, 'office form must carry every site field name (missing: ' . implode(',', $missing) . ')');
    },

    'register GET renders same option values as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/register-apartment-legacy.php');
        // امکانات: همان ۱۵ مقدار هاردکدشدهٔ فرم سایت
        preg_match_all('/name="amenities_apt\[\]"[^>]*value="([^"]*)"/', $legacy, $m);
        $legacyAmen = array_values(array_unique($m[1]));
        t_ok(count($legacyAmen) >= 10, 'legacy amenities extracted (' . count($legacyAmen) . ')');
        // سند: رادیوهای فرم سایت
        preg_match_all('/name="deed_type"[^>]*value="([^"]*)"/', $legacy, $m2);
        $legacyDeeds = array_values(array_unique($m2[1]));
        t_ok(count($legacyDeeds) >= 5, 'legacy deed options extracted (' . count($legacyDeeds) . ')');
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'apartment'];
        $r = office_test_run_page('register.php');
        foreach (array_merge($legacyAmen, $legacyDeeds) as $v) {
            t_ok(str_contains($r['output'], 'value="' . $v . '"'), "office form carries option value: $v");
        }
        // کمبوها از همان کاتالوگ سایت می‌آیند
        foreach (office_combo_items('rooms') as $v) {
            t_ok(str_contains($r['output'], (string)$v), "rooms combo item rendered: $v");
        }
        t_ok(str_contains($legacy, "melkinoFormSelectOptions('rooms')"), 'site form uses same catalog fn for rooms');
    },

    'register POST rejects bad csrf' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = office2_sale_fixture();
        $post['csrf_token'] = 'bogus';
        $r = office2_register_post($pdo, $post, null, true);
        t_ok(str_contains($r['output'], 'نشست منقضی'), 'bad csrf shows session error');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before, $after, 'bad csrf inserts nothing');
    },

    'register POST validates required fields' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = office2_sale_fixture();
        unset($post['area_apt'], $post['price_sell']);
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ ملک را وارد کنید.'), 'missing area shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً قیمت فروش را وارد کنید.'), 'missing price_sell shows site-identical error');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before, $after, 'invalid POST inserts nothing');
    },

    'register POST enforces loan rule' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = office2_sale_fixture();
        $post['has_loan'] = '1';
        $post['loan_amount'] = '99999999999';
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'مبلغ وام باید از قیمت ملک کمتر باشد'), 'oversize loan shows site-identical error');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before, $after, 'loan violation inserts nothing');
    },

    'register POST rent branch validates deposit and rent' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = office2_sale_fixture();
        $post['transaction_type'] = 'اجاره';
        unset($post['price_sell']);
        $post['phone'] = '09000000002';
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'لطفاً مبلغ ودیعه را وارد کنید.'), 'missing deposit shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً مبلغ اجاره ماهانه را وارد کنید.'), 'missing rent shows site-identical error');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before, $after, 'invalid rent POST inserts nothing');

        // حالت موفق اجاره
        office2_clean_fixtures($pdo);
        $post['deposit'] = '500000000';
        $post['rent_monthly'] = '30000000';
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'rent fixture saves');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before + 1, $after, 'rent POST inserts exactly one row');
        $row = $pdo->query("SELECT transaction_type, deposit, rent_monthly, price_sell FROM ads WHERE phone = '09000000002' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('اجاره', (string)($row['transaction_type'] ?? ''), 'rent tx stored');
        t_eq('500000000', (string)($row['deposit'] ?? ''), 'deposit stored');
        t_eq('30000000', (string)($row['rent_monthly'] ?? ''), 'rent_monthly stored');
    },

    'register POST sale saves full mapping with image' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office2_clean_fixtures($pdo);
        office_test_reset();
        $_FILES = [];
        $adminId = office2_login_session($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = office2_sale_fixture();
        $files = office2_png_file('sale-test.png');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'apartment'];
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        $_FILES = ['images' => $files];
        $r = office_test_run_page('register.php');
        t_ok(str_contains($r['output'], 'ثبت شد'), 'sale fixture shows success panel');
        t_ok((bool)preg_match('/AD-\d{8}-\d{4}/', $r['output'], $mm), 'success panel shows ad code');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        t_eq($before + 1, $after, 'sale POST inserts exactly one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '09000000001' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'sale row found by phone');
        if (is_array($row)) {
            t_eq('OF2 آپارتمان تستی فروش', (string)($row['title'] ?? ''), 'title stored');
            t_eq('آپارتمان', (string)($row['property_type'] ?? ''), 'property_type stored');
            t_eq('فروش', (string)($row['transaction_type'] ?? ''), 'transaction stored');
            t_eq('published', (string)($row['status'] ?? ''), 'status stored');
            t_eq('2800000000', (string)($row['price_sell'] ?? ''), 'price_sell stored');
            t_eq('85', (string)($row['area'] ?? ''), 'area stored');
            t_eq('3', (string)($row['floor'] ?? ''), 'floor stored');
            t_eq('1402', (string)($row['year'] ?? ''), 'year stored');
            t_eq('OF2 مالک فروش', (string)($row['last_name'] ?? ''), 'owner last_name stored');
            t_eq('آقا', (string)($row['gender'] ?? ''), 'gender stored');
            t_eq($adminId, (int)($row['consultant_id'] ?? 0), 'consultant_id = posting admin');
            $amen = $pdo->query("SELECT a.name FROM ad_amenities l JOIN amenities a ON a.id = l.amenity_id WHERE l.ad_id = " . $pdo->quote((string)$row['id']))->fetchAll(PDO::FETCH_COLUMN) ?: [];
            t_ok(in_array('آسانسور', $amen, true) && in_array('پارکینگ', $amen, true), 'amenities linked via ad_amenities');
            $img = $pdo->query("SELECT storage_path FROM images WHERE ad_id = " . $pdo->quote((string)$row['id']) . ' LIMIT 1')->fetchColumn();
            t_ok(is_string($img) && $img !== '' && is_file((string)$img), 'image stored on disk and linked');
        }
    },

    'image store rejects bad files' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office2_clean_fixtures($pdo);
        $good = office2_png_file('ok.png');
        $tmpBad = tempnam(sys_get_temp_dir(), 'of2bad');
        file_put_contents($tmpBad, 'not-an-image');
        $entry = [
            'name' => [$good['name'][0], 'evil.php'],
            'type' => ['image/png', 'application/x-php'],
            'tmp_name' => [$good['tmp_name'][0], $tmpBad],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [$good['size'][0], filesize($tmpBad)],
        ];
        [$paths, $errs] = office_store_images($entry, 'AD-TEST-1');
        t_eq(1, count($paths), 'one good image stored');
        t_eq(1, count($errs), 'one bad file rejected');
        t_ok(is_file($paths[0]), 'stored path exists on disk');
        @unlink($tmpBad);
    },

    'register partnership tab renders form' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'partnership'];
        $r = office_test_run_page('register.php');
        t_ok(str_contains($r['output'], 'name="owner_name"'), 'partnership tab renders partnership form');
        t_ok(str_contains($r['output'], 'name="submit_partnership"'), 'partnership submit keeps site button name');
        t_ok(!str_contains($r['output'], 'name="area_apt"'), 'partnership tab renders no apartment form');
    },
];
