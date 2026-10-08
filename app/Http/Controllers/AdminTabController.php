<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\AdminTotp;
use Melkino\Core\Auth;
use Melkino\Core\Response;
use Melkino\Support\Assets;
use PDO;
use Throwable;

/**
 * Melkino V2 — converted panel tabs (gradual panel migration, spec §34–§37).
 * Each tab renders its fragment VERBATIM inside the V2 admin shell; the
 * legacy panel keeps including the same root fragments, so both stay live.
 * Unknown tabs fall back to the dashboard (panel remains canonical there).
 */
final class AdminTabController
{
    /** tab => [title, content_view, scripts]. */
    public const TABS = [
        'map' => ['نقشه و حریم', 'pages/admin/tabs/map.php', ['./admin-map.js', 'admin-tab-map']],
        'ads' => ['مدیریت آگهی‌ها', 'pages/admin/tabs/ads.php', ['admin-tab-shared', './telegram-relay.js', './admin-ads-map.js', './admin-ads.js', 'admin-tab-ads']],
        'requests' => ['مدیریت درخواست‌ها', 'pages/admin/tabs/requests.php', ['admin-tab-shared', './admin-requests.js', 'admin-tab-requests']],
        'notifications' => ['اعلان‌ها و پیام همگانی', 'pages/admin/tabs/notifications.php', ['admin-tab-notifications']],
        // DEAD TAB upstream (all handler fns absent from repo; only guarded caller at panel L4440):
        // static HTML shell, byte-identical to admin-visits.php. No scripts.
        'visits' => ['درخواست‌های بازدید', 'pages/admin/tabs/visits.php', []],
        'users' => ['مدیریت کاربران', 'pages/admin/tabs/users.php', ['admin-tab-shared', 'admin-tab-users']],
        'stats' => ['آمار', 'pages/admin/tabs/stats.php', []],
        'support' => ['تیکت‌های پشتیبانی', 'pages/admin/tabs/support.php', ['admin-tab-support']],
        'promotions' => ['مدیریت تبلیغات', 'pages/admin/tabs/promotions.php', ['admin-tab-promotions']],
        'global' => ['تنظیمات عمومی', 'pages/admin/tabs/global.php', ['admin-tab-shared', 'admin-tab-global']],
        'password' => ['تغییر رمز و امنیت', 'pages/admin/tabs/password.php', ['admin-tab-shared', 'admin-tab-password']],
        'sms' => ['برنامهٔ پیامک', 'pages/admin/tabs/sms.php', ['./admin-sms.js', 'admin-tab-sms']],
        'onboarding' => ['صفحات هدایت', 'pages/admin/tabs/onboarding.php', ['admin-tab-onboarding']],
        'display' => ['مدیریت نمایش', 'pages/admin/tabs/display.php', ['./admin-field-display.js', 'admin-tab-display']],
        'forms' => ['گزینه‌های فرم‌ها', 'pages/admin/tabs/forms.php', ['admin-tab-forms']],
        'diagnostics' => ['عیب‌یابی سیستم', 'pages/admin/tabs/diagnostics.php', ['admin-tab-diagnostics']],
        'legacy-dash' => ['داشبورد قدیمی', 'pages/admin/tabs/legacy-dash.php', ['admin-tab-legacy-dash']],
        'bots' => ['ربات و کانال', 'pages/admin/tabs/bots.php', ['./telegram-relay.js', 'admin-bots-controls', 'admin-tab-bots']],
        'images' => ['مدیریت تصاویر', 'pages/admin/tabs/images.php', ['admin-tab-images']],
        'assistant' => ['دستیار هوشمند', 'pages/admin/tabs/assistant.php', ['./admin-assistant.js']],
        'comm' => ['مرکز ارتباطات', 'pages/admin/tabs/comm.php', ['./admin-comm.js']],
        'studio' => ['استودیو طراحی', 'pages/admin/tabs/studio.php', ['./design-studio.js']],
        'contact' => ['ارتباط با ما', 'pages/admin/tabs/contact.php', ['admin-tab-contact']],
    ];

    public function handle(string $tab): string
    {
        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php?redirect=' . rawurlencode('admin.php?tab=' . $tab));
        }

        if (!isset(self::TABS[$tab])) {
            Response::redirect('admin.php');
        }

        // 2FA gate (spec §38–§39): enrolled admins must pass TOTP this session.
        if (AdminTotp::needsCheck()) {
            Response::redirect('admin-2fa.php?redirect=' . rawurlencode('admin.php?tab=' . $tab));
        }

        foreach (['config.php', 'db_helpers.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }

        // Same-URL POST contracts (admin-requests.js posts to location.href):
        // served byte-identically by the panel, which exits with JSON.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['request_action'])) {
            require MELKINO_ROOT . '/admin-panel.php';
            exit;
        }

        [$title, $view, $scripts] = self::TABS[$tab];

        $data = [];
        if ($tab === 'ads') {
            $data = ['ads' => $this->adsDataset()];
        }
        if ($tab === 'stats') {
            $lib = MELKINO_ROOT . '/admin-stats-lib.php';
            if (is_file($lib)) {
                require_once $lib;
            }
            $jal = MELKINO_ROOT . '/jalali-lib.php';
            if (is_file($jal)) {
                require_once $jal;
            }
            $pdo = \Melkino\Core\Database::pdo();
            $data = function_exists('melkinoStatsDashboard')
                ? melkinoStatsDashboard($pdo ?: null)
                : ['counts' => [], 'rankings' => [], 'top_ads' => []];
        }
        if ($tab === 'requests') {
            [$rows, $total, $totals] = $this->requestsDataset();
            $data = [
                'requestsData' => $rows,
                'requestsTotalCount' => $total,
                'requestsTotals' => $totals,
            ];
        }

        return melkinoView('layouts/admin.php', [
            'title' => $title . ' | مدیریت ملکینو',
            'active' => $tab,
            'identity' => Auth::identity(),
            'head_extra' => Assets::css('assets/css/admin-tabs-legacy.css'),
            'scripts' => $scripts,
            'content_view' => $view,
            'content_data' => $data,
        ]);
    }

    /**
     * Requests dataset (panel preamble VERBATIM: totals + rows + matches +
     * amenities + owner telegram enrichment + combinations).
     *
     * @return array{0: array, 1: int, 2: array}
     */
    private function requestsDataset(): array
    {
        $pdo = \Melkino\Core\Database::pdo();
        $isMockMode = defined('MOCK_MODE') && MOCK_MODE === true;
        if (!defined('MELKINO_ADMIN_REQUESTS_LIMIT')) {
            define('MELKINO_ADMIN_REQUESTS_LIMIT', 200);
        }

        $requestsData = [];
        if (!$isMockMode) {
            try {
                $requestsTotalCount = 0;
                $requestsTotals = ['total' => 0, 'new_count' => 0, 'tracking_count' => 0, 'matched' => 0, 'matches' => 0];
                try {
                    $requestsTotalCount = (int)$pdo->query("SELECT COUNT(*) FROM property_requests")->fetchColumn();
                    $requestsTotals['total'] = $requestsTotalCount;
                    try {
                        $reqRow = $pdo->query(
                            "SELECT
                                COALESCE(SUM(status IS NULL OR status = '' OR status = 'new'), 0) AS c_new,
                                COALESCE(SUM(status = 'tracking'), 0) AS c_tracking
                             FROM property_requests"
                        )->fetch(PDO::FETCH_ASSOC);
                        if (is_array($reqRow)) {
                            $requestsTotals['new_count'] = (int)($reqRow['c_new'] ?? 0);
                            $requestsTotals['tracking_count'] = (int)($reqRow['c_tracking'] ?? 0);
                        }
                    } catch (Throwable $e2) { /* ستون وضعیت ممکن است وجود نداشته باشد */ }
                    try {
                        $requestsTotals['matched'] = (int)$pdo->query("SELECT COUNT(DISTINCT request_id) FROM request_matches")->fetchColumn();
                        $requestsTotals['matches'] = (int)$pdo->query("SELECT COUNT(*) FROM request_matches")->fetchColumn();
                    } catch (Throwable $e3) { /* جدول تطبیق ممکن است هنوز ساخته نشده باشد */ }
                } catch (Throwable $e) {
                    $requestsTotalCount = 0;
                }
                $requestsLimit = max(20, min(2000, (int)MELKINO_ADMIN_REQUESTS_LIMIT));
                $stmt = $pdo->query("SELECT * FROM property_requests ORDER BY created_at DESC, id DESC LIMIT " . $requestsLimit);
                $requestsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $requestsLoadedIds = [];
                foreach ($requestsData as $__r) { $requestsLoadedIds[] = (string)$__r['id']; }
                $reqIdFilter = '';
                if (!empty($requestsLoadedIds) && count($requestsData) < $requestsTotalCount) {
                    $qIds = [];
                    foreach ($requestsLoadedIds as $__id) { $qIds[] = $pdo->quote($__id); }
                    $reqIdFilter = ' WHERE request_id IN (' . implode(',', $qIds) . ')';
                }

                $matchStmt = $pdo->query("SELECT request_id, ad_id, match_percent, matched_transaction, matched_property_type, location_score, area_score, budget_score, amenities_score, is_notified FROM request_matches" . $reqIdFilter . " ORDER BY request_id ASC, match_percent DESC, id ASC");
                $matchesByRequest = [];
                foreach ($matchStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    $rid = (string)$m['request_id'];
                    $matchesByRequest[$rid][] = [
                        'ad_id' => (string)$m['ad_id'],
                        'match_percent' => (int)$m['match_percent'],
                        'matched_transaction' => (int)$m['matched_transaction'],
                        'matched_property_type' => (int)$m['matched_property_type'],
                        'location_score' => (float)$m['location_score'],
                        'area_score' => (float)$m['area_score'],
                        'budget_score' => (float)$m['budget_score'],
                        'amenities_score' => (float)$m['amenities_score'],
                        'is_notified' => (int)$m['is_notified'],
                    ];
                }

                $amenitiesByRequest = [];
                try {
                    $reqAmenStmt = $pdo->query("SELECT ra.request_id, am.name FROM request_amenities ra LEFT JOIN amenities am ON am.id = ra.amenity_id" . str_replace('request_id', 'ra.request_id', $reqIdFilter) . " ORDER BY ra.request_id ASC, am.sort_order ASC, am.id ASC");
                    foreach ($reqAmenStmt->fetchAll(PDO::FETCH_ASSOC) as $ra) {
                        $rid = (string)$ra['request_id'];
                        $nm = trim((string)($ra['name'] ?? ''));
                        if ($nm !== '') {
                            $amenitiesByRequest[$rid][] = $nm;
                        }
                    }
                } catch (Throwable $eAmen) { }

                $userTg = [];
                $userIds = [];
                foreach ($requestsData as $__reqRow) {
                    $uid = (int) ($__reqRow['user_id'] ?? 0);
                    if ($uid > 0) {
                        $userIds[$uid] = true;
                    }
                }
                if ($userIds) {
                    try {
                        $inU = implode(',', array_map('intval', array_keys($userIds)));
                        $uSql = "SELECT id, telegram_id, username FROM users WHERE id IN ($inU)";
                        try {
                            $uSql = "SELECT id, telegram_id, username, telegram_username FROM users WHERE id IN ($inU)";
                            $uRows = $pdo->query($uSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
                        } catch (Throwable $e) {
                            $uRows = $pdo->query("SELECT id, telegram_id, username FROM users WHERE id IN ($inU)")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                        }
                        foreach ($uRows as $uRow) {
                            $userTg[(int) $uRow['id']] = $uRow;
                        }
                    } catch (Throwable $e) {
                    }
                }

                foreach ($requestsData as &$req) {
                    $rid = (string)$req['id'];
                    $uid = (int) ($req['user_id'] ?? 0);
                    if ($uid && isset($userTg[$uid])) {
                        if (trim((string) ($req['telegram_id'] ?? '')) === '') {
                            $req['telegram_id'] = $userTg[$uid]['telegram_id'] ?? '';
                        }
                        if (trim((string) ($req['username'] ?? '')) === '') {
                            $req['username'] = $userTg[$uid]['telegram_username'] ?? $userTg[$uid]['username'] ?? '';
                        }
                    }
                    $details = [];
                    if (!empty($req['property_details'])) {
                        $decoded = json_decode((string)$req['property_details'], true);
                        if (is_array($decoded)) $details = $decoded;
                    }
                    $req['followup_note'] = (string)($details['followup_note'] ?? '');
                    $req['matches'] = $matchesByRequest[$rid] ?? [];
                    $amenList = $amenitiesByRequest[$rid] ?? [];
                    if (!$amenList && !empty($details['amenities']) && is_array($details['amenities'])) {
                        $amenList = $details['amenities'];
                    }
                    $req['amenities'] = $amenList;

                    // ترکیب‌های چندفایلی که به کاربر پیشنهاد شده (my-request-matches.php آن‌ها
                    // را در ستون additional ذخیره می‌کند) — قبلاً در پنل ادمین نمایش داده نمی‌شدند.
                    $additionalData = [];
                    if (!empty($req['additional'])) {
                        $decodedAdditional = json_decode((string)$req['additional'], true);
                        if (is_array($decodedAdditional)) $additionalData = $decodedAdditional;
                    }
                    $req['combinations'] = is_array($additionalData['combinations'] ?? null) ? $additionalData['combinations'] : [];
                    if (empty($req['amenities']) && !empty($additionalData['amenities'])) {
                        $am = $additionalData['amenities'];
                        $req['amenities'] = is_array($am) ? $am : preg_split('/[،,]+/u', (string) $am);
                        $req['amenities'] = array_values(array_filter(array_map('trim', $req['amenities'])));
                    }

                    unset($req['additional'], $req['property_details']);
                }
                unset($req);
            } catch (Throwable $e) {
                $requestsData = [];
            }
        }

        return [$requestsData, (int)($requestsTotalCount ?? count($requestsData)), $requestsTotals ?? []];
    }

    /**
     * Ads dataset (panel preamble VERBATIM: getMockAds + normalize fns +
     * DB load + partnership merge + counts).
     * ONE deviation: __DIR__.'/ads.json' -> MELKINO_ROOT.'/ads.json'
     * (inside a method __DIR__ would point at the controller dir).
     *
     * @return array{rows: array, loaded: int, total: int, hasMore: bool, totals: array, error: string}
     */
    private function adsDataset(): array
    {
        $pdo = \Melkino\Core\Database::pdo();
        $isMockMode = defined('MOCK_MODE') && MOCK_MODE === true;
        if (!defined('MELKINO_ADMIN_ADS_LIMIT')) {
            define('MELKINO_ADMIN_ADS_LIMIT', 200);
        }
        $adsLoadError = '';

// ==============================================
// داده‌های نمونه (فقط برای حالت MOCK)
// ==============================================
function getMockAds() {
    return [
        [
            'id' => 1,
            'title' => 'آپارتمان لوکس ۱۲۰ متری در نیاوران',
            'transaction_type' => 'فروش',
            'property_type' => 'آپارتمان',
            'price_sell' => '۳,۸۰۰,۰۰۰,۰۰۰',
            'price_condition' => 'negotiable',
            'deposit' => '',
            'rent_monthly' => '',
            'full_rent_enabled' => 0,
            'full_rent' => '',
            'total_price' => '',
            'down_payment' => '',
            'payment_terms' => '',
            'last_name' => 'رضایی',
            'phone' => '۰۹۱۲۳۴۵۶۷۸۹',
            'address' => 'خیابان نیاوران، پلاک ۱۲',
            'location' => 'نیاوران، تهران',
            'description' => 'دوبلکس با نمای شمالی، پارکینگ و انباری، نزدیک به مترو',
            'status' => 'published',
            'property_details' => '{"area":"۱۲۰","floor":"۵","unit":"۳","total_units":"۱۲","rooms":"۳","year":"۱۴۰۲","flooring":"پارکت","cabinet":"ام دی اف","cooling":"اسپیلت","heating":"شوفاژ"}',
            'images' => '["uploads/sample1.jpg"]',
            'selected_images' => '["uploads/sample1.jpg"]',
            'publish_photos' => 'yes',
            'amenities' => ['آسانسور', 'پارکینگ', 'انباری'],
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => 'ویلای ۴۰۰ متری در چالوس',
            'transaction_type' => 'فروش',
            'property_type' => 'ویلا',
            'price_sell' => '۱۲,۵۰۰,۰۰۰,۰۰۰',
            'price_condition' => 'fixed',
            'deposit' => '',
            'rent_monthly' => '',
            'full_rent_enabled' => 0,
            'full_rent' => '',
            'total_price' => '',
            'down_payment' => '',
            'payment_terms' => '',
            'last_nameayment' => '',
            'payment_terms' => '',
            'last_name' => 'کریمی',
            'phone' => '۰۹۱۲۳۴۵۶۷۸۰',
            'address' => 'چالوس، خیابان دریا، کوچه ۵',
            'location' => 'چالوس، مازندران',
            'description' => 'استخر اختصاصی، باغچه و منظره دریا، سند تک‌برگ',
            'status' => 'published',
            'property_details' => '{"land_area":"۴۰۰","built_area":"۲۵۰","rooms":"۴","year":"۱۴۰۱","flooring":"سنگ","cabinet":"چوبی","cooling":"اسپیلت","heating":"پکیج"}',
            'images' => '["uploads/sample2.jpg"]',
            'selected_images' => '["uploads/sample2.jpg"]',
            'publish_photos' => 'yes',
            'amenities' => ['استخر', 'باغچه', 'سند تک‌برگ'],
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// ==============================================
// بارگذاری آگهی‌ها
// ==============================================
$adsData = [];

function normalizeAdsData($ads) {
    if (!is_array($ads)) return [];

    $propertyFieldMap = [
        'آپارتمان' => [
            'area',
            'floor',
            'unit',
            'total_units',
            'rooms',
            'year',
            'flooring',
            'cabinet',
            'cooling',
            'heating'
        ],
        'ویلا' => [
            'land_area',
            'area',
            'rooms',
            'year',
            'flooring',
            'cabinet',
            'cooling',
            'heating'
        ],
        'زمین' => [
            'land_area',
            'land_usage',
            'land_type',
            'land_width',
            'land_length',
            'land_front_width',
            'land_blocks',
            'land_direction',
            'land_shape',
            'land_deed_status',
            'land_deed_type',
            'land_division_status',
            'land_setback_status',
            'land_ownership'
        ],
        'باغ' => [
            'garden_area',
            'tree_count',
            'tree_types',
            'tree_age',
            'irrigation_type',
            'water_source',
            'water_share',
            'has_well',
            'has_pond',
            'has_building',
            'building_area',
            'document_type'
        ],
        'اداری' => [
            'office_area',
            'office_floor',
            'office_units_per_floor',
            'office_rooms',
            'office_year',
            'office_condition',
            'office_orientation',
            'office_usage'
        ],
        'تجاری' => [
            'area',
            'front',
            'flooring',
            'wall',
            'cabinet',
            'cooling',
            'heating',
            'balcony',
            'basement',
            'balcony_area',
            'basement_area',
            'location_type_1',
            'location_type_2',
            'jobs'
        ]
    ];

    $amenityKeys = [
        'آپارتمان' => ['amenities_apt', 'amenities'],
        'ویلا' => ['amenities_villa', 'amenities'],
        'زمین' => ['land_amenities', 'amenities'],
        'باغ' => ['garden_amenities', 'amenities'],
        'اداری' => ['office_amenities', 'amenities'],
        'تجاری' => ['amenities_comm', 'amenities'],
    ];

    foreach ($ads as &$ad) {

        $ad['id'] = $ad['id'] ?? $ad['ad_id'] ?? uniqid('AD-');
        $ad['ad_id'] = $ad['ad_id'] ?? $ad['id'];

        $ad['transaction_type'] =
            $ad['transaction_type'] ??
            $ad['transactionType'] ??
            '';

        $ad['property_type'] =
            $ad['property_type'] ??
            $ad['propertyType'] ??
            '';

        $ad['gender'] = $ad['gender'] ?? '';
        $ad['last_name'] = $ad['last_name'] ?? '';
        $ad['phone'] = $ad['phone'] ?? '';
        $ad['status'] = $ad['status'] ?? 'pending';

        $ad['price_sell'] = $ad['price_sell'] ?? '';
        $ad['price_condition'] = $ad['price_condition'] ?? '';
        $ad['deposit'] = $ad['deposit'] ?? '';
        $ad['rent_monthly'] = $ad['rent_monthly'] ?? '';
        $ad['full_rent_enabled'] = $ad['full_rent_enabled'] ?? 0;
        $ad['full_rent'] = $ad['full_rent'] ?? '';
        $ad['total_price'] = $ad['total_price'] ?? '';
        $ad['down_payment'] = $ad['down_payment'] ?? '';
        $ad['payment_terms'] = $ad['payment_terms'] ?? '';
        $ad['price_hidden'] = filter_var($ad['price_hidden'] ?? false, FILTER_VALIDATE_BOOLEAN);
        // فیلدهای وام
        $ad['has_loan'] = $ad['has_loan'] ?? 0;
        $ad['loan_amount'] = $ad['loan_amount'] ?? '';
        $ad['loan_type'] = $ad['loan_type'] ?? '';
        $ad['loan_duration'] = $ad['loan_duration'] ?? '';
        $ad['loan_bank'] = $ad['loan_bank'] ?? '';
        $ad['loan_installment'] = $ad['loan_installment'] ?? '';
        $ad['loan_installments_paid'] = $ad['loan_installments_paid'] ?? '';
        $ad['loan_notes'] = $ad['loan_notes'] ?? '';
        $ad['melkino_visited'] = !empty($ad['melkino_visited']) ? 1 : 0;
        $ad['melkino_rating'] = is_numeric($ad['melkino_rating'] ?? null) ? (float)$ad['melkino_rating'] : 0;
        $ad['melkino_review'] = (string)($ad['melkino_review'] ?? '');

        $ad['location'] = $ad['location'] ?? '';
        $ad['address'] = $ad['address'] ?? '';
        $ad['location_received'] = $ad['location_received'] ?? '0';
        $ad['description'] = $ad['description'] ?? '';
        $ad['publish_photos'] = $ad['publish_photos'] ?? 'yes';
        $ad['created_at'] = $ad['created_at'] ?? date('Y-m-d H:i:s');

        $ad['images'] = normalizeAdminJsonArray($ad['images'] ?? []);

        $ad['selected_images'] =
            normalizeAdminJsonArray(
                $ad['selected_images'] ??
                ($ad['selectedImages'] ?? [])
            );

        $type = $ad['property_type'];

        $amenities = [];

        foreach (($amenityKeys[$type] ?? ['amenities']) as $key) {

            if (!array_key_exists($key, $ad)) {
                continue;
            }

            $candidate =
                normalizeAdminJsonArray($ad[$key]);

            if ($candidate) {
                $amenities = $candidate;
                break;
            }
        }

        if (
            !$amenities &&
            isset($ad['amenities']) &&
            is_string($ad['amenities'])
        ) {
            $decoded =
                json_decode(
                    $ad['amenities'],
                    true
                );

            if (is_array($decoded)) {
                $amenities = $decoded;
            }
        }

        $ad['amenities'] =
            array_values(
                array_unique(
                    array_filter(
                        $amenities,
                        static fn($v) => $v !== ''
                    )
                )
            );

        $details =
            normalizeAdminJsonObject(
                $ad['property_details'] ?? []
            );

        foreach (($propertyFieldMap[$type] ?? []) as $field) {

            if (!array_key_exists($field, $ad)) {
                continue;
            }

            $value = $ad[$field];

            if (
                $value !== '' &&
                $value !== null &&
                $value !== '0' &&
                $value !== 0
            ) {
                $details[$field] = $value;
            }
        }

        $aliases = [
            'آپارتمان' => [
                'area' => 'area_apt',
                'rooms' => 'rooms_apt',
                'year' => 'year_apt',
                'flooring' => 'flooring_apt',
                'cabinet' => 'cabinet_apt',
                'cooling' => 'cooling_apt',
                'heating' => 'heating_apt'
            ],
            'ویلا' => [
                'land_area' => 'land_villa',
                'area' => 'built_villa',
                'rooms' => 'rooms_villa',
                'year' => 'year_villa',
                'flooring' => 'flooring_villa',
                'cabinet' => 'cabinet_villa',
                'cooling' => 'cooling_villa',
                'heating' => 'heating_villa'
            ],
            'تجاری' => [
                'area' => 'area_comm',
                'front' => 'front_comm',
                'flooring' => 'floor_comm',
                'wall' => 'wall_comm',
                'cabinet' => 'cabinet_comm',
                'cooling' => 'cooling_comm',
                'heating' => 'heating_comm',
                'balcony_area' => 'balcony_comm',
                'basement_area' => 'basement_comm',
                'jobs' => 'jobs_comm'
            ],
        ];

        foreach (($aliases[$type] ?? []) as $standard => $source) {

            if (
                (!isset($details[$standard]) ||
                $details[$standard] === '') &&
                isset($ad[$standard]) &&
                $ad[$standard] !== ''
            ) {
                $details[$standard] =
                    $ad[$standard];
            }

            if (
                (!isset($details[$standard]) ||
                $details[$standard] === '') &&
                isset($ad[$source]) &&
                $ad[$source] !== ''
            ) {
                $details[$standard] =
                    $ad[$source];
            }
        }

        $ad['property_details'] = $details;
    }

    unset($ad);

    return $ads;
}

function normalizeAdminJsonArray($value) {

    if (is_array($value)) {
        return $value;
    }

    if (
        !is_string($value) ||
        trim($value) === ''
    ) {
        return [];
    }

    $decoded =
        json_decode($value, true);

    return is_array($decoded)
        ? $decoded
        : [];
}

function normalizeAdminJsonObject($value) {

    if (
        is_array($value)
    ) {
        return $value;
    }

    if (
        !is_string($value) ||
        trim($value) === ''
    ) {
        return [];
    }

    $decoded =
        json_decode($value, true);

    return is_array($decoded)
        ? $decoded
        : [];
}

if ($isMockMode) {

    $jsonFile =
        MELKINO_ROOT . '/ads.json';

    if (file_exists($jsonFile)) {

        $content =
            file_get_contents(
                $jsonFile
            );

        $adsFromJson =
            json_decode(
                $content,
                true
            );

        if (
            is_array($adsFromJson) &&
            count($adsFromJson) > 0
        ) {
            $adsData =
                normalizeAdsData(
                    $adsFromJson
                );
        } else {
            $adsData =
                normalizeAdsData(
                    getMockAds()
                );
        }

    } else {

        $adsData =
            normalizeAdsData(
                getMockAds()
            );
    }

} else {

    try {

        // آمارِ کلی از خودِ دیتابیس گرفته می‌شود تا اعدادِ نوار آمار تب آگهی‌ها حتی
        // وقتی همه‌ی آگهی‌ها لود نشده‌اند، درست و کامل بمانند.
        $adsTotalCount = 0;
        $adsTotals = ['total' => 0, 'pending' => 0, 'published' => 0, 'vip' => 0, 'published_vip' => 0];
        try {
            $adsTotalCount = (int)$pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
            $cntRow = $pdo->query(
                "SELECT
                    COALESCE(SUM(status = 'pending'), 0)                     AS c_pending,
                    COALESCE(SUM(status = 'published'), 0)                   AS c_published,
                    COALESCE(SUM(is_vip = 1), 0)                             AS c_vip,
                    COALESCE(SUM(is_vip = 1 AND status = 'published'), 0)    AS c_pub_vip
                   FROM ads"
            )->fetch(PDO::FETCH_ASSOC);
            if (is_array($cntRow)) {
                $adsTotals = [
                    'total'         => $adsTotalCount,
                    'pending'       => (int)($cntRow['c_pending'] ?? 0),
                    'published'     => (int)($cntRow['c_published'] ?? 0),
                    'vip'           => (int)($cntRow['c_vip'] ?? 0),
                    'published_vip' => (int)($cntRow['c_pub_vip'] ?? 0),
                ];
            }
        } catch (Throwable $e) {
            $adsTotals['total'] = $adsTotalCount;
        }

        if (function_exists('melkinoEnsureRatingColumns')) {
            melkinoEnsureRatingColumns($pdo);
        }
        $adsLimit = max(20, min(2000, (int)MELKINO_ADMIN_ADS_LIMIT));
        $stmt = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC, `id` DESC LIMIT " . $adsLimit);
        $adsFromDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $adsLoadedCount = count($adsFromDB);
        $adsHasMore = $adsLoadedCount < $adsTotalCount;

        // تصاویر و امکانات فقط برای همین آگهی‌های لودشده خوانده می‌شوند،
        // نه برای کلِ جدول.
        $loadedAdIds = [];
        foreach ($adsFromDB as $__a) {
            $loadedAdIds[] = (string)$__a['id'];
        }
        $adIdFilter = '';
        $adIdFilterAa = '';
        if ($adsHasMore && !empty($loadedAdIds)) {
            $quotedIds = [];
            foreach ($loadedAdIds as $__id) {
                $quotedIds[] = $pdo->quote($__id);
            }
            $inList = implode(',', $quotedIds);
            $adIdFilter = ' WHERE ad_id IN (' . $inList . ')';
            $adIdFilterAa = ' WHERE aa.ad_id IN (' . $inList . ')';
        }
        // بارگذاری یک‌جای تصاویر و امکانات:
        // قبلاً برای هر آگهی دو کوئری جداگانه اجرا می‌شد (N+1) و با زیاد شدن
        // آگهی‌ها پنل به‌شدت کند می‌شد. حالا فقط دو کوئریِ کلی اجرا می‌شود.
        $imagesByAd = [];
        try {
            $allImages = $pdo->query(
                "SELECT ad_id, filename, sort_order, is_selected, is_primary, publish_publicly
                   FROM images" . $adIdFilter . " ORDER BY sort_order ASC, id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allImages as $img) {
                $imagesByAd[(string)$img['ad_id']][] = $img;
            }
        } catch (Throwable $e) {
            $imagesByAd = [];
        }

        $amenitiesByAd = [];
        try {
            $allAmenities = $pdo->query(
                "SELECT aa.ad_id AS ad_id, am.name AS name
                   FROM ad_amenities aa
                   INNER JOIN amenities am ON am.id = aa.amenity_id"
                . $adIdFilterAa .
                " ORDER BY am.sort_order ASC, am.id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allAmenities as $am) {
                $amenitiesByAd[(string)$am['ad_id']][] = $am['name'];
            }
        } catch (Throwable $e) {
            $amenitiesByAd = [];
        }

        foreach ($adsFromDB as &$dbAd) {
            $adKey = (string)$dbAd['id'];
            $imgs = $imagesByAd[$adKey] ?? [];
            $dbAd['images'] = array_map(static fn($img) => $img['filename'], $imgs);
            $dbAd['selected_images'] = array_values(array_map(
                static fn($img) => $img['filename'],
                array_filter($imgs, static fn($img) => (int)$img['is_selected'] === 1 && (int)$img['publish_publicly'] === 1)
            ));
            $dbAd['amenities'] = array_values($amenitiesByAd[$adKey] ?? []);
        }
        unset($dbAd);
        $adsData = normalizeAdsData($adsFromDB);

    } catch (Throwable $e) {

        // خطا دیگر پنهان نمی‌شود: هم در لاگ ثبت می‌شود و هم به ادمین
        // نشان داده می‌شود تا بداند پنل به داده‌ی واقعی وصل نیست.
        // متن خطای خام (شامل SQL/مسیر) نمایش داده نمی‌شود؛ فقط لاگ می‌شود.
        error_log('[melkino] ads load failed: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        $adsLoadError = 'اتصال به دادهٔ واقعی آگهی‌ها برقرار نشد؛ جزئیات در لاگ سرور ثبت شد.';
        $adsData =
            normalizeAdsData(
                getMockAds()
            );
    }
}
/* ==========================================================
   راند مشارکت: درخواست‌های «مشارکت در ساخت» هم در لیست آگهی‌های
   تب «آگهی‌ها» دیده شوند (همه آگهی‌ها + در انتظار بررسی).
   این ردیف‌ها فقط نمایشی‌اند (id با پیشوند MKP تا با آگهی‌های
   واقعی قاطی نشوند)؛ مدیریت کامل در تب «مشارکت در ساخت» است.
   ========================================================== */
if (is_array($adsData) && isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $mkpStmt = $pdo->prepare("SELECT * FROM partnership_requests ORDER BY id DESC LIMIT 200");
        $mkpStmt->execute();
        foreach ($mkpStmt->fetchAll(PDO::FETCH_ASSOC) as $mkp) {
            $mkpTitle = trim((string)($mkp['title'] ?? ''));
            if ($mkpTitle === '') {
                $mkpTitle = 'درخواست مشارکت در ساخت #' . (int)$mkp['id'];
            }
            $mkpLocParts = [];
            foreach (['city', 'neighborhood'] as $mkpLocKey) {
                $mkpLocVal = trim((string)($mkp[$mkpLocKey] ?? ''));
                if ($mkpLocVal !== '') {
                    $mkpLocParts[] = $mkpLocVal;
                }
            }
            $adsData[] = [
                'id'               => 'MKP' . (int)$mkp['id'],
                'is_partnership'   => 1,
                'part_id'          => (int)$mkp['id'],
                'part_code'        => (string)($mkp['code'] ?? ''),
                'title'            => $mkpTitle,
                'location'         => implode('، ', $mkpLocParts),
                'property_type'    => (string)($mkp['property_type'] ?? ''),
                'transaction_type' => 'مشارکت در ساخت',
                'status'           => (string)($mkp['status'] ?? 'pending'),
                'last_name'        => (string)($mkp['owner_name'] ?? ''),
                'phone'            => (string)($mkp['phone'] ?? ''),
                'area'             => (string)($mkp['area'] ?? ''),
                'price_sell'       => '0',
                'total_price'      => '0',
                'deposit'          => '0',
                'rent_monthly'     => '0',
                'is_vip'           => 0,
                'selected_images'  => '[]',
                'images'           => '[]',
                'property_details' => [],
                'code'             => (string)($mkp['code'] ?? ''),
                'city'             => (string)($mkp['city'] ?? ''),
                'neighborhood'     => (string)($mkp['neighborhood'] ?? ''),
                'address'          => (string)($mkp['address'] ?? ''),
                'current_status'   => (string)($mkp['current_status'] ?? ''),
                'br_count'         => (string)($mkp['br_count'] ?? ''),
                'direction'        => (string)($mkp['direction'] ?? ''),
                'passage_width'    => (string)($mkp['passage_width'] ?? ''),
                'land_width'       => (string)($mkp['land_width'] ?? ''),
                'permit_status'    => (string)($mkp['permit_status'] ?? ''),
                'density'          => (string)($mkp['density'] ?? ''),
                'occupancy_rate'   => (string)($mkp['occupancy_rate'] ?? ''),
                'buildable_floors' => (string)($mkp['buildable_floors'] ?? ''),
                'buildable_area'   => (string)($mkp['buildable_area'] ?? ''),
                'deed_status'      => (string)($mkp['deed_status'] ?? ''),
                'deed_kind'        => (string)($mkp['deed_kind'] ?? ''),
                'owners_count'     => (string)($mkp['owners_count'] ?? ''),
                'occupancy'        => (string)($mkp['occupancy'] ?? ''),
                'legal_status'     => (string)($mkp['legal_status'] ?? ''),
                'notes'            => (string)($mkp['notes'] ?? ''),
                'latitude'         => (string)($mkp['latitude'] ?? ''),
                'longitude'        => (string)($mkp['longitude'] ?? ''),
                'location_source'  => (string)($mkp['location_source'] ?? ''),
                'doc_deed'         => (string)($mkp['doc_deed'] ?? ''),
                'doc_permit'       => (string)($mkp['doc_permit'] ?? ''),
                'doc_endjob'       => (string)($mkp['doc_endjob'] ?? ''),
                'doc_other'        => (string)($mkp['doc_other'] ?? ''),
                'completeness'     => (string)($mkp['completeness'] ?? ''),
                'photos'           => (string)($mkp['photos'] ?? ''),
                'created_at'       => (string)($mkp['created_at'] ?? ''),
            ];
        }
    } catch (Throwable $mkpE) {
        /* جدول مشارکت هنوز ساخته نشده — بی‌صدا رد شو */
    }
}

/* مشارکت در ساخت هم نوعی آگهی است و در شمارنده‌های تب آگهی‌ها حساب
   می‌شود: «کل آگهی‌ها» و «در انتظار تایید» تا با لیست یکی باشند. */
if (isset($pdo) && ($pdo instanceof PDO) && isset($adsTotals) && is_array($adsTotals)) {
    try {
        $mkpRow = $pdo->query("SELECT COUNT(*) AS c_all, COALESCE(SUM(status = 'pending'), 0) AS c_pending FROM partnership_requests")->fetch(PDO::FETCH_ASSOC);
        if (is_array($mkpRow)) {
            $adsTotals['total']   += (int)($mkpRow['c_all'] ?? 0);
            $adsTotals['pending'] += (int)($mkpRow['c_pending'] ?? 0);
        }
    } catch (Throwable $mkpE) {
        /* جدول مشارکت هنوز ساخته نشده — بی‌صدا */
    }
}

if (!isset($adsLoadedCount)) { $adsLoadedCount = count($adsData); }
if (!isset($adsTotalCount))  { $adsTotalCount  = $adsLoadedCount; }
if (!isset($adsHasMore))     { $adsHasMore     = false; }
if (!isset($adsTotals))      { $adsTotals = ['total' => $adsTotalCount, 'pending' => 0, 'published' => 0, 'vip' => 0, 'published_vip' => 0]; }

        return [
            'rows' => $adsData,
            'loaded' => $adsLoadedCount,
            'total' => $adsTotalCount,
            'hasMore' => $adsHasMore,
            'totals' => $adsTotals,
            'error' => $adsLoadError,
        ];
    }
}
