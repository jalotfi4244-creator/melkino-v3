<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کاربران سایت (مرحله ۲۵)
 *--------------------------------------------------------------------------
 * آینهٔ تب کاربران پنل سایت (identity-sync.php): آمار، فهرست+جست‌وجو،
 * جزئیات (تاریخچه ورود + بازدیدها) و پاک‌سازی شماره با حسابرسی.
 */

declare(strict_types=1);

require_once __DIR__ . '/_siteusers.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($action === 'clear_phone') {
            $me = office_user();
            [$ok, $msg] = office_su_clear_phone($pdo, $uid, (int)$me['id'] ?: null, (string)($me['username'] ?? ''));
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

$viewId = (int)($_GET['id'] ?? 0);
$view = $viewId > 0 ? office_su_get($pdo, $viewId) : null;
if ($viewId > 0 && !$view) {
    $flashErr = 'کاربر پیدا نشد.';
}
$stats = office_su_stats($pdo);
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$total = $view ? 0 : office_su_count($pdo, $q);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$rows = $view ? [] : office_su_list($pdo, $q, $page, $perPage);
$history = $view ? office_su_history($pdo, (int)$view['id']) : [];
$views = $view ? office_su_views($pdo, (int)$view['id']) : [];

office_shell_open('customers.php', $view ? 'کاربر سایت' : 'کاربران سایت');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<div class="of-panel">
<strong>کل کاربران:</strong> <?= office_num($stats['users']) ?>
&nbsp;•&nbsp; <strong>کل بازدید آگهی‌ها:</strong> <?= office_num($stats['visits']) ?>
</div>

<?php if ($view !== null) : ?>
<p><a class="of-btn ghost" href="site-users.php">← بازگشت به فهرست</a></p>
<div class="of-panel">
<h3 style="margin-top:0"><?= office_h((string)($view['name'] ?? ('کاربر #' . $view['id']))) ?></h3>
<?php foreach (['id' => 'شناسه', 'name' => 'نام', 'username' => 'نام کاربری', 'phone' => 'شماره', 'telegram_id' => 'تلگرام', 'bale_id' => 'بله', 'is_active' => 'فعال', 'login_count' => 'تعداد ورود', 'first_login' => 'اولین ورود', 'last_login' => 'آخرین ورود', 'last_ip' => 'آخرین IP', 'last_platform' => 'پلتفرم'] as $k => $label) : ?>
<?php if (!array_key_exists($k, $view)) continue; ?>
<div><span class="of-muted"><?= office_h($label) ?>:</span> <strong><?= office_h(office_fa((string)($view[$k] ?? '—'))) ?></strong></div>
<?php endforeach; ?>
<p>
<form method="post" style="display:inline" onsubmit="return confirm('شماره تماس این کاربر پاک شود؟');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="clear_phone">
<input type="hidden" name="user_id" value="<?= (int)$view['id'] ?>">
<button class="of-btn" type="submit">🧹 پاک‌سازی شماره تماس</button>
</form>
</p>
</div>

<h3>🕘 تاریخچه ورود (<?= office_num(count($history)) ?>)</h3>
<?php if (!$history) : ?><p class="of-muted">رکوردی ثبت نشده است.</p><?php else : ?>
<div class="of-table-wrap"><table class="of-table">
<tr><th>زمان</th><th>IP</th><th>پلتفرم</th><th>عامل کاربر</th></tr>
<?php foreach ($history as $h) : ?>
<tr>
<td><?= office_h(office_fa((string)($h['created_at'] ?? ''))) ?></td>
<td><?= office_h((string)($h['ip_address'] ?? ($h['ip'] ?? ''))) ?></td>
<td><?= office_h((string)($h['platform'] ?? '')) ?></td>
<td class="of-muted"><?= office_h(mb_substr((string)($h['user_agent'] ?? ''), 0, 80)) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>

<h3>👁 بازدید آگهی‌ها (<?= office_num(count($views)) ?>)</h3>
<?php if (!$views) : ?><p class="of-muted">بازدیدی ثبت نشده است.</p><?php else : ?>
<div class="of-table-wrap"><table class="of-table">
<tr><th>زمان</th><th>آگهی</th><th>عنوان</th></tr>
<?php foreach ($views as $v) : ?>
<tr>
<td><?= office_h(office_fa((string)($v['viewed_at'] ?? ''))) ?></td>
<td><?= office_h((string)($v['ad_id'] ?? '')) ?></td>
<td><?= office_h((string)($v['ad_title'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>

<?php else : ?>
<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
<input type="text" name="q" placeholder="جست‌وجوی نام، شماره، نام کاربری…" value="<?= office_h($q) ?>" style="min-width:220px">
<button class="of-btn" type="submit">🔍 جست‌وجو</button>
<?php if ($q !== '') : ?><a class="of-btn ghost" href="site-users.php">حذف فیلتر</a><?php endif; ?>
</form>
<p class="of-muted"><?= office_num($total) ?> کاربر یافت شد.</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>کاربر</th><th>شماره</th><th>تلگرام</th><th>آخرین ورود</th><th>ورودها</th><th>وضعیت</th></tr>
<?php foreach ($rows as $r) : ?>
<tr>
<td><a href="site-users.php?id=<?= (int)$r['id'] ?>"><?= office_h((string)($r['name'] ?? ('#' . $r['id']))) ?></a><?php if (!empty($r['username'])) : ?><div class="of-muted">@<?= office_h((string)$r['username']) ?></div><?php endif; ?></td>
<td><?= office_h(office_fa((string)($r['phone'] ?? '—'))) ?></td>
<td><?= office_h((string)($r['telegram_id'] ?? '—')) ?></td>
<td><?= office_h(office_fa((string)($r['last_login'] ?? '—'))) ?></td>
<td><?= office_num((int)($r['login_count'] ?? 0)) ?></td>
<td><?= ((int)($r['is_active'] ?? 1) === 1) ? '✅' : '⏸' ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php if ($pages > 1) : ?>
<p>
<?php if ($page > 1) : ?><a class="of-btn ghost" href="site-users.php?page=<?= $page - 1 ?>&amp;q=<?= urlencode($q) ?>">قبلی</a><?php endif; ?>
<span class="of-muted">صفحه <?= office_num($page) ?> از <?= office_num($pages) ?></span>
<?php if ($page < $pages) : ?><a class="of-btn ghost" href="site-users.php?page=<?= $page + 1 ?>&amp;q=<?= urlencode($q) ?>">بعدی</a><?php endif; ?>
</p>
<?php endif; ?>
<?php endif; ?>
<?php office_shell_close(); ?>
