<?php
declare(strict_types=1);

/**
 * Melkino V2 — Office (Shahr) stage-4 tests: price display (rent deposit+rent,
 * trimmed trailing zeros, thousands separators) in files + dashboard.
 *
 * SAFETY: same guard — DB cases run ONLY on melkino_test.
 *
 * Run: php -d auto_prepend_file=/home/user/qa/force-test-db.php tests/run.php --filter=OfficeStage4
 */
if (!defined('OFFICE_NOEXIT')) {
    define('OFFICE_NOEXIT', true);
}
require_once dirname(__DIR__) . '/office/_lib.php';

if (!function_exists('office4_ensure_rent_row')) {
    function office4_ensure_rent_row(PDO $pdo): void
    {
        foreach (['OF4-RENT-1'] as $id) {
            $pdo->prepare('DELETE FROM ad_amenities WHERE ad_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM images WHERE ad_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([$id]);
        }
        $pdo->prepare("INSERT INTO ads (id, title, status, transaction_type, property_type, location, area, rooms, deposit, rent_monthly, phone, created_at)
            VALUES ('OF4-RENT-1','OF4 آپارتمان اجاره','published','اجاره','آپارتمان','OF4 تهران','100','2','500000000','30000000','09140000001',NOW())")->execute();
    }
}

return [
    'stage4 db guard is armed' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_ok(true);
    },

    'stage4 money formatter groups full number, strips zero decimals' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_eq('18,000,000,000', office_format_money('18000000000'));
        t_eq('500,000,000', office_format_money('500000000'));
        t_eq('30,000,000', office_format_money('30000000'));
        t_eq('12,345', office_format_money('12345'), 'grouped as-is');
        t_eq('2,800,000,000', office_format_money('۲۸۰۰۰۰۰۰۰۰'), 'persian digits accepted');
        t_eq('2,800,000,000', office_format_money('2,800,000,000'), 'pre-grouped input accepted');
        t_eq('2,800,000,000', office_format_money('2800000000.00'), 'zero decimals after dot stripped');
        t_eq('1,234.50', office_format_money('1234.50'), 'non-zero decimals kept');
        t_eq('—', office_format_money('0'));
        t_eq('—', office_format_money(''));
        t_eq('—', office_format_money(null));
    },

    'stage4 ad price cell branches' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $rent = office_ad_price(['transaction_type' => 'اجاره', 'deposit' => '500000000', 'rent_monthly' => '30000000', 'full_rent_enabled' => '0', 'full_rent' => '']);
        t_ok(str_contains($rent, 'ودیعه') && str_contains($rent, '500,000,000'), 'rent shows full deposit');
        t_ok(str_contains($rent, 'اجاره') && str_contains($rent, '30,000,000'), 'rent shows full monthly rent');
        t_ok(str_contains($rent, '<span dir="ltr">500,000,000</span>'), 'full deposit in ltr span');
        $full = office_ad_price(['transaction_type' => 'اجاره', 'deposit' => '', 'rent_monthly' => '', 'full_rent_enabled' => '1', 'full_rent' => '1000000000']);
        t_ok(str_contains($full, 'رهن کامل') && str_contains($full, '1,000,000,000'), 'full rent branch');
        $old = office_ad_price(['transaction_type' => 'رهن و اجاره', 'deposit' => '500000000', 'rent_monthly' => '', 'full_rent_enabled' => '0']);
        t_ok(str_contains($old, 'ودیعه') && !str_contains($old, 'اجاره <'), 'legacy tx label treated as rent');
        $sale = office_ad_price(['transaction_type' => 'فروش', 'price_sell' => '18000000000']);
        t_eq('قیمت <span dir="ltr">18,000,000,000</span>', $sale, 'sale branch exact');
        $pre = office_ad_price(['transaction_type' => 'پیش فروش', 'total_price' => '3000000000']);
        t_ok(str_contains($pre, 'قیمت کل') && str_contains($pre, '3,000,000,000'), 'presale branch');
        t_eq('—', office_ad_price(['transaction_type' => 'فروش']), 'empty price shows dash');
    },

    'stage4 files page shows rent deposit and rent' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office4_ensure_rent_row($pdo);
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['q' => 'OF4-RENT-1'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], 'OF4-RENT-1'), 'rent fixture found');
        t_ok(str_contains($r['output'], 'ودیعه') && str_contains($r['output'], '500,000,000'), 'files shows full deposit grouped');
        t_ok(str_contains($r['output'], '30,000,000'), 'files shows full monthly rent grouped');
        t_ok(!str_contains($r['output'], '5,000,000'), 'files does not show hundred-trimmed deposit');
    },

    'stage4 files page shows full sale price grouped' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['q' => 'QA-AD-1'];
        $r = office_test_run_page('files.php');
        t_ok(str_contains($r['output'], '18,000,000,000'), 'seed sale price shown full + grouped');
        t_ok(!str_contains($r['output'], '180,000,000'), 'hundred-trimmed value gone');
    },

    'stage4 dashboard shows price column' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office4_ensure_rent_row($pdo);
        office_test_reset();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = 999;
        $_SESSION['admin_username'] = 'qa_admin';
        $r = office_test_run_page('index.php');
        t_ok(str_contains($r['output'], 'قیمت'), 'dashboard has price column');
        t_ok(str_contains($r['output'], 'OF4-RENT-1'), 'dashboard lists rent fixture');
        t_ok(str_contains($r['output'], '500,000,000') && str_contains($r['output'], '30,000,000'), 'dashboard shows full deposit + rent');
    },

    'stage4 group digits helper' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        t_eq('2,800,000,000', office_group_digits('2800000000'));
        t_eq('2,800,000,000', office_group_digits('۲۸۰۰۰۰۰۰۰۰'), 'persian input grouped');
        t_eq('2,800,000,000', office_group_digits('2,800,000,000'), 'idempotent');
        t_eq('', office_group_digits(''));
        t_eq('0', office_group_digits('0'));
        t_eq(8, count(office_money_fields()), 'money field list intact');
    },

    'stage4 money inputs carry live-format hook' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office_test_reset();
        office2_login_session($pdo);
        $_GET = ['type' => 'apartment'];
        $r = office_test_run_page('register.php');
        foreach (['price_sell', 'deposit', 'rent_monthly', 'full_rent', 'total_price', 'down_payment', 'loan_amount', 'loan_installment'] as $nm) {
            $pos = strpos($r['output'], 'name="' . $nm . '"');
            t_ok($pos !== false, "money input rendered: $nm");
            $tag = substr($r['output'], max(0, $pos - 60), 220);
            t_ok(str_contains($tag, 'data-money="1"'), "money input has live-format hook: $nm");
        }
        $js = (string)file_get_contents(dirname(__DIR__) . '/office/assets/office.js');
        t_ok(str_contains($js, 'input[data-money]'), 'shell js formats money inputs while typing');
    },

    'stage4 grouped POST is stored clean like site' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        office2_clean_fixtures($pdo);
        $post = office2_sale_fixture();
        $post['phone'] = '09000000003';
        $post['price_sell'] = '2,800,000,000';
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'ثبت شد'), 'grouped price saves');
        $stored = $pdo->query("SELECT price_sell FROM ads WHERE phone = '09000000003' ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        t_eq(2800000000.0, (float)$stored, 'grouped price stored as clean number');
    },

    'stage4 failed POST re-renders price grouped' => static function (): void {
        $pdo = office_test_pdo();
        if ($pdo === null) {
            echo "\n    (SKIP: not on melkino_test)";
            return;
        }
        $post = office2_sale_fixture();
        unset($post['area_apt']);
        $post['price_sell'] = '2800000000';
        $r = office2_register_post($pdo, $post);
        t_ok(str_contains($r['output'], 'لطفاً متراژ ملک را وارد کنید.'), 'validation still fires');
        t_ok(str_contains($r['output'], 'value="2,800,000,000"'), 'sticky price re-rendered grouped');
    },
];
