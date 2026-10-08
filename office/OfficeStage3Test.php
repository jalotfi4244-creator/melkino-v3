<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-3 tests: villa/land/commercial/office/garden register.
 *
 * SAFETY: same guard as stages 1-2 — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage3
 * (seed first: .../qa/seed-test.php)
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
if (!defined('OFFICE_TEST_UPLOAD_DIR')) {
    define('OFFICE_TEST_UPLOAD_DIR', sys_get_temp_dir() . '/office-stage2-uploads');
}
require_once dirname(__DIR__) . '/office/_lib.php';
require_once dirname(__DIR__) . '/office/_forms.php';
require_once dirname(__DIR__) . '/office/_forms2.php';

if (!function_exists('office3_types')) {
    /** @return string[] */
    function office3_types(): array
    {
        return ['villa', 'land', 'commercial', 'office', 'garden'];
    }
}

if (!function_exists('office3_clean_fixtures')) {
    function office3_clean_fixtures(PDO $pdo): void
    {
        foreach (['0900000011', '0900000012', '0900000013', '0900000014', '0900000015'] as $ph) {
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

if (!function_exists('office3_base_fixture')) {
    /** @return array<string,mixed> */
    function office3_base_fixture(string $phone, string $title): array
    {
        $deeds = office_combo_items('deed_type');
        return [
            'transaction_type' => 'فروش',
            'status' => 'published',
            'gender' => 'خانم',
            'last_name' => 'OF3 مالک',
            'phone' => $phone,
            'title' => $title,
            'location' => 'OF3 تهران',
            'address' => 'OF3 خیابان تست',
            'map_lat' => '35.700000',
            'map_lng' => '51.400000',
            'price_sell' => '5000000000',
            'price_condition' => 'negotiable',
            'deed_type' => (string)($deeds[0] ?? 'طلق'),
            'publish_photos' => 'yes',
            'full_description' => 'OF3 شرح تستی',
        ];
    }
}

if (!function_exists('office3_spec_fixture')) {
    /** @return array<string,mixed> */
    function office3_spec_fixture(string $type): array
    {
        $rooms = office_combo_items('rooms');
        $room = (string)($rooms[1] ?? $rooms[0] ?? '2');
        switch ($type) {
            case 'villa':
                return [
                    'land_villa' => '500', 'built_villa' => '300', 'rooms_villa' => $room,
                    'year_villa' => '1400', 'villa_type' => 'دوبلکس',
                    'amenities_villa' => ['استخر', 'گلخانه'],
                ];
            case 'land':
                return [
                    'land_area' => '500', 'land_type' => 'مسکونی', 'land_width' => '12',
                    'land_length' => '42', 'land_ownership' => 'شش‌دانگ',
                    'land_direction' => 'شمالی', 'land_amenities' => ['آب', 'برق'],
                ];
            case 'commercial':
                return [
                    'area_comm' => '70', 'front_comm' => '6', 'location_type_1' => 'دونبش',
                    'location_type_2' => 'main_street', 'jobs_comm' => 'رستوران',
                    'amenities_comm' => ['ویترین', 'آسانسور'],
                ];
            case 'office':
                return [
                    'office_area' => '120', 'office_floor' => '3', 'office_units_per_floor' => '4',
                    'office_rooms' => $room, 'office_year' => '1402',
                    'office_condition' => 'نوساز', 'office_orientation' => 'شمالی',
                    'office_usage' => 'اداری', 'office_amenities' => ['آسانسور', 'پارکینگ'],
                ];
            case 'garden':
                return [
                    'garden_area' => '1000', 'document_type' => 'قولنامه',
                    'has_well' => '0', 'has_pond' => '0', 'has_building' => '1',
                    'building_area' => '150', 'garden_amenities' => ['استخر', 'برق'],
                ];
        }
        return [];
    }
}

if (!function_exists('office3_register_post')) {
    /** @return array{output:string,redirect:string} */
    function office3_register_post(PDO $pdo, string $type, array $post, mixed $files = null): array
    {
        office_test_reset();
        $_FILES = [];
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => $type];
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        if ($files !== null) {
            $_FILES = ['images' => $files];
        }
        return office_test_run_page('register.php');
    }
}

if (!function_exists('office3_legacy_names')) {
    /** @return string[] */
    function office3_legacy_names(string $legacyFile): array
    {
        $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/' . $legacyFile);
        preg_match_all('/name="([^"]+)"/', $legacy, $m);
        $names = array_values(array_unique($m[1]));
        return array_values(array_filter(
            array_diff($names, ['uploaded_images']),
            static fn($n) => !str_contains((string)$n, '+') && !str_contains((string)$n, "'")
        ));
    }
}

return [
    'stage3 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage3 all five types render same field names as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        foreach (office3_types() as $t) {
            $spec = office_type_spec($t);
            t_ok(is_array($spec), "spec exists for $t");
            $legacyNames = office3_legacy_names((string)$spec['legacy']);
            t_ok(count($legacyNames) > 20, "$t legacy names extracted (" . count($legacyNames) . ')');
            office_test_reset();
            office2_login_session($pdo);
            $_GET = ['type' => $t];
            $r = office_test_run_page('register.php');
            $missing = [];
            foreach ($legacyNames as $nm) {
                $bare = rtrim($nm, '[]');
                if (!str_contains($r['output'], 'name="' . $nm . '"') && !str_contains($r['output'], 'name="' . $bare . '[]"') && !str_contains($r['output'], 'name="' . $bare . '"')) {
                    $missing[] = $nm;
                }
            }
            t_eq([], $missing, "$t: office form must carry every site field name (missing: " . implode(',', $missing) . ')');
        }
    },

    'stage3 all five types render same option values as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        foreach (office3_types() as $t) {
            $spec = office_type_spec($t);
            $legacy = (string)file_get_contents(dirname(__DIR__) . '/views/legacy/' . $spec['legacy']);
            preg_match_all('/<input[^>]*type="(?:radio|checkbox)"[^>]*value="([^"]*)"[^>]*>/', $legacy, $m);
            $vals = array_values(array_unique(array_filter($m[1], static fn($v) => $v !== '')));
            office_test_reset();
            office2_login_session($pdo);
            $_GET = ['type' => $t];
            $r = office_test_run_page('register.php');
            foreach ($vals as $v) {
                t_ok(str_contains($r['output'], 'value="' . $v . '"'), "$t: office form carries option value: $v");
            }
            // کمبوهای هر نوع از همان کاتالوگ سایت
            foreach (office_sections_for($t) as $fields) {
                foreach ($fields as $f) {
                    if (($f[2] ?? '') === 'select' && isset($f[3]['combo'])) {
                        foreach (office_combo_items((string)$f[3]['combo']) as $cv) {
                            t_ok(str_contains($r['output'], (string)$cv), "$t: combo item rendered: $cv");
                        }
                    }
                }
            }
        }
    },

    'stage3 villa POST saves full mapping' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office3_clean_fixtures($pdo);
        $adminId = office2_login_session($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000011', 'OF3 ویلا'), office3_spec_fixture('villa'));
        $r = office3_register_post($pdo, 'villa', $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'villa fixture shows success panel');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'villa inserts one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '0900000011' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('ویلایی', (string)($row['property_type'] ?? ''), 'villa property_type stored');
        t_eq('500', (string)($row['land_area'] ?? ''), 'villa land_area stored');
        t_eq('300', (string)($row['built_area'] ?? ''), 'villa built_area stored');
        t_eq('1400', (string)($row['year'] ?? ''), 'villa year stored');
        t_eq($adminId, (int)($row['consultant_id'] ?? 0), 'villa consultant_id = posting admin');
        $det = (string)($row['property_details'] ?? '');
        t_ok(str_contains($det, 'دوبلکس'), 'villa_type in details');
        $amen = $pdo->query('SELECT a.name FROM ad_amenities l JOIN amenities a ON a.id = l.amenity_id WHERE l.ad_id = ' . $pdo->quote((string)$row['id']))->fetchAll(PDO::FETCH_COLUMN) ?: [];
        t_ok(in_array('استخر', $amen, true) && in_array('گلخانه', $amen, true), 'villa amenities linked');
    },

    'stage3 land POST saves full mapping' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office3_clean_fixtures($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000012', 'OF3 زمین'), office3_spec_fixture('land'));
        $r = office3_register_post($pdo, 'land', $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'land fixture shows success panel');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'land inserts one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '0900000012' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('زمین', (string)($row['property_type'] ?? ''), 'land property_type stored');
        t_eq('500', (string)($row['land_area'] ?? ''), 'land land_area stored');
        $det = (string)($row['property_details'] ?? '');
        t_ok(str_contains($det, 'مسکونی'), 'land_type in details');
        t_ok(str_contains($det, 'شمالی'), 'land_direction in details');
        t_ok(!str_contains($det, 'visit_hours'), 'land omits visit keys like site form');
    },

    'stage3 commercial POST saves full mapping' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office3_clean_fixtures($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000013', 'OF3 تجاری'), office3_spec_fixture('commercial'));
        $r = office3_register_post($pdo, 'commercial', $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'commercial fixture shows success panel');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'commercial inserts one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '0900000013' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('تجاری', (string)($row['property_type'] ?? ''), 'commercial property_type stored');
        t_eq('70', (string)($row['area'] ?? ''), 'commercial area stored');
        $det = (string)($row['property_details'] ?? '');
        t_ok(str_contains($det, 'بر خیابان اصلی'), 'location_features fa-map applied like site');
        t_ok(str_contains($det, 'رستوران'), 'jobs stored');
    },

    'stage3 office POST saves full mapping' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office3_clean_fixtures($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000014', 'OF3 اداری'), office3_spec_fixture('office'));
        $r = office3_register_post($pdo, 'office', $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'office fixture shows success panel');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'office inserts one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '0900000014' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('اداری', (string)($row['property_type'] ?? ''), 'office property_type stored');
        t_eq('1402', (string)($row['year'] ?? ''), 'office year stored');
        $det = (string)($row['property_details'] ?? '');
        t_ok(str_contains($det, 'نوساز'), 'office_condition in details');
        t_ok(str_contains($det, 'دفتر کار') || str_contains($det, 'اداری'), 'office_usage in details');
    },

    'stage3 garden POST saves full mapping' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office3_clean_fixtures($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000015', 'OF3 باغ'), office3_spec_fixture('garden'));
        $r = office3_register_post($pdo, 'garden', $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'garden fixture shows success panel');
        t_eq($before + 1, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'garden inserts one row');
        $row = $pdo->query("SELECT * FROM ads WHERE phone = '0900000015' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        t_eq('باغ', (string)($row['property_type'] ?? ''), 'garden property_type stored');
        $det = (string)($row['property_details'] ?? '');
        t_ok(str_contains($det, 'قولنامه'), 'document_type in details');
        t_ok(!str_contains($det, 'visit_hours'), 'garden omits visit keys like site form');
    },

    'stage3 garden conditional water validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000015', 'OF3 باغ'), office3_spec_fixture('garden'));
        $post['has_well'] = '1';
        unset($post['water_share'], $post['well_name']);
        $r = office3_register_post($pdo, 'garden', $post);
        t_ok(str_contains($r['output'], 'لطفاً مقدار ساعت آب را وارد کنید.'), 'missing water_share shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً نام چاه آب را وارد کنید.'), 'missing well_name shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid garden POST inserts nothing');
    },

    'stage3 villa required validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000011', 'OF3 ویلا'), office3_spec_fixture('villa'));
        unset($post['land_villa'], $post['built_villa']);
        $r = office3_register_post($pdo, 'villa', $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ زمین را وارد کنید.'), 'missing land_villa shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً زیربنا را وارد کنید.'), 'missing built_villa shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid villa POST inserts nothing');
    },

    'stage3 land required validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000012', 'OF3 زمین'), office3_spec_fixture('land'));
        unset($post['land_area'], $post['land_type'], $post['land_ownership']);
        $r = office3_register_post($pdo, 'land', $post);
        t_ok(str_contains($r['output'], 'لطفاً مساحت زمین را وارد کنید.'), 'missing land_area shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً کاربری زمین را انتخاب کنید.'), 'missing land_type shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً وضعیت مالکیت را انتخاب کنید.'), 'missing land_ownership shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid land POST inserts nothing');
    },

    'stage3 land presale branch validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000012', 'OF3 زمین'), office3_spec_fixture('land'));
        $post['transaction_type'] = 'پیش فروش';
        unset($post['price_sell']);
        $r = office3_register_post($pdo, 'land', $post);
        t_ok(str_contains($r['output'], 'لطفاً قیمت کل (پیش فروش) را وارد کنید.'), 'missing total_price shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid presale POST inserts nothing');
    },

    'stage3 commercial required validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000013', 'OF3 تجاری'), office3_spec_fixture('commercial'));
        unset($post['area_comm']);
        $r = office3_register_post($pdo, 'commercial', $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ ملک را وارد کنید.'), 'missing area_comm shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid commercial POST inserts nothing');
    },

    'stage3 office required validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000014', 'OF3 اداری'), office3_spec_fixture('office'));
        unset($post['office_area'], $post['office_condition'], $post['office_usage']);
        $r = office3_register_post($pdo, 'office', $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ واحد را وارد کنید.'), 'missing office_area shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً وضعیت واحد را انتخاب کنید.'), 'missing office_condition shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً کاربری واحد را انتخاب کنید.'), 'missing office_usage shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid office POST inserts nothing');
    },

    'stage3 garden required validation' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $before = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
        $post = array_merge(office3_base_fixture('0900000015', 'OF3 باغ'), office3_spec_fixture('garden'));
        unset($post['garden_area'], $post['document_type']);
        $r = office3_register_post($pdo, 'garden', $post);
        t_ok(str_contains($r['output'], 'لطفاً مساحت باغ را وارد کنید.'), 'missing garden_area shows site-identical error');
        t_ok(str_contains($r['output'], 'لطفاً نوع سند را انتخاب کنید.'), 'missing document_type shows site-identical error');
        t_eq($before, (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(), 'invalid garden POST inserts nothing');
    },
];
