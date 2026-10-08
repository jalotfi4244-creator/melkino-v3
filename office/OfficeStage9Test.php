<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-9 tests: requests.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage9
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_requests.php';

if (!function_exists('office9_clean')) {
    function office9_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM office_requests WHERE phone LIKE '0919%'");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("DELETE FROM office_customers WHERE phone LIKE '0919%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office9_fixture')) {
    /** @return array<string,string> */
    function office9_fixture(string $phone = '09190000001'): array
    {
        return [
            'customer_id' => '0',
            'name' => 'OF9 متقاضی',
            'phone' => $phone,
            'kind' => 'buy',
            'budget' => '5000000000',
            'min_area' => '100',
            'neighborhood' => 'OF9 شاهرود',
            'description' => 'OF9 آپارتمان دوخوابه',
            'status' => 'new',
        ];
    }
}

if (!function_exists('office9_make')) {
    function office9_make(PDO $pdo, string $phone = '09190000001', array $over = []): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_req_save($pdo, array_merge(office9_fixture($phone), $over), $adminId);
        if ($id === null) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office9_make_customer')) {
    function office9_make_customer(PDO $pdo, string $phone): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_cust_save($pdo, [
            'name' => 'OF9 مشتری لینک', 'phone' => $phone, 'kind' => 'buyer',
            'budget' => '', 'min_area' => '', 'neighborhood' => '', 'notes' => '',
        ], $adminId);
        if ($id === null) {
            throw new RuntimeException('customer fixture failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office9_get')) {
    /** @return array{output:string,redirect:string} */
    function office9_get(PDO $pdo, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page('requests.php');
    }
}

if (!function_exists('office9_post')) {
    /** @return array{output:string,redirect:string} */
    function office9_post(PDO $pdo, array $get, array $post): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('requests.php');
    }
}

if (!function_exists('office9_row')) {
    /** @return array<string,mixed>|null */
    function office9_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM office_requests WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office9_count')) {
    function office9_count(PDO $pdo): int
    {
        return (int)$pdo->query("SELECT COUNT(*) FROM office_requests WHERE phone LIKE '0919%'")->fetchColumn();
    }
}

return [
    'stage9 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage9 requests requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $id = office9_make($pdo);

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('requests.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        foreach ([
            ['action' => 'save'] + office9_fixture('09190000009'),
            ['action' => 'delete', 'id' => $id],
        ] as $post) {
            office_test_reset();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post + ['csrf_token' => 'x'];
            $r = office_test_run_page('requests.php');
            t_eq('login.php', $r['redirect'], 'guest action redirects: ' . $post['action']);
        }
        t_eq(1, office9_count($pdo), 'guest POSTs change nothing');
        office9_clean($pdo);
    },

    'stage9 add stores grouped budget raw with link' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $cid = office9_make_customer($pdo, '09190000011');
        $post = office9_fixture('09190000001');
        $post['budget'] = '5,000,000,000';
        $post['customer_id'] = (string)$cid;
        $r = office9_post($pdo, [], ['action' => 'save'] + $post);
        t_eq('requests.php?saved=1', $r['redirect'], 'add redirects with flag');
        $st = $pdo->prepare('SELECT * FROM office_requests WHERE phone = ? LIMIT 1');
        $st->execute(['09190000001']);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted');
        t_eq('5000000000', (string)$row['budget'], 'budget stored raw');
        t_eq($cid, (int)$row['customer_id'], 'customer linked');
        t_eq('new', (string)$row['status'], 'status default');
        office9_clean($pdo);
    },

    'stage9 add copies identity from linked customer' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $cid = office9_make_customer($pdo, '09190000011');
        $post = office9_fixture('09190000011');
        $post['name'] = '';
        $post['phone'] = '';
        $post['customer_id'] = (string)$cid;
        office9_post($pdo, [], ['action' => 'save'] + $post);
        $st = $pdo->prepare('SELECT * FROM office_requests WHERE customer_id = ? LIMIT 1');
        $st->execute([$cid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted with empty identity');
        t_eq('OF9 مشتری لینک', (string)$row['name'], 'name copied from customer');
        t_eq('09190000011', (string)$row['phone'], 'phone copied from customer');
        office9_clean($pdo);
    },

    'stage9 add validates input' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);

        $bad = office9_fixture('09190000001');
        $bad['name'] = '';
        $r = office9_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'نام متقاضی'), 'empty name rejected');

        $bad = office9_fixture('12');
        $r = office9_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'تماس معتبر'), 'bad phone rejected');

        t_eq(0, office9_count($pdo), 'invalid adds insert nothing');
        office9_clean($pdo);
    },

    'stage9 list searches and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        office9_make($pdo, '09190000001', ['name' => 'OF9 خریدار یکم', 'kind' => 'buy', 'status' => 'new']);
        office9_make($pdo, '09190000002', ['name' => 'OF9 مستأجر دوم', 'kind' => 'rent', 'status' => 'done']);

        $r = office9_get($pdo);
        t_ok(str_contains($r['output'], 'OF9 خریدار یکم') && str_contains($r['output'], 'OF9 مستأجر دوم'), 'list shows both');
        t_ok(str_contains($r['output'], '5,000,000,000'), 'budget displayed grouped');

        $r = office9_get($pdo, ['kind' => 'rent']);
        t_ok(str_contains($r['output'], 'OF9 مستأجر دوم'), 'kind filter keeps rent');
        t_ok(!str_contains($r['output'], 'OF9 خریدار یکم'), 'kind filter hides buy');

        $r = office9_get($pdo, ['status' => 'done']);
        t_ok(str_contains($r['output'], 'OF9 مستأجر دوم'), 'status filter keeps done');
        t_ok(!str_contains($r['output'], 'OF9 خریدار یکم'), 'status filter hides new');

        $r = office9_get($pdo, ['status' => 'bogus', 'kind' => 'bogus']);
        t_ok(str_contains($r['output'], 'OF9 خریدار یکم'), 'bad filters ignored');

        $r = office9_get($pdo, ['q' => 'مستأجر دوم']);
        t_ok(str_contains($r['output'], 'OF9 مستأجر دوم'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF9 خریدار یکم'), 'search misses others');
        office9_clean($pdo);
    },

    'stage9 edit prefills and updates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $id = office9_make($pdo);

        $r = office9_get($pdo, ['edit' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF9 متقاضی"'), 'name prefilled');
        t_ok(str_contains($r['output'], 'value="5,000,000,000"'), 'budget prefilled grouped');
        t_ok(str_contains($r['output'], 'ویرایش درخواست'), 'edit mode title');

        $post = office9_fixture('09190000001');
        $post['description'] = 'OF9 شرح ویراسته';
        $post['status'] = 'contacted';
        $r = office9_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $post);
        t_eq('requests.php?saved=1&edit=' . $id, $r['redirect'], 'edit redirects with flag');
        $row = office9_row($pdo, $id);
        t_eq('OF9 شرح ویراسته', (string)$row['description'], 'description updated');
        t_eq('contacted', (string)$row['status'], 'status updated');
        t_ok((string)($row['updated_at'] ?? '') !== '', 'updated_at stamped');

        $bad = office9_fixture('09190000001');
        $bad['status'] = 'bogus';
        office9_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $bad);
        t_eq('contacted', (string)(office9_row($pdo, $id)['status'] ?? ''), 'bad status keeps old');

        $r = office9_get($pdo, ['edit' => '999999999']);
        t_ok(str_contains($r['output'], 'درخواست یافت نشد'), 'unknown edit id panel');
        office9_clean($pdo);
    },

    'stage9 failed POST re-renders sticky grouped' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $bad = office9_fixture('09190000001');
        $bad['name'] = '';
        $bad['budget'] = '5000000000';
        $r = office9_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'value="5,000,000,000"'), 'budget sticky grouped');
        t_ok(str_contains($r['output'], 'data-money="1"'), 'live-format hook present');
        office9_clean($pdo);
    },

    'stage9 delete works' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office9_clean($pdo);
        $id = office9_make($pdo);
        $r = office9_post($pdo, [], ['action' => 'delete', 'id' => $id]);
        t_eq('requests.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office9_row($pdo, $id) === null, 'row deleted');
        office9_clean($pdo);
    },
];
