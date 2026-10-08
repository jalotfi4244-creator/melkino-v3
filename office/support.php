<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — پشتیبانی: تیکت‌های کاربران (مرحله ۲۳)
 *--------------------------------------------------------------------------
 * زیرصفحه «مشتریان»: فهرست، گفتگو، پاسخ، بستن/بازگشایی.
 */

declare(strict_types=1);

require_once __DIR__ . '/_support.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_rev_boot($pdo);
$flash = '';
$flashErr = '';
$statuses = office_sup_statuses();

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        $tid = (int)($_POST['id'] ?? 0);
        if ($action === 'reply') {
            $me = office_user();
            $adminName = trim((string)(($me['display_name'] ?? '') !== '' ? $me['display_name'] : ($me['username'] ?? '')));
            if ($adminName === '') {
                $adminName = 'پشتیبانی ملکینو';
            }
            [$ok, $msg] = office_sup_reply($pdo, $tid, (string)($_POST['message'] ?? ''), (int)$me['id'], $adminName);
        } elseif ($action === 'close') {
            [$ok, $msg] = office_sup_set_status($pdo, $tid, 'closed');
        } elseif ($action === 'reopen') {
            [$ok, $msg] = office_sup_set_status($pdo, $tid, 'open');
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

$viewId = (int)($_GET['id'] ?? 0);
$detail = $viewId > 0 ? office_sup_get($pdo, $viewId) : null;
if ($detail) {
    office_sup_mark_read($pdo, $viewId);
    $messages = office_sup_messages($pdo, $viewId);
} else {
    if ($viewId > 0) {
        $flashErr = 'تیکت پیدا نشد.';
    }
    $fStatus = (string)($_GET['status'] ?? '');
    if ($fStatus !== '' && $fStatus !== 'all' && !array_key_exists($fStatus, $statuses)) {
        $fStatus = '';
    }
    $tickets = office_sup_list($pdo, $fStatus);
}

office_shell_open('customers.php', $detail ? 'گفتگوی تیکت' : 'پشتیبانی');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
?>
<style>
.of-msg{max-width:80%;padding:10px 14px;border-radius:12px;margin:8px 0;line-height:2}
.of-msg.admin{background:var(--accent);color:#fff;margin-right:auto}
.of-msg.user{background:var(--panel);border:1px solid var(--line)}
.of-msg small{display:block;opacity:.75;font-size:11px}
</style>

<?php if ($detail): ?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="support.php">→ همه تیکت‌ها</a>
    <a class="of-btn ghost" href="customers.php">مشتریان</a></p>
    <h3 style="margin-top:0"><?= office_h((string)($detail['subject'] ?? 'بدون موضوع')) ?></h3>
    <p class="of-muted"><?= office_h((string)($detail['user_name'] ?? '')) ?> — <span dir="ltr"><?= office_h((string)($detail['phone'] ?? '')) ?></span>
        <span class="of-badge"><?= office_h($statuses[(string)($detail['status'] ?? '')] ?? (string)($detail['status'] ?? '')) ?></span></p>
    <?php foreach ($messages as $m): ?>
        <div class="of-msg <?= ($m['sender_type'] ?? '') === 'admin' ? 'admin' : 'user' ?>">
            <?= nl2br(office_h((string)($m['message'] ?? ''))) ?>
            <small><?= office_h((string)($m['sender_name'] ?? '')) ?> — <span dir="ltr"><?= office_h(substr((string)($m['created_at'] ?? ''), 0, 16)) ?></span></small>
        </div>
    <?php endforeach; ?>
    <h3>پاسخ</h3>
    <form method="post" action="support.php?id=<?= $viewId ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="id" value="<?= $viewId ?>">
        <textarea name="message" rows="4" style="width:100%;max-width:640px" maxlength="10000" placeholder="متن پاسخ..."></textarea><br>
        <button class="of-btn" type="submit">ارسال پاسخ</button>
    </form>
    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <?php if ((string)($detail['status'] ?? '') !== 'closed'): ?>
            <form method="post" action="support.php?id=<?= $viewId ?>" onsubmit="return confirm('این تیکت بسته شود؟');">
                <?= office_csrf_field() ?>
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="id" value="<?= $viewId ?>">
                <button class="of-btn ghost" type="submit">🔒 بستن تیکت</button>
            </form>
        <?php else: ?>
            <form method="post" action="support.php?id=<?= $viewId ?>">
                <?= office_csrf_field() ?>
                <input type="hidden" name="action" value="reopen">
                <input type="hidden" name="id" value="<?= $viewId ?>">
                <button class="of-btn" type="submit">🔓 بازگشایی</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="customers.php">→ مشتریان</a></p>
    <form method="get" action="support.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <select name="status">
            <option value="all">همه وضعیت‌ها</option>
            <?php foreach ($statuses as $k => $lb): ?><option value="<?= $k ?>"<?= $k === $fStatus ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">فیلتر</button>
    </form>
    <p class="of-muted"><?= office_num(count($tickets)) ?> تیکت.</p>
    <?php if (!$tickets): ?>
        <p class="of-muted">تیکتی یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>#</th><th>کاربر</th><th>موضوع</th><th>وضعیت</th><th>نخوانده</th><th>آخرین پیام</th><th>اقدام</th></tr>
            <?php foreach ($tickets as $t): ?>
                <?php $unread = (int)($t['unread_count'] ?? 0); $last = trim((string)($t['last_message'] ?? '')); ?>
                <tr<?= $unread > 0 ? ' style="font-weight:700"' : '' ?>>
                    <td><?= (int)$t['id'] ?></td>
                    <td><?= office_h((string)($t['user_name'] ?? '')) ?><br><span dir="ltr" class="of-muted"><?= office_h((string)($t['phone'] ?? '')) ?></span></td>
                    <td><?= office_h((string)($t['subject'] ?? '')) ?></td>
                    <td><span class="of-badge"><?= office_h($statuses[(string)($t['status'] ?? '')] ?? (string)($t['status'] ?? '')) ?></span></td>
                    <td><?= $unread > 0 ? office_fa((string)$unread) : '—' ?></td>
                    <td class="of-muted"><?= office_h(function_exists('mb_substr') && function_exists('mb_strlen') && mb_strlen($last) > 60 ? mb_substr($last, 0, 60) . '…' : substr($last, 0, 90)) ?></td>
                    <td><a class="of-btn ghost" style="padding:4px 10px" href="support.php?id=<?= (int)$t['id'] ?>" title="مشاهده">👁</a></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php office_shell_close(); ?>
