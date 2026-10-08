<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — لیست و مدیریت مشارکت در ساخت (مرحله ۷)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ کنسول ادمین سایت: لیست (فیلتر وضعیت + جست‌وجو + شمارش)،
 * جزئیات، تغییر وضعیت، یادداشت ادمین، انتشار/قطع انتشار در سایت، حذف.
 * ویرایش کامل فرم در partnership-edit.php است.
 */

declare(strict_types=1);

// محافظ تشخیصی هاست: اگر فایلی از اکسترکت جا افتاده باشد، به‌جای ۵۰۰ِ کور علت گفته می‌شود.
foreach (['_lib.php', '_forms.php', '_forms3.php', '_parts.php'] as $__need) {
    if (!is_file(__DIR__ . '/' . $__need)) {
        http_response_code(500);
        exit('فایل office/' . $__need . ' روی هاست نیست؛ زیپ هاست را کامل اکسترکت کنید.');
    }
}
unset($__need);

// گزارش‌گر خطای هاست (فقط همین صفحه، فقط برای ادمین لاگین‌کرده):
// به‌جای ۵۰۰ِ کور، علت واقعی را نشان می‌دهد تا قابل ردیابی باشد.
set_exception_handler(static function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit('خطای صفحه مشارکت‌ها: ' . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . basename((string)$e->getFile()) . ':' . (int)$e->getLine());
});

require_once __DIR__ . '/_parts.php';

$pdo = office_db();
$statuses = office_part_statuses();
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
        $pid = (int)($_POST['id'] ?? 0);
        $row = office_part_get($pdo, $pid);
        if (!$row) {
            $flashErr = 'درخواست یافت نشد.';
        } elseif ($action === 'status') {
            $ns = trim((string)($_POST['status'] ?? ''));
            if (!array_key_exists($ns, $statuses)) {
                $flashErr = 'وضعیت نامعتبر است.';
            } elseif (office_part_set_status($pdo, $pid, $ns)) {
                $flash = 'وضعیت به «' . $statuses[$ns] . '» تغییر کرد.';
            } else {
                $flashErr = 'تغییر وضعیت ناموفق بود.';
            }
        } elseif ($action === 'note') {
            if (office_part_save_note($pdo, $pid, (string)($_POST['note'] ?? ''))) {
                $flash = 'یادداشت ادمین ذخیره شد.';
            } else {
                $flashErr = 'ذخیره یادداشت ناموفق بود.';
            }
        } elseif ($action === 'publish') {
            $res = office_part_publish($pdo, $row, !empty($_POST['on']));
            if ($res['published'] || str_contains($res['message'], 'قطع شد')) {
                $flash = $res['message'];
            } else {
                $flashErr = $res['message'];
            }
        } elseif ($action === 'delete') {
            if (office_part_delete($pdo, $pid)) {
                office_redirect('partnerships.php?deleted=1');
            } else {
                $flashErr = 'حذف ناموفق بود.';
            }
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

$viewId = (int)($_GET['id'] ?? 0);
$detail = $viewId > 0 ? office_part_get($pdo, $viewId) : null;

office_shell_open('partnerships.php', $detail ? 'جزئیات مشارکت' : 'مشارکت‌ها');

if ($flash !== '') {
    echo '<div class="of-alert ok">' . office_h($flash) . '</div>';
}
if ($flashErr !== '') {
    echo '<div class="of-alert err">' . office_h($flashErr) . '</div>';
}
if (($isPost === false) && (string)($_GET['deleted'] ?? '') === '1') {
    echo '<div class="of-alert ok">درخواست حذف شد.</div>';
}
?>
<style>
.of-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
.of-chip{border:1px solid var(--line);background:var(--panel);border-radius:99px;padding:7px 14px;font-size:12.5px;font-weight:700;color:var(--muted);text-decoration:none}
.of-chip.on{background:var(--accent);border-color:var(--accent);color:#fff}
.of-kv{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin:12px 0}
.of-kv div{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:8px 12px}
.of-kv b{display:block;font-size:11.5px;color:var(--muted);font-weight:700;margin-bottom:2px}
.of-mini{display:inline-block;margin:2px 4px 2px 0}
</style>

<?php if ($detail): ?>
<?php
    $pid = (int)$detail['id'];
    $mirror = office_part_mirror_status($pdo, $pid);
    $money = static function (mixed $v): string {
        $s = trim((string)$v);
        return $s === '' ? '—' : '<span dir="ltr">' . office_h(office_format_money($s)) . '</span>';
    };
    $valRange = static function () use ($detail, $money): string {
        $f = trim((string)($detail['value_from'] ?? ''));
        $t = trim((string)($detail['value_to'] ?? ''));
        if ($f === '' && $t === '') {
            return '—';
        }
        if ($f !== '' && $t !== '') {
            return $money($f) . ' تا ' . $money($t);
        }
        return $money($f !== '' ? $f : $t);
    };
    $kv = [
        'کد' => '<span dir="ltr">' . office_h((string)($detail['code'] ?? '')) . '</span>',
        'عنوان' => office_h((string)($detail['title'] ?? '')),
        'وضعیت' => '<span class="of-badge">' . office_h($statuses[(string)($detail['status'] ?? '')] ?? (string)($detail['status'] ?? '')) . '</span>',
        'نوع ملک' => office_h((string)($detail['property_type'] ?? '')),
        'مساحت' => office_h((string)($detail['area'] ?? '')) . ' متر',
        'وضعیت فعلی' => office_h((string)($detail['current_status'] ?? '')),
        'محله' => office_h((string)($detail['neighborhood'] ?? '')),
        'آدرس' => office_h((string)($detail['address'] ?? '')),
        'مختصات' => (($detail['latitude'] ?? null) !== null && ($detail['latitude'] ?? '') !== '')
            ? '<span dir="ltr">' . office_h((string)$detail['latitude']) . ' ، ' . office_h((string)($detail['longitude'] ?? '')) . '</span>' : '—',
        'عرض گذر' => office_h((string)($detail['passage_width'] ?? '')),
        'عرض زمین' => office_h((string)($detail['land_width'] ?? '')),
        'تعداد بر' => office_h((string)($detail['br_count'] ?? '')),
        'جهت' => office_h((string)($detail['direction'] ?? '')),
        'وضعیت پروانه' => office_h((string)($detail['permit_status'] ?? '')),
        'تراکم / سطح اشغال' => office_h(trim((string)($detail['density'] ?? '') . ' / ' . (string)($detail['occupancy_rate'] ?? ''), ' /')),
        'طبقات / زیربنای قابل ساخت' => office_h(trim((string)($detail['buildable_floors'] ?? '') . ' / ' . (string)($detail['buildable_area'] ?? ''), ' /')),
        'مالک' => office_h((string)($detail['owner_name'] ?? '')),
        'موبایل مالک' => '<span dir="ltr">' . office_h((string)($detail['phone'] ?? '')) . '</span>',
        'وضعیت سند' => office_h((string)($detail['deed_status'] ?? '')),
        'نوع سند' => office_h((string)($detail['deed_kind'] ?? '')),
        'تعداد مالکین' => office_h((string)($detail['owners_count'] ?? '')),
        'بهره‌برداری' => office_h((string)($detail['occupancy'] ?? '')),
        'وضعیت حقوقی' => office_h(implode('، ', office_part_json_list($detail['legal_status'] ?? '[]'))),
        'سهم مالک / سازنده' => office_h(trim((string)($detail['owner_share'] ?? '') . ' / ' . (string)($detail['builder_share'] ?? ''), ' /')),
        'بلغز' => office_h((string)($detail['balaghz'] ?? '')) . ' — مبلغ: ' . $money($detail['balaghz_amount'] ?? ''),
        'ارزش (از / تا)' => $valRange(),
        'مدت / تأمین مالی' => office_h(trim((string)($detail['duration'] ?? '') . ' / ' . (string)($detail['funding'] ?? ''), ' /')),
        'امتیاز کامل بودن' => office_fa((string)(int)($detail['completeness'] ?? 0)) . '٪',
        'ثبت' => office_h((string)($detail['created_at'] ?? '')),
        'یادداشت ادمین' => office_h((string)($detail['admin_note'] ?? '')) !== '' ? office_h((string)$detail['admin_note']) : '—',
    ];
    $photos = office_part_json_list($detail['photos'] ?? '[]');
    $docOther = office_part_json_list($detail['doc_other'] ?? '[]');
    $docLink = static function (string $p, string $label): string {
        if ($p === '') {
            return '';
        }
        return '<a class="of-btn ghost of-mini" style="padding:4px 10px" href="../' . office_h($p) . '" target="_blank" rel="noopener">' . office_h($label) . '</a>';
    };
?>
<div class="of-panel">
    <p><a class="of-btn ghost" href="partnerships.php">→ بازگشت به لیست</a>
    <a class="of-btn" href="partnership-edit.php?id=<?= $pid ?>">✏️ ویرایش</a></p>
    <div class="of-kv">
        <?php foreach ($kv as $k => $v): ?><div><b><?= office_h($k) ?></b><span><?= $v !== '' ? $v : '—' ?></span></div><?php endforeach; ?>
    </div>
    <h3>عکس‌ها و مدارک</h3>
    <p>
        <?php foreach ($photos as $i => $p): ?><?= $docLink($p, 'عکس ' . office_fa((string)($i + 1))) ?><?php endforeach; ?>
        <?= $docLink((string)($detail['doc_deed'] ?? ''), 'سند') ?>
        <?= $docLink((string)($detail['doc_permit'] ?? ''), 'پروانه') ?>
        <?= $docLink((string)($detail['doc_endjob'] ?? ''), 'پایان‌کار') ?>
        <?php foreach ($docOther as $i => $p): ?><?= $docLink($p, 'مدرک ' . office_fa((string)($i + 1))) ?><?php endforeach; ?>
        <?php if (!$photos && !$docOther && ($detail['doc_deed'] ?? '') === '' && ($detail['doc_permit'] ?? '') === '' && ($detail['doc_endjob'] ?? '') === ''): ?>
            <span class="of-muted">فایلی ثبت نشده است.</span>
        <?php endif; ?>
    </p>
    <h3>تغییر وضعیت</h3>
    <form method="post" action="partnerships.php?id=<?= $pid ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="id" value="<?= $pid ?>">
        <select name="status">
            <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= $k === (string)($detail['status'] ?? '') ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
        </select>
        <button class="of-btn" type="submit">ذخیره وضعیت</button>
    </form>
    <h3>یادداشت ادمین</h3>
    <form method="post" action="partnerships.php?id=<?= $pid ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="note">
        <input type="hidden" name="id" value="<?= $pid ?>">
        <textarea name="note" rows="3" style="width:100%;max-width:560px" maxlength="1000"><?= office_h((string)($detail['admin_note'] ?? '')) ?></textarea><br>
        <button class="of-btn" type="submit">ذخیره یادداشت</button>
    </form>
    <h3>انتشار در سایت</h3>
    <p class="of-muted">وضعیت فعلی آینهٔ <span dir="ltr"><?= office_h(office_part_mirror_id($pid)) ?></span>:
        <b><?= $mirror === 'published' ? 'منتشرشده ✅' : ($mirror === '' ? 'بدون آینه' : office_h($mirror)) ?></b></p>
    <form method="post" action="partnerships.php?id=<?= $pid ?>">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="publish">
        <input type="hidden" name="id" value="<?= $pid ?>">
        <?php if ($mirror === 'published'): ?>
            <input type="hidden" name="on" value="0">
            <button class="of-btn ghost" type="submit">قطع انتشار از سایت</button>
        <?php else: ?>
            <input type="hidden" name="on" value="1">
            <button class="of-btn" type="submit">انتشار در سایت</button>
        <?php endif; ?>
    </form>
    <h3>حذف</h3>
    <form method="post" action="partnerships.php" onsubmit="return confirm('این درخواست حذف شود؟');">
        <?= office_csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $pid ?>">
        <button class="of-btn danger" type="submit">حذف درخواست</button>
    </form>
</div>

<?php else: ?>
<?php
    $q = trim((string)($_GET['q'] ?? ''));
    $fStatus = (string)($_GET['status'] ?? '');
    if ($fStatus !== '' && !array_key_exists($fStatus, $statuses)) {
        $fStatus = '';
    }
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 25;
    $counts = office_part_counts($pdo);
    [$rows, $total] = office_part_list($pdo, $fStatus, $q, $page, $perPage);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $totalAll = array_sum($counts);
    $qsBase = http_build_query(array_filter(['q' => $q, 'status' => $fStatus], static fn($v) => $v !== ''));
    $qsBase = $qsBase !== '' ? $qsBase . '&' : '';
    $chipUrl = static function (string $st) use ($q): string {
        return 'partnerships.php?' . http_build_query(array_filter(['q' => $q, 'status' => $st], static fn($v) => $v !== ''));
    };
?>
<div class="of-panel">
    <form method="get" action="partnerships.php" class="of-filters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="text" name="q" value="<?= office_h($q) ?>" placeholder="جست‌وجو: کد، عنوان، محله، موبایل..." style="min-width:240px">
        <?php if ($fStatus !== ''): ?><input type="hidden" name="status" value="<?= office_h($fStatus) ?>"><?php endif; ?>
        <button class="of-btn" type="submit">جست‌وجو</button>
        <a class="of-btn ghost" href="register.php?type=partnership">＋ ثبت مشارکت</a>
    </form>
    <div class="of-chips">
        <a class="of-chip<?= $fStatus === '' ? ' on' : '' ?>" href="<?= office_h($chipUrl('')) ?>">همه (<?= office_fa((string)$totalAll) ?>)</a>
        <?php foreach ($statuses as $k => $lb): ?>
            <a class="of-chip<?= $fStatus === $k ? ' on' : '' ?>" href="<?= office_h($chipUrl($k)) ?>"><?= office_h($lb) ?> (<?= office_fa((string)($counts[$k] ?? 0)) ?>)</a>
        <?php endforeach; ?>
    </div>
    <p class="of-muted"><?= office_num($total) ?> درخواست یافت شد.</p>
    <?php if (!$rows): ?>
        <p class="of-muted">درخواستی با این مشخصات یافت نشد.</p>
    <?php else: ?>
        <div class="of-table-wrap"><table class="of-table">
            <tr><th>کد</th><th>عنوان</th><th>نوع</th><th>محله</th><th>مالک / موبایل</th><th>ارزش</th><th>وضعیت</th><th>اقدام</th></tr>
            <?php foreach ($rows as $r): ?>
                <?php
                $st = (string)($r['status'] ?? '');
                $vf = trim((string)($r['value_from'] ?? ''));
                $vt = trim((string)($r['value_to'] ?? ''));
                $valCell = '—';
                if ($vf !== '' || $vt !== '') {
                    $parts = [];
                    if ($vf !== '') $parts[] = '<span dir="ltr">' . office_h(office_format_money($vf)) . '</span>';
                    if ($vt !== '') $parts[] = '<span dir="ltr">' . office_h(office_format_money($vt)) . '</span>';
                    $valCell = implode(' تا ', $parts);
                }
                ?>
                <tr>
                    <td dir="ltr"><?= office_h((string)($r['code'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['title'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['property_type'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['neighborhood'] ?? '')) ?></td>
                    <td><?= office_h((string)($r['owner_name'] ?? '')) ?><br><span dir="ltr"><?= office_h((string)($r['phone'] ?? '')) ?></span></td>
                    <td><?= $valCell ?></td>
                    <td><span class="of-badge"><?= office_h($statuses[$st] ?? $st) ?></span></td>
                    <td style="white-space:nowrap"><a class="of-btn ghost" style="padding:4px 10px" href="partnerships.php?id=<?= (int)$r['id'] ?>" title="مشاهده">👁</a> <a class="of-btn ghost" style="padding:4px 10px" href="partnership-edit.php?id=<?= (int)$r['id'] ?>" title="ویرایش">✏️</a></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <div class="of-pager" style="display:flex;gap:10px;align-items:center;margin-top:12px;font-size:13px;color:var(--muted)">
            <?php if ($page > 1): ?><a class="of-btn ghost" href="partnerships.php?<?= $qsBase ?>page=<?= $page - 1 ?>">→ قبلی</a><?php endif; ?>
            <span>صفحه <?= office_fa((string)$page) ?> از <?= office_fa((string)$pages) ?></span>
            <?php if ($page < $pages): ?><a class="of-btn ghost" href="partnerships.php?<?= $qsBase ?>page=<?= $page + 1 ?>">بعدی ←</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php office_shell_close(); ?>
