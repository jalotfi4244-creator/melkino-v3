<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه برنامه پیامک (مرحله ۳۳)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-sms-api.php سایت: وضعیت، تنظیمات اهداف، اجرای
 * دستی (تیک)، اعتبار، تست ارسال، صندوق خروجی، لغو عضویت‌ها و
 * جست‌وجوهای ذخیره‌شده.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_sms.php';

$__ofSpF = dirname(__DIR__) . '/sms-program.php';
if (is_file($__ofSpF)) {
    require_once $__ofSpF;
}
unset($__ofSpF);
if (!function_exists('smsProgramSettings')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/sms-program.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_sp_boot')) {
    function office_sp_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
        if (function_exists('smsProgramEnsureSchema')) {
            try {
                smsProgramEnsureSchema($pdo);
            } catch (Throwable $e) {
            }
        }
    }
}

if (!function_exists('office_sp_status')) {
    /** @return array{cfg:array,sms:array,counts:array} */
    function office_sp_status(PDO $pdo): array
    {
        $cfg = smsProgramSettings($pdo);
        $sms = smsEffectiveSettings();
        $pendingAds = 0;
        try {
            $pendingAds = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE status IN ('pending','new','waiting')")->fetchColumn();
        } catch (Throwable $e) {
        }
        $pendingReqs = 0;
        try {
            $pendingReqs = (int)$pdo->query("SELECT COUNT(*) FROM property_requests WHERE status = 'new'")->fetchColumn();
        } catch (Throwable $e) {
        }
        $counts = ['queued' => 0, 'sent' => 0, 'failed' => 0];
        try {
            foreach ($pdo->query('SELECT status, COUNT(*) c FROM sms_outbox GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
                if (array_key_exists($r['status'], $counts)) {
                    $counts[$r['status']] = (int)$r['c'];
                }
            }
        } catch (Throwable $e) {
        }
        $searchesN = 0;
        $optoutsN = 0;
        try {
            $searchesN = (int)$pdo->query('SELECT COUNT(*) FROM saved_searches')->fetchColumn();
        } catch (Throwable $e) {
        }
        try {
            $optoutsN = (int)$pdo->query('SELECT COUNT(*) FROM sms_optouts')->fetchColumn();
        } catch (Throwable $e) {
        }
        return [
            'cfg' => $cfg,
            'sms' => $sms,
            'counts' => [
                'pending_ads' => $pendingAds,
                'pending_requests' => $pendingReqs,
                'queued' => $counts['queued'],
                'sent' => $counts['sent'],
                'failed' => $counts['failed'],
                'saved_searches' => $searchesN,
                'optouts' => $optoutsN,
            ],
        ];
    }
}

if (!function_exists('office_sp_save')) {
    /** @return array{0:bool,1:string} */
    function office_sp_save(PDO $pdo, array $body): array
    {
        $cfg = smsProgramSettings($pdo);
        $next = [];
        $next['enabled'] = !empty($body['enabled']) ? 1 : 0;
        $next['quiet_start'] = max(0, min(23, (int)($body['quiet_start'] ?? $cfg['quiet_start'])));
        $next['quiet_end'] = max(0, min(23, (int)($body['quiet_end'] ?? $cfg['quiet_end'])));
        $next['lagoo11'] = !empty($body['lagoo11']) ? 1 : 0;
        $next['max_per_tick'] = max(1, min(100, (int)($body['max_per_tick'] ?? $cfg['max_per_tick'])));
        $next['site_url'] = trim((string)($body['site_url'] ?? $cfg['site_url']));
        $next['cron_token'] = trim((string)($body['cron_token'] ?? ''));
        if ($next['cron_token'] === '') {
            $next['cron_token'] = (string)$cfg['cron_token'];
        }
        $cleanTpl = static function ($v, $fallback) {
            $v = trim((string)$v);
            $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? '';
            return $v === '' ? $fallback : mb_substr($v, 0, 600);
        };
        $ss = is_array($body['saved_search'] ?? null) ? $body['saved_search'] : [];
        $next['saved_search'] = [
            'on' => !empty($ss['on']) ? 1 : 0,
            'cap_day' => max(1, min(10, (int)($ss['cap_day'] ?? $cfg['saved_search']['cap_day']))),
            'min_new' => max(1, min(20, (int)($ss['min_new'] ?? $cfg['saved_search']['min_new']))),
            'template' => $cleanTpl($ss['template'] ?? '', $cfg['saved_search']['template']),
            'digest_template' => $cleanTpl($ss['digest_template'] ?? '', $cfg['saved_search']['digest_template']),
        ];
        $rm = is_array($body['request_match'] ?? null) ? $body['request_match'] : [];
        $next['request_match'] = [
            'on' => !empty($rm['on']) ? 1 : 0,
            'cap_day' => max(1, min(10, (int)($rm['cap_day'] ?? $cfg['request_match']['cap_day']))),
            'score_min' => max(0, min(100, (float)($rm['score_min'] ?? $cfg['request_match']['score_min']))),
            'min_new' => max(1, min(20, (int)($rm['min_new'] ?? $cfg['request_match']['min_new']))),
            'template' => $cleanTpl($rm['template'] ?? '', $cfg['request_match']['template']),
        ];
        $aa = is_array($body['admin_alert'] ?? null) ? $body['admin_alert'] : [];
        $aaMode = (($aa['mode'] ?? $cfg['admin_alert']['mode']) === 'every') ? 'every' : 'over';
        $next['admin_alert'] = [
            'on' => !empty($aa['on']) ? 1 : 0,
            'ads_thr' => max(1, (int)($aa['ads_thr'] ?? $cfg['admin_alert']['ads_thr'])),
            'req_thr' => max(1, (int)($aa['req_thr'] ?? $cfg['admin_alert']['req_thr'])),
            'debounce_h' => max(1, (int)($aa['debounce_h'] ?? $cfg['admin_alert']['debounce_h'])),
            'phone' => smsProgramNormPhone((string)($aa['phone'] ?? $cfg['admin_alert']['phone'])),
            'mode' => $aaMode,
            'every_n' => max(1, min(500, (int)($aa['every_n'] ?? $cfg['admin_alert']['every_n'] ?? 20))),
            'every_req_n' => max(1, min(500, (int)($aa['every_req_n'] ?? $cfg['admin_alert']['every_req_n'] ?? 0)) ?: (int)($cfg['admin_alert']['every_n'] ?? 20)),
            'last_ads' => (int)($cfg['admin_alert']['last_ads'] ?? 0),
            'last_reqs' => (int)($cfg['admin_alert']['last_reqs'] ?? 0),
            'template' => $cleanTpl($aa['template'] ?? '', $cfg['admin_alert']['template']),
        ];
        $mk = is_array($body['marketing'] ?? null) ? $body['marketing'] : [];
        $next['marketing'] = ['on' => !empty($mk['on']) ? 1 : 0];
        try {
            smsProgramSaveSettings($pdo, $next);
        } catch (Throwable $e) {
            return [false, 'ذخیره تنظیمات برنامهٔ پیامک ناموفق بود.'];
        }
        return [true, 'تنظیمات برنامهٔ پیامک ذخیره شد.'];
    }
}

if (!function_exists('office_sp_tick')) {
    /** @return array{0:bool,1:string,2:array} */
    function office_sp_tick(PDO $pdo): array
    {
        try {
            $res = smsProgramTick($pdo, true);
        } catch (Throwable $e) {
            return [false, 'اجرای برنامه ناموفق بود.', []];
        }
        $msg = 'اجرا شد: ' . (int)($res['sent'] ?? 0) . ' ارسال، ' . (int)($res['failed'] ?? 0) . ' ناموفق، ' . (int)($res['queued'] ?? 0) . ' در صف';
        if (!empty($res['skipped'])) {
            $msg .= ' (' . (string)$res['skipped'] . ')';
        }
        return [true, $msg, $res];
    }
}

if (!function_exists('office_sp_balance')) {
    /** @return array{0:bool,1:string} */
    function office_sp_balance(): array
    {
        $sms = smsEffectiveSettings();
        if ((string)($sms['provider'] ?? '') !== 'melipayamak' || trim((string)($sms['api_key'] ?? '')) === '' || trim((string)($sms['password'] ?? '')) === '') {
            return [false, 'نام کاربری/رمز پنل پیامک کامل تنظیم نشده است (تب ربات و کانال).'];
        }
        $resp = melkinoHttpPost('https://rest.payamak-panel.com/api/SendSMS/GetBalance', http_build_query([
            'username' => (string)$sms['api_key'],
            'password' => (string)$sms['password'],
        ]));
        $j = is_string($resp) ? json_decode($resp, true) : null;
        if (is_array($j) && isset($j['RetStatus']) && (string)$j['RetStatus'] === '1') {
            return [true, 'اعتبار: ' . (string)($j['Value'] ?? '')];
        }
        $ret = is_array($j) ? (string)($j['RetStatus'] ?? '') : '';
        return [false, $ret !== '' ? melipayamakErrorText($ret) : 'اتصال به سامانه برقرار نشد.'];
    }
}

if (!function_exists('office_sp_test')) {
    /** @return array{0:bool,1:string} */
    function office_sp_test(string $phone): array
    {
        $phone = smsProgramNormPhone($phone);
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return [false, 'شماره موبایل معتبر نیست.'];
        }
        $res = smsSendText($phone, 'تست برنامهٔ پیامک ملکینو ✅');
        return [!empty($res['success']), (string)($res['message'] ?? '')];
    }
}

if (!function_exists('office_sp_outbox')) {
    /** @return array<int,array> */
    function office_sp_outbox(PDO $pdo, string $goal = '', string $status = '', string $phone = ''): array
    {
        $q = smsProgramNormPhone($phone);
        $where = ['1=1'];
        $params = [];
        if ($goal !== '' && $goal !== 'all') {
            $where[] = 'goal = ?';
            $params[] = $goal;
        }
        if ($status !== '' && $status !== 'all') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = 'phone LIKE ?';
            $params[] = $q . '%';
        }
        try {
            $st = $pdo->prepare('SELECT * FROM sms_outbox WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 150');
            $st->execute($params);
            $rows = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
                $meta = json_decode((string)$r['meta_json'], true) ?: [];
                $rows[] = [
                    'id' => (int)$r['id'],
                    'goal' => (string)$r['goal'],
                    'phone' => (string)$r['phone'],
                    'status' => (string)$r['status'],
                    'fail_reason' => (string)($r['fail_reason'] ?? ''),
                    'rec_id' => (string)($r['rec_id'] ?? ''),
                    'ad_id' => (string)($meta['ad_id'] ?? ''),
                    'request_id' => (int)($meta['request_id'] ?? 0),
                    'body' => mb_substr(trim(str_replace("\n", ' | ', (string)$r['body'])), 0, 90),
                    'created_at' => (string)$r['created_at'],
                    'sent_at' => (string)($r['sent_at'] ?? ''),
                ];
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_sp_optout_add')) {
    /** @return array{0:bool,1:string} */
    function office_sp_optout_add(PDO $pdo, string $phone, string $scope = 'all'): array
    {
        $phone = smsProgramNormPhone($phone);
        if (!in_array($scope, ['alerts', 'promo', 'all'], true)) {
            $scope = 'all';
        }
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return [false, 'شماره موبایل معتبر نیست.'];
        }
        try {
            $pdo->prepare('INSERT IGNORE INTO sms_optouts (phone, scope, source) VALUES (?,?,?)')->execute([$phone, $scope, 'admin']);
        } catch (Throwable $e) {
            return [false, 'افزودن به فهرست لغو ناموفق بود.'];
        }
        return [true, 'شماره به فهرست لغو عضویت اضافه شد.'];
    }
}

if (!function_exists('office_sp_optout_del')) {
    function office_sp_optout_del(PDO $pdo, int $id): bool
    {
        try {
            $pdo->prepare('DELETE FROM sms_optouts WHERE id = ?')->execute([$id]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_sp_optouts')) {
    /** @return array<int,array> */
    function office_sp_optouts(PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT * FROM sms_optouts ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_sp_searches')) {
    /** @return array<int,array> */
    function office_sp_searches(PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT s.*, u.name AS user_name FROM saved_searches s LEFT JOIN users u ON u.id = s.user_id ORDER BY s.id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_sp_search_del')) {
    function office_sp_search_del(PDO $pdo, int $id): bool
    {
        try {
            $pdo->prepare('DELETE FROM saved_searches WHERE id = ?')->execute([$id]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
