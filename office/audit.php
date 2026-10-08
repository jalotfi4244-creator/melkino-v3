<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — گزارش عملکرد ادمین‌ها (مرحله ۲۰)
 *--------------------------------------------------------------------------
 * فقط خواندنی: فیلتر action + صفحه‌بندی روی melkino_audit_log.
 * زیرصفحه «کاربران و دسترسی‌ها».
 */

declare(strict_types=1);

require_once __DIR__ . '/_audit.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();

$action = substr(trim((string)($_GET['action'] ?? '')), 0, 80);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$actions = office_audit_actions($pdo);
[$rows, $total] = office_audit_list($pdo, $action, $page, $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
    [$rows] = office_audit_list($pdo, $action, $page, $perPage);
}
$qsBase = $action !== '' ? 'action=' . urlencode($action) . '&' : '';

office_shell_open('staff.php', 'گزارش عملکرد ادمین‌ها');
?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="staff.php">→ کاربران و دسترسی‌ها</a></p>
    <form method="get" action="audit.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <select name="action">
            <option value="">همه عملیات‌ها</option>
            <?php foreach ($actions as $a): ?><option value="<?= office_h($a) ?>"<?= $a === $action ? ' selected' : '' ?>><?= office_h($a) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">فیلتر</button>
    </form>
    <p class="of-muted"><?= office_num($total) ?> رکورد.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">رکوردی یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>#</th><th>کنشگر</th><th>عملیات</th><th>موجودیت</th><th>جزئیات</th><th>IP</th><th>تاریخ</th></tr>
            <?php foreach ($rows as $r): ?>
                <?php $det = trim((string)($r['details'] ?? '')); ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= office_h((string)($r['actor_name'] ?? '')) ?><br><span class="of-muted" dir="ltr"><?= office_h((string)($r['actor_type'] ?? '')) ?>#<?= office_h((string)($r['actor_id'] ?? '')) ?></span></td>
                    <td dir="ltr"><?= office_h((string)($r['action'] ?? '')) ?></td>
                    <td dir="ltr"><?= office_h(trim((string)($r['entity'] ?? '') . ' ' . (string)($r['entity_id'] ?? ''))) ?></td>
                    <td class="of-muted"><?= office_h(function_exists('mb_substr') && function_exists('mb_strlen') && mb_strlen($det) > 120 ? mb_substr($det, 0, 120) . '…' : substr($det, 0, 160)) ?></td>
                    <td dir="ltr"><?= office_h((string)($r['ip_address'] ?? '')) ?></td>
                    <td dir="ltr"><?= office_h(substr((string)($r['created_at'] ?? ''), 0, 16)) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:12px;font-size:13px;color:var(--muted)">
            <?php if ($page > 1): ?><a class="of-btn ghost" href="audit.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="audit.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
