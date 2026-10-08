<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — گزارش‌ها (فقط خواندن، مرحله ۱۳)
 *--------------------------------------------------------------------------
 * نمای تجمیعی همه ماژول‌ها: شمارش‌های تفکیکی + دو لیست هشدار
 * (سررسیدگذشته‌ها و بازدیدهای ۷ روز آینده). هیچ جدولی ساخته/تغییر نمی‌کند.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_visits.php';
require_once __DIR__ . '/_followups.php';
require_once __DIR__ . '/_parts.php';

if (!function_exists('office_rep_group')) {
    /** @return array<string,int> */
    function office_rep_group(PDO $pdo, string $table, string $column, string $extra = '', array $params = []): array
    {
        $out = [];
        $table = preg_replace('/[^a-z_]/', '', $table);
        $column = preg_replace('/[^a-z_]/', '', $column);
        try {
            $st = $pdo->prepare("SELECT `$column` AS k, COUNT(*) AS n FROM `$table` $extra GROUP BY `$column`");
            $st->execute($params);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $out[(string)($r['k'] ?? '')] = (int)$r['n'];
            }
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_rep_overview')) {
    /**
     * همه شمارش‌ها در یک آرایه.
     * @return array<string,mixed>
     */
    function office_rep_overview(PDO $pdo): array
    {
        office_ensure_tables();
        if (function_exists('melkinoEnsurePartnershipSchema')) {
            try {
                melkinoEnsurePartnershipSchema($pdo);
            } catch (Throwable $e) {
            }
        }
        $followOpen = 0;
        $followDone = 0;
        try {
            $followOpen = (int)$pdo->query('SELECT COUNT(*) FROM office_followups WHERE done = 0')->fetchColumn();
            $followDone = (int)$pdo->query('SELECT COUNT(*) FROM office_followups WHERE done = 1')->fetchColumn();
        } catch (Throwable $e) {
        }
        $calls30 = office_rep_group($pdo, 'office_calls', 'direction', 'WHERE called_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $upcoming = [];
        $overdue = [];
        try {
            $st = $pdo->prepare("SELECT v.id, v.ad_id, v.name, v.phone, v.visit_at, a.title AS ad_title
                FROM office_visits v LEFT JOIN ads a ON a.id = v.ad_id
                WHERE v.status = 'scheduled' AND v.visit_at >= NOW() AND v.visit_at < DATE_ADD(NOW(), INTERVAL 8 DAY)
                ORDER BY v.visit_at ASC LIMIT 10");
            $st->execute();
            $upcoming = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
        }
        $today = date('Y-m-d');
        try {
            $st = $pdo->prepare('SELECT id, entity, entity_id, title, due_date FROM office_followups
                WHERE done = 0 AND due_date IS NOT NULL AND due_date < ? ORDER BY due_date ASC LIMIT 10');
            $st->execute([$today]);
            $overdue = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
        }
        return [
            'files_total' => office_count_all('ads'),
            'files_by_status' => office_rep_group($pdo, 'ads', 'status'),
            'files_by_tx' => office_rep_group($pdo, 'ads', 'transaction_type'),
            'parts_by_status' => office_part_counts($pdo),
            'cust_total' => office_count_all('office_customers'),
            'cust_by_kind' => office_rep_group($pdo, 'office_customers', 'kind'),
            'req_by_status' => office_rep_group($pdo, 'office_requests', 'status'),
            'visit_by_status' => office_rep_group($pdo, 'office_visits', 'status'),
            'calls30' => $calls30,
            'follow_open' => $followOpen,
            'follow_done' => $followDone,
            'upcoming' => $upcoming,
            'overdue' => $overdue,
        ];
    }
}
