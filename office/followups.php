<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — پیگیری‌ها (مرحله ۱۲)
 *--------------------------------------------------------------------------
 * لیست (جست‌وجو + فیلتر نوع/وضعیت/سررسیدگذشته) + ثبت + ویرایش + حذف +
 * تیک انجام. لینک به فایل/مشتری/درخواست/بازدید/مشارکت اعتبارسنجی می‌شود.
 */

declare(strict_types=1);

require_once __DIR__ . '/_followups.php';

$pdo = office_db();
$entities = office_follow_entities();
$today = date('Y-m-d');
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_follow_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'پیگیری یافت نشد.';
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
            [$savedId, $formErrors] = office_follow_save($pdo, is_array($_POST) ? $_POST : [], office_user()['id'], $id > 0 ? $id : null);
            if ($savedId !== null) {
                office_redirect('followups.php?saved=1' . ($id > 0 ? '&edit=' . $savedId : ''));
                $flash = $id > 0 ? 'تغییرات پیگیری ذخیره شد.' : 'پیگیری ثبت شد.';
                if ($id > 0) {
                    $editId = $savedId;
                    $editRow = office_follow_get($pdo, $savedId);
                    $values = $editRow ?? [];
                } else {
                    $values = [];
                }
            } else {
                $values = is_array($_POST) ? $_POST : [];
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $new = office_follow_toggle($pdo, $id);
            if ($new === null) {
                $flashErr = 'پیگیری یافت نشد.';
            } else {
                $flash = $new === 1 ? 'انجام شد. ✅' : 'به لیست باز برگشت.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (office_follow_delete($pdo, $id)) {
                office_redirect('followups.php?deleted=1');
                $flash = 'پیگیری حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('followups.php', 'پیگیری‌ها');

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
        echo '<div class="of-alert ok">پیگیری حذف شد.</div>';
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
$fEntity = (string)($_GET['entity'] ?? '');
if ($fEntity !== '' && !array_key_exists($fEntity, $entities)) {
    $fEntity = '';
}
$fDone = (string)($_GET['done'] ?? 'open');
if (!in_array($fDone, ['open', 'done', ''], true)) {
    $fDone = 'open';
}
$fOverdue = (string)($_GET['overdue'] ?? '') === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
[$rows, $total] = office_follow_list($pdo, $fEntity, $fDone, $fOverdue, $q, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$qsBase = http_build_query(array_filter(
    ['q' => $q, 'entity' => $fEntity, 'done' => $fDone, 'overdue' => $fOverdue ? '1' : ''],
    static fn($v) => $v !== ''
));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
$v = static fn(string $k) => (string)($values[$k] ?? '');
?>
<style>
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input,.of-form-grid select,.of-form-grid textarea{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-filter{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-overdue{color:var(--danger);font-weight:800}
</style>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش پیگیری' : 'ثبت پیگیری جدید' ?></h3>
    <form method="post" action="followups.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editId > 0): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
        <div class="of-form-grid">
            <label>عنوان <b style="color:var(--danger)">*</b>
                <input type="text" name="title" value="<?= office_h($v('title')) ?>" placeholder="مثلاً تماس با مالک"></label>
            <label>لینک به
                <select name="entity">
                    <?php foreach ($entities as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= ($v('entity') !== '' ? $v('entity') : 'other') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
            <label>شناسه لینک
                <input type="text" name="entity_id" dir="ltr" style="text-align:left" value="<?= office_h($v('entity_id')) ?>" placeholder="کد فایل / آی‌دی..."></label>
            <label>سررسید
                <input type="date" name="due_date" value="<?= office_h($v('due_date')) ?>"></label>
        </div>
        <p><label style="display:flex;flex-direction:column;gap:5px;font-size:13px">توضیحات
            <textarea name="note" rows="2"><?= office_h($v('note')) ?></textarea></label></p>
        <p>
            <button class="of-btn" type="submit"><?= $editId > 0 ? 'ذخیره تغییرات' : 'ثبت پیگیری' ?></button>
            <?php if ($editId > 0): ?><a class="of-btn ghost" href="followups.php">＋ پیگیری جدید</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="of-panel">
    <form method="get" action="followups.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: عنوان، توضیح..." style="min-width:180px" class="of-filter">
        <select name="entity" class="of-filter">
            <option value="">همه لینک‌ها</option>
            <?php foreach ($entities as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $fEntity === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <select name="done" class="of-filter">
            <option value="open"<?= $fDone === 'open' ? ' selected' : '' ?>>باز</option>
            <option value="done"<?= $fDone === 'done' ? ' selected' : '' ?>>انجام‌شده</option>
            <option value=""<?= $fDone === '' ? ' selected' : '' ?>>همه</option>
        </select>
        <label style="font-size:13px"><input type="checkbox" name="overdue" value="1"<?= $fOverdue ? ' checked' : '' ?>> فقط سررسیدگذشته</label>
        <button class="of-btn" type="submit">جست‌وجو</button>
    </form>
    <p class="of-muted"><?= office_num($total) ?> پیگیری یافت شد.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">پیگیری‌ای با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th></th><th>عنوان</th><th>لینک</th><th>سررسید</th><th>وضعیت</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <?php
                $done = ((int)($r['done'] ?? 0)) === 1;
                $overdue = office_follow_is_overdue($r, $today);
                $linkLabel = office_follow_entity_title($pdo, (string)($r['entity'] ?? 'other'), (string)($r['entity_id'] ?? ''));
                ?>
                <tr>
                    <td>
                        <form method="post" action="followups.php" style="display:inline">
                            <?= office_csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="<?= $done ? 'برگردان به باز' : 'انجام شد' ?>"><?= $done ? '✅' : '⬜' ?></button>
                        </form>
                    </td>
                    <td><?= $done ? '<s class="of-muted">' . office_h((string)($r['title'] ?? '')) . '</s>' : office_h((string)($r['title'] ?? '')) ?></td>
                    <td><span class="of-badge"><?= office_h($entities[(string)($r['entity'] ?? '')] ?? (string)($r['entity'] ?? '')) ?></span>
                        <?php if (($r['entity_id'] ?? '') !== ''): ?><br><span dir="ltr"><?= office_h((string)$r['entity_id']) ?></span><?php if ($linkLabel): ?><br><span class="of-muted"><?= office_h($linkLabel) ?></span><?php endif; ?><?php endif; ?></td>
                    <td dir="ltr"><?= ($r['due_date'] ?? '') !== '' && $r['due_date'] !== null ? office_h((string)$r['due_date']) . ($overdue ? ' <span class="of-overdue">سررسید گذشته!</span>' : '') : '—' ?></td>
                    <td><span class="of-badge<?= $done ? ' green' : '' ?>"><?= $done ? 'انجام‌شده' : 'باز' ?></span></td>
                    <td style="white-space:nowrap">
                        <a class="of-btn ghost" style="padding:4px 10px" href="followups.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                        <form method="post" action="followups.php" style="display:inline" onsubmit="return confirm('این پیگیری حذف شود؟');">
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
            <?php if ($page > 1): ?><a class="of-btn ghost" href="followups.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="followups.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
