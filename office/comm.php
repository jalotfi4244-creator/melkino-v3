<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ارتباطات و کمپین (مرحله ۳۴)
 *--------------------------------------------------------------------------
 * آینهٔ تب «ارتباطات» پنل سایت: داشبورد، مخاطبین، ارسال، قالب‌ها،
 * سگمنت، کمپین، اعلان‌های خودکار، گزارش‌ها، تنظیمات و ایمپورت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_comm.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_cm_boot($pdo);
$flash = '';
$flashErr = '';
$suggestOut = '';

if ((string)($_GET['export'] ?? '') === '1') {
    $file = office_cm_export_file($pdo, $_GET);
    if ($file === '' || !is_file($file)) {
        $flashErr = 'ساخت اکسل ممکن نشد.';
    } elseif (!defined('OFFICE_NOEXIT')) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="melkino_contacts_' . date('Y-m-d') . '.xlsx"');
        readfile($file);
        @unlink($file);
        exit;
    } else {
        echo 'OFFICE_COMM_EXPORT:' . basename($file) . ':' . filesize($file);
        @unlink($file);
        return;
    }
}

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'sync') {
            $n = commSyncContacts($pdo);
            $ok = true;
            $msg = $n . ' مخاطب همگام‌سازی شد.';
        } elseif ($action === 'save_contact') {
            [$ok, $msg] = office_cm_save_contact($pdo, $_POST);
        } elseif ($action === 'note') {
            [$ok, $msg] = office_cm_note($pdo, (int)($_POST['contact_id'] ?? 0), (string)($_POST['body'] ?? ''));
        } elseif ($action === 'followup') {
            [$ok, $msg] = office_cm_followup($pdo, $_POST);
        } elseif ($action === 'save_template') {
            [$ok, $msg] = office_cm_save_template($pdo, (int)($_POST['id'] ?? 0), (string)($_POST['name'] ?? ''), (string)($_POST['body'] ?? ''));
        } elseif ($action === 'delete_template') {
            $ok = office_cm_delete_template($pdo, (int)($_POST['id'] ?? 0));
            $msg = $ok ? 'قالب حذف شد.' : 'حذف ناموفق بود.';
        } elseif ($action === 'send') {
            $phones = array_filter(array_map('trim', preg_split('/\r\n|\n|,/', (string)($_POST['phones'] ?? '')) ?: []));
            $ids = isset($_POST['contact_ids']) && is_array($_POST['contact_ids']) ? array_map('intval', $_POST['contact_ids']) : [];
            $r = office_cm_send($pdo, ['body' => (string)($_POST['body'] ?? ''), 'contact_ids' => $ids, 'phones' => $phones, 'template_id' => (int)($_POST['template_id'] ?? 0), 'when' => (string)($_POST['when'] ?? 'now'), 'send_at' => (string)($_POST['send_at'] ?? '')]);
            $ok = ($r['ok'] + $r['deferred']) > 0;
            $msg = $r['message'];
        } elseif ($action === 'save_settings') {
            office_cm_save_settings($pdo, $_POST);
            $ok = true;
            $msg = 'تنظیمات ذخیره شد.';
        } elseif ($action === 'save_segment') {
            $crit = [];
            foreach (['status', 'role', 'search', 'hot', 'has_request', 'has_property', 'min_views', 'days', 'no_visit'] as $k) {
                if (isset($_POST['crit'][$k]) && $_POST['crit'][$k] !== '') {
                    $crit[$k] = $_POST['crit'][$k];
                }
            }
            [$ok, $msg] = office_cm_save_segment($pdo, (string)($_POST['name'] ?? ''), $crit);
        } elseif ($action === 'save_campaign') {
            [$ok, $msg] = office_cm_save_campaign($pdo, $_POST);
        } elseif ($action === 'run_campaign') {
            [$ok, $msg] = office_cm_run_campaign($pdo, (int)($_POST['id'] ?? 0));
        } elseif ($action === 'flush') {
            [$ok, $msg] = office_cm_flush($pdo);
        } elseif ($action === 'add_tag') {
            [$ok, $msg] = office_cm_add_tag($pdo, (string)($_POST['name'] ?? ''), (int)($_POST['contact_id'] ?? 0));
        } elseif ($action === 'import') {
            [$ok, $msg] = office_cm_import($pdo, (string)($_POST['csv'] ?? ''), (string)($_POST['mode'] ?? 'UPDATE_OR_CREATE'));
        } elseif ($action === 'suggest') {
            $suggestOut = office_cm_suggest((string)($_POST['prompt'] ?? ''));
            $ok = true;
            $msg = '';
        } elseif ($action === 'save_auto') {
            [$ok, $msg] = office_cm_save_automation($pdo, $_POST);
        } elseif ($action === 'toggle_auto') {
            $ok = office_cm_toggle_auto($pdo, (int)($_POST['id'] ?? 0), !empty($_POST['enabled']));
            $msg = $ok ? 'ذخیره شد.' : 'ناموفق بود.';
        } elseif ($action === 'run_auto') {
            [$ok, $msg] = office_cm_run_auto($pdo, (int)($_POST['id'] ?? 0));
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

$view = (string)($_GET['view'] ?? 'dashboard');
if ($isPost && !$flashErr && ($action ?? '') === 'suggest') {
    $view = 'send';
}
$allowedViews = ['dashboard', 'contacts', 'contact', 'send', 'templates', 'logs', 'segments', 'campaigns', 'automations', 'reports', 'settings', 'import', 'tags', 'audit', 'clicks', 'search'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}
$nav = ['dashboard' => '📊 داشبورد', 'contacts' => '📇 مخاطبین', 'send' => '✉️ ارسال', 'templates' => '📝 قالب‌ها', 'logs' => '🧾 گزارش ارسال', 'segments' => '🧩 سگمنت‌ها', 'campaigns' => '📣 کمپین‌ها', 'automations' => '🤖 خودکارها', 'reports' => '📈 گزارش‌ها', 'settings' => '⚙️ تنظیمات', 'import' => '📥 ایمپورت', 'tags' => '🏷 تگ‌ها', 'search' => '🔍 جست‌وجو'];

$dash = $view === 'dashboard' ? office_cm_dashboard($pdo) : [];
$fSearch = trim((string)($_GET['search'] ?? ''));
$fStatus = (string)($_GET['status'] ?? '');
$cPage = max(1, (int)($_GET['page'] ?? 1));
[$cRows, $cTotal] = $view === 'contacts' ? office_cm_contacts($pdo, array_filter(['search' => $fSearch, 'status' => $fStatus]), $cPage) : [[], 0];
$cPages = max(1, (int)ceil($cTotal / 40));
$detail = $view === 'contact' ? office_cm_contact($pdo, (int)($_GET['id'] ?? 0)) : null;
$templates = $view === 'send' || $view === 'templates' ? office_cm_templates($pdo) : [];
$logs = $view === 'logs' ? office_cm_logs($pdo) : [];
$segments = in_array($view, ['segments', 'campaigns'], true) ? office_cm_segments($pdo) : [];
$campaigns = $view === 'campaigns' ? office_cm_campaigns($pdo) : [];
$autos = $view === 'automations' ? office_cm_automations($pdo) : [];
$rep = $view === 'reports' ? office_cm_reports($pdo) : [];
$cset = $view === 'settings' ? office_cm_settings($pdo) : [];
$tags = $view === 'tags' ? office_cm_tags($pdo) : [];
$audit = $view === 'audit' ? office_cm_audit($pdo) : [];
$clicks = $view === 'clicks' ? office_cm_clicks($pdo) : [];
$searchRes = ($view === 'search' && isset($_GET['q'])) ? office_cm_search($pdo, (string)$_GET['q']) : [];

office_shell_open('promotions.php', 'ارتباطات و کمپین');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<p>
<?php foreach ($nav as $vk => $vl) : ?>
<a class="of-btn<?= $view === $vk || ($view === 'contact' && $vk === 'contacts') ? '' : ' ghost' ?>" href="comm.php?view=<?= $vk ?>"><?= office_h($vl) ?></a>
<?php endforeach; ?>
</p>

<?php if ($view === 'dashboard') : ?>
<div class="of-panel">
<strong>مخاطبین:</strong> <?= office_num((int)($dash['kpis']['contacts'] ?? 0)) ?>
&nbsp;•&nbsp; <strong>ارسال امروز:</strong> <?= office_num((int)($dash['today']['sent'] ?? 0)) ?>
&nbsp;•&nbsp; <strong>منتظر:</strong> <?= office_num((int)($dash['today']['deferred'] ?? 0)) ?>
&nbsp;•&nbsp; <strong>پیامک:</strong> <?= !empty($dash['sms_on']) ? 'فعال ✅' : 'غیرفعال ⏸' ?>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="sync">
<button class="of-btn ghost" type="submit">🔄 همگام‌سازی مخاطبین از دیتابیس</button>
</form>
<form method="post" style="display:inline">
<?= office_csrf_field() ?><input type="hidden" name="action" value="flush">
<button class="of-btn ghost" type="submit">📤 ارسال پیام‌های منتظر</button>
</form>
</div>
<h3>⚡ نیازمند توجه</h3>
<?php if (empty($dash['attention'])) : ?><p class="of-muted">موردی نیست.</p><?php endif; ?>
<?php foreach ((array)($dash['attention'] ?? []) as $a) : ?>
<div class="of-panel"><strong><?= office_h((string)($a['title'] ?? '')) ?></strong><div class="of-muted"><?= office_h((string)($a['text'] ?? '')) ?></div></div>
<?php endforeach; ?>
<h3>🔥 مخاطبین داغ</h3>
<div class="of-table-wrap"><table class="of-table"><tr><th>مخاطب</th><th>بازدید</th><th>نقش پیشنهادی</th></tr>
<?php foreach ((array)($dash['hot'] ?? []) as $h) : ?>
<tr><td><a href="comm.php?view=contact&amp;id=<?= (int)$h['id'] ?>"><?= office_h((string)($h['name'] ?: ($h['last_name'] ?: $h['phone']))) ?></a></td><td><?= office_num((int)$h['views_n']) ?></td><td><?= office_h((string)($h['roles_suggested'] ?? '')) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'contacts') : ?>
<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
<input type="hidden" name="view" value="contacts">
<input type="text" name="search" placeholder="شماره، نام…" value="<?= office_h($fSearch) ?>" style="min-width:200px">
<select name="status"><option value="">همه وضعیت‌ها</option>
<?php foreach (['active' => 'فعال', 'inactive' => 'غیرفعال', 'blocked' => 'مسدود', 'archived' => 'بایگانی'] as $k => $l) : ?><option value="<?= $k ?>"<?= $fStatus === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
</select>
<button class="of-btn" type="submit">🔍 جست‌وجو</button>
<a class="of-btn ghost" href="comm.php?export=1&amp;search=<?= urlencode($fSearch) ?>&amp;status=<?= urlencode($fStatus) ?>">⬇️ خروجی اکسل</a>
</form>
<p class="of-muted"><?= office_num($cTotal) ?> مخاطب.</p>
<div class="of-table-wrap"><table class="of-table"><tr><th>مخاطب</th><th>نقش</th><th>آمار</th><th>آخرین فعالیت</th></tr>
<?php foreach ($cRows as $c) : ?>
<tr>
<td><a href="comm.php?view=contact&amp;id=<?= (int)$c['id'] ?>"><?= office_h((string)($c['name'] ?: ($c['last_name'] ?: $c['phone']))) ?></a><div class="of-muted" dir="ltr"><?= office_h((string)$c['phone']) ?></div></td>
<td><?= office_h((string)($c['roles_verified'] ?: $c['roles_suggested'])) ?></td>
<td class="of-muted" style="font-size:12px">👁<?= office_num((int)$c['views_n']) ?> 📝<?= office_num((int)$c['requests_n']) ?> 🏠<?= office_num((int)$c['properties_n']) ?></td>
<td><?= office_h(office_fa((string)($c['last_activity'] ?? ''))) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php if ($cPages > 1) : ?><p>
<?php if ($cPage > 1) : ?><a class="of-btn ghost" href="comm.php?view=contacts&amp;page=<?= $cPage - 1 ?>&amp;search=<?= urlencode($fSearch) ?>">قبلی</a><?php endif; ?>
<span class="of-muted">صفحه <?= office_num($cPage) ?> از <?= office_num($cPages) ?></span>
<?php if ($cPage < $cPages) : ?><a class="of-btn ghost" href="comm.php?view=contacts&amp;page=<?= $cPage + 1 ?>&amp;search=<?= urlencode($fSearch) ?>">بعدی</a><?php endif; ?>
</p><?php endif; ?>
<div class="of-panel"><h3 style="margin-top:0">➕ مخاطب دستی</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_contact">
<input type="text" name="phone" placeholder="09123456789" dir="ltr">
<input type="text" name="name" placeholder="نام">
<input type="text" name="last_name" placeholder="نام خانوادگی">
<input type="text" name="roles_verified" placeholder="نقش">
<button class="of-btn" type="submit">ذخیره</button>
</form></div>

<?php elseif ($view === 'contact') : ?>
<?php if (!$detail) : ?><p class="of-alert err">مخاطب پیدا نشد.</p><?php else : ?>
<?php $cc = $detail['contact']; ?>
<p><a class="of-btn ghost" href="comm.php?view=contacts">← بازگشت</a></p>
<div class="of-panel">
<h3 style="margin-top:0"><?= office_h((string)($cc['name'] ?: ($cc['last_name'] ?: $cc['phone']))) ?></h3>
<div><span class="of-muted">شماره:</span> <strong dir="ltr"><?= office_h((string)$cc['phone']) ?></strong></div>
<div><span class="of-muted">نقش:</span> <?= office_h((string)($cc['roles_verified'] ?: $cc['roles_suggested'])) ?> · <span class="of-muted">وضعیت:</span> <?= office_h((string)$cc['status']) ?></div>
<?php if (!empty($cc['telegram_link'])) : ?><div><a href="<?= office_h((string)$cc['telegram_link']) ?>">تلگرام</a></div><?php endif; ?>
<?php if (!empty($cc['bale_link'])) : ?><div><a href="<?= office_h((string)$cc['bale_link']) ?>">بله</a></div><?php endif; ?>
<form method="post" style="margin-top:8px">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_contact"><input type="hidden" name="id" value="<?= (int)$cc['id'] ?>">
<input type="hidden" name="phone" value="<?= office_h((string)$cc['phone']) ?>">
<input type="text" name="name" value="<?= office_h((string)($cc['name'] ?? '')) ?>" placeholder="نام">
<input type="text" name="last_name" value="<?= office_h((string)($cc['last_name'] ?? '')) ?>" placeholder="نام خانوادگی">
<input type="text" name="roles_verified" value="<?= office_h((string)($cc['roles_verified'] ?? '')) ?>" placeholder="نقش تأییدشده">
<select name="status"><?php foreach (['active' => 'فعال', 'inactive' => 'غیرفعال', 'blocked' => 'مسدود', 'archived' => 'بایگانی'] as $k => $l) : ?><option value="<?= $k ?>"<?= ($cc['status'] ?? '') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
<button class="of-btn ghost" type="submit">ذخیره تغییرات</button>
</form>
<form method="post" style="margin-top:8px">
<?= office_csrf_field() ?><input type="hidden" name="action" value="add_tag"><input type="hidden" name="contact_id" value="<?= (int)$cc['id'] ?>">
<input type="text" name="name" placeholder="تگ جدید">
<button class="of-btn ghost" type="submit">🏷 افزودن تگ</button>
</form>
</div>
<h3>🏠 آگهی‌ها (<?= office_num(count($detail['ads'])) ?>)</h3>
<?php foreach ($detail['ads'] as $a) : ?><div>• <?= office_h((string)($a['title'] ?? $a['id'])) ?> <span class="of-muted"><?= office_h((string)($a['status'] ?? '')) ?></span></div><?php endforeach; ?>
<h3>📝 درخواست‌ها (<?= office_num(count($detail['requests'])) ?>)</h3>
<?php foreach ($detail['requests'] as $rq) : ?><div>• <?= office_h((string)($rq['tracking_code'] ?? $rq['id'])) ?> <span class="of-muted"><?= office_h((string)($rq['location'] ?? '')) ?></span></div><?php endforeach; ?>
<h3>📅 بازدیدها (<?= office_num(count($detail['visits'])) ?>)</h3>
<?php foreach ($detail['visits'] as $vs) : ?><div>• <?= office_h((string)($vs['ad_title'] ?? $vs['ad_id'])) ?> <span class="of-muted"><?= office_h((string)($vs['status'] ?? '')) ?></span></div><?php endforeach; ?>
<h3>✉️ پیامک‌ها (<?= office_num(count($detail['sms'])) ?>)</h3>
<?php foreach ($detail['sms'] as $sm) : ?><div>• <?= $sm['success'] ? '✅' : '❌' ?> <?= office_h(mb_substr((string)$sm['body'], 0, 80)) ?> <span class="of-muted"><?= office_h(office_fa((string)$sm['created_at'])) ?></span></div><?php endforeach; ?>
<h3>🗒 یادداشت‌ها</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="note"><input type="hidden" name="contact_id" value="<?= (int)$cc['id'] ?>">
<input type="text" name="body" placeholder="یادداشت جدید…" style="min-width:min(400px,90%)">
<button class="of-btn ghost" type="submit">ثبت</button>
</form>
<?php foreach ($detail['notes'] as $nt) : ?><div>• <?= office_h((string)$nt['body']) ?> <span class="of-muted"><?= office_h(office_fa((string)($nt['created_at'] ?? ''))) ?></span></div><?php endforeach; ?>
<h3>⏰ پیگیری‌ها</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="followup"><input type="hidden" name="contact_id" value="<?= (int)$cc['id'] ?>">
<input type="text" name="title" placeholder="عنوان پیگیری">
<input type="text" name="due_date" placeholder="مهلت (اختیاری)">
<button class="of-btn ghost" type="submit">ثبت</button>
</form>
<?php foreach ($detail['followups'] as $fw) : ?><div>• <?= office_h((string)$fw['title']) ?> <span class="of-muted"><?= office_h((string)($fw['status'] ?? '')) ?></span></div><?php endforeach; ?>
<?php endif; ?>

<?php elseif ($view === 'send') : ?>
<div class="of-panel">
<h3 style="margin-top:0">✉️ ارسال پیامک گروهی</h3>
<?php if ($suggestOut !== '') : ?><p class="of-alert ok">پیشنهاد: <?= office_h($suggestOut) ?><br><span class="of-muted">پیشنهاد متن است؛ بدون تأیید شما ارسال نمی‌شود.</span></p><?php endif; ?>
<form method="post" style="margin-bottom:8px">
<?= office_csrf_field() ?><input type="hidden" name="action" value="suggest">
<input type="text" name="prompt" placeholder="موضوع پیام (بازدید، درخواست، آگهی، خرید)…" style="min-width:min(360px,90%)">
<button class="of-btn ghost" type="submit">💡 پیشنهاد متن</button>
</form>
<form method="post" onsubmit="return confirm('ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="send">
<p>متن پیام (متغیرها: <?= office_h('{{first_name}} {{property_title}} {{link}}') ?>)<br><textarea name="body" rows="3" style="width:100%;box-sizing:border-box"></textarea></p>
<p>شماره‌ها (هر خط یکی)<br><textarea name="phones" rows="3" dir="ltr" style="width:100%;box-sizing:border-box"></textarea></p>
<p><label>قالب <select name="template_id"><option value="0">—</option><?php foreach ($templates as $t) : ?><option value="<?= (int)$t['id'] ?>"><?= office_h((string)$t['name']) ?></option><?php endforeach; ?></select></label>
<label>زمان <select name="when"><option value="now">الان</option><option value="later">زمان‌بندی</option></select></label>
<input type="text" name="send_at" placeholder="YYYY-MM-DD HH:MM (برای زمان‌بندی)" dir="ltr"></p>
<p><button class="of-btn" type="submit">📤 ارسال</button></p>
</form>
</div>

<?php elseif ($view === 'templates') : ?>
<div class="of-panel"><h3 style="margin-top:0">➕ قالب جدید</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_template">
<input type="text" name="name" placeholder="نام قالب">
<input type="text" name="body" placeholder="متن قالب…" style="min-width:min(400px,90%)">
<button class="of-btn" type="submit">ذخیره</button>
</form></div>
<div class="of-table-wrap"><table class="of-table"><tr><th>نام</th><th>متن</th><th></th></tr>
<?php foreach ($templates as $t) : ?>
<tr><td><strong><?= office_h((string)$t['name']) ?></strong></td><td><?= office_h((string)$t['body']) ?></td>
<td><form method="post" style="display:inline" onsubmit="return confirm('حذف شود؟');"><?= office_csrf_field() ?><input type="hidden" name="action" value="delete_template"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="of-btn ghost" type="submit">🗑</button></form></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'logs') : ?>
<p class="of-muted"><?= office_num(count($logs)) ?> پیام آخر.</p>
<div class="of-table-wrap"><table class="of-table"><tr><th>زمان</th><th>شماره</th><th>وضعیت</th><th>متن</th><th>نتیجه</th></tr>
<?php foreach ($logs as $l) : ?>
<tr><td><?= office_h(office_fa((string)($l['created_at'] ?? ''))) ?></td><td dir="ltr"><?= office_h((string)$l['phone']) ?></td><td><?= !empty($l['success']) ? '✅' : '❌' ?></td><td><?= office_h(mb_substr((string)$l['body'], 0, 90)) ?></td><td class="of-muted" style="font-size:12px"><?= office_h((string)($l['result_message'] ?? '')) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'segments') : ?>
<div class="of-panel"><h3 style="margin-top:0">➕ سگمنت جدید</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_segment">
<input type="text" name="name" placeholder="نام سگمنت">
<input type="text" name="crit[search]" placeholder="جست‌وجو">
<label><input type="checkbox" name="crit[hot]" value="1"> داغ (۵+ بازدید)</label>
<label><input type="checkbox" name="crit[has_request]" value="1"> درخواست دارد</label>
<label><input type="checkbox" name="crit[has_property]" value="1"> ملک دارد</label>
<button class="of-btn" type="submit">ذخیره</button>
</form></div>
<div class="of-table-wrap"><table class="of-table"><tr><th>نام</th><th>تعداد</th><th>معیار</th></tr>
<?php foreach ($segments as $sg) : ?>
<tr><td><strong><?= office_h((string)$sg['name']) ?></strong></td><td><?= office_num((int)($sg['count'] ?? 0)) ?></td><td class="of-muted" style="font-size:12px"><?= office_h(json_encode($sg['criteria'] ?? [], JSON_UNESCAPED_UNICODE)) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'campaigns') : ?>
<div class="of-panel"><h3 style="margin-top:0">➕ کمپین جدید</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_campaign">
<input type="text" name="name" placeholder="نام کمپین">
<select name="segment_id"><option value="0">همه مخاطبین فعال</option><?php foreach ($segments as $sg) : ?><option value="<?= (int)$sg['id'] ?>"><?= office_h((string)$sg['name']) ?> (<?= office_num((int)($sg['count'] ?? 0)) ?>)</option><?php endforeach; ?></select>
<input type="text" name="scheduled_at" placeholder="زمان‌بندی (اختیاری) YYYY-MM-DD HH:MM" dir="ltr">
<br><textarea name="body" rows="2" placeholder="متن پیام…" style="width:100%;box-sizing:border-box"></textarea>
<button class="of-btn" type="submit">ذخیره</button>
</form></div>
<div class="of-table-wrap"><table class="of-table"><tr><th>نام</th><th>وضعیت</th><th>آمار</th><th></th></tr>
<?php foreach ($campaigns as $cp) : ?>
<tr><td><strong><?= office_h((string)$cp['name']) ?></strong><div class="of-muted" style="font-size:12px"><?= office_h(mb_substr((string)$cp['body'], 0, 80)) ?></div></td>
<td><?= office_h((string)$cp['status']) ?></td>
<td class="of-muted" style="font-size:12px">👥<?= office_num((int)($cp['recipients_n'] ?? 0)) ?> ✅<?= office_num((int)($cp['sent_n'] ?? 0)) ?> ❌<?= office_num((int)($cp['fail_n'] ?? 0)) ?></td>
<td><form method="post" style="display:inline" onsubmit="return confirm('کمپین اجرا شود؟ (ارسال واقعی)');"><?= office_csrf_field() ?><input type="hidden" name="action" value="run_campaign"><input type="hidden" name="id" value="<?= (int)$cp['id'] ?>"><button class="of-btn ghost" type="submit">▶ اجرا</button></form></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'automations') : ?>
<div class="of-panel"><h3 style="margin-top:0">➕ اعلان خودکار جدید</h3>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_auto">
<input type="text" name="title" placeholder="عنوان">
<select name="event_key"><?php foreach (['new_ad' => 'آگهی جدید', 'new_request' => 'درخواست جدید', 'visit' => 'بازدید', 'hot_lead' => 'لید داغ', 'visit_remind' => 'یادآوری بازدید'] as $k => $l) : ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select>
<input type="text" name="recipient_phone" placeholder="شماره گیرنده (خالی = مدیر)" dir="ltr">
<select name="timing"><option value="digest">digest</option><option value="now">now (فوری)</option></select>
<label>حد نصاب <input type="number" name="min_count" value="1" min="1" style="width:60px"></label>
<br><textarea name="body" rows="2" placeholder="متن با {{count}} و {{event}}…" style="width:100%;box-sizing:border-box"></textarea>
<button class="of-btn" type="submit">ذخیره</button>
</form></div>
<div class="of-table-wrap"><table class="of-table"><tr><th>عنوان</th><th>رویداد</th><th>فعال</th><th></th></tr>
<?php foreach ($autos as $au) : ?>
<tr><td><strong><?= office_h((string)$au['title']) ?></strong></td><td><?= office_h((string)$au['event_key']) ?></td>
<td><form method="post" style="display:inline"><?= office_csrf_field() ?><input type="hidden" name="action" value="toggle_auto"><input type="hidden" name="id" value="<?= (int)$au['id'] ?>"><input type="checkbox" name="enabled" value="1"<?= !empty($au['enabled']) ? ' checked' : '' ?> onchange="this.form.submit()"></form></td>
<td><form method="post" style="display:inline"><?= office_csrf_field() ?><input type="hidden" name="action" value="run_auto"><input type="hidden" name="id" value="<?= (int)$au['id'] ?>"><button class="of-btn ghost" type="submit">▶ اجرای آزمایشی</button></form></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'reports') : ?>
<div class="of-panel">
<strong>پیامک امروز:</strong> <?= office_num((int)($rep['sms']['today'] ?? 0)) ?> (✅<?= office_num((int)($rep['sms']['ok'] ?? 0)) ?> ❌<?= office_num((int)($rep['sms']['fail'] ?? 0)) ?> ⏳<?= office_num((int)($rep['sms']['waiting'] ?? 0)) ?>)
&nbsp;•&nbsp; <strong>کلیک امروز:</strong> <?= office_num((int)($rep['rel']['clicks'] ?? 0)) ?>
&nbsp;•&nbsp; <strong>کمپین‌ها:</strong> <?= office_num((int)($rep['work']['campaigns'] ?? 0)) ?>
&nbsp;•&nbsp; <strong>خودکارها:</strong> <?= office_num((int)($rep['work']['autos'] ?? 0)) ?>
</div>
<p><a class="of-btn ghost" href="comm.php?view=clicks">🖱 کلیک‌ها</a> <a class="of-btn ghost" href="comm.php?view=audit">🧾 حسابرسی</a></p>

<?php elseif ($view === 'clicks') : ?>
<div class="of-table-wrap"><table class="of-table"><tr><th>زمان</th><th>کد</th><th>مقصد</th><th>مخاطب</th></tr>
<?php foreach ($clicks as $cl) : ?>
<tr><td><?= office_h(office_fa((string)($cl['clicked_at'] ?? ''))) ?></td><td dir="ltr"><?= office_h((string)($cl['short_code'] ?? '')) ?></td><td><?= office_h((string)($cl['destination_url'] ?? '')) ?></td><td><?= office_h((string)($cl['contact_id'] ?? '')) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'audit') : ?>
<div class="of-table-wrap"><table class="of-table"><tr><th>زمان</th><th>عمل</th><th>جزئیات</th></tr>
<?php foreach ($audit as $au2) : ?>
<tr><td><?= office_h(office_fa((string)($au2['created_at'] ?? ''))) ?></td><td><?= office_h((string)($au2['action'] ?? '')) ?></td><td><?= office_h((string)($au2['detail'] ?? '')) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'settings') : ?>
<div class="of-panel">
<h3 style="margin-top:0">⚙️ تنظیمات ارسال</h3>
<p class="of-muted">پنل پیامک: <?= !empty($cset['sms_enabled']) ? 'فعال ✅' : 'غیرفعال ⏸' ?> · اکنون ساعات سکوت: <?= !empty($cset['quiet_now']) ? 'بله 🌙' : 'خیر ☀️' ?></p>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="save_settings">
<p><label><input type="checkbox" name="quiet_enabled" value="1"<?= ($cset['quiet_enabled'] ?? '0') === '1' ? ' checked' : '' ?>> ساعات سکوت فعال</label>
<label>از <input type="text" name="quiet_start" dir="ltr" value="<?= office_h((string)($cset['quiet_start'] ?? '')) ?>" placeholder="22:00"></label>
<label>تا <input type="text" name="quiet_end" dir="ltr" value="<?= office_h((string)($cset['quiet_end'] ?? '')) ?>" placeholder="08:00"></label></p>
<p><label>سقف پیامک روزانه هر شماره <input type="number" name="max_sms_day" min="1" max="30" value="<?= office_h((string)($cset['max_sms_day'] ?? '8')) ?>"></label></p>
<p><label>شماره مدیر <input type="text" name="admin_phone" dir="ltr" value="<?= office_h((string)($cset['admin_phone'] ?? '')) ?>"></label>
<label><input type="checkbox" name="urgent_bypass" value="1"<?= ($cset['urgent_bypass'] ?? '0') === '1' ? ' checked' : '' ?>> عبور فوری از سکوت</label></p>
<p><button class="of-btn" type="submit">ذخیره تنظیمات</button></p>
</form>
</div>

<?php elseif ($view === 'import') : ?>
<div class="of-panel">
<h3 style="margin-top:0">📥 ایمپورت CSV</h3>
<p class="of-muted">ستون‌ها: phone,name,last_name,role (سطر اول سرستون)</p>
<form method="post">
<?= office_csrf_field() ?><input type="hidden" name="action" value="import">
<textarea name="csv" rows="6" dir="ltr" style="width:100%;box-sizing:border-box" placeholder="phone,name,last_name,role&#10;09123456789,علی,رضایی,خریدار"></textarea>
<p><label>حالت <select name="mode"><option value="UPDATE_OR_CREATE">به‌روزرسانی یا ساخت</option><option value="CREATE_ONLY">فقط ساخت جدید</option></select></label>
<button class="of-btn" type="submit">ایمپورت</button></p>
</form>
</div>

<?php elseif ($view === 'tags') : ?>
<div class="of-table-wrap"><table class="of-table"><tr><th>تگ</th><th>تعداد</th></tr>
<?php foreach ($tags as $tg) : ?>
<tr><td><?= office_h((string)$tg['name']) ?></td><td><?= office_num((int)$tg['n']) ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'search') : ?>
<form method="get">
<input type="hidden" name="view" value="search">
<input type="text" name="q" placeholder="شماره، نام…" value="<?= office_h((string)($_GET['q'] ?? '')) ?>" style="min-width:220px">
<button class="of-btn" type="submit">🔍 جست‌وجو</button>
</form>
<?php foreach ($searchRes as $sr) : ?>
<div>• <a href="comm.php?view=contact&amp;id=<?= (int)$sr['id'] ?>"><?= office_h((string)($sr['name'] ?: ($sr['last_name'] ?: $sr['phone']))) ?></a> <span class="of-muted" dir="ltr"><?= office_h((string)$sr['phone']) ?></span></div>
<?php endforeach; ?>
<?php endif; ?>
<?php office_shell_close(); ?>
