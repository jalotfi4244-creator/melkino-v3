<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — بازدیدها (مرحله ۱۰)
 *--------------------------------------------------------------------------
 * لیست (جست‌وجو + فیلتر وضعیت/روز) + ثبت + ویرایش + حذف.
 * هر بازدید به یک فایل موجود لینک است + لینک اختیاری به مشتری.
 */

declare(strict_types=1);

require_once __DIR__ . '/_visits.php';

$pdo = office_db();
$statuses = office_visit_statuses();
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_visit_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'بازدید یافت نشد.';
    $editId = 0;
}
$values = $editRow ?? [];
// visit_at ← دو اینپوت date/time
if ($editRow && !isset($values['visit_date'])) {
    $at = (string)($editRow['visit_at'] ?? '');
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/', $at, $m)) {
        $values['visit_date'] = $m[1];
        $values['visit_time'] = $m[2];
    }
}

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
            [$savedId, $formErrors] = office_visit_save($pdo, is_array($_POST) ? $_POST : [], office_user()['id'], $id > 0 ? $id : null);
            if ($savedId !== null) {
                office_redirect('visits.php?saved=1' . ($id > 0 ? '&edit=' . $savedId : ''));
                $flash = $id > 0 ? 'تغییرات بازدید ذخیره شد.' : 'بازدید ثبت شد.';
                if ($id > 0) {
                    $editId = $savedId;
                    $editRow = office_visit_get($pdo, $savedId);
                    $values = $editRow ?? [];
                    $at = (string)($editRow['visit_at'] ?? '');
                    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/', $at, $m)) {
                        $values['visit_date'] = $m[1];
                        $values['visit_time'] = $m[2];
                    }
                } else {
                    $values = [];
                }
            } else {
                $values = is_array($_POST) ? $_POST : [];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (office_visit_delete($pdo, $id)) {
                office_redirect('visits.php?deleted=1');
                $flash = 'بازدید حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('visits.php', 'بازدیدها');

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
        echo '<div class="of-alert ok">بازدید حذف شد.</div>';
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
$fStatus = (string)($_GET['status'] ?? '');
if ($fStatus !== '' && !array_key_exists($fStatus, $statuses)) {
    $fStatus = '';
}
$fDay = (string)($_GET['day'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDay)) {
    $fDay = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
[$rows, $total] = office_visit_list($pdo, $fStatus, $fDay, $q, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$qsBase = http_build_query(array_filter(['q' => $q, 'status' => $fStatus, 'day' => $fDay], static fn($v) => $v !== ''));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
$customers = office_req_customer_options($pdo);
$files = office_visit_file_options($pdo);
$v = static fn(string $k) => (string)($values[$k] ?? '');
$selCustomer = (int)($values['customer_id'] ?? 0);
?>
<style>
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input,.of-form-grid select,.of-form-grid textarea{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-filter{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
</style>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش بازدید' : 'ثبت بازدید جدید' ?></h3>
    <form method="post" action="visits.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editId > 0): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
        <div class="of-form-grid">
            <label>کد فایل <b style="color:var(--danger)">*</b>
                <input type="text" name="ad_id" dir="ltr" style="text-align:left" list="ofFiles" value="<?= office_h($v('ad_id')) ?>" placeholder="کد فایل">
                <datalist id="ofFiles">
                    <?php foreach ($files as $f): ?><option value="<?= office_h((string)$f['id']) ?>"><?= office_h((string)($f['title'] ?? '')) ?></option><?php endforeach; ?>
                </datalist></label>
            <label>مشتری لینک‌شده (اختیاری)
                <select name="customer_id">
                    <option value="0">— بدون لینک —</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"<?= $selCustomer === (int)$c['id'] ? ' selected' : '' ?>><?= office_h((string)($c['name'] ?? '') . ' — ' . (string)($c['phone'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>نام بازدیدکننده <b style="color:var(--danger)">*</b>
                <input type="text" name="name" value="<?= office_h($v('name')) ?>" placeholder="خالی = از کارت مشتری"></label>
            <label>شماره تماس <b style="color:var(--danger)">*</b>
                <input type="text" name="phone" dir="ltr" style="text-align:left" value="<?= office_h($v('phone')) ?>" placeholder="خالی = از کارت مشتری"></label>
            <label>تاریخ بازدید <b style="color:var(--danger)">*</b>
                <input type="date" name="visit_date" value="<?= office_h($v('visit_date')) ?>"></label>
            <label>ساعت بازدید <b style="color:var(--danger)">*</b>
                <input type="time" name="visit_time" value="<?= office_h($v('visit_time')) ?>"></label>
            <label>وضعیت
                <select name="status">
                    <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= ($v('status') !== '' ? $v('status') : 'scheduled') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
        </div>
        <p><label style="display:flex;flex-direction:column;gap:5px;font-size:13px">توضیحات
            <textarea name="notes" rows="2"><?= office_h($v('notes')) ?></textarea></label></p>
        <p>
            <button class="of-btn" type="submit"><?= $editId > 0 ? 'ذخیره تغییرات' : 'ثبت بازدید' ?></button>
            <?php if ($editId > 0): ?><a class="of-btn ghost" href="visits.php">＋ بازدید جدید</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="of-panel">
    <form method="get" action="visits.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: کد فایل، نام، تماس..." style="min-width:200px" class="of-filter">
        <select name="status" class="of-filter">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $fStatus === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="day" value="<?= office_h($fDay) ?>" class="of-filter" title="فیلتر روز">
        <button class="of-btn" type="submit">جست‌وجو</button>
    </form>
    <p class="of-muted"><?= office_num($total) ?> بازدید یافت شد.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">بازدیدی با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>فایل</th><th>بازدیدکننده / تماس</th><th>زمان بازدید</th><th>وضعیت</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <?php $st = (string)($r['status'] ?? 'scheduled'); ?>
                <tr>
                    <td><span dir="ltr"><?= office_h((string)($r['ad_id'] ?? '')) ?></span><br><span class="of-muted"><?= office_h((string)($r['ad_title'] ?? '')) ?></span></td>
                    <td><?= office_h((string)($r['name'] ?? '')) ?><br><span dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></span></td>
                    <td dir="ltr"><?= office_h(substr((string)($r['visit_at'] ?? ''), 0, 16)) ?></td>
                    <td><span class="of-badge<?= $st === 'done' ? ' green' : '' ?>"><?= office_h($statuses[$st] ?? $st) ?></span></td>
                    <td style="white-space:nowrap">
                        <a class="of-btn ghost" style="padding:4px 10px" href="visits.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                        <form method="post" action="visits.php" style="display:inline" onsubmit="return confirm('این بازدید حذف شود؟');">
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
            <?php if ($page > 1): ?><a class="of-btn ghost" href="visits.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="visits.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
