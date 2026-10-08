<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — پشتیبان‌گیری و بازیابی (مرحله ۲۹)
 *--------------------------------------------------------------------------
 * آینهٔ admin-backup.php سایت: ساخت، فهرست، آپلود، دانلود، حذف و
 * بازیابی (با نسخه ایمنی اجباری). پوشه مشترک backups/ کنار سایت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_backup.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
$flash = '';
$flashErr = '';

$dl = trim((string)($_GET['download'] ?? ''));
if ($dl !== '') {
    $path = office_bk_download_path($dl);
    if ($path === '') {
        $flashErr = 'فایل پشتیبان پیدا نشد.';
    } else {
        if (!defined('OFFICE_NOEXIT')) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
        echo 'OFFICE_BACKUP_DOWNLOAD:' . basename($path) . ':' . filesize($path);
        return;
    }
}

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'create') {
            [$ok, $msg] = office_bk_create($pdo, !empty($_POST['with_db']), !empty($_POST['with_files']));
        } elseif ($action === 'delete') {
            [$ok, $msg] = office_bk_delete((string)($_POST['file'] ?? ''));
        } elseif ($action === 'upload') {
            [$ok, $msg] = office_bk_upload($_FILES['backup_file'] ?? null);
        } elseif ($action === 'restore') {
            [$ok, $msg] = office_bk_restore($pdo, (string)($_POST['file'] ?? ''), !empty($_POST['restore_db']), !isset($_POST['restore_files']) || !empty($_POST['restore_files']));
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

$items = office_bk_list();
$available = office_bk_available();

office_shell_open('promotions.php', 'پشتیبان‌گیری و بازیابی');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<?php if (!$available) : ?>
<p class="of-alert err">هیچ کتابخانه‌ی فشرده‌سازی روی سرور در دسترس نیست.</p>
<?php endif; ?>

<div class="of-panel">
<h3 style="margin-top:0">📦 ساخت پشتیبان جدید</h3>
<form method="post">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="create">
<label><input type="checkbox" name="with_db" value="1" checked> دیتابیس</label>
&nbsp;
<label><input type="checkbox" name="with_files" value="1" checked> فایل‌ها</label>
&nbsp;
<button class="of-btn" type="submit">ساخت پشتیبان</button>
</form>
</div>

<div class="of-panel">
<h3 style="margin-top:0">⬆️ آپلود فایل پشتیبان</h3>
<form method="post" enctype="multipart/form-data">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="upload">
<input type="file" name="backup_file" accept=".zip,.tar,.tar.gz">
<button class="of-btn" type="submit">آپلود</button>
<span class="of-muted">فقط zip و tar</span>
</form>
</div>

<h3>🗂 فایل‌های پشتیبان (<?= office_num(count($items)) ?>)</h3>
<?php if (!$items) : ?><p class="of-muted">هنوز پشتیبانی ساخته نشده است.</p><?php else : ?>
<div class="of-table-wrap"><table class="of-table">
<tr><th>فایل</th><th>حجم</th><th>ساخت</th><th>جزئیات</th><th>عملیات</th></tr>
<?php foreach ($items as $it) : ?>
<tr>
<td><?= office_h($it['name']) ?><?php if ($it['is_safety']) : ?> <span class="of-badge">🛡 ایمنی</span><?php endif; ?></td>
<td><?= office_h(office_fa($it['size_human'])) ?></td>
<td><?= office_h(office_fa($it['created_at'])) ?></td>
<td class="of-muted" style="font-size:12px">
<?php if (is_array($it['meta'])) : ?>
<?= $it['meta']['with_db'] ? 'دیتابیس ✓' : '—' ?> · <?= $it['meta']['with_files'] ? office_fa((string)$it['meta']['file_count']) . ' فایل' : '—' ?> · <?= office_fa((string)$it['meta']['tables']) ?> جدول
<?php else : ?>—<?php endif; ?>
</td>
<td style="white-space:nowrap">
<a class="of-btn ghost" style="padding:4px 10px" href="backup.php?download=<?= urlencode($it['name']) ?>">⬇️</a>
<form method="post" style="display:inline" onsubmit="return confirm('⚠️ بازیابی جایگزین وضعیت فعلی می‌شود! (نسخه ایمنی خودکار ساخته می‌شود) ادامه می‌دهید؟');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="restore">
<input type="hidden" name="file" value="<?= office_h($it['name']) ?>">
<input type="hidden" name="restore_files" value="1">
<label class="of-muted" style="font-size:11px"><input type="checkbox" name="restore_db" value="1"> دیتابیس</label>
<button class="of-btn ghost" style="padding:4px 10px" type="submit" title="بازیابی">♻️</button>
</form>
<form method="post" style="display:inline" onsubmit="return confirm('این فایل پشتیبان حذف شود؟');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="file" value="<?= office_h($it['name']) ?>">
<button class="of-btn ghost" style="padding:4px 10px" type="submit" title="حذف">🗑</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php office_shell_close(); ?>
