<?php
/** Melkino — shared legacy/V2 admin stats fragment; real PDO data, server-rendered.
 * Vars: counts, rankings, top_ads, ads_by_tx, ads_by_ptype, ads_by_type,
 * ads_breakdown_error (from melkinoStatsDashboard).
 */
if (!function_exists('e')) {
    function e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
$counts = is_array($counts ?? null) ? $counts : [];
$rankings = is_array($rankings ?? null) ? $rankings : [];
$topAds = is_array($top_ads ?? null) ? $top_ads : [];
$byTx = is_array($ads_by_tx ?? null) ? $ads_by_tx : [];
$byProperty = is_array($ads_by_ptype ?? null) ? $ads_by_ptype : [];
$byType = is_array($ads_by_type ?? null) ? $ads_by_type : [];
$breakdownError = (bool)($ads_breakdown_error ?? false);
$adsTotal = (int)($counts['ads_total'] ?? 0);
$matrix = [];
foreach ($byType as $cell) {
    $matrix[(string)$cell['pt']][(string)$cell['tx']] = (int)$cell['c'];
}
$n = static function ($v): string {
    return number_format((int)$v);
};
$percent = static function ($v) use ($adsTotal): string {
    return number_format($adsTotal > 0 ? ((int)$v * 100 / $adsTotal) : 0, 1) . '٪';
};
$medal = static function (int $i): string {
    return [1 => '🥇', 2 => '🥈', 3 => '🥉'][$i]
        ?? (function_exists('melkinoFaDigits') ? melkinoFaDigits((string)$i) : (string)$i);
};
$fmtDay = static function (string $d): string {
    if ($d === '') {
        return '';
    }
    if (function_exists('melkinoFormatTehranFa')) {
        try {
            return (string)melkinoFormatTehranFa($d . ' 12:00:00', false);
        } catch (Throwable $e) {
            return $d;
        }
    }
    return $d;
};
$summaryCards = [
    ['users_total', 'کاربران (کل)'],
    ['users_active', 'کاربران فعال'],
    ['users_inactive', 'کاربران غیرفعال'],
    ['active_24h', 'فعال ۲۴ ساعت اخیر'],
    ['active_30d', 'فعال ۳۰ روز اخیر'],
    ['new_7d', 'کاربران تازه (۷ روز)'],
    ['ads_total', 'کل آگهی‌ها'],
    ['requests_total', 'درخواست‌ها'],
    ['views_total', 'بازدیدهای آگهی'],
    ['shares_total', 'اشتراک‌گذاری‌ها'],
    ['matches_total', 'تطبیق‌ها'],
    ['logins_total', 'ورودها'],
    ['searches_total', 'جستجوهای ذخیره‌شده'],
    ['favs_total', 'علاقه‌مندی‌ها'],
];
?>
<div role="tabpanel" class="tab-content" id="tab-stats" aria-label="آمار">
    <style>
        #tab-stats{--mk-stats-border:rgba(6,78,78,.16);--mk-stats-soft:#eef6f3;--mk-stats-ink:#24443e;--mk-stats-muted:#546d64;--mk-stats-accent:#0e6155;color:var(--mk-stats-ink);min-width:0}
        .mx-admin #tab-stats,[data-theme="dark"] #tab-stats{--mk-stats-border:#34534b;--mk-stats-soft:#1d3430;--mk-stats-ink:#e4eeea;--mk-stats-muted:#aec5bb;--mk-stats-accent:#88ddd0}
        #tab-stats .mk-page-head{padding:16px}
        #tab-stats .mk-page-title{font-size:19px;margin:0 0 8px;color:var(--mk-stats-ink)}
        #tab-stats .mk-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:10px;padding:0 16px 6px}
        #tab-stats .mk-stats-grid .stat-card{text-align:center;padding:14px 8px;border:1px solid var(--mk-stats-border);border-radius:12px;background:var(--mk-stats-soft)}
        #tab-stats .mk-stats-grid .number{font-size:24px;font-weight:800;color:var(--mk-stats-accent);direction:ltr}
        #tab-stats .mk-stats-grid .label{font-size:12px;color:var(--mk-stats-muted);margin-top:4px}
        #tab-stats .mk-rank{margin:14px 16px 20px;border:1px solid var(--mk-stats-border);border-radius:14px;overflow:hidden;min-width:0}
        #tab-stats .mk-rank h3{margin:0;padding:12px 14px;font-size:14px;background:var(--mk-stats-soft);color:var(--mk-stats-ink)}
        #tab-stats .mk-rank table{width:100%;border-collapse:collapse;font-size:13px;color:var(--mk-stats-ink)}
        #tab-stats .mk-rank th,#tab-stats .mk-rank td{padding:10px 12px;border-top:1px solid var(--mk-stats-border);text-align:right;overflow-wrap:anywhere}
        #tab-stats .mk-rank thead th{border-top:0;color:var(--mk-stats-accent)}
        #tab-stats .mk-rank tfoot{background:var(--mk-stats-soft);font-weight:800}
        #tab-stats .mk-rank .c{text-align:center;white-space:nowrap}
        #tab-stats .mk-rank .num{font-weight:800;color:var(--mk-stats-accent);white-space:nowrap;font-variant-numeric:tabular-nums}
        #tab-stats .mk-empty{padding:14px;color:var(--mk-stats-muted);font-size:13px}
        #tab-stats .mk-stats-note{font-size:12px;line-height:1.9;color:var(--mk-stats-muted);margin:0}
        #tab-stats .mk-stats-defs{padding:0 16px 20px}
        #tab-stats .mk-breakdowns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:14px 16px 0}
        #tab-stats .mk-breakdowns .mk-rank{margin:0 0 6px}
        #tab-stats .mk-stats-scroll{overflow-x:auto;max-width:100%;-webkit-overflow-scrolling:touch}
        #tab-stats .mk-stats-scroll:focus-visible{outline:2px solid var(--mk-stats-accent);outline-offset:-2px}
        #tab-stats .mk-user-rank table{min-width:620px}
        #tab-stats .mk-matrix th{white-space:nowrap}
        #tab-stats .mk-matrix .mk-zero{color:var(--mk-stats-muted);font-weight:400}
        #tab-stats .mk-stats-alert{padding:14px 16px;margin:14px 16px;border:1px solid var(--mk-stats-border);border-radius:12px;color:var(--mk-stats-ink)}
        @media(max-width:760px){#tab-stats .mk-breakdowns{grid-template-columns:1fr}#tab-stats .mk-rank{margin-left:10px;margin-right:10px}#tab-stats .mk-breakdowns .mk-rank{margin-left:0;margin-right:0}#tab-stats .mk-stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));padding-left:10px;padding-right:10px}}
    </style>

    <div class="admin-card">
        <div class="mk-page-head">
            <h2 class="mk-page-title">📊 آمار آگهی‌ها و کاربران</h2>
            <p class="mk-stats-note">آمار از داده‌های ثبت‌شده در پایگاه داده محاسبه می‌شود؛ برای به‌روزرسانی، صفحه را تازه‌سازی کنید.</p>
        </div>

        <div class="stats-grid mk-stats-grid">
            <?php foreach ($summaryCards as [$key, $label]): ?>
            <div class="stat-card" data-stat="<?= e($key) ?>">
                <div class="number"><?= $n($counts[$key] ?? 0) ?></div>
                <div class="label"><?= e($label) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mk-stats-defs">
            <p class="mk-stats-note">تفکیک آگهی‌ها شامل <strong>تمام آگهی‌های موجود، با هر وضعیت</strong> است؛ نه فقط آگهی‌های منتشرشده. نوع خالی با «نامشخص» نمایش داده می‌شود و از جمع حذف نمی‌شود.</p>
        </div>

        <?php if ($breakdownError): ?>
        <p class="mk-stats-alert" role="alert">خواندن تفکیک آگهی‌ها ممکن نشد. اتصال و ساختار پایگاه داده را بررسی کنید؛ این پیام به معنی صفر بودن آگهی‌ها نیست.</p>
        <?php else: ?>
        <div class="mk-breakdowns">
            <?php foreach ([['transaction', 'آگهی‌ها به تفکیک نوع معامله', 'نوع معامله', $byTx], ['property', 'آگهی‌ها به تفکیک نوع ملک', 'نوع ملک', $byProperty]] as [$groupKey, $heading, $column, $groups]): ?>
            <section class="mk-rank" data-breakdown="<?= e($groupKey) ?>">
                <h3><?= e($heading) ?></h3>
                <?php if ($groups): ?>
                <div class="mk-stats-scroll" role="region" aria-label="<?= e($heading) ?>" tabindex="0">
                    <table>
                        <thead><tr><th scope="col"><?= e($column) ?></th><th scope="col" class="c">تعداد آگهی</th><th scope="col" class="c">سهم از کل</th></tr></thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                            <tr><th scope="row"><?= e($group['t']) ?></th><td class="c num"><?= $n($group['c']) ?></td><td class="c"><?= $percent($group['c']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th scope="row">جمع کل</th><td class="c num"><?= $n($adsTotal) ?></td><td class="c">100.0٪</td></tr></tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="mk-empty">هنوز آگهی‌ای ثبت نشده است؛ جمع کل: 0</div>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        </div>

        <section class="mk-rank mk-matrix" data-breakdown="combined">
            <h3>تفکیک هم‌زمان نوع ملک و معامله</h3>
            <?php if ($byType): ?>
            <div class="mk-stats-scroll" role="region" aria-label="جدول ترکیبی نوع ملک و معامله" tabindex="0">
                <table>
                    <thead><tr><th scope="col">نوع ملک / نوع معامله</th><?php foreach ($byTx as $tx): ?><th scope="col" class="c"><?= e($tx['t']) ?></th><?php endforeach; ?><th scope="col" class="c">جمع</th></tr></thead>
                    <tbody>
                        <?php foreach ($byProperty as $pt): ?>
                        <tr>
                            <th scope="row"><?= e($pt['t']) ?></th>
                            <?php foreach ($byTx as $tx): $value = $matrix[$pt['t']][$tx['t']] ?? 0; ?>
                            <td class="c num<?= $value === 0 ? ' mk-zero' : '' ?>"><?= $n($value) ?></td>
                            <?php endforeach; ?>
                            <td class="c num"><?= $n($pt['c']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th scope="row">جمع کل</th><?php foreach ($byTx as $tx): ?><td class="c num"><?= $n($tx['c']) ?></td><?php endforeach; ?><td class="c num"><?= $n($adsTotal) ?></td></tr></tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="mk-empty">هنوز آگهی‌ای ثبت نشده است؛ جمع کل: 0</div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php foreach ($rankings as $rankKey => $rk): ?>
        <section class="mk-rank mk-user-rank" data-ranking="<?= e((string)$rankKey) ?>">
            <h3><?= e((string)($rk['title'] ?? '')) ?> <small>(تا ۵ نفر)</small></h3>
            <?php if (!empty($rk['rows'])): ?>
            <div class="mk-stats-scroll" role="region" aria-label="<?= e((string)($rk['title'] ?? 'رتبه‌بندی')) ?>" tabindex="0">
                <table>
                    <thead><tr><th scope="col" class="c">رتبه</th><th scope="col">نام و نام خانوادگی</th><th scope="col">آیدی تلگرام / بله</th><th scope="col">شماره تماس</th><th scope="col" class="c"><?= e((string)($rk['unit'] ?? 'تعداد')) ?></th></tr></thead>
                    <tbody>
                        <?php $i = 0; foreach ($rk['rows'] as $row): $i++; ?>
                        <tr data-user-id="<?= (int)($row['uid'] ?? 0) ?>">
                            <td class="c"><?= $medal($i) ?></td>
                            <td><?= e((string)($row['name'] ?? '—')) ?></td>
                            <td dir="ltr" style="text-align:right"><?= e((string)($row['handle'] ?? '—')) ?></td>
                            <td dir="ltr" style="text-align:right"><?= e((string)($row['phone'] ?? '—')) ?></td>
                            <td class="c num"><?= $n($row['c'] ?? 0) ?><?php if (!empty($row['extra'])): ?><br><small>📅 <?= e($fmtDay((string)$row['extra'])) ?></small><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="mk-empty">هنوز داده‌ای ثبت نشده است.</div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

        <section class="mk-rank" data-ranking="top_ads">
            <h3>پربازدیدترین آگهی‌ها <small>(تا ۵ آگهی)</small></h3>
            <?php if ($topAds): ?>
            <div class="mk-stats-scroll" role="region" aria-label="پربازدیدترین آگهی‌ها" tabindex="0">
                <table>
                    <thead><tr><th scope="col" class="c">رتبه</th><th scope="col">آگهی</th><th scope="col">شناسه</th><th scope="col" class="c">بازدید</th></tr></thead>
                    <tbody>
                        <?php $i = 0; foreach ($topAds as $ad): $i++; ?>
                        <tr>
                            <td class="c"><?= $medal($i) ?></td>
                            <td><?= e((string)($ad['ad_title'] ?? '—')) ?></td>
                            <td dir="ltr" style="text-align:right"><?= e((string)($ad['ad_id'] ?? '')) ?></td>
                            <td class="c num"><?= $n($ad['c'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="mk-empty">هنوز بازدیدی ثبت نشده است.</div>
            <?php endif; ?>
        </section>

        <div class="mk-stats-defs">
            <p class="mk-stats-note"><strong>مبنای محاسبه:</strong> رتبه‌بندی‌ها بر اساس تمام داده‌های موجود هستند. در تساوی تعداد، شناسهٔ کوچک‌تر کاربر اول قرار می‌گیرد. «کمترین تطبیق» فقط کاربران دارای درخواست را شامل می‌شود، حتی با صفر تطبیق.</p>
            <p class="mk-stats-note">«جستجو» یعنی جستجوی ذخیره‌شده. «بازدید» یعنی بازدید ثبت‌شدهٔ آگهی و «مرور تکراری» بازدیدهای بعد از اولین مشاهدهٔ همان آگهی توسط همان کاربر است. فعال/غیرفعال وضعیت حساب است؛ فعالیت ۲۴ساعته و ۳۰روزه بر اساس آخرین ورود محاسبه می‌شود.</p>
            <p class="mk-stats-note">اشتراک‌گذاری از زمان نصب ثبت می‌شود: هر بار فشردن دکمهٔ اشتراک توسط کاربر واردشده یک رویداد است، نه تأیید ارسال نهایی در پیام‌رسان. درصدها به یک رقم اعشار گرد شده‌اند.</p>
        </div>
    </div>
</div>
