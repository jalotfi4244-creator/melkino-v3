<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-7 tests: partnership management.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage7
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
require_once dirname(__DIR__) . '/office/_parts.php';

if (!function_exists('office7_clean')) {
    function office7_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM partnership_requests WHERE phone LIKE '0917%'");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("DELETE FROM ads WHERE id LIKE 'PRT-%' AND phone LIKE '0917%'");
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

if (!function_exists('office7_fixture')) {
    /** @return array<string,mixed> */
    function office7_fixture(string $phone, string $hood = 'OF7 کوی تست'): array
    {
        return [
            'owner_name' => 'OF7 مالک',
            'phone' => $phone,
            'property_type' => 'زمین',
            'area' => '300',
            'current_status' => 'زمین خالی',
            'neighborhood' => $hood,
            'address' => 'OF7 خیابان تست کوچه هفتم پلاک ۷',
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

if (!function_exists('office7_make')) {
    function office7_make(PDO $pdo, string $phone, string $hood = 'OF7 کوی تست'): int
    {
        $adminId = office2_login_session($pdo);
        [$saved, $errors] = office_save_partnership($pdo, office7_fixture($phone, $hood), [], $adminId);
        if (!$saved) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        $st = $pdo->prepare('SELECT id FROM partnership_requests WHERE phone = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$phone]);
        return (int)$st->fetchColumn();
    }
}

if (!function_exists('office7_get')) {
    /** @return array{output:string,redirect:string} */
    function office7_get(PDO $pdo, string $page, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page($page);
    }
}

if (!function_exists('office7_post')) {
    /** @return array{output:string,redirect:string} */
    function office7_post(PDO $pdo, string $page, array $get, array $post, array $files = []): array
    {
        office_test_reset();
        $_FILES = $files;
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page($page);
    }
}

if (!function_exists('office7_row')) {
    /** @return array<string,mixed>|null */
    function office7_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM partnership_requests WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office7_pdf_file')) {
    /** @return array{name:string,type:string,tmp_name:string,error:int,size:int} */
    function office7_pdf_file(string $name): array
    {
        $raw = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n";
        $tmp = tempnam(sys_get_temp_dir(), 'of7pdf');
        file_put_contents($tmp, $raw);
        return ['name' => $name, 'type' => 'application/pdf', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
    }
}

return [
    'stage7 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage7 partnerships requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('partnerships.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'status', 'id' => $id, 'status' => 'approved', 'csrf_token' => 'x'];
        $r = office_test_run_page('partnerships.php');
        t_eq('login.php', $r['redirect'], 'action redirects guests');
        t_eq('pending', (string)(office7_row($pdo, $id)['status'] ?? ''), 'guest status POST changes nothing');

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'delete', 'id' => $id, 'csrf_token' => 'x'];
        office_test_run_page('partnerships.php');
        t_ok(office7_row($pdo, $id) !== null, 'guest delete changes nothing');
        office7_clean($pdo);
    },

    'stage7 list renders rows, counts and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id1 = office7_make($pdo, '09170000001', 'OF7 کوی یکم');
        $id2 = office7_make($pdo, '09170000002', 'OF7 کوی دوم');
        office_part_set_status($pdo, $id2, 'approved');

        $r = office7_get($pdo, 'partnerships.php');
        $row1 = office7_row($pdo, $id1);
        t_ok(str_contains($r['output'], (string)$row1['code']), 'list shows code');
        t_ok(str_contains($r['output'], 'OF7 کوی یکم') && str_contains($r['output'], 'OF7 کوی دوم'), 'list shows both hoods');
        t_ok(str_contains($r['output'], 'در انتظار بررسی') && str_contains($r['output'], 'تأیید شد'), 'status chips rendered');
        t_ok(str_contains($r['output'], 'partnerships.php?id=' . $id1), 'view link present');
        t_ok(str_contains($r['output'], 'partnership-edit.php?id=' . $id1), 'edit link present');

        $r = office7_get($pdo, 'partnerships.php', ['status' => 'approved']);
        t_ok(str_contains($r['output'], 'OF7 کوی دوم'), 'filter keeps approved');
        t_ok(!str_contains($r['output'], 'OF7 کوی یکم'), 'filter hides pending');

        $r = office7_get($pdo, 'partnerships.php', ['status' => 'bogus']);
        t_ok(str_contains($r['output'], 'OF7 کوی یکم'), 'bad status filter ignored');

        $r = office7_get($pdo, 'partnerships.php', ['q' => 'کوی دوم']);
        t_ok(str_contains($r['output'], 'OF7 کوی دوم'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF7 کوی یکم'), 'search misses others');
        office7_clean($pdo);
    },

    'stage7 detail shows full row and grouped money' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');
        $pdo->prepare("UPDATE partnership_requests SET value_from = '15000000000.00', value_to = '20000000000', balaghz_amount = '500000000' WHERE id = ?")->execute([$id]);

        $r = office7_get($pdo, 'partnerships.php', ['id' => (string)$id]);
        t_ok(str_contains($r['output'], 'OF7 مالک'), 'detail shows owner');
        t_ok(str_contains($r['output'], '15,000,000,000'), 'value_from grouped full');
        t_ok(str_contains($r['output'], '20,000,000,000'), 'value_to grouped full');
        t_ok(str_contains($r['output'], '500,000,000'), 'balaghz grouped');
        t_ok(!str_contains($r['output'], '15000000000.00'), 'raw decimal value gone');
        t_ok(str_contains($r['output'], 'یادداشت ادمین'), 'note form present');
        t_ok(str_contains($r['output'], 'انتشار در سایت'), 'publish form present');
        t_ok(str_contains($r['output'], 'حذف درخواست'), 'delete form present');

        $r = office7_get($pdo, 'partnerships.php', ['id' => '999999999']);
        t_ok(!str_contains($r['output'], 'یادداشت ادمین'), 'unknown id falls back to list');
        office7_clean($pdo);
    },

    'stage7 status change accepts valid, rejects invalid' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');

        $r = office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'status', 'id' => $id, 'status' => 'reviewing']);
        t_ok(str_contains($r['output'], 'در حال بررسی'), 'status flash shown');
        t_eq('reviewing', (string)(office7_row($pdo, $id)['status'] ?? ''), 'status stored');

        $r = office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'status', 'id' => $id, 'status' => 'bogus']);
        t_ok(str_contains($r['output'], 'وضعیت نامعتبر'), 'invalid status error shown');
        t_eq('reviewing', (string)(office7_row($pdo, $id)['status'] ?? ''), 'invalid status keeps old');
        office7_clean($pdo);
    },

    'stage7 note saves stripped and capped' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');

        $r = office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'note', 'id' => $id, 'note' => 'OF7 <b>تماس</b> گرفته شد']);
        t_ok(str_contains($r['output'], 'یادداشت ادمین ذخیره شد'), 'note flash shown');
        t_eq('OF7 تماس گرفته شد', (string)(office7_row($pdo, $id)['admin_note'] ?? ''), 'note stored stripped');

        office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'note', 'id' => $id, 'note' => str_repeat('ن', 1500)]);
        t_eq(1000, mb_strlen((string)(office7_row($pdo, $id)['admin_note'] ?? '')), 'note capped at 1000');
        office7_clean($pdo);
    },

    'stage7 publish mirrors ad and unpublishes' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');
        $mirror = 'PRT-' . $id;

        $r = office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'publish', 'id' => $id, 'on' => '1']);
        t_ok(str_contains($r['output'], 'منتشر شد'), 'publish flash shown');
        $st = $pdo->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
        $st->execute([$mirror]);
        $ad = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($ad), 'mirror row created');
        t_eq('published', (string)($ad['status'] ?? ''), 'mirror published');
        t_eq('مشارکت در ساخت', (string)($ad['transaction_type'] ?? ''), 'mirror tx type');
        t_eq('09170000001', (string)($ad['phone'] ?? ''), 'mirror phone');
        t_ok(str_contains((string)($ad['description'] ?? ''), 'مشارکت در ساخت'), 'mirror desc prefix');

        $r = office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'publish', 'id' => $id, 'on' => '0']);
        t_ok(str_contains($r['output'], 'قطع شد'), 'unpublish flash shown');
        $st->execute([$mirror]);
        t_eq('archived', (string)($st->fetch(PDO::FETCH_ASSOC)['status'] ?? ''), 'mirror archived');

        $pdo->prepare('UPDATE partnership_requests SET title = ? WHERE id = ?')->execute(['OF7 عنوان تازه', $id]);
        office7_post($pdo, 'partnerships.php', ['id' => (string)$id], ['action' => 'publish', 'id' => $id, 'on' => '1']);
        $st->execute([$mirror]);
        $ad2 = $st->fetch(PDO::FETCH_ASSOC);
        t_eq('published', (string)($ad2['status'] ?? ''), 'republish revives mirror');
        t_eq('OF7 عنوان تازه', (string)($ad2['title'] ?? ''), 'republish refreshes title');
        office7_clean($pdo);
    },

    'stage7 delete removes row, mirror untouched' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');
        office_part_publish($pdo, office_part_get($pdo, $id) ?? [], true);
        $mirror = 'PRT-' . $id;

        $r = office7_post($pdo, 'partnerships.php', [], ['action' => 'delete', 'id' => $id]);
        t_eq('partnerships.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office7_row($pdo, $id) === null, 'row deleted');
        $st = $pdo->prepare('SELECT status FROM ads WHERE id = ? LIMIT 1');
        $st->execute([$mirror]);
        t_eq('published', (string)$st->fetchColumn(), 'mirror untouched like site');
        // آینهٔ یتیم را هم پاک می‌کنیم تا شمارش‌های دیگر آلوده نشود
        $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$mirror]);
        office7_clean($pdo);
    },

    'stage7 edit prefills form' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');

        $r = office7_get($pdo, 'partnership-edit.php', ['id' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF7 مالک"'), 'owner prefilled');
        t_ok(str_contains($r['output'], 'value="OF7 کوی تست"'), 'hood prefilled');
        t_ok(str_contains($r['output'], 'value="35.7000000"'), 'map lat prefilled');
        t_ok(str_contains($r['output'], 'value="زمین" checked'), 'property type radio checked');
        t_ok(str_contains($r['output'], 'value="هیچ‌کدام" checked'), 'legal flag checked');
        t_ok(str_contains($r['output'], 'value="pending" selected'), 'current status selected');

        $r = office7_get($pdo, 'partnership-edit.php', ['id' => '999999999']);
        t_ok(str_contains($r['output'], 'درخواست یافت نشد'), 'unknown id panel');
        office7_clean($pdo);
    },

    'stage7 edit POST updates and validates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');
        $before = office7_row($pdo, $id);

        $post = office7_fixture('09170000001', 'OF7 کوی جدید');
        $post['area'] = '350';
        $post['status'] = 'approved';
        $r = office7_post($pdo, 'partnership-edit.php', ['id' => (string)$id], $post);
        t_ok(str_contains($r['output'], 'تغییرات ذخیره شد'), 'edit success panel');
        $after = office7_row($pdo, $id);
        t_eq('350', (string)($after['area'] ?? ''), 'area updated');
        t_eq('OF7 کوی جدید', (string)($after['neighborhood'] ?? ''), 'hood updated');
        t_eq('approved', (string)($after['status'] ?? ''), 'status updated via edit');
        t_eq((string)($before['code'] ?? ''), (string)($after['code'] ?? ''), 'code preserved');
        t_eq((string)($before['created_at'] ?? ''), (string)($after['created_at'] ?? ''), 'created_at preserved');
        t_ok((string)($after['updated_at'] ?? '') !== '', 'updated_at stamped');

        $bad = office7_fixture('09170000001', 'OF7 کوی جدید');
        $bad['area'] = '';
        $r = office7_post($pdo, 'partnership-edit.php', ['id' => (string)$id], $bad);
        t_ok(str_contains($r['output'], 'مساحت ملک'), 'validation error shown');
        t_eq('350', (string)(office7_row($pdo, $id)['area'] ?? ''), 'failed edit changes nothing');
        office7_clean($pdo);
    },

    'stage7 edit rejects bad status and appends docs' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office7_clean($pdo);
        $id = office7_make($pdo, '09170000001');

        $post = office7_fixture('09170000001');
        $post['status'] = 'bogus';
        office7_post($pdo, 'partnership-edit.php', ['id' => (string)$id], $post);
        t_eq('pending', (string)(office7_row($pdo, $id)['status'] ?? ''), 'bad status keeps old');

        $multi = office7_pdf_file('of7-a.pdf');
        $files = ['photos' => ['name' => [$multi['name']], 'type' => [$multi['type']], 'tmp_name' => [$multi['tmp_name']], 'error' => [$multi['error']], 'size' => [$multi['size']]]];
        office7_post($pdo, 'partnership-edit.php', ['id' => (string)$id], office7_fixture('09170000001'), $files);
        $photos = json_decode((string)(office7_row($pdo, $id)['photos'] ?? '[]'), true);
        t_eq(1, is_array($photos) ? count($photos) : -1, 'new photo stored');

        $multi2 = office7_pdf_file('of7-b.pdf');
        $files2 = ['photos' => ['name' => [$multi2['name']], 'type' => [$multi2['type']], 'tmp_name' => [$multi2['tmp_name']], 'error' => [$multi2['error']], 'size' => [$multi2['size']]]];
        office7_post($pdo, 'partnership-edit.php', ['id' => (string)$id], office7_fixture('09170000001'), $files2);
        $photos2 = json_decode((string)(office7_row($pdo, $id)['photos'] ?? '[]'), true);
        t_eq(2, is_array($photos2) ? count($photos2) : -1, 'second photo appended');
        office7_clean($pdo);
    },
];
