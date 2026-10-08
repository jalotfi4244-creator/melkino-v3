<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — لیست فایل‌ها با جست‌وجو و فیلتر (مرحله ۲)
 *--------------------------------------------------------------------------
 * فقط خواندن از جدول ads؛ همه ورودی‌ها whitelist/prepared.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_revisions.php';
office_shell_open('files.php', 'فایل‌ها');

$pdo = office_db();

$q = trim((string)($_GET['q'] ?? ''));
$fStatus = (string)($_GET['status'] ?? '');
$fTx = (string)($_GET['tx'] ?? '');
$fType = (string)($_GET['ptype'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$statusFa = ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی'];

// مقادیر مجاز فیلترها از خود دیتا (بدون hardcode)
$allowed = ['status' => [], 'tx' => [], 'ptype' => []];
try {
    foreach (['status' => 'status', 'tx' => 'transaction_type', 'ptype' => 'property_type'] as $k => $col) {
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
if ($fType !== '' && !in_array($fType, $allowed['ptype'], true)) {
    $fType = '';
}

$where = [];
$params = [];
if ($q !== '') {
    // هر ستون پلیس‌هولدر جدا: تکرار یک نام در prepare واقعی MySQL خطای HY093 می‌دهد.
    $ors = [];
    foreach (['id', 'title', 'location', 'phone', 'address'] as $i => $col) {
        $ors[] = "`$col` LIKE :q$i";
        $params[":q$i"] = '%' . $q . '%';
    }
    $where[] = '(' . implode(' OR ', $ors) . ')';
}
if ($fStatus !== '') {
    $where[] = 'status = :st';
    $params[':st'] = $fStatus;
}
if ($fTx !== '') {
    $where[] = 'transaction_type = :tx';
    $params[':tx'] = $fTx;
}
if ($fType !== '') {
    $where[] = 'property_type = :pt';
    $params[':pt'] = $fType;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = 0;
$rows = [];
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM ads $whereSql");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $off = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        "SELECT id, title, property_type, transaction_type, location, phone, status, created_at,
                price_sell, total_price, deposit, rent_monthly, full_rent, full_rent_enabled
         FROM ads $whereSql ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v);
    }
    $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $st->bindValue(':off', $off, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $pages = 1;
}

$qsBase = http_build_query(array_filter(['q' => $q, 'status' => $fStatus, 'tx' => $fTx, 'ptype' => $fType], static fn($v) => $v !== ''));
$qsBase = $qsBase !== '' ? $qsBase . '&' : '';
?>
<style>
.of-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.of-filters input[type=text],.of-filters select{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:9px 10px;font:inherit;font-size:13px;color:var(--text)}
.of-pager{display:flex;gap:10px;align-items:center;margin-top:12px;font-size:13px;color:var(--muted)}
</style>

<div class="of-panel">
    <form method="get" action="files.php" class="of-filters">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: کد، عنوان، موقعیت، موبایل..." style="min-width:240px">
        <select name="status">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach ($allowed['status'] as $s): ?><option value="<?= office_h($s) ?>"<?= $s === $fStatus ? ' selected' : '' ?>><?= office_h($statusFa[$s] ?? $s) ?></option><?php endforeach; ?>
        </select>
        <select name="tx">
            <option value="">همه معاملات</option>
            <?php foreach ($allowed['tx'] as $t): ?><option value="<?= office_h($t) ?>"<?= $t === $fTx ? ' selected' : '' ?>><?= office_h($t) ?></option><?php endforeach; ?>
        </select>
        <select name="ptype">
            <option value="">همه انواع</option>
            <?php foreach ($allowed['ptype'] as $t): ?><option value="<?= office_h($t) ?>"<?= $t === $fType ? ' selected' : '' ?>><?= office_h($t) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">جست‌وجو</button>
        <a class="of-btn ghost" href="register.php?type=apartment">＋ ثبت فایل</a>
        <a class="of-btn ghost" href="revisions.php">✏️ ویرایش‌های در انتظار (<?= office_fa((string)count(office_rev_pending($pdo))) ?>)</a>
    </form>
    <p class="of-muted"><?= office_num($total) ?> فایل یافت شد. <a class="of-btn ghost" style="padding:4px 10px" href="images.php">🖼 تصاویر آگهی‌ها</a></p>
    <?php if (!$rows): ?>
        <p class="of-muted">فایلی با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>کد</th><th>عنوان</th><th>معامله</th><th>نوع</th><th>موقعیت</th><th>موبایل</th><th>قیمت</th><th>وضعیت</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
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
                    <td dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></td>
                    <td><?= office_ad_price($r) ?></td>
                    <td><span class="of-badge <?= $cls ?>"><?= office_h($statusFa[$st] ?? $st) ?></span></td>
                    <td style="white-space:nowrap"><a class="of-btn ghost" style="padding:4px 10px" href="file-edit.php?id=<?= urlencode((string)($r['id'] ?? '')) ?>" title="ویرایش">✏️</a> <a class="of-btn ghost" style="padding:4px 10px" href="../property-details.php?id=<?= urlencode((string)($r['id'] ?? '')) ?>" title="مشاهده">👁</a></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <div class="of-pager">
            <?php if ($page > 1): ?><a class="of-btn ghost" href="files.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="files.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php office_shell_close(); ?>
