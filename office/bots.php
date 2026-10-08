<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ربات‌ها و پیامک (مرحله ۳۲)
 *--------------------------------------------------------------------------
 * آینهٔ تب «ربات و کانال» پنل سایت: تنظیمات ربات‌ها و پنل پیامک،
 * تست‌های اتصال، روش‌های ورود، و تنظیمات انتشار آگهی در کانال.
 */

declare(strict_types=1);

require_once __DIR__ . '/_bots.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_bt_boot($pdo);
$flash = '';
$flashErr = '';
$preview = null;

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'save') {
            $data = [];
            foreach (['telegram_token', 'telegram_channel', 'telegram_bot_username', 'bale_token', 'bale_channel', 'bale_bot_username', 'eitaa_token', 'eitaa_bot_username', 'http_proxy', 'sms_api_key', 'sms_api_url', 'sms_sender_line', 'sms_provider', 'sms_password', 'sms_otp_line', 'sms_promo_line', 'sms_otp_body_id', 'sms_otp_template'] as $k) {
                $data[$k] = (string)($_POST[$k] ?? '');
            }
            $data['sms_enabled'] = !empty($_POST['sms_enabled']) ? '1' : '0';
            foreach (['login_telegram_enabled', 'login_bale_enabled', 'login_eitaa_enabled', 'login_sms_enabled'] as $k) {
                $data[$k] = !empty($_POST[$k]) ? '1' : '0';
            }
            [$ok, $msg] = office_bt_save($data);
        } elseif ($action === 'test_telegram') {
            [$ok, $msg] = office_bt_test_telegram();
        } elseif ($action === 'test_bale') {
            [$ok, $msg] = office_bt_test_bale();
        } elseif ($action === 'test_eitaa') {
            [$ok, $msg] = office_bt_test_eitaa();
        } elseif ($action === 'test_channel') {
            [$ok, $msg] = office_bt_test_channel((string)($_POST['channel'] ?? ''));
        } elseif ($action === 'test_bale_channel') {
            [$ok, $msg] = office_bt_test_bale_channel((string)($_POST['bale_channel'] ?? ''));
        } elseif ($action === 'test_sms') {
            [$ok, $msg] = office_bt_test_sms((string)($_POST['phone'] ?? ''));
        } elseif ($action === 'publish_save' || $action === 'publish_reset') {
            $pdata = [
                'property_type' => (string)($_POST['property_type'] ?? ''),
                'transaction_type' => (string)($_POST['transaction_type'] ?? ''),
                'fields' => isset($_POST['fields']) && is_array($_POST['fields']) ? array_map('strval', $_POST['fields']) : [],
                'header' => (string)($_POST['header'] ?? ''),
                'footer' => (string)($_POST['footer'] ?? ''),
            ];
            if ($action === 'publish_reset') {
                $pdata['reset_combo'] = 1;
            }
            [$ok, $msg] = office_bt_publish_save((string)($_POST['platform'] ?? 'telegram'), $pdata);
        } elseif ($action === 'publish_preview') {
            $preview = office_bt_preview($pdo, (string)($_POST['platform'] ?? 'telegram'), (string)($_POST['property_type'] ?? ''), (string)($_POST['transaction_type'] ?? ''));
            $ok = true;
            $msg = '';
        } else {
            $ok = false;
            $msg = 'عملیات ناشناخته.';
        }
        if ($msg !== '') {
            if ($ok) {
                $flash = $msg;
            } else {
                $flashErr = $msg;
            }
        }
    }
}

$s = office_bt_masked();
$platform = melkinoPublishPlatform((string)($_POST['platform'] ?? $_GET['platform'] ?? 'telegram'));
$ptype = trim((string)($_POST['property_type'] ?? $_GET['property_type'] ?? ''));
$trans = trim((string)($_POST['transaction_type'] ?? $_GET['transaction_type'] ?? ''));
$defs = melkinoPublishFieldDefs($ptype !== '' ? $ptype : null, $trans !== '' ? $trans : null);
$combos = melkinoPublishCombos();
$pset = melkinoPublishSettings($platform, $ptype, $trans);
$onFields = array_fill_keys((array)($pset['fields'] ?? []), true);

office_shell_open('promotions.php', 'ربات‌ها و پیامک');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<div class="of-panel">
<h3 style="margin-top:0">🤖 تنظیمات ربات‌ها و پنل پیامک</h3>
<p class="of-muted">توکن‌ها ماسک نمایش داده می‌شوند؛ ورودی خالی = نگه‌داشتن مقدار قبلی. برای پاک‌کردن عمدی، یک خط تیره (-) وارد کنید.</p>
<form method="post">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="save">
<div class="of-form-grid">
<div class="of-combo"><b>توکن تلگرام <?= office_h((string)($s['telegram_token_masked'] ?? '')) ?></b><input type="text" name="telegram_token" value="" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>کانال تلگرام</b><input type="text" name="telegram_channel" value="<?= office_h((string)($s['telegram_channel'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>یوزرنیم ربات تلگرام</b><input type="text" name="telegram_bot_username" value="<?= office_h((string)($s['telegram_bot_username'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>توکن بله <?= office_h((string)($s['bale_token_masked'] ?? '')) ?></b><input type="text" name="bale_token" value="" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>کانال بله</b><input type="text" name="bale_channel" value="<?= office_h((string)($s['bale_channel'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>یوزرنیم ربات بله</b><input type="text" name="bale_bot_username" value="<?= office_h((string)($s['bale_bot_username'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>توکن ایتا <?= office_h((string)($s['eitaa_token_masked'] ?? '')) ?></b><input type="text" name="eitaa_token" value="" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>یوزرنیم ربات ایتا</b><input type="text" name="eitaa_bot_username" value="<?= office_h((string)($s['eitaa_bot_username'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>پروکسی HTTP</b><input type="text" name="http_proxy" value="<?= office_h((string)($s['http_proxy'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
</div>
<h4>پنل پیامک</h4>
<div class="of-form-grid">
<div class="of-combo"><label><input type="checkbox" name="sms_enabled" value="1"<?= ($s['sms_enabled'] ?? '0') === '1' ? ' checked' : '' ?>> پنل پیامک فعال</label></div>
<div class="of-combo"><b>نام کاربری <?= office_h((string)($s['sms_api_key_masked'] ?? '')) ?></b><input type="text" name="sms_api_key" value="" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>رمز <?= office_h((string)($s['sms_password_masked'] ?? '')) ?></b><input type="password" name="sms_password" value="" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>آدرس سرویس</b><input type="text" name="sms_api_url" value="<?= office_h((string)($s['sms_api_url'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>خط فرستنده</b><input type="text" name="sms_sender_line" value="<?= office_h((string)($s['sms_sender_line'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>سرویس‌دهنده</b><input type="text" name="sms_provider" value="<?= office_h((string)($s['sms_provider'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>خط خدماتی (OTP)</b><input type="text" name="sms_otp_line" value="<?= office_h((string)($s['sms_otp_line'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>خط تبلیغاتی</b><input type="text" name="sms_promo_line" value="<?= office_h((string)($s['sms_promo_line'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>کد الگو (bodyId)</b><input type="text" name="sms_otp_body_id" value="<?= office_h((string)($s['sms_otp_body_id'] ?? '')) ?>" dir="ltr" style="width:100%;box-sizing:border-box"></div>
<div class="of-combo"><b>قالب متن کد ورود ({code})</b><input type="text" name="sms_otp_template" value="<?= office_h((string)($s['sms_otp_template_masked'] ?? '')) ?>" style="width:100%;box-sizing:border-box"></div>
</div>
<h4>🔐 روش‌های ورود کاربران</h4>
<p>
<label><input type="checkbox" name="login_telegram_enabled" value="1"<?= ($s['login_telegram_enabled'] ?? '1') === '1' ? ' checked' : '' ?>> تلگرام</label>
&nbsp;<label><input type="checkbox" name="login_bale_enabled" value="1"<?= ($s['login_bale_enabled'] ?? '1') === '1' ? ' checked' : '' ?>> بله</label>
&nbsp;<label><input type="checkbox" name="login_eitaa_enabled" value="1"<?= ($s['login_eitaa_enabled'] ?? '1') === '1' ? ' checked' : '' ?>> ایتا</label>
&nbsp;<label><input type="checkbox" name="login_sms_enabled" value="1"<?= ($s['login_sms_enabled'] ?? '0') === '1' ? ' checked' : '' ?>> پیامک</label>
</p>
<p><button class="of-btn" type="submit">ذخیره تنظیمات</button></p>
</form>
</div>

<div class="of-panel">
<h3 style="margin-top:0">🔌 تست اتصال</h3>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_telegram">
<button class="of-btn ghost" type="submit">تست تلگرام</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_bale">
<button class="of-btn ghost" type="submit">تست بله</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_eitaa">
<button class="of-btn ghost" type="submit">تست ایتا</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_channel">
<input type="text" name="channel" placeholder="شناسه کانال تلگرام" dir="ltr" value="<?= office_h((string)($s['telegram_channel'] ?? '')) ?>">
<button class="of-btn ghost" type="submit">تست کانال</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_bale_channel">
<input type="text" name="bale_channel" placeholder="شناسه کانال بله" dir="ltr" value="<?= office_h((string)($s['bale_channel'] ?? '')) ?>">
<button class="of-btn ghost" type="submit">تست کانال بله</button>
</form>
<form method="post" style="display:inline" onsubmit="return confirm('یک پیامک واقعی ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test_sms">
<input type="text" name="phone" placeholder="09123456789" dir="ltr">
<button class="of-btn ghost" type="submit">تست پیامک واقعی</button>
</form>
</div>

<div class="of-panel">
<h3 style="margin-top:0">📢 تنظیمات انتشار آگهی در کانال</h3>
<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
<select name="platform">
<option value="telegram"<?= $platform === 'telegram' ? ' selected' : '' ?>>تلگرام</option>
<option value="bale"<?= $platform === 'bale' ? ' selected' : '' ?>>بله</option>
</select>
<select name="property_type">
<option value="">همه انواع ملک</option>
<?php foreach ((array)($combos['property_types'] ?? []) as $t) : ?><option value="<?= office_h($t) ?>"<?= $ptype === $t ? ' selected' : '' ?>><?= office_h($t) ?></option><?php endforeach; ?>
</select>
<select name="transaction_type">
<option value="">همه معاملات</option>
<?php foreach ((array)($combos['transactions'] ?? []) as $t) : ?><option value="<?= office_h($t) ?>"<?= $trans === $t ? ' selected' : '' ?>><?= office_h($t) ?></option><?php endforeach; ?>
</select>
<button class="of-btn ghost" type="submit">نمایش</button>
</form>
<?php if (!empty($pset['is_override'])) : ?><p class="of-alert ok">برای این ترکیب، تنظیم اختصاصی ذخیره شده است.</p><?php endif; ?>
<form method="post">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="publish_save">
<input type="hidden" name="platform" value="<?= office_h($platform) ?>">
<input type="hidden" name="property_type" value="<?= office_h($ptype) ?>">
<input type="hidden" name="transaction_type" value="<?= office_h($trans) ?>">
<div class="of-combo-grid">
<?php foreach ($defs as $fk => $fd) : ?>
<div class="of-combo"><label><input type="checkbox" name="fields[]" value="<?= office_h((string)$fk) ?>"<?= isset($onFields[$fk]) ? ' checked' : '' ?>> <?= office_h((string)(is_array($fd) ? ($fd['emoji'] ?? '') : '')) ?> <?= office_h((string)(is_array($fd) ? ($fd['label'] ?? $fk) : (string)$fd)) ?></label></div>
<?php endforeach; ?>
</div>
<?php if ($ptype === '' && $trans === '') : ?>
<p><b>متن بالای پیام (header)</b><br><textarea name="header" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)($pset['header'] ?? '')) ?></textarea></p>
<p><b>متن پایین پیام (footer)</b><br><textarea name="footer" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)($pset['footer'] ?? '')) ?></textarea></p>
<?php endif; ?>
<p>
<button class="of-btn" type="submit">ذخیره تنظیمات انتشار</button>
<button class="of-btn ghost" type="submit" formaction="bots.php" name="action" value="publish_preview">👁 پیش‌نمایش متن</button>
<?php if ($ptype !== '' || $trans !== '') : ?>
<button class="of-btn ghost" type="submit" name="action" value="publish_reset">↩ حذف تنظیم اختصاصی این ترکیب</button>
<?php endif; ?>
</p>
</form>
<?php if ($preview !== null) : ?>
<h4>👁 پیش‌نمایش<?= $preview['is_sample'] ? ' (آگهی نمونه)' : ' — ' . office_h($preview['ad_title']) ?></h4>
<pre dir="auto" style="white-space:pre-wrap;background:var(--bg);border:1px solid var(--line);border-radius:8px;padding:10px"><?= office_h($preview['text']) ?></pre>
<?php endif; ?>
</div>
<?php office_shell_close(); ?>
