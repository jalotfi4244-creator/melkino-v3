<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تنظیمات گزینه‌های فرم (مرحله ۱۶)
 *--------------------------------------------------------------------------
 * ویرایش کمبوباکس‌های فرم ثبت (همان کاتالوگ سایت، جدول settings).
 * هر خط یک گزینه؛ خط خالی نادیده گرفته می‌شود؛ تکراری حذف می‌شود.
 */

declare(strict_types=1);

require_once __DIR__ . '/_settings.php';

$pdo = office_db();
office_settings_boot($pdo);
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'save') {
            $raw = $_POST['combos'] ?? [];
            $data = [];
            if (is_array($raw)) {
                foreach ($raw as $k => $text) {
                    if (!is_string($k) || $k === '') {
                        continue;
                    }
                    if (is_array($text)) {
                        $data[$k] = array_map('strval', $text);
                        continue;
                    }
                    $lines = preg_split("/\r\n|\r|\n/", (string)$text);
                    $data[$k] = is_array($lines) ? $lines : [];
                }
            }
            $err = office_combos_save($data);
            if ($err === null) {
                office_redirect('settings.php?saved=1');
                $flash = 'تنظیمات ذخیره شد.';
            } else {
                $flashErr = $err;
            }
        } elseif ($action === 'save_global') {
            $data = [];
            foreach (array_keys(office_global_defaults()) as $k) {
                if (in_array($k, office_global_bools(), true)) {
                    $data[$k] = !empty($_POST['g'][$k]);
                } elseif (isset($_POST['g'][$k])) {
                    $data[$k] = $_POST['g'][$k];
                }
            }
            $me = office_is_logged_in() ? office_user() : [];
            $err = office_global_save($pdo, $data, isset($me['id']) ? (int)$me['id'] : null);
            if ($err === null) {
                office_redirect('settings.php?gsaved=1');
                $flash = 'تنظیمات عمومی ذخیره شد.';
            } else {
                $flashErr = $err;
            }
        } elseif ($action === 'reset') {
            if (office_combos_reset($pdo)) {
                office_redirect('settings.php?reset=1');
                $flash = 'به پیش‌فرض‌های سایت برگشت.';
            } else {
                $flashErr = 'بازنشانی ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('settings.php', 'تنظیمات گزینه‌های فرم');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
if (!$isPost) {
    if ((string)($_GET['saved'] ?? '') === '1') {
        echo '<div class="of-alert ok">تنظیمات ذخیره شد.</div>';
    }
    if ((string)($_GET['reset'] ?? '') === '1') {
        echo '<div class="of-alert ok">به پیش‌فرض‌های سایت برگشت.</div>';
    }
    if ((string)($_GET['gsaved'] ?? '') === '1') {
        echo '<div class="of-alert ok">تنظیمات عمومی ذخیره شد.</div>';
    }
}

$catalog = office_combos_catalog();
if (!$catalog) {
    echo '<div class="of-alert err">کاتالوگ گزینه‌ها در دسترس نیست (فایل form-options.php روی هاست نیست؟).</div>';
    office_shell_close();
    return;
}
?>
<style>
.of-combo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px}
.of-combo{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:10px 12px}
.of-combo b{display:block;font-size:13px;margin-bottom:6px}
.of-combo textarea{width:100%;box-sizing:border-box;min-height:96px;background:var(--bg);border:1px solid var(--line);border-radius:8px;padding:7px 9px;font:inherit;font-size:12.5px;line-height:1.9;color:var(--text)}
.of-combo code{font-size:11px;color:var(--muted)}
</style>

<div class="of-panel">
    <p class="of-muted">این گزینه‌ها در فرم‌های ثبت سایت و دفتر استفاده می‌شوند. هر خط یک گزینه.</p>
    <form method="post" action="settings.php">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="of-combo-grid">
            <?php foreach ($catalog as $key => $entry): ?>
                <div class="of-combo">
                    <b><?= office_h((string)($entry['label'] ?? $key)) ?></b>
                    <textarea name="combos[<?= office_h($key) ?>]" dir="auto"><?= office_h(implode("\n", array_map('strval', (array)($entry['items'] ?? [])))) ?></textarea>
                    <code dir="ltr"><?= office_h($key) ?></code>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:12px">
            <button class="of-btn" type="submit">ذخیره همه</button>
        </p>
    </form>
    <form method="post" action="settings.php" onsubmit="return confirm('همه سفارشی‌سازی‌ها حذف و به پیش‌فرض سایت برگردد؟');">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="reset">
        <button class="of-btn ghost" type="submit">بازنشانی به پیش‌فرض سایت</button>
    </form>
</div>

<div class="of-panel" style="margin-top:14px">
    <h3 style="margin-top:0">🌐 تنظیمات عمومی سایت</h3>
    <p class="of-muted">همان کلیدهای پنل سایت (نام، شهر، شعار، نمایش قیمت‌ها، تم پیش‌فرض، چیدمان کارت، حالت تعمیر).</p>
    <form method="post" action="settings.php">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save_global">
        <?php $gs = office_global_get($pdo); ?>
        <div class="of-combo-grid">
            <div class="of-combo"><b>نام سایت</b><input type="text" name="g[site_name]" value="<?= office_h((string)$gs['site_name']) ?>" style="width:100%;box-sizing:border-box"></div>
            <div class="of-combo"><b>شهر</b><input type="text" name="g[city]" value="<?= office_h((string)$gs['city']) ?>" style="width:100%;box-sizing:border-box"></div>
            <div class="of-combo"><b>شعار</b><input type="text" name="g[slogan]" value="<?= office_h((string)$gs['slogan']) ?>" style="width:100%;box-sizing:border-box"></div>
            <div class="of-combo"><b>تعداد آیتم هر صفحه (۴ تا ۱۰۰)</b><input type="number" name="g[items_per_page]" min="4" max="100" value="<?= (int)$gs['items_per_page'] ?>" style="width:100%;box-sizing:border-box"></div>
            <div class="of-combo"><b>تم پیش‌فرض</b>
                <select name="g[default_theme]" style="width:100%;box-sizing:border-box">
                    <option value="dark"<?= $gs['default_theme'] === 'dark' ? ' selected' : '' ?>>تیره</option>
                    <option value="light"<?= $gs['default_theme'] === 'light' ? ' selected' : '' ?>>روشن</option>
                </select>
            </div>
            <div class="of-combo"><b>چیدمان کارت</b>
                <select name="g[card_layout]" dir="ltr" style="width:100%;box-sizing:border-box">
                    <?php foreach (office_global_layouts() as $lay): ?>
                        <option value="<?= office_h($lay) ?>"<?= $gs['card_layout'] === $lay ? ' selected' : '' ?>><?= office_h($lay) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="of-combo-grid" style="margin-top:10px">
            <?php foreach (['show_prices' => 'نمایش قیمت‌ها', 'hide_all_prices' => 'پنهان‌کردن همه قیمت‌ها', 'enable_favorites' => 'علاقه‌مندی‌ها', 'enable_property_requests' => 'درخواست‌های ملک', 'enable_notifications' => 'اعلان‌ها', 'enable_property_calculator' => 'ماشین‌حساب ملک', 'maintenance_mode' => 'حالت تعمیر و نگهداری'] as $bk => $bl): ?>
                <div class="of-combo"><label><input type="checkbox" name="g[<?= office_h($bk) ?>]" value="1"<?= !empty($gs[$bk]) ? ' checked' : '' ?>> <?= office_h($bl) ?></label></div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:12px"><button class="of-btn" type="submit">ذخیره تنظیمات عمومی</button></p>
    </form>
</div>

<?php office_shell_close(); ?>
