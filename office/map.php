<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — نقشه فایل‌ها (مرحله ۱۴ نهایی)
 *--------------------------------------------------------------------------
 * همان نقشه‌ای که ملکینو نشان می‌دهد (Leaflet + تایل OSM + رنگ‌های سایت)،
 * ولی با داده دفتر: مختصات دقیق دیتابیس (بدون حریم ~۵۰ متری نقشه عمومی)
 * و همه وضعیت‌ها. Leaflet داخل خود دفتر است تا به CDN وابسته نباشد.
 * تنها منبع بیرونی: تایل‌های نقشه.
 */

declare(strict_types=1);

require_once __DIR__ . '/_mappins.php';

$pdo = office_db();

$q = trim((string)($_GET['q'] ?? ''));
$fStatus = (string)($_GET['status'] ?? '');
$fTx = (string)($_GET['tx'] ?? '');

$allowed = ['status' => [], 'tx' => []];
try {
    foreach (['status' => 'status', 'tx' => 'transaction_type'] as $k => $col) {
        $st = $pdo->query("SELECT DISTINCT `$col` AS v FROM ads ORDER BY `$col`");
        $allowed[$k] = array_values(array_filter(array_map(static fn($r) => (string)($r['v'] ?? ''), $st ? $st->fetchAll(PDO::FETCH_ASSOC) : []), static fn($v) => $v !== ''));
    }
} catch (Throwable $e) {
}
if ($fStatus !== '' && !in_array($fStatus, $allowed['status'], true)) {
    $fStatus = '';
}
if ($fTx !== '' && !in_array($fTx, $allowed['tx'], true)) {
    $fTx = '';
}

$rows = office_mappins($pdo, $fStatus, $fTx, $q);
$points = [];
foreach ($rows as $r) {
    $points[] = [
        'code' => (string)($r['id'] ?? ''),
        'title' => (string)($r['title'] ?? ''),
        'type' => (string)($r['property_type'] ?? ''),
        'tx' => (string)($r['transaction_type'] ?? ''),
        'area' => (string)($r['area'] ?? ''),
        'lat' => (string)($r['latitude'] ?? ''),
        'lng' => (string)($r['longitude'] ?? ''),
    ];
}
$mapCfg = [
    'points' => $points,
    'colors' => office_mappin_colors($pdo),
    'tiles' => office_mappin_tiles($pdo),
    'center' => [36.4182, 54.9763],
];
$mapJson = json_encode($mapCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$statusFa = ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی', 'archived' => 'بایگانی'];

office_shell_open('map.php', 'نقشه فایل‌ها');
?>
<link rel="stylesheet" href="assets/leaflet/leaflet.css">
<style>
#ofMapCanvas{height:560px;border-radius:14px;border:1px solid var(--line);background:var(--bg);z-index:0}
.of-filter{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-pin-wrap{background:none;border:0}
</style>

<div class="of-panel">
    <form method="get" action="map.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: کد، عنوان، موقعیت..." style="min-width:200px" class="of-filter">
        <select name="status" class="of-filter">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach ($allowed['status'] as $s): ?><option value="<?= office_h($s) ?>"<?= $fStatus === $s ? ' selected' : '' ?>><?= office_h($statusFa[$s] ?? $s) ?></option><?php endforeach; ?>
        </select>
        <select name="tx" class="of-filter">
            <option value="">همه معاملات</option>
            <?php foreach ($allowed['tx'] as $t): ?><option value="<?= office_h($t) ?>"<?= $fTx === $t ? ' selected' : '' ?>><?= office_h($t) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">جست‌وجو</button>
    </form>
    <p class="of-muted"><?= office_num(count($points)) ?> فایل روی نقشه (موقعیت دقیق).</p>
    <div id="ofMapCanvas"></div>
    <script>
    window.OFFICE_MAP = <?= $mapJson ?>;
    </script>
    <script src="assets/leaflet/leaflet.js"></script>
    <script src="assets/office-map.js"></script>

    <?php if ($rows): ?>
        <div class="of-table-wrap" style="margin-top:12px"><table class="of-table">
            <tr><th>کد</th><th>عنوان</th><th>معامله</th><th>مختصات دقیق</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td dir="ltr"><?= office_h((string)$r['id']) ?></td>
                    <td><?= office_h((string)($r['title'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['transaction_type'] ?? '')) ?></td>
                    <td dir="ltr"><?= office_h((string)$r['latitude']) ?> ، <?= office_h((string)$r['longitude']) ?></td>
                    <td><a class="of-btn ghost" style="padding:4px 10px" href="file-edit.php?id=<?= urlencode((string)$r['id']) ?>" title="ویرایش">✏️</a></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
