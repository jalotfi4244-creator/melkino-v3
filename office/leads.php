<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — لیدها (مرحله ۳۵)
 *--------------------------------------------------------------------------
 * آینهٔ تب «لیدها» پنل سایت: بینندگان پرتکرار، درخواست‌های دارای
 * تطبیق، لاگ پیامک‌ها و ارسال پیامک تطبیق/یادآوری.
 */

declare(strict_types=1);

require_once __DIR__ . '/_leads.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_ld_boot($pdo);
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'sms_matches' || $action === 'sms_count') {
            [$ok, $msg] = office_ld_sms_matches($pdo, (int)($_POST['request_id'] ?? 0), $action);
        } elseif ($action === 'sms_viewer') {
            [$ok, $msg] = office_ld_sms_viewer($pdo, (string)($_POST['phone'] ?? ''), (string)($_POST['ad_id'] ?? ''), (string)($_POST['ad_title'] ?? ''), (int)($_POST['user_id'] ?? 0));
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

$view = (string)($_GET['view'] ?? 'viewers');
if (!in_array($view, ['viewers', 'matches', 'log'], true)) {
    $view = 'viewers';
}
$min = max(2, min(20, (int)($_GET['min'] ?? 3)));
$viewers = $view === 'viewers' ? office_ld_viewers($pdo, $min) : [];
$matches = $view === 'matches' ? office_ld_matches($pdo) : [];
$logRows = $view === 'log' ? office_ld_log_rows($pdo) : [];

office_shell_open('promotions.php', 'لیدها');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<p>
<a class="of-btn<?= $view === 'viewers' ? '' : ' ghost' ?>" href="leads.php">👁 بینندگان پرتکرار</a>
<a class="of-btn<?= $view === 'matches' ? '' : ' ghost' ?>" href="leads.php?view=matches">🎯 درخواست‌های دارای تطبیق</a>
<a class="of-btn<?= $view === 'log' ? '' : ' ghost' ?>" href="leads.php?view=log">🧾 لاگ پیامک‌ها</a>
</p>

<?php if ($view === 'viewers') : ?>
<form method="get" style="display:flex;gap:8px;align-items:center">
<label>حداقل بازدید <input type="number" name="min" min="2" max="20" value="<?= $min ?>" style="width:64px"></label>
<button class="of-btn" type="submit">نمایش</button>
</form>
<p class="of-muted"><?= office_num(count($viewers)) ?> لید (حداقل <?= office_num($min) ?> بازدید).</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>بازدیدکننده</th><th>آگهی</th><th>بازدید</th><th>آخرین بازدید</th><th>پیامک</th></tr>
<?php foreach ($viewers as $v) : ?>
<tr>
<td><?= office_h((string)($v['name'] ?: ('کاربر #' . ($v['user_id'] ?? '')))) ?><div class="of-muted" dir="ltr"><?= office_h((string)$v['phone']) ?></div></td>
<td><?= office_h((string)($v['ad_title'] ?: $v['ad_id'])) ?></td>
<td><?= office_num((int)$v['views']) ?></td>
<td><?= office_h(office_fa((string)$v['last_view'])) ?></td>
<td>
<?php if (preg_match('/^09\d{9}$/', office_ld_norm_phone((string)$v['phone']))) : ?>
<form method="post" style="display:inline" onsubmit="return confirm('پیامک یادآوری ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="sms_viewer">
<input type="hidden" name="phone" value="<?= office_h((string)$v['phone']) ?>">
<input type="hidden" name="ad_id" value="<?= office_h((string)$v['ad_id']) ?>">
<input type="hidden" name="ad_title" value="<?= office_h((string)$v['ad_title']) ?>">
<input type="hidden" name="user_id" value="<?= (int)($v['user_id'] ?? 0) ?>">
<button class="of-btn ghost" type="submit">📩 یادآوری</button>
</form>
<?php else : ?><span class="of-muted">—</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'matches') : ?>
<p class="of-muted"><?= office_num(count($matches)) ?> درخواست دارای تطبیق.</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>درخواست</th><th>متقاضی</th><th>تطبیق</th><th>پیامک</th></tr>
<?php foreach ($matches as $m) : ?>
<tr>
<td><a href="site-requests.php?id=<?= (int)$m['request_id'] ?>"><?= office_h((string)($m['tracking_code'] ?? ('#' . $m['request_id']))) ?></a><div class="of-muted"><?= office_h((string)($m['location'] ?? '')) ?></div></td>
<td><?= office_h((string)($m['last_name'] ?? '')) ?><div class="of-muted" dir="ltr"><?= office_h((string)($m['phone'] ?? '')) ?></div></td>
<td><?= office_num((int)$m['match_count']) ?></td>
<td style="white-space:nowrap">
<form method="post" style="display:inline" onsubmit="return confirm('پیامک فهرست تطبیق‌ها ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="sms_matches">
<input type="hidden" name="request_id" value="<?= (int)$m['request_id'] ?>">
<button class="of-btn ghost" type="submit">📩 فهرست</button>
</form>
<form method="post" style="display:inline" onsubmit="return confirm('پیامک تعداد تطبیق‌ها ارسال شود؟');">
<?= office_csrf_field() ?><input type="hidden" name="action" value="sms_count">
<input type="hidden" name="request_id" value="<?= (int)$m['request_id'] ?>">
<button class="of-btn ghost" type="submit">🔢 تعداد</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table></div>

<?php elseif ($view === 'log') : ?>
<p class="of-muted"><?= office_num(count($logRows)) ?> رکورد آخر.</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>زمان</th><th>نوع</th><th>شماره</th><th>وضعیت</th><th>متن</th><th>نتیجه</th></tr>
<?php foreach ($logRows as $l) : ?>
<tr>
<td><?= office_h(office_fa((string)($l['created_at'] ?? ''))) ?></td>
<td><?= office_h((string)$l['kind']) ?></td>
<td dir="ltr"><?= office_h((string)$l['phone']) ?></td>
<td><?= !empty($l['success']) ? '✅' : '❌' ?></td>
<td><?= office_h(mb_substr((string)$l['message'], 0, 90)) ?></td>
<td class="of-muted" style="font-size:12px"><?= office_h((string)($l['result_message'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php office_shell_close(); ?>
