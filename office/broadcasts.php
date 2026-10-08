<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — اعلان همگانی به کاربران سایت (مرحله ۱۸)
 *--------------------------------------------------------------------------
 * آمار، ارسال همگانی، فهرست ارسال‌شده‌ها، آخرین اعلان‌های کاربران.
 * زیرصفحه «مدیریت سایت» (از promotions.php لینک می‌شود).
 */

declare(strict_types=1);

require_once __DIR__ . '/_broadcasts.php';

$pdo = office_db();
office_bc_boot($pdo);
$flash = '';
$flashErr = '';

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'send') {
            [$bid, $msg] = office_bc_send(
                $pdo,
                (string)($_POST['title'] ?? ''),
                (string)($_POST['message'] ?? ''),
                (string)($_POST['url'] ?? ''),
                office_is_logged_in() ? (int)office_user()['id'] : null
            );
            if ($bid !== null) {
                office_redirect('broadcasts.php?sent=' . $bid);
                $flash = $msg;
            } else {
                $flashErr = $msg;
            }
        } elseif ($action === 'delete') {
            if (office_bc_delete($pdo, (int)($_POST['id'] ?? 0))) {
                office_redirect('broadcasts.php?deleted=1');
                $flash = 'اعلان عمومی و همه نسخه‌هایش حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } elseif ($action === 'notif_delete') {
            if (office_bc_notif_delete($pdo, (int)($_POST['id'] ?? 0))) {
                $flash = 'اعلان حذف شد.';
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } elseif ($action === 'events_save') {
            $map = is_array($_POST['events'] ?? null) ? $_POST['events'] : [];
            if (office_bc_events_save($map)) {
                $flash = 'تنظیمات رویدادهای اعلان ذخیره شد.';
            } else {
                $flashErr = 'ذخیره ناموفق بود.';
            }
        } elseif ($action === 'notif_delete_bulk') {
            $ids = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
            if ($ids === []) {
                $flashErr = 'موردی انتخاب نشده است.';
            } else {
                $n = office_bc_notif_delete_bulk($pdo, $ids);
                $flash = office_num($n) . ' اعلان حذف شد.';
            }
        } elseif ($action === 'notif_delete_filtered') {
            [$ok, $msg] = office_bc_notif_delete_filtered($pdo, (string)($_POST['type'] ?? ''));
            if ($ok) {
                $flash = $msg;
            } else {
                $flashErr = $msg;
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

office_shell_open('promotions.php', 'اعلان همگانی');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
if (!$isPost) {
    if ((string)($_GET['sent'] ?? '') !== '' && (int)($_GET['sent'] ?? 0) > 0) {
        echo '<div class="of-alert ok">اعلان عمومی ارسال شد.</div>';
    }
    if ((string)($_GET['deleted'] ?? '') === '1') {
        echo '<div class="of-alert ok">اعلان عمومی و همه نسخه‌هایش حذف شد.</div>';
    }
}

$stats = office_bc_stats($pdo);
$rows = office_bc_list($pdo);
$recent = office_bc_recent($pdo);
$ev = office_bc_events();
$evMaster = office_bc_events_master($pdo);
$evTypes = office_bc_notif_types();
?>
<style>
.of-stat-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:12px}
.of-stat-cards div{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:10px 12px}
.of-stat-cards b{display:block;font-size:20px}
.of-stat-cards span{font-size:12px;color:var(--muted)}
.of-bc-form input[type=text],.of-bc-form textarea{width:100%;max-width:640px;box-sizing:border-box;background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-bc-form label{display:block;font-size:13px;margin:8px 0 4px}
</style>

<div class="of-stat-cards">
    <div><b><?= office_num($stats['users']) ?></b><span>کاربر سایت</span></div>
    <div><b><?= office_num($stats['total']) ?></b><span>اعلان</span></div>
    <div><b><?= office_num($stats['unread']) ?></b><span>خوانده‌نشده</span></div>
    <div><b><?= office_num($stats['broadcasts']) ?></b><span>همگانی ارسال‌شده</span></div>
</div>

<div class="of-panel">
    <p><a class="of-btn ghost" href="promotions.php">→ بنرهای تبلیغاتی</a></p>
    <h3 style="margin-top:0">📢 ارسال اعلان عمومی</h3>
    <p class="of-muted">این اعلان برای <b>همه کاربران</b> ارسال می‌شود و در صفحه «اعلان‌ها»ی هر کس نمایش داده می‌شود.</p>
    <form method="post" action="broadcasts.php" class="of-bc-form" onsubmit="return confirm('این اعلان برای همه کاربران ارسال شود؟');">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="send">
        <label>عنوان <b style="color:var(--danger)">*</b></label>
        <input type="text" name="title" maxlength="200" placeholder="مثلاً: 🎉 جشنواره فروش ویژه ملکینو">
        <label>متن اعلان <b style="color:var(--danger)">*</b></label>
        <textarea name="message" rows="3" placeholder="متن کامل اعلان..."></textarea>
        <label>لینک (اختیاری)</label>
        <input type="text" name="url" dir="ltr" style="text-align:left" placeholder="properties.php یا https://...">
        <p><button class="of-btn" type="submit">ارسال برای همه</button></p>
    </form>
</div>

<div class="of-panel">
    <h3 style="margin-top:0">⚙ رویدادهای اعلان سیستمی</h3>
    <p class="of-muted">سوییچ اصلی اعلان‌ها: <b><?= $evMaster ? 'روشن ✅' : 'خاموش ❌ (هیچ اعلان خودکاری ارسال نمی‌شود)' ?></b></p>
    <?php if (!$ev['defs']): ?>
        <p class="of-muted">فهرست رویدادها در دسترس نیست.</p>
    <?php else: ?>
        <form method="post" action="broadcasts.php">
            <?= office_csrf_field() ?>
            <input type="hidden" name="action" value="events_save">
            <div class="of-table-wrap"><table class="of-table">
                <tr><th>رویداد</th><th>توضیح</th><th>وضعیت</th></tr>
                <?php foreach ($ev['defs'] as $id => $def): ?>
                    <?php $on = !empty($ev['state'][$id]); $tog = !empty($def['toggleable']); ?>
                    <tr>
                        <td><?= office_h((string)($def['emoji'] ?? '')) ?> <b><?= office_h((string)($def['title'] ?? $id)) ?></b><br><code dir="ltr" class="of-muted"><?= office_h($id) ?></code></td>
                        <td class="of-muted"><?= office_h((string)($def['desc'] ?? '')) ?></td>
                        <td>
                            <?php if ($tog): ?>
                                <input type="hidden" name="events[<?= office_h($id) ?>]" value="0">
                                <label style="font-size:13px"><input type="checkbox" name="events[<?= office_h($id) ?>]" value="1"<?= $on ? ' checked' : '' ?>> فعال</label>
                            <?php else: ?>
                                <span class="of-muted">همیشه فعال</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table></div>
            <p><button class="of-btn" type="submit">ذخیرهٔ تنظیمات اعلان‌ها</button></p>
        </form>
    <?php endif; ?>
</div>

<div class="of-panel">
    <h3 style="margin-top:0">اعلان‌های عمومی ارسال‌شده</h3>
    <?php if (!$rows): ?>
        <p class="of-muted">هنوز اعلان عمومی ارسال نشده است.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>#</th><th>عنوان</th><th>ارسال به</th><th>خوانده‌نشده</th><th>تاریخ</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><b><?= office_h((string)($r['title'] ?? '')) ?></b><br><span class="of-muted"><?= office_h(mb_strlen((string)($r['message'] ?? '')) > 90 ? mb_substr((string)$r['message'], 0, 90) . '…' : (string)($r['message'] ?? '')) ?></span></td>
                    <td><?= office_num((int)($r['sent_count'] ?? 0)) ?></td>
                    <td><?= office_num((int)($r['unread_count'] ?? 0)) ?></td>
                    <td dir="ltr"><?= office_h(substr((string)($r['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <form method="post" action="broadcasts.php" style="display:inline" onsubmit="return confirm('این اعلان عمومی و همه نسخه‌هایش حذف شود؟');">
                            <?= office_csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="حذف">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>

<div class="of-panel">
    <h3 style="margin-top:0">آخرین اعلان‌های کاربران</h3>
    <?php if (!$recent): ?>
        <p class="of-muted">اعلانی ثبت نشده است.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th><input type="checkbox" onclick="document.querySelectorAll('.of-bc-pick').forEach(c=>c.checked=this.checked)" title="انتخاب همه"></th><th>#</th><th>کاربر</th><th>نوع</th><th>عنوان</th><th>وضعیت</th><th>تاریخ</th><th>اقدام</th></tr>
            <?php foreach ($recent as $n): ?>
                <tr>
                    <td><input type="checkbox" class="of-bc-pick" form="ofBulkForm" name="ids[]" value="<?= (int)$n['id'] ?>"></td>
                    <td><?= (int)$n['id'] ?></td>
                    <td><?= office_h((string)($n['user_name'] ?? '')) ?><br><span dir="ltr" class="of-muted"><?= office_h((string)($n['user_phone'] ?? '')) ?></span></td>
                    <td dir="ltr"><?= office_h((string)($n['type'] ?? '')) ?></td>
                    <td><?= office_h((string)($n['title'] ?? '')) ?></td>
                    <td><span class="of-badge<?= ((int)($n['is_read'] ?? 0)) === 1 ? ' green' : '' ?>"><?= ((int)($n['is_read'] ?? 0)) === 1 ? 'خوانده‌شده' : 'نخوانده' ?></span></td>
                    <td dir="ltr"><?= office_h(substr((string)($n['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <form method="post" action="broadcasts.php" style="display:inline" onsubmit="return confirm('این اعلان حذف شود؟');">
                            <?= office_csrf_field() ?>
                            <input type="hidden" name="action" value="notif_delete">
                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                            <button class="of-btn ghost" style="padding:4px 10px" type="submit" title="حذف">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <form id="ofBulkForm" method="post" action="broadcasts.php" onsubmit="return confirm('موارد انتخاب‌شده حذف شود؟');">
            <?= office_csrf_field() ?>
            <input type="hidden" name="action" value="notif_delete_bulk">
            <p><button class="of-btn danger" type="submit">حذف انتخاب‌شده‌ها</button></p>
        </form>
    <?php endif; ?>
    <h3>حذف بر اساس نوع</h3>
    <form method="post" action="broadcasts.php" onsubmit="return confirm('اعلان‌های این نوع حذف شود؟ این عمل برگشت ندارد.');">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="notif_delete_filtered">
        <select name="type">
            <option value="all">همهٔ اعلان‌ها</option>
            <?php foreach ($evTypes as $t): ?><option value="<?= office_h($t) ?>" dir="ltr"><?= office_h($t) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn danger" type="submit">حذف</button>
    </form>
</div>

<?php office_shell_close(); ?>
