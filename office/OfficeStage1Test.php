<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-1 tests: shell, admin login, dashboard.
 *
 * SAFETY: every DB case runs ONLY when the live connection is the TEST
 * database (SELECT DATABASE() === 'melkino_test'); otherwise it SKIPS
 * (loudly) so production can never be touched by accident.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage1
 * (seed first: .../qa/seed-test.php)
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';

if (!function_exists('office_test_pdo')) {
    /** @return PDO|null null = SKIP (not on test DB) */
    function office_test_pdo(): ?PDO
    {
        global $pdo;
        if (!($pdo instanceof PDO)) {
            return null;
        }
        try {
            if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'melkino_test') {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }
        return $pdo;
    }
}

if (!function_exists('office_test_reset')) {
    function office_test_reset(): void
    {
        unset($GLOBALS['office_test_redirect']);
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_ORIGIN']);
    }
}

if (!function_exists('office_test_run_page')) {
    /** @return array{output:string,headers:string[]} */
    function office_test_run_page(string $file): array
    {
        unset($GLOBALS['office_test_redirect']);
        ob_start();
        require dirname(__DIR__) . '/office/' . $file;
        $out = (string)ob_get_clean();
        return ['output' => $out, 'redirect' => (string)($GLOBALS['office_test_redirect'] ?? '')];
    }
}

if (!function_exists('office_test_login_post')) {
    /** @return array{output:string,headers:string[]} */
    function office_test_login_post(array $post): array
    {
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post;
        if (!isset($_POST['csrf_token'])) {
            $_POST['csrf_token'] = office_csrf();
        }
        return office_test_run_page('login.php');
    }
}

if (!function_exists('office_test_clean_office_fixtures')) {
    /**
     * حذف ردیف‌های تستی ثبت دفتر (مراحل ۲ و ۳) تا شمارش‌های دقیق seed
     * مستقل از تاریخچهٔ اجراهای قبلی باشند. تلفن‌ها همان‌هایی‌اند که
     * در office2_clean_fixtures و office3_clean_fixtures آمده است.
     */
    function office_test_clean_office_fixtures(PDO $pdo): void
    {
        foreach (['09000000001', '09000000002', '09000000003', '0900000011', '0900000012', '0900000013', '0900000014', '0900000015', '09140000001', '09160000001', '09160000002'] as $ph) {
            try {
                $ids = $pdo->query('SELECT id FROM ads WHERE phone = ' . $pdo->quote($ph))->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($ids as $id) {
                    $pdo->prepare('DELETE FROM ad_amenities WHERE ad_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM images WHERE ad_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([$id]);
                }
            } catch (Throwable $e) {
            }
        }
    }
}

return [
    'test db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'office tables exist with expected columns' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_ensure_tables();
        $expect = [
            'office_customers' => ['id', 'name', 'phone', 'kind', 'budget'],
            'office_followups' => ['id', 'entity', 'entity_id', 'title', 'due_date', 'done'],
            'office_calls' => ['id', 'name', 'phone', 'direction', 'called_at'],
            'office_settings' => ['k', 'v'],
        ];
        foreach ($expect as $t => $cols) {
            $have = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($cols as $c) {
                t_ok(in_array($c, $have, true), "$t: missing column $c");
            }
        }
    },

    'helpers: fa digits, numbers, theme' => static function (): void {
        t_eq('۱۲۳', office_fa('123'));
        t_eq('۱,۲۵۰', office_num(1250));
        $_COOKIE = [];
        t_eq('dark', office_theme());
        $_COOKIE = ['office_theme' => 'light'];
        t_eq('light', office_theme());
        $_COOKIE = [];
    },

    'login rejects missing csrf' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['username' => 'qa_admin', 'password' => 'QaTest123456', 'csrf_token' => 'bad'];
        $r = office_test_run_page('login.php');
        t_ok(str_contains($r['output'], 'نامعتبر'), 'expected csrf error');
        t_ok(empty($_SESSION['is_admin']), 'must not log in');
    },

    'login rejects wrong password + audits failure' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM settings WHERE setting_group='security' AND setting_key='admin_attempt_state'");
        $r = office_test_login_post(['username' => 'qa_admin', 'password' => 'WrongPass123456']);
        t_ok(str_contains($r['output'], 'اشتباه'), 'expected bad-credentials error');
        t_ok(empty($_SESSION['is_admin']), 'must not log in');
        $n = (int)$pdo->query("SELECT COUNT(*) FROM admin_login_attempts WHERE username='qa_admin' AND success=0")->fetchColumn();
        t_ok($n >= 1, 'failure must be audited');
    },

    'login rejects empty password' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM settings WHERE setting_group='security' AND setting_key='admin_attempt_state'");
        $r = office_test_login_post(['username' => 'qa_admin', 'password' => '']);
        t_ok(str_contains($r['output'], 'اشتباه'), 'expected error for empty password');
        t_ok(empty($_SESSION['is_admin']), 'must not log in');
    },

    'login locks out after 5 failures' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM settings WHERE setting_group='security' AND setting_key='admin_attempt_state'");
        for ($i = 0; $i < 5; $i++) {
            office_test_login_post(['username' => 'qa_admin', 'password' => 'WrongPass123456']);
        }
        $r = office_test_login_post(['username' => 'qa_admin', 'password' => 'WrongPass123456']);
        t_ok(str_contains($r['output'], 'قفل شده'), 'expected lockout message, got: ' . mb_substr(strip_tags($r['output']), 0, 120));
        // locked state must also block the CORRECT password
        $r2 = office_test_login_post(['username' => 'qa_admin', 'password' => 'QaTest123456']);
        t_ok(str_contains($r2['output'], 'قفل شده'), 'lockout must block correct password too');
        t_ok(empty($_SESSION['is_admin']), 'must not log in while locked');
        $pdo->exec("DELETE FROM settings WHERE setting_group='security' AND setting_key='admin_attempt_state'");
    },

    'login succeeds with correct password' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM settings WHERE setting_group='security' AND setting_key='admin_attempt_state'");
        $r = office_test_login_post(['username' => 'qa_admin', 'password' => 'QaTest123456']);
        t_ok(!empty($_SESSION['is_admin']), 'is_admin must be set');
        t_eq('qa_admin', (string)($_SESSION['admin_username'] ?? ''));
        t_ok(((int)($_SESSION['admin_id'] ?? 0)) > 0, 'admin_id must be set');
        t_eq('index.php', $r['redirect'], 'must redirect to dashboard');
        $n = (int)$pdo->query("SELECT COUNT(*) FROM admin_login_attempts WHERE username='qa_admin' AND success=1")->fetchColumn();
        t_ok($n >= 1, 'success must be audited');
    },

    'dashboard requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $r = office_test_run_page('index.php');
        t_eq('login.php', $r['redirect'], 'must redirect to login');
    },

    'dashboard renders seeded stats' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_clean_office_fixtures($pdo);
        office_test_reset();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = 999;
        $_SESSION['admin_username'] = 'qa_admin';
        $r = office_test_run_page('index.php');
        $out = $r['output'];
        t_ok(str_contains($out, 'داشبورد'), 'missing title');
        t_ok(str_contains($out, 'QA-AD-1'), 'missing seeded ad row');
        // 6 seeded ads → total card shows ۶
        t_ok(str_contains($out, '>' . office_num(6) . '<'), 'total card must show 6');
        t_ok(str_contains($out, 'آپارتمان'), 'missing breakdown');
    },

    'logout clears admin session' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = 999;
        $_SESSION['user_phone'] = '09120000001'; // user-side key must survive
        $r = office_test_run_page('logout.php');
        t_ok(empty($_SESSION['is_admin']), 'is_admin must be cleared');
        t_ok(empty($_SESSION['admin_id']), 'admin_id must be cleared');
        t_eq('09120000001', (string)($_SESSION['user_phone'] ?? ''), 'user session must survive');
        t_eq('login.php?out=1', $r['redirect'], 'must redirect to login');
    },

    'count helpers match fixtures' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_clean_office_fixtures($pdo);
        $by = office_count_groups('ads', 'status');
        t_eq(3, (int)($by['published'] ?? 0));
        t_eq(2, (int)($by['pending'] ?? 0));
        t_eq(1, (int)($by['sold'] ?? 0));
        t_eq(2, office_count_all('property_requests'));
        t_eq(0, office_count_all('no_such_table_xyz'), 'missing table must yield 0, not throw');
    },

    'office_followups CRUD roundtrip' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM office_followups WHERE entity_id='QA-CRUD-1'");
        $pdo->prepare('INSERT INTO office_followups (entity, entity_id, title, note, due_date, done, created_by) VALUES (?,?,?,?,?,?,?)')
            ->execute(['ad', 'QA-CRUD-1', 'تماس تست', 'یادداشت', '2026-10-10', 0, 1]);
        $id = (int)$pdo->lastInsertId();
        t_ok($id > 0, 'insert id');
        $row = $pdo->query("SELECT * FROM office_followups WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        t_eq('تماس تست', (string)$row['title']);
        $pdo->prepare('UPDATE office_followups SET done=1 WHERE id=?')->execute([$id]);
        t_eq(1, (int)$pdo->query("SELECT done FROM office_followups WHERE id=$id")->fetchColumn());
        $pdo->prepare('DELETE FROM office_followups WHERE id=?')->execute([$id]);
        t_eq(0, (int)$pdo->query("SELECT COUNT(*) FROM office_followups WHERE id=$id")->fetchColumn());
    },

    'transaction rollback keeps tables clean' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM office_calls')->fetchColumn();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO office_calls (name, phone, direction, note) VALUES (?,?,?,?)')
            ->execute(['rollback-me', '09000000001', 'out', 'x']);
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM office_calls')->fetchColumn());
        $pdo->rollBack();
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM office_calls')->fetchColumn());
    },

    'duplicate primary key raises, handled' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $pdo->exec("DELETE FROM office_settings WHERE k='qa_dup'");
        $pdo->prepare('INSERT INTO office_settings (k, v) VALUES (?,?)')->execute(['qa_dup', '1']);
        $threw = false;
        try {
            $pdo->prepare('INSERT INTO office_settings (k, v) VALUES (?,?)')->execute(['qa_dup', '2']);
        } catch (PDOException $e) {
            $threw = true;
            t_ok(str_contains($e->getMessage(), 'uplicate'), 'expected duplicate-key error');
        }
        t_ok($threw, 'duplicate insert must throw');
        $pdo->exec("DELETE FROM office_settings WHERE k='qa_dup'");
    },
];
