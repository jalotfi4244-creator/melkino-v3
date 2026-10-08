<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — درخواست‌ها (مرحله ۹)
 *--------------------------------------------------------------------------
 * لیست (جست‌وجو + فیلتر نوع/وضعیت) + ثبت + ویرایش + حذف.
 * لینک اختیاری به مشتری؛ بودجه با قانون مبالغ دفتر.
 */

declare(strict_types=1);

require_once __DIR__ . '/_requests.php';

$pdo = office_db();
$kinds = office_req_kinds();
$statuses = office_req_statuses();
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_req_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'درخواست یافت نشد.';
    $editId = 0;
}
$values = $editRow ?? [];

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            [$savedId, $formErrors] = office_req_save($pdo, is_array($_POST) ? $_POST : [], office_user()['id'], $id > 0 ? $id : null);
            if ($savedId !== null) {
                office_redirect('requests.php?saved=1' . ($id > 0 ? '&edit=' . $savedId : ''));
                $flash = $id > 0 ? 'تغییرات درخواست ذخیره شد.' : 'درخواست ثبت شد.';
                if ($id > 0) {
                    $editId = $savedId;
                    $editRow = office_req_get($pdo, $savedId);
                    $values = $editRow ?? [];
                } else {
                    $values = [];
                }
            } else {
                $values = is_array($_POST) ? $_POST : [];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (office_req_delete($pdo, $id)) {
                office_redirect('requests.php?deleted=1');
                $flash = 'درخواست حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('requests.php', 'درخواست‌ها');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
if (!$isPost) {
    if ((string)($_GET['saved'] ?? '') === '1') {
        echo '<div class="of-alert ok">ذخیره شد.</div>';
    }
    if ((string)($_GET['deleted'] ?? '') === '1') {
        echo '<div class="of-alert ok">درخواست حذف شد.</div>';
    }
}
if ($formErrors) {
    echo '<div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px">';
    foreach ($formErrors as $e) {
        echo '<li>' . office_h($e) . '</li>';
    }
    echo '</ul></div>';
}

$q = trim((string)($_GET['q'] ?? ''));
$fKind = (string)($_GET['kind'] ?? '');
if ($fKind !== '' && !array_key_exists($fKind, $kinds)) {
    $fKind = '';
}
$fStatus = (string)($_GET['status'] ?? '');
if ($fStatus !== '' && !array_key_exists($fStatus, $statuses)) {
    $fStatus = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
[$rows, $total] = office_req_list($pdo, $fKind, $fStatus, $q, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$qsBase = http_build_query(array_filter(['q' => $q, 'kind' => $fKind, 'status' => $fStatus], static fn($v) => $v !== ''));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
$customers = office_req_customer_options($pdo);
$v = static fn(string $k) => (string)($values[$k] ?? '');
$selCustomer = (int)($values['customer_id'] ?? 0);
?>
<style>
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input[type=text],.of-form-grid select,.of-form-grid textarea{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-filter{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
</style>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش درخواست' : 'ثبت درخواست جدید' ?></h3>
    <form method="post" action="requests.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editId > 0): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
        <div class="of-form-grid">
            <label>مشتری لینک‌شده (اختیاری)
                <select name="customer_id">
                    <option value="0">— بدون لینک —</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"<?= $selCustomer === (int)$c['id'] ? ' selected' : '' ?>><?= office_h((string)($c['name'] ?? '') . ' — ' . (string)($c['phone'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>نام متقاضی <b style="color:var(--danger)">*</b>
                <input type="text" name="name" value="<?= office_h($v('name')) ?>" placeholder="خالی = از کارت مشتری"></label>
            <label>شماره تماس <b style="color:var(--danger)">*</b>
                <input type="text" name="phone" dir="ltr" style="text-align:left" value="<?= office_h($v('phone')) ?>" placeholder="خالی = از کارت مشتری"></label>
            <label>نوع درخواست
                <select name="kind">
                    <?php foreach ($kinds as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= ($v('kind') !== '' ? $v('kind') : 'buy') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
            <label>بودجه (تومان)
                <input type="text" name="budget" data-money="1" inputmode="numeric" dir="ltr" style="text-align:left" value="<?= office_h($v('budget') !== '' ? office_group_digits($v('budget')) : '') ?>"></label>
            <label>حداقل متراژ
                <input type="text" name="min_area" dir="ltr" style="text-align:left" value="<?= office_h($v('min_area')) ?>"></label>
            <label>محله مدنظر
                <input type="text" name="neighborhood" value="<?= office_h($v('neighborhood')) ?>"></label>
            <label>وضعیت
                <select name="status">
                    <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= ($v('status') !== '' ? $v('status') : 'new') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
        </div>
        <p><label style="display:flex;flex-direction:column;gap:5px;font-size:13px">شرح درخواست
            <textarea name="description" rows="2"><?= office_h($v('description')) ?></textarea></label></p>
        <p>
            <button class="of-btn" type="submit"><?= $editId > 0 ? 'ذخیره تغییرات' : 'ثبت درخواست' ?></button>
            <?php if ($editId > 0): ?><a class="of-btn ghost" href="requests.php">＋ درخواست جدید</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="of-panel">
    <form method="get" action="requests.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: نام، تماس، محله..." style="min-width:200px" class="of-filter">
        <select name="kind" class="of-filter">
            <option value="">همه انواع</option>
            <?php foreach ($kinds as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $fKind === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="of-filter">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $fStatus === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">جست‌وجو</button>
    </form>
    <p class="of-muted"><?= office_num($total) ?> درخواست یافت شد. <a class="of-btn ghost" style="padding:4px 10px" href="site-requests.php">🎯 درخواست‌های سایت</a></p>
    <?php if (!$rows): ?>
        <p class="of-muted">درخواستی با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>متقاضی / تماس</th><th>نوع</th><th>بودجه</th><th>متراژ ≥</th><th>محله</th><th>مشتری</th><th>وضعیت</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <?php $st = (string)($r['status'] ?? 'new'); ?>
                <tr>
                    <td><?= office_h((string)($r['name'] ?? '')) ?><br><span dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></span></td>
                    <td><?= office_h($kinds[(string)($r['kind'] ?? '')] ?? (string)($r['kind'] ?? '')) ?></td>
                    <td><span dir="ltr"><?= office_h(office_format_money($r['budget'] ?? 0)) ?></span></td>
                    <td><?= office_h((string)($r['min_area'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['neighborhood'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['customer_name'] ?? '')) !== '' ? office_h((string)$r['customer_name']) : '—' ?></td>
                    <td><span class="of-badge<?= $st === 'done' ? ' green' : '' ?>"><?= office_h($statuses[$st] ?? $st) ?></span></td>
                    <td style="white-space:nowrap">
                        <a class="of-btn ghost" style="padding:4px 10px" href="requests.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                        <form method="post" action="requests.php" style="display:inline" onsubmit="return confirm('این درخواست حذف شود؟');">
                            <?= office_csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="حذف">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:12px;font-size:13px;color:var(--muted)">
            <?php if ($page > 1): ?><a class="of-btn ghost" href="requests.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="requests.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
