<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — برنامه پیامک (مرحله ۳۳)
 *--------------------------------------------------------------------------
 * آینهٔ تب «برنامه پیامک» پنل سایت: وضعیت و شمارنده‌ها، تنظیمات
 * اهداف، اجرای دستی، اعتبار، تست، صندوق خروجی، لغو عضویت‌ها و
 * جست‌وجوهای ذخیره‌شده.
 */

declare(strict_types=1);

require_once __DIR__ . '/_smsprog.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_sp_boot($pdo);
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'save') {
            $body = [
                'enabled' => !empty($_POST['enabled']),
                'quiet_start' => $_POST['quiet_start'] ?? null,
                'quiet_end' => $_POST['quiet_end'] ?? null,
                'lagoo11' => !empty($_POST['lagoo11']),
                'max_per_tick' => $_POST['max_per_tick'] ?? null,
                'site_url' => $_POST['site_url'] ?? null,
                'cron_token' => $_POST['cron_token'] ?? '',
                'saved_search' => is_array($_POST['saved_search'] ?? null) ? $_POST['saved_search'] : [],
                'request_match' => is_array($_POST['request_match'] ?? null) ? $_POST['request_match'] : [],
                'admin_alert' => is_array($_POST['admin_alert'] ?? null) ? $_POST['admin_alert'] : [],
                'marketing' => is_array($_POST['marketing'] ?? null) ? $_POST['marketing'] : [],
            ];
            [$ok, $msg] = office_sp_save($pdo, $body);
        } elseif ($action === 'tick') {
            [$ok, $msg] = office_sp_tick($pdo);
        } elseif ($action === 'balance') {
            [$ok, $msg] = office_sp_balance();
        } elseif ($action === 'test') {
            [$ok, $msg] = office_sp_test((string)($_POST['phone'] ?? ''));
        } elseif ($action === 'optout_add') {
            [$ok, $msg] = office_sp_optout_add($pdo, (string)($_POST['phone'] ?? ''), (string)($_POST['scope'] ?? 'all'));
        } elseif ($action === 'optout_del') {
            $ok = office_sp_optout_del($pdo, (int)($_POST['id'] ?? 0));
            $msg = $ok ? 'حذف شد.' : 'حذف ناموفق بود.';
        } elseif ($action === 'search_del') {
            $ok = office_sp_search_del($pdo, (int)($_POST['id'] ?? 0));
            $msg = $ok ? 'حذف شد.' : 'حذف ناموفق بود.';
        } else {
            $ok = false;
            $msg = 'عملیات ناشناخته.';
        }
        if ($ok) {
            $flash = $msg;
        } else {
            $flashErr = $msg;
        }
    }
}

$view = (string)($_GET['view'] ?? 'status');
if (!in_array($view, ['status', 'outbox', 'optouts', 'searches'], true)) {
    $view = 'status';
}
$st = office_sp_status($pdo);
$cfg = $st['cfg'];
$counts = $st['counts'];
$sms = $st['sms'];
$fGoal = (string)($_GET['goal'] ?? '');
$fStatus = (string)($_GET['status'] ?? '');
$fPhone = (string)($_GET['phone'] ?? '');
$outbox = $view === 'outbox' ? office_sp_outbox($pdo, $fGoal, $fStatus, $fPhone) : [];
$optouts = $view === 'optouts' ? office_sp_optouts($pdo) : [];
$searches = $view === 'searches' ? office_sp_searches($pdo) : [];

office_shell_open('promotions.php', 'برنامه پیامک');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<p>
<a class="of-btn<?= $view === 'status' ? '' : ' ghost' ?>" href="sms.php">⚙️ وضعیت و تنظیمات</a>
<a class="of-btn<?= $view === 'outbox' ? '' : ' ghost' ?>" href="sms.php?view=outbox">📤 صندوق خروجی</a>
<a class="of-btn<?= $view === 'optouts' ? '' : ' ghost' ?>" href="sms.php?view=optouts">🚫 لغو عضویت‌ها</a>
<a class="of-btn<?= $view === 'searches' ? '' : ' ghost' ?>" href="sms.php?view=searches">🔍 جست‌وجوهای ذخیره‌شده</a>
</p>

<?php if ($view === 'status') : ?>
<div class="of-panel">
<strong>سرویس‌دهنده:</strong> <?= office_h((string)($sms['provider'] ?? '')) ?> (<?= !empty($sms['enabled']) ? 'فعال ✅' : 'غیرفعال ⏸' ?>)
&nbsp;•&nbsp; <strong>در صف:</strong> <?= office_num($counts['queued']) ?>
&nbsp;•&nbsp; <strong>ارسال‌شده:</strong> <?= office_num($counts['sent']) ?>
&nbsp;•&nbsp; <strong>ناموفق:</strong> <?= office_num($counts['failed']) ?>
&nbsp;•&nbsp; <strong>آگهی منتظر:</strong> <?= office_num($counts['pending_ads']) ?>
&nbsp;•&nbsp; <strong>درخواست جدید:</strong> <?= office_num($counts['pending_requests']) ?>
&nbsp;•&nbsp; <strong>آخرین اجرا:</strong> <?= office_h(office_fa((string)($cfg['last_tick'] ?? '—'))) ?>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="tick">
<button class="of-btn" type="submit" onclick="return confirm('برنامه الان اجرا شود؟ (ارسال واقعی پیامک‌های در صف)');">▶ اجرای الان</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="balance">
<button class="of-btn ghost" type="submit">💳 اعتبار سامانه</button>
</form>
</div>

<div class="of-panel">
<h3 style="margin-top:0">✉️ تست ارسال واقعی</h3>
<form method="post" onsubmit="return confirm('یک پیامک واقعی ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="test">
<input type="text" name="phone" placeholder="09123456789" dir="ltr">
<button class="of-btn ghost" type="submit">ارسال تست</button>
</form>
</div>

<div class="of-panel">
<h3 style="margin-top:0">⚙️ تنظیمات برنامه</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save">
<p>
<label><input type="checkbox" name="enabled" value="1"<?= !empty($cfg['enabled']) ? ' checked' : '' ?>> برنامه فعال</label>
&nbsp;<label><input type="checkbox" name="lagoo11" value="1"<?= !empty($cfg['lagoo11']) ? ' checked' : '' ?>> لغو ۱۱؟</label>
&nbsp;<label>سکوت از <input type="number" name="quiet_start" min="0" max="23" value="<?= (int)$cfg['quiet_start'] ?>" style="width:60px"></label>
<label>تا <input type="number" name="quiet_end" min="0" max="23" value="<?= (int)$cfg['quiet_end'] ?>" style="width:60px"></label>
&nbsp;<label>سقف هر اجرا <input type="number" name="max_per_tick" min="1" max="100" value="<?= (int)$cfg['max_per_tick'] ?>" style="width:70px"></label>
</p>
<p><label>آدرس سایت <input type="text" name="site_url" dir="ltr" value="<?= office_h((string)$cfg['site_url']) ?>" style="min-width:260px"></label>
&nbsp;<label>توکن کرون <input type="text" name="cron_token" dir="ltr" value="" placeholder="<?= trim((string)$cfg['cron_token']) !== '' ? '(ثبت شده — خالی = نگه‌داشتن)' : '' ?>"></label></p>
<?php $ss = $cfg['saved_search']; ?>
<h4>🔍 اطلاع جست‌وجوهای ذخیره‌شده</h4>
<p><label><input type="checkbox" name="saved_search[on]" value="1"<?= !empty($ss['on']) ? ' checked' : '' ?>> فعال</label>
<label>سقف روزانه <input type="number" name="saved_search[cap_day]" min="1" max="10" value="<?= (int)$ss['cap_day'] ?>" style="width:60px"></label>
<label>حداقل تازه‌ها <input type="number" name="saved_search[min_new]" min="1" max="20" value="<?= (int)$ss['min_new'] ?>" style="width:60px"></label></p>
<p>قالب<br><textarea name="saved_search[template]" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)$ss['template']) ?></textarea></p>
<p>قالب خلاصه<br><textarea name="saved_search[digest_template]" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)$ss['digest_template']) ?></textarea></p>
<?php $rm = $cfg['request_match']; ?>
<h4>🎯 اطلاع تطبیق درخواست‌ها</h4>
<p><label><input type="checkbox" name="request_match[on]" value="1"<?= !empty($rm['on']) ? ' checked' : '' ?>> فعال</label>
<label>سقف روزانه <input type="number" name="request_match[cap_day]" min="1" max="10" value="<?= (int)$rm['cap_day'] ?>" style="width:60px"></label>
<label>حداقل امتیاز <input type="number" name="request_match[score_min]" min="0" max="100" step="0.5" value="<?= office_h((string)$rm['score_min']) ?>" style="width:70px"></label>
<label>حداقل تازه‌ها <input type="number" name="request_match[min_new]" min="1" max="20" value="<?= (int)$rm['min_new'] ?>" style="width:60px"></label></p>
<p>قالب<br><textarea name="request_match[template]" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)$rm['template']) ?></textarea></p>
<?php $aa = $cfg['admin_alert']; ?>
<h4>🚨 هشدار به ادمین</h4>
<p><label><input type="checkbox" name="admin_alert[on]" value="1"<?= !empty($aa['on']) ? ' checked' : '' ?>> فعال</label>
<label>آستانه آگهی <input type="number" name="admin_alert[ads_thr]" min="1" value="<?= (int)$aa['ads_thr'] ?>" style="width:70px"></label>
<label>آستانه درخواست <input type="number" name="admin_alert[req_thr]" min="1" value="<?= (int)$aa['req_thr'] ?>" style="width:70px"></label>
<label>شماره ادمین <input type="text" name="admin_alert[phone]" dir="ltr" value="<?= office_h((string)$aa['phone']) ?>"></label>
<label>حالت <select name="admin_alert[mode]"><option value="over"<?= ($aa['mode'] ?? '') === 'over' ? ' selected' : '' ?>>over</option><option value="every"<?= ($aa['mode'] ?? '') === 'every' ? ' selected' : '' ?>>every</option></select></label>
<label>هر N آگهی <input type="number" name="admin_alert[every_n]" min="1" max="500" value="<?= (int)($aa['every_n'] ?? 20) ?>" style="width:70px"></label>
<label>ضدتکرار (ساعت) <input type="number" name="admin_alert[debounce_h]" min="1" value="<?= (int)$aa['debounce_h'] ?>" style="width:70px"></label></p>
<p>قالب<br><textarea name="admin_alert[template]" rows="2" style="width:100%;box-sizing:border-box"><?= office_h((string)$aa['template']) ?></textarea></p>
<?php $mk = $cfg['marketing']; ?>
<h4>📣 بازاریابی</h4>
<p><label><input type="checkbox" name="marketing[on]" value="1"<?= !empty($mk['on']) ? ' checked' : '' ?>> فعال</label></p>
<p><button class="of-btn" type="submit">ذخیره تنظیمات</button></p>
</form>
</div>

<?php elseif ($view === 'outbox') : ?>
<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
<input type="hidden" name="view" value="outbox">
<input type="text" name="goal" placeholder="هدف" value="<?= office_h($fGoal) ?>">
<select name="status"><option value="">همه وضعیت‌ها</option>
<?php foreach (['queued' => 'در صف', 'sent' => 'ارسال‌شده', 'failed' => 'ناموفق'] as $k => $l) : ?><option value="<?= $k ?>"<?= $fStatus === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
</select>
<input type="text" name="phone" placeholder="شماره" dir="ltr" value="<?= office_h($fPhone) ?>">
<button class="of-btn" type="submit">🔍 فیلتر</button>
</form>
<p class="of-muted"><?= office_num(count($outbox)) ?> رکورد (آخرین ۱۵۰).</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>زمان</th><th>هدف</th><th>شماره</th><th>وضعیت</th><th>متن</th><th>نتیجه</th></tr>
<?php foreach ($outbox as $r) : ?>
<tr>
<td><?= office_h(office_fa($r['created_at'])) ?></td>
<td><?= office_h($r['goal']) ?></td>
<td dir="ltr"><?= office_h($r['phone']) ?></td>
<td><?= $r['status'] === 'sent' ? '✅ ارسال‌شده' : ($r['status'] === 'failed' ? '❌ ناموفق' : '⏳ در صف') ?></td>
<td><?= office_h($r['body']) ?></td>
<td class="of-muted" style="font-size:12px"><?= office_h($r['rec_id'] !== '' ? ('کد: ' . $r['rec_id']) : $r['fail_reason']) ?></td>
</tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'optouts') : ?>
<div class="of-panel">
<h3 style="margin-top:0">➕ افزودن لغو عضویت</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="optout_add">
<input type="text" name="phone" placeholder="09123456789" dir="ltr">
<select name="scope"><option value="all">همه</option><option value="alerts">هشدارها</option><option value="promo">تبلیغاتی</option></select>
<button class="of-btn" type="submit">افزودن</button>
</form>
</div>
<div class="of-table-wrap"><table class="of-table">
<tr><th>شماره</th><th>دامنه</th><th>منبع</th><th></th></tr>
<?php foreach ($optouts as $o) : ?>
<tr>
<td dir="ltr"><?= office_h((string)($o['phone'] ?? '')) ?></td>
<td><?= office_h((string)($o['scope'] ?? '')) ?></td>
<td><?= office_h((string)($o['source'] ?? '')) ?></td>
<td><form method="post" style="display:inline" onsubmit="return confirm('حذف شود؟');"><?= office_csrf_field() ?><input type="hidden" name="action" value="optout_del"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="of-btn ghost" type="submit">🗑</button></form></td>
</tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'searches') : ?>
<p class="of-muted"><?= office_num(count($searches)) ?> جست‌وجوی ذخیره‌شده.</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>کاربر</th><th>شرایط</th><th>ثبت</th><th></th></tr>
<?php foreach ($searches as $srow) : ?>
<tr>
<td><?= office_h((string)($srow['user_name'] ?? $srow['user_id'] ?? '')) ?></td>
<td class="of-muted" style="font-size:12px"><?= office_h(mb_substr(json_encode($srow, JSON_UNESCAPED_UNICODE) ?: '', 0, 160)) ?></td>
<td><?= office_h(office_fa((string)($srow['created_at'] ?? ''))) ?></td>
<td><form method="post" style="display:inline" onsubmit="return confirm('حذف شود؟');"><?= office_csrf_field() ?><input type="hidden" name="action" value="search_del"><input type="hidden" name="id" value="<?= (int)$srow['id'] ?>"><button class="of-btn ghost" type="submit">🗑</button></form></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php office_shell_close(); ?>
