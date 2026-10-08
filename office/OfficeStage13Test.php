<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-13 tests: reports.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage13
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_reports.php';
if (!function_exists('office_test_pdo')) {
    require __DIR__ . '/OfficeStage1Test.php';
}
if (!function_exists('office2_login_session')) {
    require __DIR__ . '/OfficeStage2Test.php';
}

if (!function_exists('office13_clean')) {
    function office13_clean(PDO $pdo): void
    {
        foreach (['office_customers', 'office_requests', 'office_visits', 'office_calls'] as $t) {
            try {
                $pdo->exec("DELETE FROM $t WHERE phone LIKE '0913%'");
            } catch (Throwable $e) {
            }
        }
        try {
            $pdo->exec("DELETE FROM office_followups WHERE title LIKE 'OF13%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office13_seed')) {
    function office13_seed(PDO $pdo): void
    {
        $adminId = office2_login_session($pdo);
        [$cid, $e1] = office_cust_save($pdo, ['name' => 'OF13 مشتری', 'phone' => '09130000001', 'kind' => 'buyer', 'budget' => '', 'min_area' => '', 'neighborhood' => '', 'notes' => ''], $adminId);
        [$rid, $e2] = office_req_save($pdo, ['customer_id' => '0', 'name' => 'OF13 متقاضی', 'phone' => '09130000002', 'kind' => 'rent', 'budget' => '', 'min_area' => '', 'neighborhood' => '', 'description' => '', 'status' => 'new'], $adminId);
        [$vid, $e3] = office_visit_save($pdo, ['ad_id' => 'QA-AD-1', 'customer_id' => '0', 'name' => 'OF13 بازدیدکننده', 'phone' => '09130000003', 'visit_date' => date('Y-m-d', strtotime('+2 days')), 'visit_time' => '11:00', 'status' => 'scheduled', 'notes' => ''], $adminId);
        [$clid, $e4] = office_call_save($pdo, ['name' => 'OF13 تماس‌گیرنده', 'phone' => '09130000004', 'direction' => 'in', 'ad_id' => '', 'duration' => '3', 'call_date' => date('Y-m-d'), 'call_time' => '09:00', 'note' => ''], $adminId);
        [$fid, $e5] = office_follow_save($pdo, ['title' => 'OF13 سررسیدگذشته', 'entity' => 'ad', 'entity_id' => 'QA-AD-1', 'due_date' => date('Y-m-d', strtotime('-2 days')), 'note' => ''], $adminId);
        if ($cid === null || $rid === null || $vid === null || $clid === null || $fid === null) {
            throw new RuntimeException('seed failed: ' . implode(' / ', array_merge($e1, $e2, $e3, $e4, $e5)));
        }
    }
}

if (!function_exists('office13_get')) {
    /** @return array{output:string,redirect:string} */
    function office13_get(PDO $pdo): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        return office_test_run_page('reports.php');
    }
}

return [
    'stage13 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage13 reports requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('reports.php');
        t_eq('login.php', $r['redirect'], 'redirects guests');
    },

    'stage13 page renders all sections' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $r = office13_get($pdo);
        foreach (['فایل‌ها به تفکیک وضعیت', 'مشارکت‌ها به تفکیک وضعیت', 'مشتریان به تفکیک نوع', 'درخواست‌ها به تفکیک وضعیت', 'بازدیدها به تفکیک وضعیت', 'تماس‌های ۳۰ روز اخیر', 'پیگیری‌ها', 'سررسیدگذشته', '۷ روز آینده'] as $h) {
            t_ok(str_contains($r['output'], $h), 'section: ' . $h);
        }
        t_ok(!str_contains($r['output'], 'method="post"'), 'read-only: no POST forms');
    },

    'stage13 counts reflect fixtures' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office13_clean($pdo);
        $before = office_rep_overview($pdo);
        office13_seed($pdo);
        $after = office_rep_overview($pdo);

        t_eq($before['cust_total'] + 1, $after['cust_total'], 'customer total +1');
        t_eq(($before['cust_by_kind']['buyer'] ?? 0) + 1, $after['cust_by_kind']['buyer'] ?? 0, 'buyer +1');
        t_eq(($before['req_by_status']['new'] ?? 0) + 1, $after['req_by_status']['new'] ?? 0, 'new request +1');
        t_eq(($before['visit_by_status']['scheduled'] ?? 0) + 1, $after['visit_by_status']['scheduled'] ?? 0, 'scheduled visit +1');
        t_eq(($before['calls30']['in'] ?? 0) + 1, $after['calls30']['in'] ?? 0, 'in call +1');
        t_eq($before['follow_open'] + 1, $after['follow_open'], 'open followup +1');

        $r = office13_get($pdo);
        t_ok(str_contains($r['output'], 'OF13 سررسیدگذشته'), 'overdue listed');
        t_ok(str_contains($r['output'], 'OF13 بازدیدکننده'), 'upcoming listed');
        t_ok(str_contains($r['output'], 'آپارتمان ۸۵ متری سعادت‌آباد'), 'upcoming file title shown');
        office13_clean($pdo);
    },

    'stage13 read does not mutate' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office13_clean($pdo);
        office13_seed($pdo);
        $a = office_rep_overview($pdo);
        office13_get($pdo);
        $b = office_rep_overview($pdo);
        t_eq($a['cust_total'], $b['cust_total'], 'stable customers');
        t_eq($a['follow_open'], $b['follow_open'], 'stable followups');
        t_eq(count($a['overdue']), count($b['overdue']), 'stable overdue');
        t_eq(count($a['upcoming']), count($b['upcoming']), 'stable upcoming');
        office13_clean($pdo);
    },
];
