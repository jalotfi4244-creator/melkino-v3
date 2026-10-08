<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-5 tests: partnership register.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage5
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
if (!defined('OFFICE_TEST_UPLOAD_DIR')) {
    define('OFFICE_TEST_UPLOAD_DIR', sys_get_temp_dir() . '/office-stage2-uploads');
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_forms.php';
require_once dirname(__DIR__) . '/office/_forms3.php';

if (!function_exists('office5_clean')) {
    function office5_clean(PDO $pdo): void
    {
        try {
            $pdo->prepare("DELETE FROM partnership_requests WHERE phone = '09150000001'")->execute();
        } catch (Throwable $e) {
        }
        $dir = rtrim((string)OFFICE_TEST_UPLOAD_DIR, '/') . '/partnership';
        foreach ((array)glob($dir . '/*') as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }
}

if (!function_exists('office5_fixture')) {
    /** @return array<string,mixed> */
    function office5_fixture(): array
    {
        return [
            'owner_name' => 'OF5 مالک',
            'phone' => '09150000001',
            'property_type' => 'زمین',
            'area' => '300',
            'current_status' => 'زمین خالی',
            'neighborhood' => 'OF5 سعادت‌آباد',
            'address' => 'OF5 خیابان تست کوچه پنجم پلاک ۱۰',
            'map_lat' => '35.700000',
            'map_lng' => '51.400000',
            'passage_width' => '8',
            'land_width' => '10',
            'br_count' => 'دو بر',
            'direction' => 'شمالی',
            'permit_status' => 'پروانه ندارم',
            'deed_status' => 'سند تک‌برگ',
            'deed_kind' => 'طلق',
            'owners_count' => '2',
            'legal_status' => ['هیچ‌کدام'],
        ];
    }
}

if (!function_exists('office5_pdf_file')) {
    /** @return array{name:string,type:string,tmp_name:string,error:int,size:int} */
    function office5_pdf_file(string $name): array
    {
        $raw = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n";
        $tmp = tempnam(sys_get_temp_dir(), 'of5pdf');
        file_put_contents($tmp, $raw);
        return ['name' => $name, 'type' => 'application/pdf', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
    }
}

if (!function_exists('office5_register_post')) {
    /** @return array{output:string,redirect:string} */
    function office5_register_post(PDO $pdo, array $post, array $files = []): array
    {
        office_test_reset();
        $_FILES = $files;
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'partnership'];
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('register.php');
    }
}

return [
    'stage5 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage5 partnership requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn();
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'partnership'];
        $_POST = office5_fixture();
        $r = office_test_run_page('register.php');
        t_eq('login.php', $r['redirect'], 'unauth partnership POST redirects');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn(), 'unauth POST inserts nothing');
    },

    'stage5 partnership renders same field names as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/register-partnership-legacy.php');
        preg_match_all('/name="([^"]+)"/', $legacy, $m);
        $names = array_values(array_unique($m[1]));
        // حمل‌ونقل آژاکس‌ی سایت؛ معادل دفتر فیلدهای مستقیم است
        $names = array_values(array_filter(
            array_diff($names, ['partnership_payload']),
            static fn($n) => !str_contains((string)$n, '+') && !str_contains((string)$n, "'")
        ));
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'partnership'];
        $r = office_test_run_page('register.php');
        $missing = [];
        foreach ($names as $nm) {
            $bare = rtrim($nm, '[]');
            if (!str_contains($r['output'], 'name="' . $nm . '"') && !str_contains($r['output'], 'name="' . $bare . '[]"') && !str_contains($r['output'], 'name="' . $bare . '"')) {
                $missing[] = $nm;
            }
        }
        t_eq([], $missing, 'office partnership form must carry every site field name (missing: ' . implode(',', $missing) . ')');
    },

    'stage5 partnership options come from same site catalog' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/register-partnership-legacy.php');
        t_ok(str_contains($legacy, 'melkinoPartOptions()'), 'site form builds options from melkinoPartOptions');
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'partnership'];
        $r = office_test_run_page('register.php');
        foreach (['property_types', 'current_statuses', 'br_counts', 'directions', 'permit_statuses', 'deed_statuses', 'deed_kinds', 'occupancies', 'legal_flags'] as $k) {
            t_ok(str_contains($legacy, "\$opt['" . $k . "']"), "site loops same catalog key: $k");
            foreach ((array)(melkinoPartOptions()[$k] ?? []) as $v) {
                t_ok(str_contains($r['output'], (string)$v), "partnership option rendered: $v");
            }
        }
    },

    'stage5 partnership POST saves full mapping with files' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office5_clean($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn();
        $files = ['photos' => office2_png_file('p1.png'), 'doc_deed' => office5_pdf_file('sanad.pdf')];
        $r = office5_register_post($pdo, office5_fixture(), $files);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'partnership fixture shows success panel');
        t_ok((bool)preg_match('/MKP-[A-Z0-9]{8}/', $r['output'], $mm), 'success panel shows tracking code');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn(), 'partnership inserts one row');
        $row = $pdo->query("SELECT * FROM partnership_requests WHERE phone = '09150000001' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'partnership row found');
        if (is_array($row)) {
            t_eq('OF5 مالک', (string)($row['owner_name'] ?? ''), 'owner stored');
            t_eq('pending', (string)($row['status'] ?? ''), 'status pending like site');
            t_eq('زمین', (string)($row['property_type'] ?? ''), 'property_type stored');
            t_eq('300', (string)($row['area'] ?? ''), 'area stored');
            t_ok(str_starts_with((string)($row['title'] ?? ''), 'مشارکت در ساخت'), 'auto title like site: ' . (string)($row['title'] ?? ''));
            t_eq('سند تک‌برگ', (string)($row['deed_status'] ?? ''), 'deed_status stored');
            t_eq('manual', (string)($row['location_source'] ?? ''), 'location_source manual');
            t_ok(abs((float)($row['latitude'] ?? 0) - 35.7) < 0.0001, 'latitude stored');
            t_ok((int)($row['completeness'] ?? 0) > 0, 'completeness computed');
            t_ok(str_contains((string)($row['legal_status'] ?? ''), 'هیچ‌کدام'), 'legal_status stored');
            $photos = json_decode((string)($row['photos'] ?? '[]'), true);
            t_ok(is_array($photos) && count($photos) === 1, 'one photo linked');
            $pp = is_array($photos) ? (string)($photos[0] ?? '') : '';
            t_ok((bool)preg_match('#^uploads/partnership/doc_\d+_[a-f0-9]{16}\.png$#', $pp), 'photo path follows site contract: ' . $pp);
            t_ok(melkinoPartCleanPath($pp) === $pp, 'photo path passes site CleanPath');
            $tmpdir = rtrim((string)OFFICE_TEST_UPLOAD_DIR, '/') . '/partnership/';
            t_ok(is_file($tmpdir . basename($pp)), 'photo exists on disk (test dir)');
            $dp = (string)($row['doc_deed'] ?? '');
            t_ok((bool)preg_match('#^uploads/partnership/doc_\d+_[a-f0-9]{16}\.pdf$#', $dp), 'doc path follows site contract: ' . $dp);
            t_ok(is_file($tmpdir . basename($dp)), 'doc exists on disk (test dir)');
        }
    },

    'stage5 partnership POST validates required fields' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn();
        $post = office5_fixture();
        unset($post['property_type'], $post['area'], $post['current_status'], $post['neighborhood'], $post['address'], $post['deed_status']);
        $r = office5_register_post($pdo, $post);
        foreach (['نوع ملک را انتخاب کنید.', 'مساحت ملک را وارد کنید.', 'وضعیت فعلی ملک را انتخاب کنید.', 'محله را مشخص کنید.', 'آدرس ملک را کامل‌تر بنویسید.', 'وضعیت سند را انتخاب کنید.'] as $msg) {
            t_ok(str_contains($r['output'], $msg), "site-identical error shown: $msg");
        }
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn(), 'invalid POST inserts nothing');
    },

    'stage5 partnership POST requires owner identity' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn();
        $post = office5_fixture();
        unset($post['owner_name'], $post['phone']);
        $r = office5_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'نام مالک'), 'missing owner shows error');
        t_ok(str_contains($r['output'], 'موبایل مالک'), 'missing phone shows error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM partnership_requests')->fetchColumn(), 'ownerless POST inserts nothing');
    },

    'stage5 partnership doc store rejects bad files' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $tmpBad = tempnam(sys_get_temp_dir(), 'of5bad');
        file_put_contents($tmpBad, 'not-a-doc');
        [$paths, $errs] = office_store_part_docs([
            'name' => 'evil.exe', 'tmp_name' => $tmpBad, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmpBad),
        ], false);
        t_eq([], $paths, 'exe not stored');
        t_eq(1, count($errs), 'exe rejected');
        [$paths2, $errs2] = office_store_part_docs([
            'name' => 'big.pdf', 'tmp_name' => $tmpBad, 'error' => UPLOAD_ERR_OK, 'size' => 9 * 1024 * 1024,
        ], false);
        t_eq([], $paths2, 'oversize not stored');
        t_eq(1, count($errs2), 'oversize rejected');
        @unlink($tmpBad);
    },
];
