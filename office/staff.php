<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کاربران و دسترسی‌ها (مرحله ۱۵)
 *--------------------------------------------------------------------------
 * ثبت/ویرایش/فعال/حذف حساب‌های admins (همان لاگین دفتر و پنل).
 * محافظ‌ها: نه خود، نه آخرین ادمین فعال.
 */

declare(strict_types=1);

require_once __DIR__ . '/_staff.php';

$pdo = office_db();
$selfId = office_user()['id'];
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_staff_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'کاربر یافت نشد.';
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
        if ($action === 'create') {
            [$savedId, $formErrors] = office_staff_create(
                $pdo,
                (string)($_POST['username'] ?? ''),
                (string)($_POST['password'] ?? ''),
                (string)($_POST['display_name'] ?? '')
            );
            if ($savedId !== null) {
                office_redirect('staff.php?saved=1');
                $flash = 'کاربر ساخته شد.';
                $values = [];
            } else {
                $values = ['username' => (string)($_POST['username'] ?? ''), 'display_name' => (string)($_POST['display_name'] ?? '')];
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $formErrors = office_staff_update($pdo, $id, (string)($_POST['display_name'] ?? ''), (string)($_POST['new_password'] ?? ''));
            if (!$formErrors) {
                office_redirect('staff.php?saved=1&edit=' . $id);
                $flash = 'تغییرات ذخیره شد.';
                $editId = $id;
                $editRow = office_staff_get($pdo, $id);
                $values = $editRow ?? [];
            } else {
                $editId = $id;
                $values = array_merge($editRow ?? [], ['display_name' => (string)($_POST['display_name'] ?? '')]);
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $row = office_staff_get($pdo, $id);
            if (!$row) {
                $flashErr = 'کاربر یافت نشد.';
            } else {
                $err = office_staff_set_active($pdo, $id, !((int)($row['is_active'] ?? 1)), $selfId);
                if ($err === null) {
                    $flash = 'وضعیت کاربر تغییر کرد.';
                } else {
                    $flashErr = $err;
                }
            }
        } elseif ($action === 'delete') {
            $err = office_staff_delete($pdo, (int)($_POST['id'] ?? 0), $selfId);
            if ($err === null) {
                office_redirect('staff.php?deleted=1');
                $flash = 'کاربر حذف شد.';
            } else {
                $flashErr = $err;
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('staff.php', 'کاربران و دسترسی‌ها');

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
        echo '<div class="of-alert ok">کاربر حذف شد.</div>';
    }
}
if ($formErrors) {
    echo '<div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px">';
    foreach ($formErrors as $e) {
        echo '<li>' . office_h($e) . '</li>';
    }
    echo '</ul></div>';
}

$rows = office_staff_list($pdo);
$v = static fn(string $k) => (string)($values[$k] ?? '');
?>
<style>
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
</style>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش کاربر' : 'کاربر جدید' ?></h3>
    <?php if ($editId > 0): ?>
        <p class="of-muted">نام کاربری: <b dir="ltr"><?= office_h((string)($editRow['username'] ?? '')) ?></b> (قابل تغییر نیست)</p>
        <form method="post" action="staff.php?edit=<?= $editId ?>">
            <?= office_csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editId ?>">
            <div class="of-form-grid">
                <label>نام نمایشی
                    <input type="text" name="display_name" value="<?= office_h($v('display_name')) ?>"></label>
                <label>رمز جدید (خالی = بدون تغییر)
                    <input type="password" name="new_password" dir="ltr" style="text-align:left" autocomplete="new-password" placeholder="حداقل ۱۲ کاراکتر، حرف+رقم"></label>
            </div>
            <p>
                <button class="of-btn" type="submit">ذخیره تغییرات</button>
                <a class="of-btn ghost" href="staff.php">＋ کاربر جدید</a>
            </p>
        </form>
    <?php else: ?>
        <form method="post" action="staff.php">
            <?= office_csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="of-form-grid">
                <label>نام کاربری <b style="color:var(--danger)">*</b>
                    <input type="text" name="username" dir="ltr" style="text-align:left" value="<?= office_h($v('username')) ?>" placeholder="a-z 0-9 . _ -"></label>
                <label>رمز <b style="color:var(--danger)">*</b>
                    <input type="password" name="password" dir="ltr" style="text-align:left" autocomplete="new-password" placeholder="حداقل ۱۲ کاراکتر، حرف+رقم"></label>
                <label>نام نمایشی
                    <input type="text" name="display_name" value="<?= office_h($v('display_name')) ?>"></label>
            </div>
            <p><button class="of-btn" type="submit">ساخت کاربر</button></p>
        </form>
    <?php endif; ?>
</div>

<div class="of-panel">
    <p class="of-muted"><?= office_num(count($rows)) ?> کاربر. <a class="of-btn ghost" style="padding:4px 10px" href="audit.php">📋 گزارش عملکرد</a></p>
    <div class="of-table-wrap"><table class="of-table">
        <tr><th>#</th><th>نام کاربری</th><th>نام نمایشی</th><th>وضعیت</th><th>آخرین ورود موفق</th><th>اقدام</th></tr>
        <?php foreach ($rows as $r): ?>
            <?php $active = ((int)($r['is_active'] ?? 1)) === 1; $isSelf = ((int)$r['id'] === $selfId); ?>
            <tr<?= $isSelf ? ' style="background:var(--bg)"' : '' ?>>
                <td><?= (int)$r['id'] ?></td>
                <td dir="ltr"><?= office_h((string)($r['username'] ?? '')) ?><?= $isSelf ? ' <span class="of-badge">شما</span>' : '' ?></td>
                <td><?= office_h((string)($r['display_name'] ?? '')) ?></td>
                <td><span class="of-badge<?= $active ? ' green' : '' ?>"><?= $active ? 'فعال' : 'غیرفعال' ?></span></td>
                <td dir="ltr"><?= ($r['last_login'] ?? '') !== '' && $r['last_login'] !== null ? office_h(substr((string)$r['last_login'], 0, 16)) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a class="of-btn ghost" style="padding:4px 10px" href="staff.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                    <form method="post" action="staff.php" style="display:inline" onsubmit="return confirm('وضعیت این کاربر تغییر کند؟');">
                        <?= office_csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="<?= $active ? 'غیرفعال' : 'فعال' ?>"><?= $active ? '⏸' : '▶' ?></button>
                    </form>
                    <form method="post" action="staff.php" style="display:inline" onsubmit="return confirm('این کاربر حذف شود؟');">
                        <?= office_csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="حذف">🗑</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table></div>
</div>

<?php office_shell_close(); ?>
