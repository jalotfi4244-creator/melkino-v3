<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ویرایش‌های در انتظار تأیید مالک (مرحله ۲۱)
 *--------------------------------------------------------------------------
 * زیرصفحه «فایل‌ها»: فهرست pending + تأیید/رد با عین منطق سایت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_revisions.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_rev_boot($pdo);
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        $rid = (int)($_POST['id'] ?? 0);
        if ($action === 'approve') {
            [$ok, $msg] = office_rev_approve($pdo, $rid);
        } elseif ($action === 'reject') {
            [$ok, $msg] = office_rev_reject($pdo, $rid);
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

$rows = office_rev_pending($pdo);

office_shell_open('files.php', 'ویرایش‌های در انتظار تأیید');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}

$fields = ['title' => 'عنوان', 'area' => 'متراژ', 'price_sell' => 'قیمت فروش', 'deposit' => 'ودیعه', 'rent_monthly' => 'اجاره ماهانه', 'description' => 'توضیحات'];
?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="files.php">→ فایل‌ها</a></p>
    <p class="of-muted"><?= office_num(count($rows)) ?> ویرایش در انتظار بررسی.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">ویرایش در انتظاری نیست. 🎉</p>
    <?php else: ?>
        <?php foreach ($rows as $r): ?>
            <?php
            $snap = is_array($r['snapshot'] ?? null) ? $r['snapshot'] : [];
            $before = is_array($snap['before'] ?? null) ? $snap['before'] : [];
            $after = is_array($snap['after'] ?? null) ? $snap['after'] : [];
            ?>
            <div class="of-panel" style="margin-bottom:12px">
                <p><b><?= office_h((string)($r['title'] ?? '')) ?></b>
                    <span class="of-muted" dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></span>
                    <span class="of-badge"><?= office_h((string)($r['status'] ?? '')) ?></span>
                    <span class="of-muted" dir="ltr"><?= office_h(substr((string)($r['created_at'] ?? ''), 0, 16)) ?></span></p>
                <?php if (trim((string)($r['change_note'] ?? '')) !== ''): ?>
                    <p class="of-muted">یادداشت مالک: <?= office_h((string)$r['change_note']) ?></p>
                <?php endif; ?>
                <div class="of-table-wrap"><table class="of-table">
                    <tr><th>فیلد</th><th>قبلی</th><th>جدید</th></tr>
                    <?php foreach ($fields as $k => $lb): ?>
                        <?php $b = trim((string)($before[$k] ?? '')); $a = trim((string)($after[$k] ?? '')); ?>
                        <?php if ($b === $a) continue; ?>
                        <tr>
                            <td><?= office_h($lb) ?></td>
                            <td><?= $b !== '' ? office_h($b) : '—' ?></td>
                            <td><b><?= $a !== '' ? office_h($a) : '—' ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                </table></div>
                <p>
                    <a class="of-btn ghost" href="file-edit.php?id=<?= urlencode((string)($r['ad_id'] ?? '')) ?>">مشاهده فایل</a>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <form method="post" action="revisions.php" onsubmit="return confirm('این ویرایش تأیید و آگهی منتشر شود؟');">
                        <?= office_csrf_field() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="of-btn" type="submit">✅ تأیید و انتشار</button>
                    </form>
                    <form method="post" action="revisions.php" onsubmit="return confirm('این ویرایش رد شود؟');">
                        <?= office_csrf_field() ?>
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="of-btn danger" type="submit">❌ رد</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
