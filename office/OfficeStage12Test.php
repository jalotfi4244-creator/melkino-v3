<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-12 tests: followups.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage12
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_followups.php';
if (!function_exists('office_test_pdo')) {
    require __DIR__ . '/OfficeStage1Test.php';
}
if (!function_exists('office2_login_session')) {
    require __DIR__ . '/OfficeStage2Test.php';
}

if (!function_exists('office12_clean')) {
    function office12_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM office_followups WHERE title LIKE 'OF12%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office12_fixture')) {
    /** @return array<string,string> */
    function office12_fixture(string $title = 'OF12 پیگیری'): array
    {
        return [
            'title' => $title,
            'entity' => 'ad',
            'entity_id' => 'QA-AD-1',
            'due_date' => '2026-12-01',
            'note' => 'OF12 یادداشت',
        ];
    }
}

if (!function_exists('office12_make')) {
    function office12_make(PDO $pdo, string $title = 'OF12 پیگیری', array $over = []): int
    {
        $adminId = office2_login_session($pdo);
        [$id, $errors] = office_follow_save($pdo, array_merge(office12_fixture($title), $over), $adminId);
        if ($id === null) {
            throw new RuntimeException('fixture insert failed: ' . implode(' / ', $errors));
        }
        return $id;
    }
}

if (!function_exists('office12_get')) {
    /** @return array{output:string,redirect:string} */
    function office12_get(PDO $pdo, array $get = []): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get;
        return office_test_run_page('followups.php');
    }
}

if (!function_exists('office12_post')) {
    /** @return array{output:string,redirect:string} */
    function office12_post(PDO $pdo, array $get, array $post): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $get;
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        return office_test_run_page('followups.php');
    }
}

if (!function_exists('office12_row')) {
    /** @return array<string,mixed>|null */
    function office12_row(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM office_followups WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : null;
    }
}

if (!function_exists('office12_count')) {
    function office12_count(PDO $pdo): int
    {
        return (int)$pdo->query("SELECT COUNT(*) FROM office_followups WHERE title LIKE 'OF12%'")->fetchColumn();
    }
}

return [
    'stage12 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage12 followups requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $id = office12_make($pdo);

        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('followups.php');
        t_eq('login.php', $r['redirect'], 'list redirects guests');

        foreach ([
            ['action' => 'save'] + office12_fixture('OF12 مهمان'),
            ['action' => 'toggle', 'id' => $id],
            ['action' => 'delete', 'id' => $id],
        ] as $post) {
            office_test_reset();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post + ['csrf_token' => 'x'];
            $r = office_test_run_page('followups.php');
            t_eq('login.php', $r['redirect'], 'guest action redirects: ' . $post['action']);
        }
        t_eq(1, office12_count($pdo), 'guest POSTs change nothing');
        t_eq(0, (int)(office12_row($pdo, $id)['done'] ?? 1), 'guest toggle changes nothing');
        office12_clean($pdo);
    },

    'stage12 add stores followup with link' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $r = office12_post($pdo, [], ['action' => 'save'] + office12_fixture('OF12 تماس با مالک'));
        t_eq('followups.php?saved=1', $r['redirect'], 'add redirects with flag');
        $st = $pdo->prepare('SELECT * FROM office_followups WHERE title = ? LIMIT 1');
        $st->execute(['OF12 تماس با مالک']);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        t_ok(is_array($row), 'row inserted');
        t_eq('ad', (string)$row['entity'], 'entity stored');
        t_eq('QA-AD-1', (string)$row['entity_id'], 'link stored');
        t_eq('2026-12-01', (string)$row['due_date'], 'due stored');
        t_eq(0, (int)$row['done'], 'open by default');
        t_ok((int)$row['created_by'] > 0, 'created_by stamped');

        $free = office12_fixture('OF12 متفرقه');
        $free['entity'] = 'other';
        $free['entity_id'] = '';
        $r = office12_post($pdo, [], ['action' => 'save'] + $free);
        t_eq('followups.php?saved=1', $r['redirect'], 'other without link accepted');
        office12_clean($pdo);
    },

    'stage12 add validates input' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);

        $bad = office12_fixture('OF12 بد');
        $bad['title'] = '';
        $r = office12_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'عنوان پیگیری'), 'empty title rejected');

        $bad = office12_fixture('OF12 بد');
        $bad['entity_id'] = 'NO-SUCH-AD';
        $r = office12_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'لینک داده‌شده یافت نشد'), 'bad link rejected');

        $bad = office12_fixture('OF12 بد');
        $bad['due_date'] = '2026-02-30';
        $r = office12_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'سررسید معتبر نیست'), 'bad due rejected');

        t_eq(0, office12_count($pdo), 'invalid adds insert nothing');
        office12_clean($pdo);
    },

    'stage12 list searches and filters' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $open = office12_make($pdo, 'OF12 باز یکم', ['due_date' => '2026-12-01']);
        $late = office12_make($pdo, 'OF12 سررسیدگذشته', ['due_date' => '2026-01-01']);
        $done = office12_make($pdo, 'OF12 انجام‌شده', ['entity' => 'other', 'entity_id' => '']);
        office_follow_toggle($pdo, $done);

        $r = office12_get($pdo, ['done' => '']);
        t_ok(str_contains($r['output'], 'OF12 باز یکم') && str_contains($r['output'], 'OF12 انجام‌شده'), 'all shows both');
        t_ok(str_contains($r['output'], 'آپارتمان ۸۵ متری سعادت‌آباد'), 'link title shown');

        $r = office12_get($pdo);
        t_ok(str_contains($r['output'], 'OF12 باز یکم'), 'default shows open');
        t_ok(!str_contains($r['output'], 'OF12 انجام‌شده'), 'default hides done');

        $r = office12_get($pdo, ['overdue' => '1', 'done' => 'open']);
        t_ok(str_contains($r['output'], 'OF12 سررسیدگذشته'), 'overdue keeps late');
        t_ok(!str_contains($r['output'], 'OF12 باز یکم'), 'overdue hides future');
        t_ok(str_contains($r['output'], 'سررسید گذشته!'), 'overdue badge shown');

        $r = office12_get($pdo, ['entity' => 'other', 'done' => '']);
        t_ok(str_contains($r['output'], 'OF12 انجام‌شده'), 'entity filter keeps other');
        t_ok(!str_contains($r['output'], 'OF12 باز یکم'), 'entity filter hides ad');

        $r = office12_get($pdo, ['q' => 'سررسیدگذشته', 'done' => '']);
        t_ok(str_contains($r['output'], 'OF12 سررسیدگذشته'), 'search hits');
        t_ok(!str_contains($r['output'], 'OF12 باز یکم'), 'search misses others');
        office12_clean($pdo);
    },

    'stage12 toggle flips done' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $id = office12_make($pdo);

        $r = office12_post($pdo, [], ['action' => 'toggle', 'id' => $id]);
        t_ok(str_contains($r['output'], 'انجام شد'), 'done flash shown');
        t_eq(1, (int)(office12_row($pdo, $id)['done'] ?? 0), 'marked done');

        office12_post($pdo, [], ['action' => 'toggle', 'id' => $id]);
        t_eq(0, (int)(office12_row($pdo, $id)['done'] ?? 1), 'reopened');
        office12_clean($pdo);
    },

    'stage12 edit prefills and updates' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $id = office12_make($pdo);

        $r = office12_get($pdo, ['edit' => (string)$id]);
        t_ok(str_contains($r['output'], 'value="OF12 پیگیری"'), 'title prefilled');
        t_ok(str_contains($r['output'], 'value="2026-12-01"'), 'due prefilled');
        t_ok(str_contains($r['output'], 'ویرایش پیگیری'), 'edit mode title');

        $post = office12_fixture('OF12 ویراسته');
        $post['due_date'] = '2026-12-15';
        $r = office12_post($pdo, ['edit' => (string)$id], ['action' => 'save', 'id' => $id] + $post);
        t_eq('followups.php?saved=1&edit=' . $id, $r['redirect'], 'edit redirects with flag');
        $row = office12_row($pdo, $id);
        t_eq('OF12 ویراسته', (string)$row['title'], 'title updated');
        t_eq('2026-12-15', (string)$row['due_date'], 'due updated');

        $r = office12_get($pdo, ['edit' => '999999999']);
        t_ok(str_contains($r['output'], 'پیگیری یافت نشد'), 'unknown edit id panel');
        office12_clean($pdo);
    },

    'stage12 failed POST re-renders sticky' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $bad = office12_fixture('OF12 چسبنده');
        $bad['entity_id'] = 'NO-SUCH-AD';
        $r = office12_post($pdo, [], ['action' => 'save'] + $bad);
        t_ok(str_contains($r['output'], 'value="OF12 چسبنده"'), 'title sticky');
        t_ok(str_contains($r['output'], 'value="NO-SUCH-AD"'), 'link sticky');
        t_ok(str_contains($r['output'], 'لینک داده‌شده یافت نشد'), 'error shown');
        office12_clean($pdo);
    },

    'stage12 delete works' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office12_clean($pdo);
        $id = office12_make($pdo);
        $r = office12_post($pdo, [], ['action' => 'delete', 'id' => $id]);
        t_eq('followups.php?deleted=1', $r['redirect'], 'delete redirects with flag');
        t_ok(office12_row($pdo, $id) === null, 'row deleted');
        office12_clean($pdo);
    },
];
