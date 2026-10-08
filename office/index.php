<?php
/**
 *--------------------------------------------------------------------------
 * داشبورد دفتر ملکینو شهر (مرحله ۱) — فقط خواندن از جدول‌های ملکینو
 *--------------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_revisions.php';
require_once __DIR__ . '/_support.php';
require_once __DIR__ . '/_broadcasts.php';
office_shell_open('index.php', 'داشبورد');

// --- آمار (همه try/catch داخل هلپرها؛ اگر جدولی نبود صفر نشان می‌دهد) ---
$byStatus = office_count_groups('ads', 'status');
$totalAds = array_sum($byStatus);
$byTx = office_count_groups('ads', 'transaction_type');
$byType = office_count_groups('ads', 'property_type');
arsort($byType);
$byType = array_slice($byType, 0, 6, true);

$nRequests = office_count_all('property_requests');
$nVisits = office_count_all('visit_requests');
$nTickets = office_count_all('support_tickets');
$nUsers = office_count_all('users');
$nFollows = 0;
try {
    $nFollows = (int)(office_db()->query('SELECT COUNT(*) FROM office_followups WHERE done = 0')->fetchColumn() ?: 0);
} catch (Throwable $e) {
}

// --- هشدارهای امروز (مرحله ۲۴) ---
$alertRev = 0;
$alertTickets = 0;
$alertTicketUnread = 0;
$alertNotifUnread = 0;
try {
    $alertRev = count(office_rev_pending(office_db()));
    foreach (office_sup_list(office_db(), 'open') as $t) {
        $alertTickets++;
        $alertTicketUnread += (int)($t['unread_count'] ?? 0);
    }
    office_bc_ensure(office_db());
    $alertNotifUnread = (int)(office_bc_stats(office_db())['unread'] ?? 0);
} catch (Throwable $e) {
}
$alertAllOk = ($alertRev + $alertTickets + $alertTicketUnread + $alertNotifUnread) === 0;

$statusFa = ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی'];
?>

<div class="of-cards">
    <div class="of-card"><b><?= office_num($totalAds) ?></b><span>کل فایل‌ها</span></div>
    <?php foreach ($statusFa as $en => $fa): ?>
        <?php if (!empty($byStatus[$en])): ?>
            <div class="of-card"><b><?= office_num($byStatus[$en]) ?></b><span><?= office_h($fa) ?></span></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php foreach ($byTx as $tx => $n): ?>
        <div class="of-card"><b><?= office_num($n) ?></b><span><?= office_h($tx !== '' ? $tx : 'نامشخص') ?></span></div>
    <?php endforeach; ?>
    <div class="of-card"><b><?= office_num($nRequests) ?></b><span>درخواست‌ها</span></div>
    <div class="of-card"><b><?= office_num($nVisits) ?></b><span>بازدیدها</span></div>
    <div class="of-card"><b><?= office_num($nTickets) ?></b><span>تیکت‌های پشتیبانی</span></div>
    <div class="of-card"><b><?= office_num($nUsers) ?></b><span>کاربران سایت</span></div>
    <div class="of-card"><b><?= office_num($nFollows) ?></b><span>پیگیری باز</span></div>
</div>

<div class="of-panel">
    <h2>🔔 هشدارهای امروز</h2>
    <?php if ($alertAllOk): ?>
        <p class="of-muted">همه‌چیز به‌روزه. 🎉</p>
    <?php else: ?>
        <div class="of-cards">
            <a class="of-card" href="revisions.php" style="text-decoration:none"><b><?= office_num($alertRev) ?></b><span>✏️ ویرایش در انتظار</span></a>
            <a class="of-card" href="support.php" style="text-decoration:none"><b><?= office_num($alertTickets) ?></b><span>🎧 تیکت باز (<?= office_num($alertTicketUnread) ?> پیام نخوانده)</span></a>
            <a class="of-card" href="broadcasts.php" style="text-decoration:none"><b><?= office_num($alertNotifUnread) ?></b><span>📢 اعلان نخوانده کاربران</span></a>
        </div>
    <?php endif; ?>
</div>

<div class="of-grid2">
    <div class="of-panel">
        <h2>فایل‌ها بر اساس نوع ملک</h2>
        <?php if (!$byType): ?>
            <p class="of-muted">فایلی ثبت نشده است.</p>
        <?php else: ?>
            <div class="of-table-wrap"><table class="of-table">
                <?php foreach ($byType as $t => $n): ?>
                    <tr><td><?= office_h($t !== '' ? $t : 'نامشخص') ?></td><td style="text-align:left"><b><?= office_num($n) ?></b></td></tr>
                <?php endforeach; ?>
            </table></div>
        <?php endif; ?>
    </div>
    <div class="of-panel">
        <h2>دسترسی سریع</h2>
        <div class="of-actions">
            <a class="of-btn ghost" href="register.php?type=apartment">＋ ثبت فایل</a>
            <a class="of-btn ghost" href="files.php">🗂 فایل‌ها</a>
        </div>
        <p class="of-muted" style="margin-top:12px">در دوره گذار، مدیریت کامل از پنل قدیمی:</p>
        <div class="of-actions">
            <a class="of-btn ghost" href="../admin-ads.php">مدیریت آگهی‌ها</a>
            <a class="of-btn ghost" href="../admin-create-ad.php">ثبت آگهی</a>
            <a class="of-btn ghost" href="../admin-panel.php">پنل قدیمی</a>
        </div>
    </div>
</div>

<div class="of-panel">
    <h2>آخرین فایل‌ها</h2>
    <?php
    $recent = [];
    try {
        $st = office_db()->query('SELECT id, title, transaction_type, property_type, location, status, created_at, price_sell, total_price, deposit, rent_monthly, full_rent, full_rent_enabled FROM ads ORDER BY created_at DESC LIMIT 10');
        $recent = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (Throwable $e) {
    }
    ?>
    <?php if (!$recent): ?>
        <p class="of-muted">فایلی ثبت نشده است.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>کد</th><th>عنوان</th><th>معامله</th><th>نوع</th><th>موقعیت</th><th>قیمت</th><th>وضعیت</th></tr>
            <?php foreach ($recent as $r): ?>
                <?php
                $st = (string)($r['status'] ?? '');
                $cls = $st === 'published' ? 'green' : ($st === 'expired' ? 'red' : '');
                ?>
                <tr>
                    <td dir="ltr"><?= office_h((string)($r['id'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['title'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['transaction_type'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['property_type'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['location'] ?? '')) ?></td>
                    <td><?= office_ad_price($r) ?></td>
                    <td><span class="of-badge <?= $cls ?>"><?= office_h($statusFa[$st] ?? $st) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
