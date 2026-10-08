<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تماس‌ها (مرحله ۱۱)
 *--------------------------------------------------------------------------
 * لیست (جست‌وجو + فیلتر جهت/روز) + ثبت + ویرایش + حذف.
 * لینک اختیاری به فایل؛ مدت‌زمان به دقیقه.
 */

declare(strict_types=1);

require_once __DIR__ . '/_calls.php';

$pdo = office_db();
$dirs = office_call_dirs();
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_call_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'تماس یافت نشد.';
    $editId = 0;
}
$values = $editRow ?? [];
$splitAt = static function (array &$vals, string $at): void {
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/', $at, $m)) {
        $vals['call_date'] = $m[1];
        $vals['call_time'] = $m[2];
    }
};
if ($editRow && !isset($values['call_date'])) {
    $splitAt($values, (string)($editRow['called_at'] ?? ''));
}
// پیش‌فرض ثبت جدید: اکنون
if (!$editRow && !isset($values['call_date'])) {
    $splitAt($values, date('Y-m-d H:i'));
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
            [$savedId, $formErrors] = office_call_save($pdo, is_array($_POST) ? $_POST : [], office_user()['id'], $id > 0 ? $id : null);
            if ($savedId !== null) {
                office_redirect('calls.php?saved=1' . ($id > 0 ? '&edit=' . $savedId : ''));
                $flash = $id > 0 ? 'تغییرات تماس ذخیره شد.' : 'تماس ثبت شد.';
                if ($id > 0) {
                    $editId = $savedId;
                    $editRow = office_call_get($pdo, $savedId);
                    $values = $editRow ?? [];
                    $splitAt($values, (string)($editRow['called_at'] ?? ''));
                } else {
                    $values = [];
                    $splitAt($values, date('Y-m-d H:i'));
                }
            } else {
                $values = is_array($_POST) ? $_POST : [];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (office_call_delete($pdo, $id)) {
                office_redirect('calls.php?deleted=1');
                $flash = 'تماس حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('calls.php', 'تماس‌ها');

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
        echo '<div class="of-alert ok">تماس حذف شد.</div>';
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
$fDir = (string)($_GET['dir'] ?? '');
if ($fDir !== '' && !array_key_exists($fDir, $dirs)) {
    $fDir = '';
}
$fDay = (string)($_GET['day'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDay)) {
    $fDay = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
[$rows, $total] = office_call_list($pdo, $fDir, $fDay, $q, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$qsBase = http_build_query(array_filter(['q' => $q, 'dir' => $fDir, 'day' => $fDay], static fn($v) => $v !== ''));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
$files = [];
try {
    $st = $pdo->query('SELECT id, title FROM ads ORDER BY created_at DESC, id DESC LIMIT 100');
    $files = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
} catch (Throwable $e) {
}
$v = static fn(string $k) => (string)($values[$k] ?? '');
?>
<style>
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input,.of-form-grid select,.of-form-grid textarea{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-filter{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
</style>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش تماس' : 'ثبت تماس جدید' ?></h3>
    <form method="post" action="calls.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editId > 0): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
        <div class="of-form-grid">
            <label>نام تماس‌گیرنده <b style="color:var(--danger)">*</b>
                <input type="text" name="name" value="<?= office_h($v('name')) ?>"></label>
            <label>شماره تماس <b style="color:var(--danger)">*</b>
                <input type="text" name="phone" dir="ltr" style="text-align:left" value="<?= office_h($v('phone')) ?>"></label>
            <label>جهت
                <select name="direction">
                    <?php foreach ($dirs as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= ($v('direction') !== '' ? $v('direction') : 'in') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
            <label>کد فایل (اختیاری)
                <input type="text" name="ad_id" dir="ltr" style="text-align:left" list="ofFiles" value="<?= office_h($v('ad_id')) ?>">
                <datalist id="ofFiles">
                    <?php foreach ($files as $f): ?><option value="<?= office_h((string)$f['id']) ?>"><?= office_h((string)($f['title'] ?? '')) ?></option><?php endforeach; ?>
                </datalist></label>
            <label>مدت (دقیقه)
                <input type="text" name="duration" dir="ltr" style="text-align:left" value="<?= office_h($v('duration')) ?>" placeholder="مثلاً ۵"></label>
            <label>تاریخ تماس <b style="color:var(--danger)">*</b>
                <input type="date" name="call_date" value="<?= office_h($v('call_date')) ?>"></label>
            <label>ساعت تماس <b style="color:var(--danger)">*</b>
                <input type="time" name="call_time" value="<?= office_h($v('call_time')) ?>"></label>
        </div>
        <p><label style="display:flex;flex-direction:column;gap:5px;font-size:13px">شرح تماس
            <textarea name="note" rows="2"><?= office_h($v('note')) ?></textarea></label></p>
        <p>
            <button class="of-btn" type="submit"><?= $editId > 0 ? 'ذخیره تغییرات' : 'ثبت تماس' ?></button>
            <?php if ($editId > 0): ?><a class="of-btn ghost" href="calls.php">＋ تماس جدید</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="of-panel">
    <form method="get" action="calls.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: نام، تماس، شرح..." style="min-width:200px" class="of-filter">
        <select name="dir" class="of-filter">
            <option value="">همه جهت‌ها</option>
            <?php foreach ($dirs as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $fDir === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="day" value="<?= office_h($fDay) ?>" class="of-filter" title="فیلتر روز">
        <button class="of-btn" type="submit">جست‌وجو</button>
    </form>
    <p class="of-muted"><?= office_num($total) ?> تماس یافت شد.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">تماسی با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>نام / تماس</th><th>جهت</th><th>فایل</th><th>مدت</th><th>زمان تماس</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= office_h((string)($r['name'] ?? '')) ?><br><span dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></span></td>
                    <td><?= $r['direction'] === 'out' ? '📤 خروجی' : '📥 ورودی' ?></td>
                    <td><?= ($r['ad_id'] ?? '') !== '' ? '<span dir="ltr">' . office_h((string)$r['ad_id']) . '</span><br><span class="of-muted">' . office_h((string)($r['ad_title'] ?? '')) . '</span>' : '—' ?></td>
                    <td><?= ((int)($r['duration'] ?? 0)) > 0 ? office_fa((string)(int)$r['duration']) . ' دقیقه' : '—' ?></td>
                    <td dir="ltr"><?= office_h(substr((string)($r['called_at'] ?? ''), 0, 16)) ?></td>
                    <td style="white-space:nowrap">
                        <a class="of-btn ghost" style="padding:4px 10px" href="calls.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                        <form method="post" action="calls.php" style="display:inline" onsubmit="return confirm('این تماس حذف شود؟');">
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
            <?php if ($page > 1): ?><a class="of-btn ghost" href="calls.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="calls.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
