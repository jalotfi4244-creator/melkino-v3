<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه لیدها (مرحله ۳۵)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-leads-api.php سایت: بینندگان پرتکرار آگهی‌ها،
 * درخواست‌های دارای تطبیق، لاگ پیامک‌ها و ارسال پیامک تطبیق/یادآوری
 * بازدید (با همان متن‌ها و همان ثبت در lead_sms_log).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_sms.php';

if (!function_exists('office_ld_ensure_log')) {
    function office_ld_ensure_log(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lead_sms_log (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                kind VARCHAR(40) NOT NULL,
                request_id INT NULL,
                ad_id VARCHAR(64) NULL,
                user_id INT NULL,
                phone VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                success TINYINT(1) NOT NULL DEFAULT 0,
                result_message VARCHAR(255) NULL,
                admin_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_kind (kind, created_at),
                KEY idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;
    }
}

if (!function_exists('office_ld_boot')) {
    function office_ld_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
        try {
            office_ld_ensure_log($pdo);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_ld_norm_phone')) {
    function office_ld_norm_phone(string $phone): string
    {
        $map = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];
        $d = strtr($phone, $map);
        $d = preg_replace('/\D+/', '', $d) ?? '';
        if (substr($d, 0, 2) === '98' && strlen($d) >= 12) {
            $d = '0' . substr($d, 2);
        }
        if (strlen($d) === 10 && substr($d, 0, 1) === '9') {
            $d = '0' . $d;
        }
        return $d;
    }
}

if (!function_exists('office_ld_site')) {
    function office_ld_site(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'melkino.infinityfree.me');
        return ($https ? 'https' : 'http') . '://' . $host;
    }
}

if (!function_exists('office_ld_log')) {
    function office_ld_log(PDO $pdo, array $row): void
    {
        office_ld_ensure_log($pdo);
        $pdo->prepare(
            'INSERT INTO lead_sms_log(kind,request_id,ad_id,user_id,phone,message,success,result_message,admin_id) VALUES(?,?,?,?,?,?,?,?,?)'
        )->execute([
            $row['kind'],
            $row['request_id'] ?? null,
            $row['ad_id'] ?? null,
            $row['user_id'] ?? null,
            $row['phone'],
            $row['message'],
            !empty($row['success']) ? 1 : 0,
            $row['result_message'] ?? '',
            isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null,
        ]);
    }
}

if (!function_exists('office_ld_viewers')) {
    /** @return array<int,array> */
    function office_ld_viewers(PDO $pdo, int $min = 3): array
    {
        $min = max(2, min(20, $min));
        $rows = [];
        try {
            $raw = $pdo->query(
                'SELECT ad_id, ad_title, user_id, telegram_id, bale_id, viewed_at
                   FROM ad_views
               ORDER BY id DESC
                  LIMIT 8000'
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $agg = [];
            foreach ($raw as $v) {
                $adId = trim((string)($v['ad_id'] ?? ''));
                if ($adId === '') {
                    continue;
                }
                $uid = (int)($v['user_id'] ?? 0);
                $tg = trim((string)($v['telegram_id'] ?? ''));
                $bale = trim((string)($v['bale_id'] ?? ''));
                $who = $uid > 0 ? ('u:' . $uid) : ($tg !== '' ? ('t:' . $tg) : ($bale !== '' ? ('b:' . $bale) : ''));
                if ($who === '') {
                    continue;
                }
                $key = $adId . '|' . $who;
                if (!isset($agg[$key])) {
                    $agg[$key] = [
                        'ad_id' => $adId,
                        'ad_title' => (string)($v['ad_title'] ?? ''),
                        'user_id' => $uid > 0 ? $uid : null,
                        'telegram_id' => $tg,
                        'bale_id' => $bale,
                        'phone' => '',
                        'name' => '',
                        'views' => 0,
                        'last_view' => (string)($v['viewed_at'] ?? ''),
                    ];
                }
                $agg[$key]['views']++;
                if ((string)($v['viewed_at'] ?? '') > $agg[$key]['last_view']) {
                    $agg[$key]['last_view'] = (string)$v['viewed_at'];
                }
                if ($agg[$key]['ad_title'] === '' && (string)($v['ad_title'] ?? '') !== '') {
                    $agg[$key]['ad_title'] = (string)$v['ad_title'];
                }
            }
            $userCache = [];
            foreach ($agg as &$item) {
                $u = null;
                if (!empty($item['user_id'])) {
                    $k = 'id:' . $item['user_id'];
                    if (!array_key_exists($k, $userCache)) {
                        $st = $pdo->prepare('SELECT id, phone, name FROM users WHERE id=? LIMIT 1');
                        $st->execute([(int)$item['user_id']]);
                        $userCache[$k] = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                    $u = $userCache[$k];
                }
                if (!$u && $item['telegram_id'] !== '') {
                    $k = 'tg:' . $item['telegram_id'];
                    if (!array_key_exists($k, $userCache)) {
                        $st = $pdo->prepare('SELECT id, phone, name FROM users WHERE telegram_id=? LIMIT 1');
                        $st->execute([$item['telegram_id']]);
                        $userCache[$k] = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                    $u = $userCache[$k];
                }
                if (!$u && $item['bale_id'] !== '') {
                    $k = 'bl:' . $item['bale_id'];
                    if (!array_key_exists($k, $userCache)) {
                        $st = $pdo->prepare('SELECT id, phone, name FROM users WHERE bale_id=? LIMIT 1');
                        $st->execute([$item['bale_id']]);
                        $userCache[$k] = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                    $u = $userCache[$k];
                }
                if ($u) {
                    $item['user_id'] = (int)$u['id'];
                    $item['phone'] = (string)($u['phone'] ?? '');
                    $item['name'] = (string)($u['name'] ?? '');
                }
            }
            unset($item);
            $rows = array_values(array_filter($agg, static function ($r) use ($min) {
                return (int)$r['views'] >= $min;
            }));
            usort($rows, static function ($a, $b) {
                $d = ((int)$b['views']) - ((int)$a['views']);
                return $d !== 0 ? $d : strcmp((string)$b['last_view'], (string)$a['last_view']);
            });
            $rows = array_slice($rows, 0, 300);
        } catch (Throwable $e) {
            $rows = [];
        }
        return $rows;
    }
}

if (!function_exists('office_ld_matches')) {
    /** @return array<int,array> */
    function office_ld_matches(PDO $pdo): array
    {
        try {
            $sql = "SELECT pr.id AS request_id, pr.tracking_code, pr.phone, pr.last_name, pr.property_type, pr.transaction_type, pr.location,
                           COUNT(rm.id) AS match_count
                      FROM property_requests pr
                 INNER JOIN request_matches rm ON rm.request_id = pr.id
                  GROUP BY pr.id, pr.tracking_code, pr.phone, pr.last_name, pr.property_type, pr.transaction_type, pr.location
                  ORDER BY match_count DESC, pr.id DESC
                     LIMIT 400";
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_ld_log_rows')) {
    /** @return array<int,array> */
    function office_ld_log_rows(PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT * FROM lead_sms_log ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_ld_sms_matches')) {
    /** @return array{0:bool,1:string} kind = sms_matches|sms_count */
    function office_ld_sms_matches(PDO $pdo, int $reqId, string $kind): array
    {
        if ($reqId <= 0) {
            return [false, 'شناسه درخواست نامعتبر است.'];
        }
        $st = $pdo->prepare('SELECT * FROM property_requests WHERE id=? LIMIT 1');
        $st->execute([$reqId]);
        $req = $st->fetch(PDO::FETCH_ASSOC);
        if (!$req) {
            return [false, 'درخواست پیدا نشد.'];
        }
        $phone = office_ld_norm_phone((string)($req['phone'] ?? ''));
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return [false, 'شماره موبایل درخواست معتبر نیست.'];
        }
        $matches = [];
        try {
            $mst = $pdo->prepare(
                'SELECT rm.ad_id, rm.match_percent, a.title
                   FROM request_matches rm
              LEFT JOIN ads a ON a.id = rm.ad_id
                  WHERE rm.request_id = ?
               ORDER BY rm.match_percent DESC
                  LIMIT 8'
            );
            $mst->execute([$reqId]);
            $matches = $mst->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $mst = $pdo->prepare(
                'SELECT ad_id, match_percent FROM request_matches WHERE request_id = ? ORDER BY match_percent DESC LIMIT 8'
            );
            $mst->execute([$reqId]);
            $matches = $mst->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $nSt = $pdo->prepare('SELECT COUNT(*) FROM request_matches WHERE request_id = ?');
        $nSt->execute([$reqId]);
        $n = (int)$nSt->fetchColumn();
        if ($n === 0) {
            return [false, 'فایل منطبقی برای این درخواست نیست.'];
        }
        $home = office_ld_site();
        $link = $home . '/my-request-matches.php';
        if ($kind === 'sms_count') {
            $text = 'ملکینو: ' . $n . ' فایل مطابق با درخواست شما در سایت قرار گرفت. مشاهده: ' . $link;
        } else {
            $lines = ['ملکینو: فایل‌های منطبق با درخواست شما:'];
            foreach ($matches as $i => $m) {
                $title = trim((string)($m['title'] ?? $m['ad_id']));
                if (function_exists('mb_substr')) {
                    $title = mb_substr($title, 0, 40);
                } else {
                    $title = substr($title, 0, 40);
                }
                $lines[] = ($i + 1) . ') ' . $title;
            }
            $lines[] = $link;
            $text = implode("\n", $lines);
        }
        $send = smsSendText($phone, $text);
        office_ld_log($pdo, [
            'kind' => $kind,
            'request_id' => $reqId,
            'phone' => $phone,
            'message' => $text,
            'success' => !empty($send['success']),
            'result_message' => $send['message'] ?? '',
        ]);
        if (!empty($send['success'])) {
            try {
                $pdo->prepare('UPDATE request_matches SET is_notified=1 WHERE request_id=?')->execute([$reqId]);
            } catch (Throwable $e) {
            }
        }
        return [!empty($send['success']), (string)($send['message'] ?? '')];
    }
}

if (!function_exists('office_ld_sms_viewer')) {
    /** @return array{0:bool,1:string} */
    function office_ld_sms_viewer(PDO $pdo, string $phone, string $adId, string $title, int $userId = 0): array
    {
        $phone = office_ld_norm_phone($phone);
        $adId = trim($adId);
        $title = trim($title !== '' ? $title : $adId);
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return [false, 'شماره موبایل معتبر نیست.'];
        }
        if ($adId === '') {
            return [false, 'آگهی نامعتبر است.'];
        }
        $url = office_ld_site() . '/property-details.php?id=' . rawurlencode($adId);
        $text = 'ملکینو: آگهی «' . (function_exists('mb_substr') ? mb_substr($title, 0, 50) : substr($title, 0, 50)) . '» را چند بار دیده‌اید. مشاهده دوباره: ' . $url;
        $send = smsSendText($phone, $text);
        office_ld_log($pdo, [
            'kind' => 'view_followup',
            'ad_id' => $adId,
            'user_id' => $userId > 0 ? $userId : null,
            'phone' => $phone,
            'message' => $text,
            'success' => !empty($send['success']),
            'result_message' => $send['message'] ?? '',
        ]);
        return [!empty($send['success']), (string)($send['message'] ?? '')];
    }
}
