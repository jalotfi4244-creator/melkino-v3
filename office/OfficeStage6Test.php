<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-6 tests: file edit page.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage6
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
require_once dirname(__DIR__) . '/office/_update.php';

if (!function_exists('office6_clean')) {
    function office6_clean(PDO $pdo): void
    {
        foreach (['09160000001', '09160000002'] as $ph) {
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

if (!function_exists('office6_make_apt')) {
    /** ثبت واقعی یک آپارتمان از مسیر کامل دفتر؛ برمی‌گرداند: کد فایل. */
    function office6_make_apt(PDO $pdo): string
    {
        office6_clean($pdo);
        $post = office2_sale_fixture();
        $post['phone'] = '09160000001';
        $post['title'] = 'OF6 آپارتمان ویرایش';
        office_test_reset();
        $_FILES = [];
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['type' => 'apartment'];
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        office_test_run_page('register.php');
        $id = (string)$pdo->query("SELECT id FROM ads WHERE phone = '09160000001' ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        if ($id === '') {
            throw new RuntimeException('fixture insert failed');
        }
        return $id;
    }
}

if (!function_exists('office6_edit_post')) {
    /** @return array{output:string,redirect:string} */
    function office6_edit_post(PDO $pdo, string $id, array $post, mixed $files = null): array
    {
        office_test_reset();
        $_FILES = [];
        office2_login_session($pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['id' => $id];
        $_POST = $post;
        $_POST['csrf_token'] = office_csrf();
        if ($files !== null) {
            $_FILES = ['images' => $files];
        }
        return office_test_run_page('file-edit.php');
    }
}

if (!function_exists('office6_full_post')) {
    /** @return array<string,mixed> فرم کامل ویرایش بر اساس مقادیر لودشده + تغییرات */
    function office6_full_post(PDO $pdo, string $id, array $overrides = []): array
    {
        $loaded = office_load_ad_for_edit($pdo, $id);
        if ($loaded === null) {
            throw new RuntimeException('load failed for ' . $id);
        }
        $post = $loaded['values'];
        unset($post['building_age']);
        // شبیه‌سازی مرورگر: چک‌باکس تیک‌نخورده اصلاً پست نمی‌شود (نه با مقدار خالی)
        foreach (['is_not_keyed', 'is_old', 'is_renovated', 'is_vacant', 'full_rent_enabled', 'has_loan', 'exchange_interested', 'price_condition'] as $ck) {
            if (($post[$ck] ?? null) === '') {
                unset($post[$ck]);
            }
        }
        return array_merge($post, $overrides);
    }
}

return [
    'stage6 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage6 edit requires login' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        office_test_reset();
        $_GET = ['id' => $id];
        $r = office_test_run_page('file-edit.php');
        t_eq('login.php', $r['redirect'], 'unauth GET redirects');
        office_test_reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['id' => $id];
        $_POST = ['title' => 'hacked'];
        $r = office_test_run_page('file-edit.php');
        t_eq('login.php', $r['redirect'], 'unauth POST redirects');
        t_eq('OF6 آپارتمان ویرایش', (string)$pdo->query('SELECT title FROM ads WHERE id = ' . $pdo->quote($id))->fetchColumn(), 'unauth POST changes nothing');
    },

    'stage6 edit handles unknown id and type' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['id' => 'NO-SUCH-ID'];
        $r = office_test_run_page('file-edit.php');
        t_ok(str_contains($r['output'], 'یافت نشد'), 'unknown id shows not-found');
        office6_clean($pdo);
        $pdo->prepare("INSERT INTO ads (id, title, status, transaction_type, property_type, phone, created_at) VALUES ('OF6-BAD-1','OF6 ناشناس','published','فروش','نامشخص','09160000002',NOW())")->execute();
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['id' => 'OF6-BAD-1'];
        $r = office_test_run_page('file-edit.php');
        t_ok(str_contains($r['output'], 'قابل ویرایش'), 'unsupported type shows message');
        $n = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE id = 'OF6-BAD-1'")->fetchColumn();
        t_eq(1, $n, 'bad row untouched');
    },

    'stage6 edit GET prefills same form with grouped price' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['id' => $id];
        $r = office_test_run_page('file-edit.php');
        t_ok(str_contains($r['output'], 'OF6 آپارتمان ویرایش'), 'title prefilled');
        t_ok(str_contains($r['output'], 'value="2,800,000,000"'), 'price prefilled grouped (money rule)');
        t_ok(str_contains($r['output'], 'name="area_apt"'), 'spec fields present');
        t_ok(str_contains($r['output'], 'value="published" selected'), 'status preselected');
        t_ok(str_contains($r['output'], 'value="sold"'), 'edit offers sold status');
        t_ok(str_contains($r['output'], 'value="expired"'), 'edit offers expired status');
    },

    'stage6 edit POST updates mapping and replaces amenities' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        $oldUpdated = (string)$pdo->query('SELECT updated_at FROM ads WHERE id = ' . $pdo->quote($id))->fetchColumn();
        $post = office6_full_post($pdo, $id, [
            'title' => 'OF6 ویرایش‌شده',
            'area_apt' => '95',
            'price_sell' => '3,100,000,000',
            'status' => 'sold',
            'amenities_apt' => ['استخر'],
        ]);
        $r = office6_edit_post($pdo, $id, $post);
        t_ok(str_contains($r['output'], 'ذخیره شد'), 'update shows success panel');
        $row = $pdo->query('SELECT * FROM ads WHERE id = ' . $pdo->quote($id))->fetch(PDO::FETCH_ASSOC);
        t_eq('OF6 ویرایش‌شده', (string)($row['title'] ?? ''), 'title updated');
        t_eq('95', (string)($row['area'] ?? ''), 'area updated');
        t_eq(3100000000.0, (float)($row['price_sell'] ?? 0), 'grouped price stored clean');
        t_eq('sold', (string)($row['status'] ?? ''), 'status changed to sold');
        t_ok((string)($row['updated_at'] ?? '') !== '', 'updated_at stamped');
        t_ok((string)($row['updated_at'] ?? '') !== $oldUpdated || $oldUpdated === '', 'updated_at changed');
        t_ok((string)($row['created_at'] ?? '') !== '', 'created_at preserved');
        $amen = $pdo->query('SELECT a.name FROM ad_amenities l JOIN amenities a ON a.id = l.amenity_id WHERE l.ad_id = ' . $pdo->quote($id))->fetchAll(PDO::FETCH_COLUMN) ?: [];
        t_eq(['استخر'], array_values($amen), 'amenities replaced');
    },

    'stage6 edit POST validates like register' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        $post = office6_full_post($pdo, $id, ['title' => 'نباید ذخیره شود']);
        unset($post['area_apt']);
        $r = office6_edit_post($pdo, $id, $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ ملک را وارد کنید.'), 'validation error shown');
        t_eq('OF6 آپارتمان ویرایش', (string)$pdo->query('SELECT title FROM ads WHERE id = ' . $pdo->quote($id))->fetchColumn(), 'invalid POST changes nothing');
    },

    'stage6 edit POST appends images keeping old ones' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        $before = (int)$pdo->query('SELECT COUNT(*) FROM images WHERE ad_id = ' . $pdo->quote($id))->fetchColumn();
        $post = office6_full_post($pdo, $id);
        $r = office6_edit_post($pdo, $id, $post, office2_png_file('edit-add.png'));
        t_ok(str_contains($r['output'], 'ذخیره شد'), 'update with image succeeds');
        $after = (int)$pdo->query('SELECT COUNT(*) FROM images WHERE ad_id = ' . $pdo->quote($id))->fetchColumn();
        t_eq($before + 1, $after, 'new image appended');
    },

    'stage6 edit POST rejects bad status keeping old' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $id = office6_make_apt($pdo);
        $post = office6_full_post($pdo, $id, ['status' => 'hacked', 'title' => 'OF6 وضعيت']);
        $r = office6_edit_post($pdo, $id, $post);
        t_ok(str_contains($r['output'], 'ذخیره شد'), 'update still succeeds');
        $row = $pdo->query('SELECT status, title FROM ads WHERE id = ' . $pdo->quote($id))->fetch(PDO::FETCH_ASSOC);
        t_eq('published', (string)($row['status'] ?? ''), 'bad status ignored, old kept');
        t_eq('OF6 وضعيت', (string)($row['title'] ?? ''), 'other fields still updated');
    },
];
