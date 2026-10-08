<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-14 tests: files map (same as site).
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage14
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';
if (!function_exists('office_test_pdo')) {
    require __DIR__ . '/OfficeStage1Test.php';
}
if (!function_exists('office2_login_session')) {
    require __DIR__ . '/OfficeStage2Test.php';
}

if (!function_exists('office14_clean')) {
    function office14_clean(PDO $pdo): void
    {
        try {
            $pdo->exec("DELETE FROM ads WHERE id LIKE 'OF14-%'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office14_get')) {
    /** @return array{output:string,redirect:string} */
    function office14_get(PDO $pdo): array
    {
        office_test_reset();
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        return office_test_run_page('map.php');
    }
}

if (!function_exists('office14_api')) {
    /**
     * اجرای واقعی map-api.php سایت در زیردرایور CLI روی دیتابیس تست.
     * @return array<string,mixed>|null
     */
    function office14_api(array $get): ?array
    {
        $wrap = sys_get_temp_dir() . '/of14api_' . getmypid() . '.php';
        file_put_contents($wrap, '<?php $_GET = ' . var_export($get, true) . '; require "/home/user/melkino-now/map-api.php";');
        $cmd = escapeshellarg(PHP_BINARY) . ' -d auto_prepend_file=/home/user/qa/force-test-db.php ' . escapeshellarg($wrap) . ' 2>/dev/null';
        $out = shell_exec($cmd);
        @unlink($wrap);
        if (!is_string($out) || $out === '') {
            return null;
        }
        $d = json_decode($out, true);
        return is_array($d) ? $d : null;
    }
}

return [
    'stage14 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage14 map requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = office_test_run_page('map.php');
        t_eq('login.php', $r['redirect'], 'redirects guests');
    },

    'stage14 loads same leaflet assets as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $r = office14_get($pdo);
        t_ok(str_contains($r['output'], 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'), 'leaflet css');
        t_ok(str_contains($r['output'], 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'), 'leaflet js');
        t_ok(str_contains($r['output'], '../map.css'), 'site map.css');
        t_ok(str_contains($r['output'], '../map-markers.js'), 'site map-markers.js');
        t_ok(str_contains($r['output'], 'assets/map.js'), 'office map.js');
    },

    'stage14 renders same DOM contract as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $r = office14_get($pdo);
        foreach (['id="mkMapCanvas"', 'id="mkMapSheet"', 'id="mkMapTx"', 'id="mkMapTypes"', 'data-tx="فروش"', 'data-tx="اجاره"', 'data-pt="آپارتمان"', 'data-pt="زمین"'] as $needle) {
            t_ok(str_contains($r['output'], $needle), 'DOM: ' . $needle);
        }
        t_ok(!str_contains($r['output'], '<circle'), 'no offline SVG dots anymore');
    },

    'stage14 exposes same map config as site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $r = office14_get($pdo);
        t_ok(str_contains($r['output'], 'window.MELKINO_MAP ='), 'MELKINO_MAP present');
        t_ok(str_contains($r['output'], 'tile.openstreetmap.org'), 'OSM tile url from site settings');
        t_ok(str_contains($r['output'], 'center_lat'), 'center from site settings');
        t_ok(str_contains($r['output'], 'marker_colors'), 'marker colors from site settings');
        t_ok(str_contains($r['output'], 'window.MELKINO_MAP_THEME ='), 'theme present');
        t_ok(str_contains($r['output'], 'window.MELKINO_MARKER_SHAPES ='), 'shapes present');
    },

    'stage14 office map.js matches site except two paths' => static function (): void {
        $site = (string)file_get_contents(dirname(__DIR__) . '/map.js');
        $office = (string)file_get_contents(dirname(__DIR__) . '/office/assets/map.js');
        // حذف هدر ۴خطی دفتر و برگرداندن ۲ مسیر ← باید عین سایت شود
        $body = implode("\n", array_slice(explode("\n", $office), 4));
        $back = str_replace(
            ["fetch('../map-api.php?'", 'href="file-edit.php?id='],
            ["fetch('map-api.php?'", 'href="property-details.php?id='],
            $body
        );
        t_eq($site, $back, 'byte-identical to site map.js except two paths');
        t_ok(str_contains($office, "fetch('../map-api.php?'"), 'office api path used');
        t_ok(str_contains($office, 'href="file-edit.php?id='), 'office edit link used');
    },

    'stage14 site map-api returns fixture pin' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office14_clean($pdo);
        $st = $pdo->prepare("INSERT INTO ads (id, title, status, transaction_type, property_type, location, area, phone, latitude, longitude, created_at)
            VALUES ('OF14-API-1', 'OF14 پین', 'published', 'فروش', 'آپارتمان', 'OF14', '100', '09140000001', '35.7000000', '51.4000000', NOW())");
        $st->execute();

        $d = office14_api(['action' => 'properties', 'north' => '36', 'south' => '35', 'east' => '52', 'west' => '51']);
        t_ok(is_array($d), 'api answered JSON');
        t_ok(!empty($d['success']), 'api success flag');
        $ids = array_column((array)($d['items'] ?? []), 'id');
        t_ok(in_array('OF14-API-1', $ids, true), 'fixture pin returned');
        foreach ((array)($d['items'] ?? []) as $it) {
            if (($it['id'] ?? '') === 'OF14-API-1') {
                $loc = $it['public_location'] ?? null;
                t_ok(is_array($loc), 'public_location present');
                t_ok(abs((float)$loc['latitude'] - 35.7) < 0.002, 'masked lat near real');
                t_ok(abs((float)$loc['longitude'] - 51.4) < 0.002, 'masked lng near real');
            }
        }

        $far = office14_api(['action' => 'properties', 'north' => '30', 'south' => '29', 'east' => '50', 'west' => '49']);
        $farIds = array_column((array)($far['items'] ?? []), 'id');
        t_ok(!in_array('OF14-API-1', $farIds, true), 'out-of-bounds excluded');
        office14_clean($pdo);
    },
];
