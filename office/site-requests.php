<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — درخواست‌های سایت + تطبیق‌ها (مرحله ۲۸)
 *--------------------------------------------------------------------------
 * آینهٔ تب درخواست‌های پنل سایت: آمار، فهرست+فیلتر، جزئیات با
 * فهرست تطبیق‌های هر درخواست، تغییر وضعیت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_srequests.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
$flash = '';
$flashErr = '';
$statuses = office_sr_statuses();

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'set_status') {
            [$ok, $msg] = office_sr_set_status($pdo, (int)($_POST['id'] ?? 0), (string)($_POST['status'] ?? ''));
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
$view = $viewId > 0 ? office_sr_get($pdo, $viewId) : null;
if ($viewId > 0 && !$view) {
    $flashErr = 'درخواست پیدا نشد.';
}
$stats = office_sr_stats($pdo);
$filters = [
    'status' => (string)($_GET['status'] ?? ''),
    'transaction_type' => (string)($_GET['transaction_type'] ?? ''),
    'property_type' => (string)($_GET['property_type'] ?? ''),
    'q' => trim((string)($_GET['q'] ?? '')),
];
if ($filters['status'] !== '' && !array_key_exists($filters['status'], $statuses)) {
    $filters['status'] = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
[$rows, $total] = $view ? [[], 0] : office_sr_list($pdo, $filters, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
    [$rows, $total] = office_sr_list($pdo, $filters, $page, $perPage);
}
$qsBase = http_build_query(array_filter($filters, static fn($v) => $v !== ''));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
$matches = $view ? office_sr_matches($pdo, (int)$view['id']) : [];

office_shell_open('requests.php', $view ? 'درخواست سایت' : 'درخواست‌های سایت');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<div class="of-panel">
<strong>کل:</strong> <?= office_num($stats['total']) ?>
&nbsp;•&nbsp; <strong>جدید:</strong> <?= office_num($stats['new_count']) ?>
&nbsp;•&nbsp; <strong>در حال پیگیری:</strong> <?= office_num($stats['tracking_count']) ?>
&nbsp;•&nbsp; <strong>درخواست‌های دارای تطبیق:</strong> <?= office_num($stats['matched']) ?>
&nbsp;•&nbsp; <strong>کل تطبیق‌ها:</strong> <?= office_num($stats['matches']) ?>
</div>

<?php if ($view !== null) : ?>
<p><a class="of-btn ghost" href="site-requests.php">← بازگشت به فهرست</a></p>
<div class="of-panel">
<h3 style="margin-top:0">درخواست <?= office_h((string)($view['tracking_code'] ?? ('#' . $view['id']))) ?></h3>
<?php foreach (['id' => 'شناسه', 'tracking_code' => 'کد پیگیری', 'last_name' => 'نام خانوادگی', 'phone' => 'شماره', 'telegram_id' => 'تلگرام', 'transaction_type' => 'نوع معامله', 'property_type' => 'نوع ملک', 'location' => 'محدوده', 'urgency' => 'فوریت', 'date_needed' => 'تاریخ نیاز', 'min_area' => 'حداقل متراژ', 'max_budget' => 'سقف بودجه', 'created_at' => 'ثبت'] as $k => $label) : ?>
<?php if (!array_key_exists($k, $view) || $view[$k] === null || $view[$k] === '') continue; ?>
<div><span class="of-muted"><?= office_h($label) ?>:</span> <strong><?= office_h(office_fa((string)$view[$k])) ?></strong></div>
<?php endforeach; ?>
<div><span class="of-muted">وضعیت:</span> <strong><?= office_h($statuses[$view['status'] ?? ''] ?? ($view['status'] ?? 'جدید')) ?></strong></div>
<form method="post" style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="set_status">
<input type="hidden" name="id" value="<?= (int)$view['id'] ?>">
<select name="status">
<?php foreach ($statuses as $sk => $sl) : ?>
<option value="<?= office_h($sk) ?>"<?= ($view['status'] ?? '') === $sk ? ' selected' : '' ?>><?= office_h($sl) ?></option>
<?php endforeach; ?>
</select>
<button class="of-btn" type="submit">تغییر وضعیت</button>
</form>
</div>

<h3>🎯 تطبیق‌ها (<?= office_num(count($matches)) ?>)</h3>
<?php if (!$matches) : ?><p class="of-muted">تطبیقی برای این درخواست ثبت نشده است.</p><?php else : ?>
<div class="of-table-wrap"><table class="of-table">
<tr><th>درصد</th><th>آگهی</th><th>مشخصات</th><th>قیمت</th></tr>
<?php foreach ($matches as $m) : ?>
<tr>
<td><strong><?= office_h(office_fa((string)($m['match_percent'] ?? '—'))) ?>٪</strong></td>
<td><a href="../property-details.php?id=<?= urlencode((string)$m['ad_id']) ?>"><?= office_h((string)($m['title'] ?? $m['ad_id'])) ?></a><div class="of-muted"><?= office_h((string)$m['ad_id']) ?></div></td>
<td><?= office_h(trim((string)($m['transaction_type'] ?? '') . ' ' . (string)($m['property_type'] ?? ''))) ?><div class="of-muted"><?= office_h((string)($m['location'] ?? '')) ?><?= !empty($m['area']) ? ' — ' . office_h(office_fa((string)$m['area'])) . ' متر' : '' ?></div></td>
<td><?= office_ad_price($m) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>

<?php else : ?>
<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
<select name="status">
<option value="">همه وضعیت‌ها</option>
<?php foreach ($statuses as $sk => $sl) : ?>
<option value="<?= office_h($sk) ?>"<?= $filters['status'] === $sk ? ' selected' : '' ?>><?= office_h($sl) ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="q" placeholder="کد پیگیری، شماره، محدوده، نام…" value="<?= office_h($filters['q']) ?>" style="min-width:220px">
<button class="of-btn" type="submit">🔍 جست‌وجو</button>
<?php if ($filters['status'] !== '' || $filters['q'] !== '') : ?><a class="of-btn ghost" href="site-requests.php">حذف فیلتر</a><?php endif; ?>
</form>
<p class="of-muted"><?= office_num($total) ?> درخواست یافت شد.</p>
<div class="of-table-wrap"><table class="of-table">
<tr><th>کد پیگیری</th><th>متقاضی</th><th>درخواست</th><th>وضعیت</th><th>ثبت</th></tr>
<?php foreach ($rows as $r) : ?>
<tr>
<td><a href="site-requests.php?id=<?= (int)$r['id'] ?>"><?= office_h((string)($r['tracking_code'] ?? ('#' . $r['id']))) ?></a></td>
<td><?= office_h((string)($r['last_name'] ?? '—')) ?><div class="of-muted"><?= office_h(office_fa((string)($r['phone'] ?? ''))) ?></div></td>
<td><?= office_h(trim((string)($r['transaction_type'] ?? '') . ' ' . (string)($r['property_type'] ?? ''))) ?><div class="of-muted"><?= office_h((string)($r['location'] ?? '')) ?></div></td>
<td><?= office_h($statuses[$r['status'] ?? ''] ?? ($r['status'] ?? '')) ?></td>
<td><?= office_h(office_fa((string)($r['created_at'] ?? ''))) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php if ($pages > 1) : ?>
<p>
<?php if ($page > 1) : ?><a class="of-btn ghost" href="site-requests.php?<?= $qsBase ?>page=<?= $page - 1 ?>">قبلی</a><?php endif; ?>
<span class="of-muted">صفحه <?= office_num($page) ?> از <?= office_num($pages) ?></span>
<?php if ($page < $pages) : ?><a class="of-btn ghost" href="site-requests.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی</a><?php endif; ?>
</p>
<?php endif; ?>
<?php endif; ?>
<?php office_shell_close(); ?>
