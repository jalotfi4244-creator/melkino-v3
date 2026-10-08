<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تاریخچه انتشار و عملیات یک فایل (مرحله ۲۲)
 *--------------------------------------------------------------------------
 * فقط خواندنی. زیرصفحه «فایل‌ها» (از file-edit.php لینک می‌شود).
 */

declare(strict_types=1);

require_once __DIR__ . '/_history.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_hist_ensure($pdo);

$adId = trim((string)($_GET['id'] ?? ''));
$ad = office_hist_ad($pdo, $adId);
$logs = $ad ? office_hist_logs($pdo, $adId) : [];
$history = $ad ? office_hist_history($pdo, $adId) : [];

office_shell_open('files.php', 'تاریخچه فایل');

if (!$ad) {
    echo '<div class="of-alert err">فایل یافت نشد.</div>';
    echo '<p><a class="of-btn ghost" href="files.php">→ فایل‌ها</a></p>';
    office_shell_close();
    return;
}
?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="files.php">→ فایل‌ها</a>
    <a class="of-btn ghost" href="file-edit.php?id=<?= urlencode($adId) ?>">✏️ ویرایش فایل</a></p>
    <h3 style="margin-top:0">تاریخچه: <?= office_h((string)($ad['title'] ?? '')) ?></h3>
    <p class="of-muted"><span dir="ltr"><?= office_h($adId) ?></span> — <?= office_h((string)($ad['status'] ?? '')) ?> — <span dir="ltr"><?= office_h((string)($ad['phone'] ?? '')) ?></span></p>
</div>

<div class="of-panel">
    <h3 style="margin-top:0">📡 تلاش‌های انتشار در کانال‌ها (<?= office_num(count($logs)) ?>)</h3>
    <?php if (!$logs): ?>
        <p class="of-muted">تلاش انتشاری ثبت نشده است.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>#</th><th>پلتفرم</th><th>نتیجه</th><th>شناسه پیام</th><th>یادداشت</th><th>تاریخ</th></tr>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= (int)$l['id'] ?></td>
                    <td dir="ltr"><?= office_h((string)($l['platform'] ?? '')) ?></td>
                    <td><span class="of-badge<?= ((int)($l['success'] ?? 0)) === 1 ? ' green' : '' ?>"><?= ((int)($l['success'] ?? 0)) === 1 ? 'موفق ✅' : 'ناموفق ❌' ?></span></td>
                    <td dir="ltr"><?= office_h((string)($l['message_id'] ?? '')) ?></td>
                    <td><?= office_h((string)($l['note'] ?? '')) ?></td>
                    <td dir="ltr"><?= office_h(substr((string)($l['created_at'] ?? ''), 0, 16)) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>

<div class="of-panel">
    <h3 style="margin-top:0">🕘 تاریخچه عملیات مدیریتی (<?= office_num(count($history)) ?>)</h3>
    <?php if (!$history): ?>
        <p class="of-muted">عملیاتی ثبت نشده است.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>#</th><th>عملیات</th><th>جزئیات</th><th>کنشگر</th><th>تاریخ</th></tr>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= (int)$h['id'] ?></td>
                    <td><?= office_h((string)($h['action'] ?? '')) ?></td>
                    <td><?= office_h((string)($h['detail'] ?? '')) ?></td>
                    <td><?= office_h((string)($h['actor'] ?? '')) ?></td>
                    <td dir="ltr"><?= office_h(substr((string)($h['created_at'] ?? ''), 0, 16)) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
