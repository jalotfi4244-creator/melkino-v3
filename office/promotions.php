<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — مدیریت سایت: تبلیغات/بنرها (مرحله ۱۷)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ مدیریت تبلیغات پنل سایت: آمار، لیست، ثبت/ویرایش،
 * فعال/غیرفعال، حذف + آپلود تصویر با همان قوانین سایت.
 */

declare(strict_types=1);

// نگهبان خروجی: اگر این صفحه (یا کتابخانه‌اش) بی‌صدا بمیرد، به‌جای پاسخ خالی علت گفته می‌شود.
ob_start();
register_shutdown_function(static function (): void {
    if (ob_get_level() < 1) {
        return;
    }
    $out = (string)ob_get_contents();
    if ($out !== '') {
        return; // خروجی عادی؛ دخالت نکن.
    }
    foreach (headers_list() as $h) {
        if (stripos($h, 'location:') === 0) {
            return; // ریدایرکت سالم بعد از ذخیره؛ دخالت نکن.
        }
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    $msg = 'صفحه مدیریت سایت خروجی تولید نکرد.';
    $last = error_get_last();
    if (is_array($last) && ($last['message'] ?? '') !== '') {
        $msg .= ' ' . (string)$last['message'] . ' @ ' . basename((string)($last['file'] ?? '')) . ':' . (int)($last['line'] ?? 0);
    } else {
        $msg .= ' (اگر فایل promotions.php روی هاست ۰ بایت است، زیپ را دوباره کامل اکسترکت کنید.)';
    }
    echo $msg;
});
set_exception_handler(static function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit('خطای صفحه مدیریت سایت: ' . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . basename((string)$e->getFile()) . ':' . (int)$e->getLine());
});

require_once __DIR__ . '/_promotions.php';

$pdo = office_db();
office_promo_boot($pdo);
$flash = '';
$flashErr = '';
$formErrors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? office_promo_get($pdo, $editId) : null;
if ($editId > 0 && !$editRow) {
    $flashErr = 'تبلیغ یافت نشد.';
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
            $in = [
                'title' => (string)($_POST['title'] ?? ''),
                'image_url' => trim((string)($_POST['image_url'] ?? '')),
                'link_url' => (string)($_POST['link_url'] ?? ''),
                'placement' => (string)($_POST['placement'] ?? 'all'),
                'position_after' => (string)($_POST['position_after'] ?? '3'),
                'repeat_every' => (string)($_POST['repeat_every'] ?? '0'),
                'start_date' => (string)($_POST['start_date'] ?? ''),
                'end_date' => (string)($_POST['end_date'] ?? ''),
                'is_active' => !empty($_POST['is_active']),
            ];
            $file = $_FILES['image'] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$url, $upErr] = office_promo_upload($file);
                if ($upErr !== null) {
                    $formErrors[] = $upErr;
                } else {
                    $in['image_url'] = (string)$url;
                }
            } elseif ($in['image_url'] === '' && $id > 0) {
                $in['image_url'] = (string)(office_promo_get($pdo, $id)['image_url'] ?? '');
            }
            if (!$formErrors) {
                [$savedId, $formErrors] = office_promo_save($pdo, $id > 0 ? $id : null, $in);
                if ($savedId !== null) {
                    office_redirect('promotions.php?saved=1' . ($id > 0 ? '&edit=' . $id : ''));
                    $flash = 'تبلیغ ذخیره شد.';
                    if ($id > 0) {
                        $editId = $id;
                        $values = office_promo_get($pdo, $id) ?? [];
                    } else {
                        $values = [];
                    }
                } else {
                    $values = $in;
                    if ($id > 0) {
                        $editId = $id;
                    }
                }
            } else {
                $values = $in;
                if ($id > 0) {
                    $editId = $id;
                }
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $row = office_promo_get($pdo, $id);
            if (!$row) {
                $flashErr = 'تبلیغ یافت نشد.';
            } elseif (office_promo_toggle($pdo, $id, !((int)($row['is_active'] ?? 1)))) {
                $flash = 'وضعیت تبلیغ تغییر کرد.';
            } else {
                $flashErr = 'تغییر وضعیت ناموفق بود.';
            }
        } elseif ($action === 'delete') {
            if (office_promo_delete($pdo, (int)($_POST['id'] ?? 0))) {
                office_redirect('promotions.php?deleted=1');
                $flash = 'تبلیغ حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('promotions.php', 'مدیریت سایت — تبلیغات');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
if (!$isPost) {
    if ((string)($_GET['saved'] ?? '') === '1') {
        echo '<div class="of-alert ok">تبلیغ ذخیره شد.</div>';
    }
    if ((string)($_GET['deleted'] ?? '') === '1') {
        echo '<div class="of-alert ok">تبلیغ حذف شد.</div>';
    }
}
if ($formErrors) {
    echo '<div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px">';
    foreach ($formErrors as $e) {
        echo '<li>' . office_h($e) . '</li>';
    }
    echo '</ul></div>';
}

$stats = office_promo_stats($pdo);
$rows = office_promo_list($pdo);
$placements = office_promo_placements();
$v = static fn(string $k) => (string)($values[$k] ?? '');
$dtLocal = static function (string $v): string {
    $v = trim($v);
    if (strlen($v) >= 16 && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $v)) {
        return substr($v, 0, 10) . 'T' . substr($v, 11, 5);
    }
    return str_replace(' ', 'T', $v);
};
?>
<style>
.of-stat-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:12px}
.of-stat-cards div{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:10px 12px}
.of-stat-cards b{display:block;font-size:20px}
.of-stat-cards span{font-size:12px;color:var(--muted)}
.of-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:13px}
.of-form-grid input,.of-form-grid select{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-thumb{width:120px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--line)}
</style>

<div class="of-stat-cards">
    <div><b><?= office_num($stats['total']) ?></b><span>تبلیغ</span></div>
    <div><b><?= office_num($stats['views']) ?></b><span>بازدید</span></div>
    <div><b><?= office_num($stats['clicks']) ?></b><span>کلیک</span></div>
</div>
<p><a class="of-btn ghost" href="broadcasts.php">📢 اعلان همگانی به کاربران</a></p>
<div class="of-panel">
<h3 style="margin-top:0">🛠 مدیریت سایت</h3>
<p>
<a class="of-btn ghost" href="backup.php">📦 پشتیبان‌گیری و بازیابی</a>
<a class="of-btn ghost" href="assistant.php">🧠 دستیار</a>
<a class="of-btn ghost" href="diagnostics.php">🩺 عیب‌یابی</a>
<a class="of-btn ghost" href="bots.php">🤖 ربات‌ها و پیامک</a>
<a class="of-btn ghost" href="sms.php">✉️ برنامه پیامک</a>
<a class="of-btn ghost" href="comm.php">📣 ارتباطات و کمپین</a>
<a class="of-btn ghost" href="leads.php">🧲 لیدها</a>
<a class="of-btn ghost" href="two-factor.php">🔐 دوعاملی</a>
</p>
</div>

<div class="of-panel">
    <h3 style="margin-top:0"><?= $editId > 0 ? 'ویرایش تبلیغ' : 'تبلیغ جدید' ?></h3>
    <form method="post" action="promotions.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>" enctype="multipart/form-data">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editId > 0): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
        <div class="of-form-grid">
            <label>عنوان
                <input type="text" name="title" value="<?= office_h($v('title')) ?>"></label>
            <label>آپلود تصویر (حداکثر ۵ مگ)
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
            <label>یا آدرس تصویر <b style="color:var(--danger)">*</b>
                <input type="text" name="image_url" dir="ltr" style="text-align:left" value="<?= office_h($v('image_url')) ?>" placeholder="uploads/promotions/..."></label>
            <label>لینک (اختیاری)
                <input type="text" name="link_url" dir="ltr" style="text-align:left" value="<?= office_h($v('link_url')) ?>" placeholder="https://..."></label>
            <label>جایگاه
                <select name="placement">
                    <?php foreach ($placements as $k => $lb): ?><option value="<?= $k ?>"<?= $v('placement') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select></label>
            <label>بعد از کارت چندم
                <input type="number" name="position_after" min="1" value="<?= office_h($v('position_after') !== '' ? $v('position_after') : '3') ?>"></label>
            <label>تکرار هر چند کارت (۰ = بدون تکرار)
                <input type="number" name="repeat_every" min="0" value="<?= office_h($v('repeat_every') !== '' ? $v('repeat_every') : '0') ?>"></label>
            <label>شروع نمایش
                <input type="datetime-local" name="start_date" value="<?= office_h($dtLocal($v('start_date'))) ?>"></label>
            <label>پایان نمایش
                <input type="datetime-local" name="end_date" value="<?= office_h($dtLocal($v('end_date'))) ?>"></label>
            <label style="flex-direction:row;align-items:center;gap:6px">
                <input type="checkbox" name="is_active" value="1"<?= ((int)($values['is_active'] ?? 1)) === 1 ? ' checked' : '' ?>> فعال</label>
        </div>
        <?php if ($v('image_url') !== ''): ?><p><img class="of-thumb" style="width:220px;height:80px" src="../<?= office_h($v('image_url')) ?>" alt=""></p><?php endif; ?>
        <p>
            <button class="of-btn" type="submit">ذخیره تبلیغ</button>
            <?php if ($editId > 0): ?><a class="of-btn ghost" href="promotions.php">＋ تبلیغ جدید</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="of-panel">
    <p class="of-muted"><?= office_num(count($rows)) ?> تبلیغ.</p>
    <div class="of-table-wrap"><table class="of-table">
        <tr><th>#</th><th>تصویر</th><th>عنوان</th><th>جایگاه</th><th>بعد از</th><th>وضعیت</th><th>بازدید/کلیک</th><th>اقدام</th></tr>
        <?php foreach ($rows as $r): ?>
            <?php $active = ((int)($r['is_active'] ?? 1)) === 1; ?>
            <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?php if ((string)($r['image_url'] ?? '') !== ''): ?><img class="of-thumb" src="../<?= office_h((string)$r['image_url']) ?>" alt=""><?php else: ?>—<?php endif; ?></td>
                <td><?= office_h((string)($r['title'] ?? '')) ?></td>
                <td><?= office_h($placements[(string)($r['placement'] ?? '')] ?? (string)($r['placement'] ?? '')) ?></td>
                <td><?= office_fa((string)(int)($r['position_after'] ?? 0)) ?></td>
                <td><span class="of-badge<?= $active ? ' green' : '' ?>"><?= $active ? 'فعال' : 'غیرفعال' ?></span></td>
                <td dir="ltr"><?= office_num((int)($r['views'] ?? 0)) ?> / <?= office_num((int)($r['clicks'] ?? 0)) ?></td>
                <td style="white-space:nowrap">
                    <a class="of-btn ghost" style="padding:4px 10px" href="promotions.php?edit=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a>
                    <form method="post" action="promotions.php" style="display:inline" onsubmit="return confirm('وضعیت این تبلیغ تغییر کند؟');">
                        <?= office_csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="<?= $active ? 'غیرفعال' : 'فعال' ?>"><?= $active ? '⏸' : '▶' ?></button>
                    </form>
                    <form method="post" action="promotions.php" style="display:inline" onsubmit="return confirm('این تبلیغ حذف شود؟');">
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
