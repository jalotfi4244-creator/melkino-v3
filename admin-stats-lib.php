<?php
/**
 * Melkino — admin stats library (ADDITIVE, read-only rankings + tiny recorders).
 *
 * Tab «آمار»: همهٔ رنکینگ‌ها تا ۵ نفر با نام، آیدی تلگرام/بله و شماره تماس.
 * جدول‌های موجود (بدون هیچ ALTER): users / ads / ad_views / login_events /
 * saved_searches / property_requests / request_matches / favorites.
 * جدول تازه (خودکار، CREATE IF NOT EXISTS): ad_shares.
 *
 * همهٔ توابع در برابر خطا fail-soft هستند: تب آمار هرگز ۵۰۰ نمی‌دهد.
 */
declare(strict_types=1);

if (!function_exists('melkinoStatsEnsureTables')) {
    function melkinoStatsEnsureTables(?PDO $pdo = null): void
    {
        $pdo = $pdo ?: ($GLOBALS['pdo'] ?? null);
        if (!($pdo instanceof PDO)) {
            return;
        }
        static $done = false;
        if ($done) {
            return;
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS ad_shares ('
                . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . 'user_id INT NULL,'
                . 'ad_id VARCHAR(64) NOT NULL,'
                . 'ad_title VARCHAR(255) NULL,'
                . 'shared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'PRIMARY KEY (id),'
                . 'KEY idx_user (user_id),'
                . 'KEY idx_ad (ad_id),'
                . 'KEY idx_shared (shared_at)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $done = true;
        } catch (Throwable $e) {
            // آمار هرگز صفحه را نمی‌شکند.
        }
    }
}

if (!function_exists('melkinoRecordAdShare')) {
    /**
     * ثبت اشتراک‌گذاری آگهی (از share-log.php). فقط کاربر لاگین‌کرده.
     */
    function melkinoRecordAdShare($userId, string $adId, string $title = '', ?PDO $pdo = null): bool
    {
        $pdo = $pdo ?: ($GLOBALS['pdo'] ?? null);
        $adId = trim($adId);
        $userId = (int)$userId;
        if ($adId === '' || strlen($adId) > 64 || $userId <= 0 || !($pdo instanceof PDO)) {
            return false;
        }
        melkinoStatsEnsureTables($pdo);
        try {
            $pdo->prepare(
                'INSERT INTO ad_shares (user_id, ad_id, ad_title, shared_at) VALUES (?,?,?,NOW())'
            )->execute([$userId, $adId, $title !== '' ? mb_substr($title, 0, 255) : null]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('melkinoStatsResolveSql')) {
    /**
     * قطعهٔ JOIN برای نگاشت ردیف رویداد (user_id/telegram_id/bale_id) به users.id.
     * اولویت: user_id، بعد تلگرام، بعد بله. هر ردیف دقیقاً یک‌بار شمرده می‌شود.
     *
     * @return array{0:string,1:string} [joinSql, uidExpr]
     */
    function melkinoStatsResolveSql(string $alias, bool $hasTg = true, bool $hasBale = true): array
    {
        $joins = "LEFT JOIN users u1 ON u1.id = {$alias}.user_id"
            . " AND {$alias}.user_id IS NOT NULL AND {$alias}.user_id > 0";
        $expr = ['u1.id'];
        if ($hasTg) {
            $joins .= " LEFT JOIN users u2 ON u2.telegram_id = {$alias}.telegram_id"
                . " AND {$alias}.telegram_id IS NOT NULL AND {$alias}.telegram_id <> ''";
            $expr[] = 'u2.id';
        }
        if ($hasBale) {
            $joins .= " LEFT JOIN users u3 ON u3.bale_id = {$alias}.bale_id"
                . " AND {$alias}.bale_id IS NOT NULL AND {$alias}.bale_id <> ''";
            $expr[] = 'u3.id';
        }
        return [$joins, 'COALESCE(' . implode(',', $expr) . ')'];
    }
}

if (!function_exists('melkinoStatsDisplay')) {
    /**
     * نمایش هویت کاربر برای جدول رنکینگ: نام، آیدی تلگرام/بله، شماره تماس.
     * @return array{name:string,handle:string,phone:string}
     */
    function melkinoStatsDisplay(array $u): array
    {
        $first = trim((string)($u['first_name'] ?? ''));
        $last = trim((string)($u['last_name'] ?? ''));
        $name = trim($first . ' ' . $last);
        if ($name === '') {
            $name = trim((string)($u['name'] ?? ''));
        }
        if ($name === '') {
            $name = trim((string)($u['username'] ?? ''));
        }
        if ($name === '') {
            $name = '—';
        }

        $handle = '—';
        $tgu = trim((string)($u['telegram_username'] ?? ''));
        $tg = trim((string)($u['telegram_id'] ?? ''));
        $blu = trim((string)($u['bale_username'] ?? ''));
        $bl = trim((string)($u['bale_id'] ?? ''));
        $etu = trim((string)($u['eitaa_username'] ?? ''));
        $et = trim((string)($u['eitaa_id'] ?? ''));
        if ($tgu !== '') {
            $handle = '@' . ltrim($tgu, '@');
        } elseif ($tg !== '') {
            $handle = 'تلگرام: ' . $tg;
        } elseif ($blu !== '') {
            $handle = '@' . ltrim($blu, '@');
        } elseif ($bl !== '') {
            $handle = 'بله: ' . $bl;
        } elseif ($etu !== '') {
            $handle = '@' . ltrim($etu, '@');
        } elseif ($et !== '') {
            $handle = 'ایتا: ' . $et;
        }

        $phone = trim((string)($u['phone'] ?? ''));
        if ($phone === '') {
            $phone = '—';
        }
        return ['name' => $name, 'handle' => $handle, 'phone' => $phone];
    }
}

if (!function_exists('melkinoStatsTopUsers')) {
    /**
     * اجرای یک کوئری رنکینگ و برگرداندن ردیف‌های نمایشی (حداکثر ۵).
     * @return array<int, array{uid:int,name:string,handle:string,phone:string,c:int,extra:string}>
     */
    function melkinoStatsTopUsers(PDO $pdo, string $sql, int $limit = 5): array
    {
        $out = [];
        try {
            $st = $pdo->query($sql);
            if (!$st) {
                return [];
            }
            $rank = 0;
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($rank >= $limit) {
                    break;
                }
                $rank++;
                $d = melkinoStatsDisplay($row);
                $out[] = [
                    'uid' => (int)($row['uid'] ?? $row['id'] ?? 0),
                    'name' => $d['name'],
                    'handle' => $d['handle'],
                    'phone' => $d['phone'],
                    'c' => (int)($row['c'] ?? 0),
                    'extra' => (string)($row['extra'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
            return [];
        }
        return $out;
    }
}

if (!function_exists('melkinoStatsCount')) {
    function melkinoStatsCount(?PDO $pdo, string $sql): int
    {
        if (!($pdo instanceof PDO)) {
            return 0;
        }
        try {
            $v = $pdo->query($sql);
            return $v ? (int)$v->fetchColumn() : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('melkinoStatsAdBreakdown')) {
    /**
     * تمام ردیف‌های ads، مستقل از وضعیت؛ داده‌های خالی در گروه «نامشخص».
     * یک GROUP BY مشترک، تا جمع سطری/ستونی و کل از یک snapshot باشند.
     * هیچ محدودیت ۵تایی برای دسته‌ها و هیچ تغییر روی دادهٔ آگهی اعمال نمی‌شود.
     */
    function melkinoStatsAdBreakdown(PDO $pdo): array
    {
        $result = [
            'ads_by_tx' => [], 'ads_by_ptype' => [], 'ads_by_type' => [],
            'ads_total' => 0, 'ads_breakdown_error' => false,
        ];
        try {
            $st = $pdo->query(
                "SELECT COALESCE(NULLIF(TRIM(transaction_type), ''), 'نامشخص') AS tx, "
                . "COALESCE(NULLIF(TRIM(property_type), ''), 'نامشخص') AS pt, COUNT(*) AS c "
                . 'FROM ads GROUP BY tx, pt ORDER BY tx ASC, pt ASC'
            );
            if (!$st) {
                throw new RuntimeException('Ads breakdown query failed');
            }
            $byTx = [];
            $byProperty = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tx = (string)$row['tx'];
                $pt = (string)$row['pt'];
                $count = (int)$row['c'];
                $byTx[$tx] = ($byTx[$tx] ?? 0) + $count;
                $byProperty[$pt] = ($byProperty[$pt] ?? 0) + $count;
                $result['ads_by_type'][] = ['tx' => $tx, 'pt' => $pt, 'c' => $count];
                $result['ads_total'] += $count;
            }
            foreach ($byTx as $label => $count) {
                $result['ads_by_tx'][] = ['t' => (string)$label, 'c' => $count];
            }
            foreach ($byProperty as $label => $count) {
                $result['ads_by_ptype'][] = ['t' => (string)$label, 'c' => $count];
            }
            $sort = static function (array $a, array $b): int {
                return ($b['c'] <=> $a['c']) ?: strcmp($a['t'], $b['t']);
            };
            usort($result['ads_by_tx'], $sort);
            usort($result['ads_by_ptype'], $sort);
        } catch (Throwable $e) {
            $result['ads_breakdown_error'] = true;
            error_log('[melkino][stats] Unable to read ads breakdown; SQL state: ' . $e->getCode());
        }
        return $result;
    }
}

if (!function_exists('melkinoStatsDashboard')) {
    /**
     * همهٔ دادهٔ تب «آمار». ورودی null/خطا = داشبورد خالی (نه خطا).
     * @return array{counts:array,rankings:array,top_ads:array,ads_by_tx:array,ads_by_ptype:array,ads_by_type:array,ads_breakdown_error:bool}
     */
    function melkinoStatsDashboard(?PDO $pdo = null): array
    {
        $pdo = $pdo ?: ($GLOBALS['pdo'] ?? null);
        $empty = [
            'counts' => [
                'users_total' => 0, 'users_active' => 0, 'users_inactive' => 0,
                'active_24h' => 0, 'active_30d' => 0, 'new_7d' => 0,
                'ads_total' => 0, 'requests_total' => 0, 'views_total' => 0,
                'shares_total' => 0, 'matches_total' => 0, 'logins_total' => 0,
                'searches_total' => 0, 'favs_total' => 0,
            ],
            'rankings' => [],
            'top_ads' => [],
            'ads_by_tx' => [],
            'ads_by_ptype' => [],
            'ads_by_type' => [],
            'ads_breakdown_error' => true,
        ];
        if (!($pdo instanceof PDO)) {
            return $empty;
        }
        melkinoStatsEnsureTables($pdo);

        $idCols = 'u.first_name,u.last_name,u.name,u.username,'
            . 'u.telegram_username,u.telegram_id,u.bale_username,u.bale_id,'
            . 'u.eitaa_username,u.eitaa_id,u.phone';

        // ---- شمارش‌ها ----
        $counts = [
            'users_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users'),
            'users_active' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users WHERE is_active = 1'),
            'users_inactive' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users WHERE is_active <> 1'),
            'active_24h' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)'),
            'active_30d' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)'),
            'new_7d' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM users WHERE COALESCE(created_at, first_login) > DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'ads_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM ads'),
            'requests_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM property_requests'),
            'views_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM ad_views'),
            'shares_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM ad_shares'),
            'matches_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM request_matches'),
            'logins_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM login_events'),
            'searches_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM saved_searches'),
            'favs_total' => melkinoStatsCount($pdo, 'SELECT COUNT(*) FROM favorites'),
        ];

        // ---- رنکینگ‌ها ----
        [$vj, $vu] = melkinoStatsResolveSql('v');
        [$lj, $lu] = melkinoStatsResolveSql('l');
        [$fj, $fu] = melkinoStatsResolveSql('f', true, false);

        $rankings = [];

        // ۱) بیشترین بازدید آگهی
        $rankings['views'] = [
            'title' => 'پربازدیدترین کاربران (بازدید آگهی)',
            'unit' => 'بازدید',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM ad_views v {$vj} "
                . "JOIN users u ON u.id = {$vu} GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5"),
        ];

        // ۲) بیشترین آگهی ثبت‌شده
        $rankings['ads'] = [
            'title' => 'بیشترین آگهی ثبت‌شده',
            'unit' => 'آگهی',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM ads a "
                . 'JOIN users u ON u.id = a.owner_user_id '
                . 'WHERE a.owner_user_id IS NOT NULL GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۳) بیشترین مرور تکراری (بازدید دوبارهٔ همان آگهی)
        $rankings['repeats'] = [
            'title' => 'بیشترین مرور تکراری آگهی',
            'unit' => 'بازدید تکراری',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},SUM(agg.rep) AS c FROM ("
                . "SELECT r.uid AS uid, COUNT(*) - 1 AS rep FROM ("
                . "SELECT {$vu} AS uid, v.ad_id FROM ad_views v {$vj}"
                . ') r WHERE r.uid IS NOT NULL GROUP BY r.uid, r.ad_id HAVING COUNT(*) > 1'
                . ') agg JOIN users u ON u.id = agg.uid GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۴) بیشترین جستجوی ذخیره‌شده
        $rankings['searches'] = [
            'title' => 'بیشترین جستجو (ذخیره‌شده)',
            'unit' => 'جستجو',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM saved_searches s "
                . 'JOIN users u ON u.id = s.user_id '
                . 'WHERE s.user_id IS NOT NULL GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۵) بیشترین درخواست ملک
        $rankings['requests'] = [
            'title' => 'بیشترین درخواست',
            'unit' => 'درخواست',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM property_requests r "
                . 'JOIN users u ON u.id = r.user_id '
                . 'WHERE r.user_id IS NOT NULL GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۶) بیشترین تطبیق (درخواست↔آگهی)
        $rankings['matches'] = [
            'title' => 'بیشترین تطبیق',
            'unit' => 'تطبیق',
            'rows' => melkinoStatsTopUsers($pdo,
                'SELECT u.id AS uid,' . $idCols . ',COUNT(*) AS c FROM request_matches m '
                . 'JOIN property_requests r ON r.id = m.request_id '
                . 'JOIN users u ON u.id = r.user_id '
                . 'WHERE r.user_id IS NOT NULL GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۷) کمترین تطبیق (کاربران دارای درخواست، به ترتیب صعودی)
        $rankings['least_matches'] = [
            'title' => 'کمترین تطبیق (دارای درخواست)',
            'unit' => 'تطبیق',
            'rows' => melkinoStatsTopUsers($pdo,
                'SELECT u.id AS uid,' . $idCols . ',COUNT(m.id) AS c FROM users u '
                . 'JOIN property_requests r ON r.user_id = u.id '
                . 'LEFT JOIN request_matches m ON m.request_id = r.id '
                . 'GROUP BY u.id ORDER BY c ASC, u.id ASC LIMIT 5'),
        ];

        // ۸) بیشترین اشتراک‌گذاری آگهی
        $rankings['shares'] = [
            'title' => 'بیشترین اشتراک‌گذاری آگهی',
            'unit' => 'اشتراک‌گذاری',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM ad_shares s "
                . 'JOIN users u ON u.id = s.user_id '
                . 'WHERE s.user_id IS NOT NULL GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5'),
        ];

        // ۹) بیشترین ورود در یک روز (همراه تاریخ همان روز)
        $dayBase = "SELECT {$lu} AS uid, DATE(l.created_at) AS d, COUNT(*) AS c "
            . "FROM login_events l {$lj} GROUP BY {$lu}, DATE(l.created_at)";
        $rankings['loginday'] = [
            'title' => 'بیشترین ورود در یک روز',
            'unit' => 'ورود در یک روز',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},mx.mc AS c, mind.d AS extra FROM ("
                . "SELECT b.uid AS uid, MAX(b.c) AS mc FROM ({$dayBase}) b GROUP BY b.uid"
                . ') mx JOIN ('
                . "SELECT b.uid AS uid, MIN(b.d) AS d FROM ({$dayBase}) b JOIN ("
                . "SELECT b2.uid AS uid, MAX(b2.c) AS mc FROM ({$dayBase}) b2 GROUP BY b2.uid"
                . ') m ON m.uid = b.uid AND b.c = m.mc GROUP BY b.uid'
                . ') mind ON mind.uid = mx.uid '
                . 'JOIN users u ON u.id = mx.uid ORDER BY mx.mc DESC, mx.uid ASC LIMIT 5'),
        ];

        // ۱۰) بیشترین علاقه‌مندی (پیشنهاد)
        $rankings['favs'] = [
            'title' => 'بیشترین علاقه‌مندی',
            'unit' => 'علاقه‌مندی',
            'rows' => melkinoStatsTopUsers($pdo,
                "SELECT u.id AS uid,{$idCols},COUNT(*) AS c FROM favorites f {$fj} "
                . "JOIN users u ON u.id = {$fu} GROUP BY u.id ORDER BY c DESC, u.id ASC LIMIT 5"),
        ];

        // ---- پربازدیدترین آگهی‌ها (سطح آگهی، پیشنهاد) ----
        $topAds = [];
        try {
            $st = $pdo->query(
                'SELECT ad_id, MAX(ad_title) AS ad_title, COUNT(*) AS c FROM ad_views '
                . 'GROUP BY ad_id ORDER BY c DESC LIMIT 5'
            );
            if ($st) {
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $topAds[] = [
                        'ad_id' => (string)($r['ad_id'] ?? ''),
                        'ad_title' => trim((string)($r['ad_title'] ?? '')) !== ''
                            ? trim((string)$r['ad_title']) : '—',
                        'c' => (int)($r['c'] ?? 0),
                    ];
                }
            }
        } catch (Throwable $e) {
            $topAds = [];
        }

        // ---- آگهی‌ها به تفکیک نوع معامله و نوع ملک ----
        $breakdown = melkinoStatsAdBreakdown($pdo);
        if (!$breakdown['ads_breakdown_error']) {
            // کارت کل و جمع جدول‌ها دقیقاً از همان مجموعهٔ ردیف‌ها هستند.
            $counts['ads_total'] = $breakdown['ads_total'];
        }
        unset($breakdown['ads_total']);

        return array_merge([
            'counts' => $counts,
            'rankings' => $rankings,
            'top_ads' => $topAds,
        ], $breakdown);
    }
}
