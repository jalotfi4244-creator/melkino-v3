<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — عیب‌یابی (مرحله ۳۱)
 *--------------------------------------------------------------------------
 * آینهٔ admin-diagnostics.php سایت: همان چک‌ها در همان گروه‌ها،
 * فقط‌خواندنی. خلاصه + جدول گروه‌بندی‌شده با راهنما.
 */

declare(strict_types=1);

require_once __DIR__ . '/_diagnostics.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
office_dg_boot($pdo);

$started = microtime(true);
try {
    $checks = office_dg_run();
} catch (Throwable $e) {
    $checks = [];
    $checks[] = [
        'group' => 'خطا',
        'name' => 'اجرای عیب‌یاب ناتمام ماند',
        'status' => 'fail',
        'message' => 'بخشی از بررسی‌ها اجرا نشد.',
        'hint' => 'صفحه را تازه‌سازی کنید؛ اگر تکرار شد به مدیر سرور اطلاع دهید.',
    ];
}
$elapsed = microtime(true) - $started;
$summary = office_dg_summary($checks);
$groups = [];
foreach ($checks as $c) {
    $groups[(string)($c['group'] ?? 'سایر')][] = $c;
}

office_shell_open('promotions.php', 'عیب‌یابی');
?>
<div class="of-panel">
<strong>✅ سالم:</strong> <?= office_num($summary['ok']) ?>
&nbsp;•&nbsp; <strong>⚠️ هشدار:</strong> <?= office_num($summary['warn']) ?>
&nbsp;•&nbsp; <strong>❌ خطا:</strong> <?= office_num($summary['fail']) ?>
&nbsp;•&nbsp; <span class="of-muted"><?= office_num(count($checks)) ?> بررسی در <?= office_h(office_fa(number_format($elapsed, 1))) ?> ثانیه</span>
&nbsp; <a class="of-btn ghost" style="padding:4px 10px" href="diagnostics.php">🔄 اجرای دوباره</a>
</div>

<?php foreach ($groups as $gName => $items) : ?>
<?php
$fails = 0;
$warns = 0;
foreach ($items as $i) {
    if (($i['status'] ?? '') === 'fail') {
        $fails++;
    } elseif (($i['status'] ?? '') === 'warn') {
        $warns++;
    }
}
?>
<h3><?= office_h($gName) ?> <span class="of-muted" style="font-size:12px">(<?= office_num(count($items)) ?> بررسی<?php if ($fails) : ?> · <?= office_num($fails) ?> خطا<?php endif; ?><?php if ($warns) : ?> · <?= office_num($warns) ?> هشدار<?php endif; ?>)</span></h3>
<div class="of-table-wrap"><table class="of-table">
<tr><th style="width:34px"></th><th>بررسی</th><th>نتیجه</th><th>راهنما</th></tr>
<?php foreach ($items as $i) : ?>
<?php $st = (string)($i['status'] ?? ''); ?>
<tr>
<td><?= $st === 'ok' ? '✅' : ($st === 'warn' ? '⚠️' : '❌') ?></td>
<td><strong><?= office_h((string)($i['name'] ?? '')) ?></strong></td>
<td><?= office_h((string)($i['message'] ?? '')) ?></td>
<td class="of-muted" style="font-size:12px"><?= office_h((string)($i['hint'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php endforeach; ?>
<?php office_shell_close(); ?>
