<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-11 tests: calls.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage11
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_calls.php';
if (!function_exists('office_test_pdo')) {
    require __DIR__ . '/OfficeStage1Test.php';
}
if (!function_exists('office2_login_session')) {
    require __DIR__ . '/OfficeStage2Test.php';
}

if (!function_exists('office11_clean')) {
    function office11_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM office_calls WHERE phone LIKE '0911%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office11_fixture')) {
    /** @return array<string,string> */
    function office11_fixture(string $phone = '09110000001'): array
    {
        return [
            'name' => 'OF11 تماس‌گیرنده',
            'phone' => $phone,
            'direction' => 'in',
            'ad_id' => 'QA-AD-1',
            'duration' => '5',
            'call_date' => '2026-10-20',
            'call_time' => '10:15',
            'note' => 'OF11 شرح تماس',
        ];
    }
}

if (!function_exists('office11_make')) {
    function office11_make(PDO $pdo, string $phone = '09110000001', array $over = []): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_call_save($pdo, array_merge(office11_fixture($phone), $over), $adminId);
        if ($id === null) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office11_get')) {
    /** @return array{output:string,redirect:string} */
    function office11_get(PDO $pdo, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page('calls.php');
    }
}

if (!function_exists('office11_post')) {
    /** @return array{output:string,redirect:string} */
    function office11_post(PDO $pdo, array $get, array $post): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('calls.php');
    }
}

if (!function_exists('office11_row')) {
    /** @return array<string,mixed>|null */
    function office11_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM office_calls WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office11_count')) {
    function office11_count(PDO $pdo): int
    {
        return (int)$pdo->query("SELECT COUNT(*) FROM office_calls WHERE phone LIKE '0911%'")->fetchColumn();
    }
}

return [
    'stage11 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage11 calls requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        $id = office11_make($pdo);

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('calls.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        foreach ([
            ['action' => 'save'] + office11_fixture('09110000009'),
            ['action' => 'delete', 'id' => $id],
        ] as $post) {
            office_test_reset();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post + ['csrf_token' => 'x'];
            $r = office_test_run_page('calls.php');
            t_eq('login.php', $r['redirect'], 'guest action redirects: ' . $post['action']);
        }
        t_eq(1, office11_count($pdo), 'guest POSTs change nothing');
        office11_clean($pdo);
    },

    'stage11 add stores call with file link' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        $r = office11_post($pdo, [], ['action' => 'save'] + office11_fixture('09110000001'));
        t_eq('calls.php?saved=1', $r['redirect'], 'add redirects with flag');
        $st = $pdo->prepare('SELECT * FROM office_calls WHERE phone = ? LIMIT 1');
        $st->execute(['09110000001']);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted');
        t_eq('QA-AD-1', (string)$row['ad_id'], 'file linked');
        t_eq('2026-10-20 10:15:00', (string)$row['called_at'], 'datetime combined');
        t_eq(5, (int)$row['duration'], 'duration minutes stored');
        t_eq('in', (string)$row['direction'], 'direction stored');
        t_ok((int)$row['created_by'] > 0, 'created_by stamped');

        $noAd = office11_fixture('09110000002');
        $noAd['ad_id'] = '';
        $r = office11_post($pdo, [], ['action' => 'save'] + $noAd);
        t_eq('calls.php?saved=1', $r['redirect'], 'call without file accepted');
        office11_clean($pdo);
    },

    'stage11 add validates input' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);

        $bad = office11_fixture('09110000001');
        $bad['ad_id'] = 'NO-SUCH-AD';
        $r = office11_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'فایلی با این کد یافت نشد'), 'unknown ad rejected');

        $bad = office11_fixture('09110000001');
        $bad['duration'] = 'پنج';
        $r = office11_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'مدت تماس باید عدد'), 'bad duration rejected');

        $bad = office11_fixture('09110000001');
        $bad['call_time'] = '25:99';
        $r = office11_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'تاریخ و ساعت'), 'bad datetime rejected');

        t_eq(0, office11_count($pdo), 'invalid adds insert nothing');
        office11_clean($pdo);
    },

    'stage11 list searches and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        office11_make($pdo, '09110000001', ['name' => 'OF11 یکم', 'direction' => 'in', 'call_date' => '2026-10-20']);
        office11_make($pdo, '09110000002', ['name' => 'OF11 دوم', 'direction' => 'out', 'call_date' => '2026-10-21']);

        $r = office11_get($pdo);
        t_ok(str_contains($r['output'], 'OF11 یکم') && str_contains($r['output'], 'OF11 دوم'), 'list shows both');
        t_ok(str_contains($r['output'], 'آپارتمان ۸۵ متری سعادت‌آباد'), 'file title shown');
        t_ok(str_contains($r['output'], '۵ دقیقه'), 'duration shown fa');

        $r = office11_get($pdo, ['dir' => 'out']);
        t_ok(str_contains($r['output'], 'OF11 دوم'), 'dir filter keeps out');
        t_ok(!str_contains($r['output'], 'OF11 یکم'), 'dir filter hides in');

        $r = office11_get($pdo, ['day' => '2026-10-21']);
        t_ok(str_contains($r['output'], 'OF11 دوم'), 'day filter keeps match');
        t_ok(!str_contains($r['output'], 'OF11 یکم'), 'day filter hides other day');

        $r = office11_get($pdo, ['dir' => 'bogus', 'day' => 'xx']);
        t_ok(str_contains($r['output'], 'OF11 یکم'), 'bad filters ignored');

        $r = office11_get($pdo, ['q' => 'OF11 دوم']);
        t_ok(str_contains($r['output'], 'OF11 دوم'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF11 یکم'), 'search misses others');
        office11_clean($pdo);
    },

    'stage11 edit prefills and updates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        $id = office11_make($pdo);

        $r = office11_get($pdo, ['edit' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF11 تماس‌گیرنده"'), 'name prefilled');
        t_ok(str_contains($r['output'], 'value="2026-10-20"'), 'date prefilled');
        t_ok(str_contains($r['output'], 'value="10:15"'), 'time prefilled');
        t_ok(str_contains($r['output'], 'ویرایش تماس'), 'edit mode title');

        $post = office11_fixture('09110000001');
        $post['direction'] = 'out';
        $post['note'] = 'OF11 شرح ویراسته';
        $r = office11_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $post);
        t_eq('calls.php?saved=1&edit=' . $id, $r['redirect'], 'edit redirects with flag');
        $row = office11_row($pdo, $id);
        t_eq('out', (string)$row['direction'], 'direction updated');
        t_eq('OF11 شرح ویراسته', (string)$row['note'], 'note updated');

        $r = office11_get($pdo, ['edit' => '999999999']);
        t_ok(str_contains($r['output'], 'تماس یافت نشد'), 'unknown edit id panel');
        office11_clean($pdo);
    },

    'stage11 failed POST re-renders sticky' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        $bad = office11_fixture('09110000001');
        $bad['name'] = '';
        $bad['duration'] = '7';
        $r = office11_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'value="7"'), 'duration sticky');
        t_ok(str_contains($r['output'], 'value="2026-10-20"'), 'date sticky');
        t_ok(str_contains($r['output'], 'لطفاً نام تماس‌گیرنده'), 'error shown');
        office11_clean($pdo);
    },

    'stage11 delete works' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office11_clean($pdo);
        $id = office11_make($pdo);
        $r = office11_post($pdo, [], ['action' => 'delete', 'id' => $id]);
        t_eq('calls.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office11_row($pdo, $id) === null, 'row deleted');
        office11_clean($pdo);
    },
];
