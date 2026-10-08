<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — گزارش‌ها (مرحله ۱۳)
 *--------------------------------------------------------------------------
 * فقط خواندن: کارت‌های شمارش + جدول‌های تفکیکی + هشدارهای سررسیدگذشته
 * و بازدیدهای ۷ روز آینده.
 */

declare(strict_types=1);

require_once __DIR__ . '/_reports.php';

$pdo = office_db();
$rep = office_rep_overview($pdo);

$statusFa = ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی', 'archived' => 'بایگانی'];
$partStatuses = office_part_statuses();
$custKinds = office_cust_kinds();
$reqStatuses = office_req_statuses();
$visitStatuses = office_visit_statuses();
$callDirs = office_call_dirs();

office_shell_open('reports.php', 'گزارش‌ها');
?>
<style>
.of-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-bottom:14px}
.of-card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:12px;text-align:center}
.of-card b{display:block;font-size:24px;margin-bottom:4px}
.of-card span{font-size:12.5px;color:var(--muted)}
.of-rep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.of-mini-table{width:100%;border-collapse:collapse;font-size:13px}
.of-mini-table td{padding:6px 8px;border-top:1px solid var(--line)}
.of-mini-table td:last-child{text-align:left;font-weight:800}
</style>

<div class="of-cards">
    <div class="of-card"><b><?= office_num($rep['files_total']) ?></b><span>فایل</span></div>
    <div class="of-card"><b><?= office_num(array_sum($rep['parts_by_status'])) ?></b><span>مشارکت</span></div>
    <div class="of-card"><b><?= office_num($rep['cust_total']) ?></b><span>مشتری</span></div>
    <div class="of-card"><b><?= office_num(array_sum($rep['req_by_status'])) ?></b><span>درخواست</span></div>
    <div class="of-card"><b><?= office_num(array_sum($rep['visit_by_status'])) ?></b><span>بازدید</span></div>
    <div class="of-card"><b><?= office_num(array_sum($rep['calls30'])) ?></b><span>تماس ۳۰ روز اخیر</span></div>
    <div class="of-card"><b><?= office_num($rep['follow_open']) ?></b><span>پیگیری باز</span></div>
</div>

<div class="of-rep-grid">
    <div class="of-panel"><h3 style="margin-top:0">فایل‌ها به تفکیک وضعیت</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['files_by_status'] as $k => $n): ?><tr><td><?= office_h($statusFa[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['files_by_status']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="files.php">مشاهده فایل‌ها</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">فایل‌ها به تفکیک معامله</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['files_by_tx'] as $k => $n): ?><tr><td><?= office_h($k !== '' ? $k : '(نامشخص)') ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['files_by_tx']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">مشارکت‌ها به تفکیک وضعیت</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['parts_by_status'] as $k => $n): ?><tr><td><?= office_h($partStatuses[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['parts_by_status']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="partnerships.php">مدیریت مشارکت‌ها</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">مشتریان به تفکیک نوع</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['cust_by_kind'] as $k => $n): ?><tr><td><?= office_h($custKinds[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['cust_by_kind']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="customers.php">مشاهده مشتریان</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">درخواست‌ها به تفکیک وضعیت</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['req_by_status'] as $k => $n): ?><tr><td><?= office_h($reqStatuses[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['req_by_status']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="requests.php">مشاهده درخواست‌ها</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">بازدیدها به تفکیک وضعیت</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['visit_by_status'] as $k => $n): ?><tr><td><?= office_h($visitStatuses[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['visit_by_status']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="visits.php">مشاهده بازدیدها</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">تماس‌های ۳۰ روز اخیر</h3>
        <table class="of-mini-table">
            <?php foreach ($rep['calls30'] as $k => $n): ?><tr><td><?= office_h($callDirs[$k] ?? $k) ?></td><td><?= office_num($n) ?></td></tr><?php endforeach; ?>
            <?php if (!$rep['calls30']): ?><tr><td class="of-muted">—</td><td></td></tr><?php endif; ?>
        </table>
        <p><a class="of-btn ghost" href="calls.php">دفترچه تماس</a></p>
    </div>
    <div class="of-panel"><h3 style="margin-top:0">پیگیری‌ها</h3>
        <table class="of-mini-table">
            <tr><td>باز</td><td><?= office_num($rep['follow_open']) ?></td></tr>
            <tr><td>انجام‌شده</td><td><?= office_num($rep['follow_done']) ?></td></tr>
        </table>
        <p><a class="of-btn ghost" href="followups.php">مشاهده پیگیری‌ها</a></p>
    </div>
</div>

<div class="of-panel" style="margin-top:12px"><h3 style="margin-top:0">⚠️ پیگیری‌های سررسیدگذشته</h3>
    <?php if (!$rep['overdue']): ?>
        <p class="of-muted">موردی نیست. 🎉</p>
    <?php else: ?>
        <table class="of-mini-table">
            <?php foreach ($rep['overdue'] as $o): ?>
                <tr><td><a href="followups.php?edit=<?= (int)$o['id'] ?>"><?= office_h((string)$o['title']) ?></a></td><td dir="ltr"><?= office_h((string)$o['due_date']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="of-panel" style="margin-top:12px"><h3 style="margin-top:0">📅 بازدیدهای ۷ روز آینده</h3>
    <?php if (!$rep['upcoming']): ?>
        <p class="of-muted">موردی نیست.</p>
    <?php else: ?>
        <table class="of-mini-table">
            <?php foreach ($rep['upcoming'] as $u): ?>
                <tr><td><a href="visits.php?edit=<?= (int)$u['id'] ?>"><?= office_h((string)($u['ad_title'] ?? $u['ad_id'])) ?></a> — <?= office_h((string)$u['name']) ?></td><td dir="ltr"><?= office_h(substr((string)$u['visit_at'], 0, 16)) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
