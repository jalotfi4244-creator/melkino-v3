<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تصاویر آگهی‌ها (مرحله ۲۶)
 *--------------------------------------------------------------------------
 * آینهٔ مدیریت تصاویر پنل سایت: فهرست+فیلتر، حذف تکی، حذف دسته‌ای،
 * پاک‌سازی فایل‌های یتیم.
 */

declare(strict_types=1);

require_once __DIR__ . '/_images.php';

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
        $delSingle = $_POST['del_single'] ?? null;
        if (is_array($delSingle) && $delSingle) {
            $sid = (int)array_key_first($delSingle);
            $rm = $_POST['rm'] ?? [];
            [$ok, $msg] = office_img_delete($pdo, $sid, !empty($rm[$sid]));
        } elseif ($action === 'delete') {
            [$ok, $msg] = office_img_delete($pdo, (int)($_POST['id'] ?? 0), !empty($_POST['remove_file']));
        } elseif ($action === 'bulk_delete') {
            $groups = $_POST['bulk'] ?? [];
            if (!is_array($groups)) {
                $groups = [];
            }
            $total = 0;
            $okAll = true;
            $lastMsg = 'فایلی انتخاب نشده است.';
            foreach ($groups as $adId => $files) {
                if (!is_array($files) || !$files) {
                    continue;
                }
                [$ok, $msg] = office_img_bulk($pdo, (string)$adId, $files);
                $lastMsg = $msg;
                $total++;
                if (!$ok) {
                    $okAll = false;
                }
            }
            $ok = $total > 0 && $okAll;
            $msg = $total > 0 ? $lastMsg : 'فایلی انتخاب نشده است.';
        } elseif ($action === 'delete_orphans') {
            $names = $_POST['orphans'] ?? [];
            [$ok, $msg] = office_img_delete_orphans($pdo, is_array($names) ? $names : []);
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

$view = (string)($_GET['view'] ?? 'list');
$q = trim((string)($_GET['q'] ?? ''));
$adId = trim((string)($_GET['ad_id'] ?? ''));
$onlyMissing = (($_GET['missing'] ?? '') === '1');
$rows = $view === 'orphans' ? [] : office_img_list($pdo, $q, $adId, $onlyMissing);
$orphans = $view === 'orphans' ? office_img_orphans($pdo) : [];

office_shell_open('files.php', 'تصاویر آگهی‌ها');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>

<p>
<a class="of-btn<?= $view !== 'orphans' ? '' : ' ghost' ?>" href="images.php">🖼 فهرست تصاویر</a>
<a class="of-btn<?= $view === 'orphans' ? '' : ' ghost' ?>" href="images.php?view=orphans">🧹 فایل‌های یتیم</a>
</p>

<?php if ($view === 'orphans') : ?>
<p class="of-muted"><?= office_num(count($orphans)) ?> فایل یتیم در پوشه uploads (بدون ردیف دیتابیس).</p>
<?php if ($orphans) : ?>
<form method="post" onsubmit="return confirm('فایل‌های انتخاب‌شده برای همیشه حذف شوند؟');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="delete_orphans">
<div class="of-table-wrap"><table class="of-table">
<tr><th></th><th>فایل</th><th>حجم</th></tr>
<?php foreach ($orphans as $o) : ?>
<tr>
<td><input type="checkbox" name="orphans[]" value="<?= office_h($o['name']) ?>"></td>
<td><?= office_h($o['name']) ?></td>
<td><?= office_h(office_fa(round($o['size'] / 1024, 1) . ' کیلوبایت')) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<p><button class="of-btn" type="submit">🗑 حذف انتخاب‌شده‌ها</button></p>
</form>
<?php endif; ?>
<?php else : ?>
<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
<input type="text" name="q" placeholder="جست‌وجو در نام فایل، عنوان آگهی…" value="<?= office_h($q) ?>" style="min-width:220px">
<input type="text" name="ad_id" placeholder="شناسه آگهی" value="<?= office_h($adId) ?>" style="width:120px">
<label><input type="checkbox" name="missing" value="1"<?= $onlyMissing ? ' checked' : '' ?>> فقط فایل‌های گمشده</label>
<button class="of-btn" type="submit">🔍 جست‌وجو</button>
<?php if ($q !== '' || $adId !== '' || $onlyMissing) : ?><a class="of-btn ghost" href="images.php">حذف فیلتر</a><?php endif; ?>
</form>
<p class="of-muted"><?= office_num(count($rows)) ?> تصویر (حداکثر ۳۰۰ ردیف آخر).</p>
<form method="post" onsubmit="return confirm('موارد انتخاب‌شده حذف شوند؟ (فایل‌ها هم از سرور پاک می‌شوند)');">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="bulk_delete">
<div class="of-table-wrap"><table class="of-table">
<tr><th></th><th>پیش‌نمایش</th><th>فایل / آگهی</th><th>وضعیت</th><th>حذف تکی</th></tr>
<?php foreach ($rows as $r) : ?>
<tr>
<td><input type="checkbox" name="bulk[<?= office_h((string)$r['ad_id']) ?>][]" value="<?= office_h((string)$r['filename']) ?>"></td>
<td><?php if ($r['exists']) : ?><img src="<?= office_h(office_img_web($r)) ?>" alt="" style="max-width:80px;max-height:60px;border-radius:6px"><?php else : ?><span class="of-muted">—</span><?php endif; ?></td>
<td>
<div><?= office_h(basename((string)$r['filename'])) ?></div>
<div class="of-muted">آگهی <?= office_h((string)$r['ad_id']) ?><?= !empty($r['ad_title']) ? ' — ' . office_h((string)$r['ad_title']) : '' ?></div>
</td>
<td>
<?php if (!empty($r['is_primary'])) : ?><span class="of-badge">⭐ اصلی</span><?php endif; ?>
<?php if ($r['exists']) : ?><span class="of-badge">📁 <?= office_h(office_fa(round($r['size'] / 1024, 1) . 'ک‌ب')) ?></span><?php else : ?><span class="of-badge">⚠️ فایل نیست</span><?php endif; ?>
</td>
<td>
<label class="of-muted" style="font-size:11px"><input type="checkbox" name="rm[<?= (int)$r['id'] ?>]" value="1" checked> فایل هم پاک شود</label>
<button class="of-btn ghost" type="submit" name="del_single[<?= (int)$r['id'] ?>]" value="1" formaction="images.php" onclick="return confirm('این تصویر حذف شود؟');">🗑 حذف</button>
</td>
</tr>
<?php endforeach; ?>
</table></div>
<p><button class="of-btn" type="submit">🗑 حذف دسته‌ای انتخاب‌شده‌ها</button></p>
</form>
<?php endif; ?>
<?php office_shell_close(); ?>
