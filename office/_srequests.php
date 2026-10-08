<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه درخواست‌های سایت + تطبیق‌ها (مرحله ۲۸)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ تب درخواست‌های پنل سایت (AdminTabController + RequestRepository
 * + MatchRepository): آمار، فهرست+فیلتر، جزئیات با فهرست تطبیق‌ها، تغییر وضعیت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_sr_statuses')) {
    /** @return array<string,string> */
    function office_sr_statuses(): array
    {
        return ['new' => 'جدید', 'tracking' => 'در حال پیگیری', 'done' => 'انجام‌شده', 'cancelled' => 'لغوشده'];
    }
}

if (!function_exists('office_sr_stats')) {
    /** @return array{total:int,new_count:int,tracking_count:int,matched:int,matches:int} */
    function office_sr_stats(PDO $pdo): array
    {
        $out = ['total' => 0, 'new_count' => 0, 'tracking_count' => 0, 'matched' => 0, 'matches' => 0];
        try {
            $out['total'] = (int)$pdo->query('SELECT COUNT(*) FROM property_requests')->fetchColumn();
        } catch (Throwable $e) {
            return $out;
        }
        try {
            $row = $pdo->query(
                "SELECT
                    COALESCE(SUM(status IS NULL OR status = '' OR status = 'new'), 0) AS c_new,
                    COALESCE(SUM(status = 'tracking'), 0) AS c_tracking
                 FROM property_requests"
            )->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                $out['new_count'] = (int)($row['c_new'] ?? 0);
                $out['tracking_count'] = (int)($row['c_tracking'] ?? 0);
            }
        } catch (Throwable $e) {
        }
        try {
            $out['matched'] = (int)$pdo->query('SELECT COUNT(DISTINCT request_id) FROM request_matches')->fetchColumn();
            $out['matches'] = (int)$pdo->query('SELECT COUNT(*) FROM request_matches')->fetchColumn();
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_sr_list')) {
    /** @return array{0:array<int,array>,1:int} [rows,total] عین paginateForAdmin. */
    function office_sr_list(PDO $pdo, array $filters, int $page = 1, int $perPage = 20): array
    {
        $w = [];
        $p = [];
        foreach (['status' => 'status', 'transaction_type' => 'transaction_type', 'property_type' => 'property_type'] as $k => $col) {
            if (!empty($filters[$k])) {
                $w[] = "$col = ?";
                $p[] = (string)$filters[$k];
            }
        }
        if (!empty($filters['q'])) {
            $w[] = '(tracking_code LIKE ? OR phone LIKE ? OR location LIKE ? OR last_name LIKE ?)';
            $q = '%' . (string)$filters['q'] . '%';
            array_push($p, $q, $q, $q, $q);
        }
        $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM property_requests $where");
            $st->execute($p);
            $total = (int)$st->fetchColumn();
            $page = max(1, $page);
            $perPage = min(100, max(1, $perPage));
            $offset = ($page - 1) * $perPage;
            $st = $pdo->prepare("SELECT * FROM property_requests $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
            $st->execute($p);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_sr_get')) {
    function office_sr_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM property_requests WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_sr_set_status')) {
    /** @return array{0:bool,1:string} */
    function office_sr_set_status(PDO $pdo, int $id, string $status): array
    {
        $status = substr($status, 0, 32);
        if ($id <= 0 || !array_key_exists($status, office_sr_statuses())) {
            return [false, 'وضعیت نامعتبر است.'];
        }
        try {
            $st = $pdo->prepare('UPDATE property_requests SET status = ?, updated_at = NOW() WHERE id = ?');
            $st->execute([$status, $id]);
            if ($st->rowCount() === 0 && !office_sr_get($pdo, $id)) {
                return [false, 'درخواست پیدا نشد.'];
            }
            return [true, 'وضعیت درخواست به‌روزرسانی شد.'];
        } catch (Throwable $e) {
            return [false, 'به‌روزرسانی وضعیت ناموفق بود.'];
        }
    }
}

if (!function_exists('office_sr_matches')) {
    /** @return array<int,array> عین MatchRepository::forRequest. */
    function office_sr_matches(PDO $pdo, int $requestId, int $limit = 100): array
    {
        if ($requestId <= 0) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        try {
            $st = $pdo->prepare(
                'SELECT m.*, a.title, a.transaction_type, a.property_type, a.location, a.area, a.rooms,
                        a.price_sell, a.deposit, a.rent_monthly, a.total_price, a.price_hidden, a.status AS ad_status
                 FROM request_matches m INNER JOIN ads a ON a.id = m.ad_id
                 WHERE m.request_id = ? ORDER BY m.match_percent DESC LIMIT ' . $limit
            );
            $st->execute([$requestId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
