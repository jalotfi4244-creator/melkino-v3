<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-10 tests: visits.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage10
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_visits.php';
// ترتیب glob باعث می‌شود Stage10 زودتر از Stage1/2 لود شود؛ هلپر مشترک را زود می‌آوریم.
if (!function_exists('office_test_pdo')) {
    require __DIR__ . '/OfficeStage1Test.php';
}
if (!function_exists('office2_login_session')) {
    require __DIR__ . '/OfficeStage2Test.php';
}

if (!function_exists('office10_clean')) {
    function office10_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM office_visits WHERE phone LIKE '09100%'");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("DELETE FROM office_customers WHERE phone LIKE '09100%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office10_fixture')) {
    /** @return array<string,string> */
    function office10_fixture(string $phone = '09100000001'): array
    {
        return [
            'ad_id' => 'QA-AD-1',
            'customer_id' => '0',
            'name' => 'OF10 بازدیدکننده',
            'phone' => $phone,
            'visit_date' => '2026-11-01',
            'visit_time' => '16:30',
            'status' => 'scheduled',
            'notes' => 'OF10 یادداشت',
        ];
    }
}

if (!function_exists('office10_make')) {
    function office10_make(PDO $pdo, string $phone = '09100000001', array $over = []): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_visit_save($pdo, array_merge(office10_fixture($phone), $over), $adminId);
        if ($id === null) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office10_get')) {
    /** @return array{output:string,redirect:string} */
    function office10_get(PDO $pdo, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page('visits.php');
    }
}

if (!function_exists('office10_post')) {
    /** @return array{output:string,redirect:string} */
    function office10_post(PDO $pdo, array $get, array $post): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('visits.php');
    }
}

if (!function_exists('office10_row')) {
    /** @return array<string,mixed>|null */
    function office10_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM office_visits WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office10_count')) {
    function office10_count(PDO $pdo): int
    {
        return (int)$pdo->query("SELECT COUNT(*) FROM office_visits WHERE phone LIKE '09100%'")->fetchColumn();
    }
}

return [
    'stage10 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage10 visits requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        $id = office10_make($pdo);

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('visits.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        foreach ([
            ['action' => 'save'] + office10_fixture('09100000009'),
            ['action' => 'delete', 'id' => $id],
        ] as $post) {
            office_test_reset();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post + ['csrf_token' => 'x'];
            $r = office_test_run_page('visits.php');
            t_eq('login.php', $r['redirect'], 'guest action redirects: ' . $post['action']);
        }
        t_eq(1, office10_count($pdo), 'guest POSTs change nothing');
        office10_clean($pdo);
    },

    'stage10 add stores visit with links' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        $r = office10_post($pdo, [], ['action' => 'save'] + office10_fixture('09100000001'));
        t_eq('visits.php?saved=1', $r['redirect'], 'add redirects with flag');
        $st = $pdo->prepare('SELECT * FROM office_visits WHERE phone = ? LIMIT 1');
        $st->execute(['09100000001']);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted');
        t_eq('QA-AD-1', (string)$row['ad_id'], 'file linked');
        t_eq('2026-11-01 16:30:00', (string)$row['visit_at'], 'datetime combined');
        t_eq('scheduled', (string)$row['status'], 'status stored');
        t_ok((int)$row['created_by'] > 0, 'created_by stamped');
        office10_clean($pdo);
    },

    'stage10 add validates input' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);

        $bad = office10_fixture('09100000001');
        $bad['ad_id'] = 'NO-SUCH-AD';
        $r = office10_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'فایلی با این کد یافت نشد'), 'unknown ad rejected');

        $bad = office10_fixture('09100000001');
        $bad['visit_date'] = '2026-13-99';
        $r = office10_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'تاریخ و ساعت'), 'bad datetime rejected');

        $bad = office10_fixture('09100000001');
        $bad['name'] = '';
        $r = office10_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'نام بازدیدکننده'), 'empty name rejected');

        t_eq(0, office10_count($pdo), 'invalid adds insert nothing');
        office10_clean($pdo);
    },

    'stage10 list searches and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        office10_make($pdo, '09100000001', ['name' => 'OF10 یکم', 'visit_date' => '2026-11-01', 'status' => 'scheduled']);
        office10_make($pdo, '09100000002', ['name' => 'OF10 دوم', 'visit_date' => '2026-11-02', 'status' => 'done']);

        $r = office10_get($pdo);
        t_ok(str_contains($r['output'], 'OF10 یکم') && str_contains($r['output'], 'OF10 دوم'), 'list shows both');
        t_ok(str_contains($r['output'], 'آپارتمان ۸۵ متری سعادت‌آباد'), 'file title shown');
        t_ok(str_contains($r['output'], '2026-11-01 16:30'), 'visit time shown');

        $r = office10_get($pdo, ['status' => 'done']);
        t_ok(str_contains($r['output'], 'OF10 دوم'), 'status filter keeps done');
        t_ok(!str_contains($r['output'], 'OF10 یکم'), 'status filter hides scheduled');

        $r = office10_get($pdo, ['day' => '2026-11-02']);
        t_ok(str_contains($r['output'], 'OF10 دوم'), 'day filter keeps match');
        t_ok(!str_contains($r['output'], 'OF10 یکم'), 'day filter hides other day');

        $r = office10_get($pdo, ['status' => 'bogus', 'day' => 'xx']);
        t_ok(str_contains($r['output'], 'OF10 یکم'), 'bad filters ignored');

        $r = office10_get($pdo, ['q' => 'OF10 دوم']);
        t_ok(str_contains($r['output'], 'OF10 دوم'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF10 یکم'), 'search misses others');
        office10_clean($pdo);
    },

    'stage10 edit prefills and updates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        $id = office10_make($pdo);

        $r = office10_get($pdo, ['edit' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF10 بازدیدکننده"'), 'name prefilled');
        t_ok(str_contains($r['output'], 'value="2026-11-01"'), 'date prefilled');
        t_ok(str_contains($r['output'], 'value="16:30"'), 'time prefilled');
        t_ok(str_contains($r['output'], 'ویرایش بازدید'), 'edit mode title');

        $post = office10_fixture('09100000001');
        $post['visit_time'] = '18:00';
        $post['status'] = 'done';
        $r = office10_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $post);
        t_eq('visits.php?saved=1&edit=' . $id, $r['redirect'], 'edit redirects with flag');
        $row = office10_row($pdo, $id);
        t_eq('2026-11-01 18:00:00', (string)$row['visit_at'], 'time updated');
        t_eq('done', (string)$row['status'], 'status updated');
        t_ok((string)($row['updated_at'] ?? '') !== '', 'updated_at stamped');

        $r = office10_get($pdo, ['edit' => '999999999']);
        t_ok(str_contains($r['output'], 'بازدید یافت نشد'), 'unknown edit id panel');
        office10_clean($pdo);
    },

    'stage10 failed POST re-renders sticky' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        $bad = office10_fixture('09100000001');
        $bad['ad_id'] = 'NO-SUCH-AD';
        $r = office10_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'value="NO-SUCH-AD"'), 'ad sticky');
        t_ok(str_contains($r['output'], 'value="2026-11-01"'), 'date sticky');
        t_ok(str_contains($r['output'], 'value="OF10 بازدیدکننده"'), 'name sticky');
        office10_clean($pdo);
    },

    'stage10 delete works' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office10_clean($pdo);
        $id = office10_make($pdo);
        $r = office10_post($pdo, [], ['action' => 'delete', 'id' => $id]);
        t_eq('visits.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office10_row($pdo, $id) === null, 'row deleted');
        office10_clean($pdo);
    },
];
