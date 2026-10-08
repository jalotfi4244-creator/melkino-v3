<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-8 tests: customers.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage8
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_customers.php';

if (!function_exists('office8_clean')) {
    function office8_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM office_customers WHERE phone LIKE '0918%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office8_fixture')) {
    /** @return array<string,string> */
    function office8_fixture(string $phone = '09180000001'): array
    {
        return [
            'name' => 'OF8 مشتری',
            'phone' => $phone,
            'kind' => 'buyer',
            'budget' => '2800000000',
            'min_area' => '85',
            'neighborhood' => 'OF8 سعادت‌آباد',
            'notes' => 'OF8 یادداشت',
        ];
    }
}

if (!function_exists('office8_make')) {
    function office8_make(PDO $pdo, string $phone = '09180000001', array $over = []): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_cust_save($pdo, array_merge(office8_fixture($phone), $over), $adminId);
        if ($id === null) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office8_get')) {
    /** @return array{output:string,redirect:string} */
    function office8_get(PDO $pdo, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page('customers.php');
    }
}

if (!function_exists('office8_post')) {
    /** @return array{output:string,redirect:string} */
    function office8_post(PDO $pdo, array $get, array $post): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('customers.php');
    }
}

if (!function_exists('office8_row')) {
    /** @return array<string,mixed>|null */
    function office8_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM office_customers WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office8_count')) {
    function office8_count(PDO $pdo): int
    {
        return (int)$pdo->query("SELECT COUNT(*) FROM office_customers WHERE phone LIKE '0918%'")->fetchColumn();
    }
}

return [
    'stage8 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage8 customers requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $id = office8_make($pdo);

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('customers.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        foreach ([
            ['action' => 'save'] + office8_fixture('09180000009'),
            ['action' => 'delete', 'id' => $id],
            ['action' => 'toggle', 'id' => $id],
        ] as $post) {
            office_test_reset();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post + ['csrf_token' => 'x'];
            $r = office_test_run_page('customers.php');
            t_eq('login.php', $r['redirect'], 'guest action redirects: ' . $post['action']);
        }
        t_eq(1, office8_count($pdo), 'guest POSTs change nothing');
        t_eq(1, (int)(office8_row($pdo, $id)['is_active'] ?? 0), 'guest toggle changes nothing');
        office8_clean($pdo);
    },

    'stage8 add stores grouped budget as raw digits' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $post = office8_fixture('09180000001');
        $post['budget'] = '2,800,000,000';
        $r = office8_post($pdo, [], ['action' => 'save'] + $post);
        t_eq('customers.php?saved=1', $r['redirect'], 'add redirects with flag');
        $st = $pdo->prepare('SELECT * FROM office_customers WHERE phone = ? LIMIT 1');
        $st->execute(['09180000001']);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted');
        t_eq('2800000000', (string)$row['budget'], 'budget stored raw');
        t_eq('85', (string)$row['min_area'], 'area stored');
        t_eq('buyer', (string)$row['kind'], 'kind stored');
        t_eq(1, (int)$row['is_active'], 'active by default');
        t_ok((int)$row['created_by'] > 0, 'created_by stamped');
        office8_clean($pdo);
    },

    'stage8 add validates input' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);

        $bad = office8_fixture('09180000001');
        $bad['name'] = '';
        $r = office8_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'نام مشتری'), 'empty name rejected');

        $bad = office8_fixture('abc');
        $r = office8_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'تماس معتبر'), 'bad phone rejected');

        $bad = office8_fixture('09180000001');
        $bad['budget'] = 'دو میلیارد';
        $r = office8_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'بودجه باید عدد'), 'bad budget rejected');

        t_eq(0, office8_count($pdo), 'invalid adds insert nothing');
        office8_clean($pdo);
    },

    'stage8 phone is normalized' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $post = office8_fixture('۰۹۱۸ ۰۰۰-۰۰۰۱');
        office8_post($pdo, [], ['action' => 'save'] + $post);
        t_eq(1, (int)$pdo->query("SELECT COUNT(*) FROM office_customers WHERE phone = '09180000001'")->fetchColumn(), 'fa digits + separators normalized');
        office8_clean($pdo);
    },

    'stage8 list searches and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $a = office8_fixture('09180000001');
        $a['name'] = 'OF8 خریدار یکم';
        $adminId = office2_login_session($pdo);
        office_cust_save($pdo, $a, $adminId);
        $b = office8_fixture('09180000002');
        $b['name'] = 'OF8 فروشنده دوم';
        $b['kind'] = 'seller';
        office_cust_save($pdo, $b, $adminId);

        $r = office8_get($pdo);
        t_ok(str_contains($r['output'], 'OF8 خریدار یکم') && str_contains($r['output'], 'OF8 فروشنده دوم'), 'list shows both');
        t_ok(str_contains($r['output'], '2,800,000,000'), 'budget displayed grouped');

        $r = office8_get($pdo, ['kind' => 'seller']);
        t_ok(str_contains($r['output'], 'OF8 فروشنده دوم'), 'kind filter keeps seller');
        t_ok(!str_contains($r['output'], 'OF8 خریدار یکم'), 'kind filter hides buyer');

        $r = office8_get($pdo, ['kind' => 'bogus']);
        t_ok(str_contains($r['output'], 'OF8 خریدار یکم'), 'bad kind ignored');

        $r = office8_get($pdo, ['q' => 'فروشنده دوم']);
        t_ok(str_contains($r['output'], 'OF8 فروشنده دوم'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF8 خریدار یکم'), 'search misses others');
        office8_clean($pdo);
    },

    'stage8 edit prefills and updates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $id = office8_make($pdo);

        $r = office8_get($pdo, ['edit' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF8 مشتری"'), 'name prefilled');
        t_ok(str_contains($r['output'], 'value="2,800,000,000"'), 'budget prefilled grouped');
        t_ok(str_contains($r['output'], 'ویرایش مشتری'), 'edit mode title');

        $post = office8_fixture('09180000001');
        $post['name'] = 'OF8 مشتری ویراسته';
        $post['kind'] = 'investor';
        $r = office8_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $post);
        t_eq('customers.php?saved=1&edit=' . $id, $r['redirect'], 'edit redirects with flag');
        $row = office8_row($pdo, $id);
        t_eq('OF8 مشتری ویراسته', (string)$row['name'], 'name updated');
        t_eq('investor', (string)$row['kind'], 'kind updated');
        t_ok((string)($row['updated_at'] ?? '') !== '', 'updated_at stamped');

        $r = office8_get($pdo, ['edit' => '999999999']);
        t_ok(str_contains($r['output'], 'مشتری یافت نشد'), 'unknown edit id panel');
        office8_clean($pdo);
    },

    'stage8 failed POST re-renders sticky grouped' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $bad = office8_fixture('09180000001');
        $bad['name'] = '';
        $bad['budget'] = '2800000000';
        $r = office8_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'value="2,800,000,000"'), 'budget sticky grouped');
        t_ok(str_contains($r['output'], 'data-money="1"'), 'live-format hook present');
        office8_clean($pdo);
    },

    'stage8 toggle and delete work' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office8_clean($pdo);
        $id = office8_make($pdo);

        office8_post($pdo, [], ['action' => 'toggle', 'id' => $id]);
        t_eq(0, (int)(office8_row($pdo, $id)['is_active'] ?? 1), 'toggle deactivates');
        office8_post($pdo, [], ['action' => 'toggle', 'id' => $id]);
        t_eq(1, (int)(office8_row($pdo, $id)['is_active'] ?? 0), 'toggle reactivates');

        $r = office8_post($pdo, [], ['action' => 'delete', 'id' => $id]);
        t_eq('customers.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office8_row($pdo, $id) === null, 'row deleted');
        office8_clean($pdo);
    },
];
