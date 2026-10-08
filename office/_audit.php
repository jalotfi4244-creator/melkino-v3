<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه گزارش عملکرد ادمین‌ها (مرحله ۲۰)
 *--------------------------------------------------------------------------
 * آینهٔ فقط-خواندنیِ گزارش حسابرسی سایت (AdminAuditController):
 * فیلتر action + صفحه‌بندی ۵۰تایی روی همان جدول melkino_audit_log.
 * هیچ نوشتی انجام نمی‌شود.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_audit_actions')) {
    /** @return string[] */
    function office_audit_actions(PDO $pdo): array
    {
        try {
            $rows = $pdo->query('SELECT DISTINCT action FROM melkino_audit_log ORDER BY action LIMIT 200')
                ->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return array_values(array_filter(array_map('strval', $rows)));
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_audit_list')) {
    /** @return array{0:array<int,array>,1:int} */
    function office_audit_list(PDO $pdo, string $action, int $page, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $where = '';
        $params = [];
        if ($action !== '') {
            $where = 'WHERE action = ?';
            $params[] = $action;
        }
        try {
            $st = $pdo->prepare('SELECT COUNT(*) FROM melkino_audit_log ' . $where);
            $st->execute($params);
            $total = max(0, (int)$st->fetchColumn());
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                'SELECT id, actor_type, actor_id, actor_name, action, entity, entity_id,'
                . ' details, ip_address, created_at FROM melkino_audit_log '
                . $where . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $off
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}
