<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ثبت فایل / درخواست مشارکت جدید
 *--------------------------------------------------------------------------
 * هر ۷ نوع کامل عین سایت: آپارتمان/ویلا/زمین/تجاری/اداری/باغ + مشارکت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms.php';
require_once __DIR__ . '/_forms2.php';
require_once __DIR__ . '/_forms3.php';

$types = office_register_types();
$type = (string)($_GET['type'] ?? 'apartment');
if (!isset($types[$type])) {
    $type = 'apartment';
}
$typeReady = !empty($types[$type]['ready']);
$isPartnership = ($type === 'partnership');

$errors = [];
$uploadErrors = [];
$values = [];
$savedAd = null;

if ($typeReady && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values = is_array($_POST) ? $_POST : [];
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $errors[] = 'نشست منقضی شده است؛ لطفاً دوباره تلاش کنید.';
    } elseif ($isPartnership) {
        [$saved, $errors, $uploadErrors] = office_save_partnership(office_db(), $_POST, is_array($_FILES) ? $_FILES : [], office_user()['id']);
        if ($saved !== null && !$errors) {
            $savedAd = ['id' => (string)$saved['code'], 'title' => (string)($saved['title'] ?? '')];
            $values = [];
        }
    } else {
        [$newAd, $errors, $uploadErrors] = office_collect_for($type, $_POST, $_FILES['images'] ?? null, office_user()['id']);
        if (!$errors && function_exists('savePropertyToDatabase')) {
            try {
                $pdo = office_db();
                savePropertyToDatabase($pdo, $newAd);
                // ستون consultant_id در INSERT هلپر نیست؛ جداگانه ثبت می‌شود (بدون تغییر اسکیما).
                try {
                    $st = $pdo->prepare('UPDATE ads SET consultant_id = ? WHERE id = ?');
                    $st->execute([office_user()['id'], $newAd['id']]);
                } catch (Throwable $e2) {
                }
                $savedAd = $newAd;
                $values = [];
            } catch (Throwable $e) {
                $errors[] = 'خطا در ذخیره فایل: ' . $e->getMessage();
            }
        }
    }
}

office_shell_open('register.php', $isPartnership ? 'ثبت مشارکت' : 'ثبت فایل');
?>
<style>
.of-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.of-tab{padding:8px 14px;border:1px solid var(--border);border-radius:999px;font-size:13px;text-decoration:none;color:var(--muted)}
.of-tab.active{background:var(--accent);border-color:var(--accent);color:#fff}
.of-tab .soon{font-size:11px;opacity:.7}
.of-sec{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:12px}
.of-sec h3{margin:0 0 10px;font-size:15px}
.of-fgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-field{display:flex;flex-direction:column;gap:6px;font-size:13px}
.of-field input[type=text],.of-field input[type=date],.of-field select,.of-field textarea{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 10px;font-size:13px;font-family:inherit;color:var(--text);width:100%;box-sizing:border-box}
.of-radio{display:inline-flex;align-items:center;gap:5px;margin:2px 8px 2px 0;font-size:13px;white-space:nowrap}
.of-checks{display:flex;flex-wrap:wrap}
</style>

<div class="of-tabs">
    <?php foreach ($types as $k => $t): ?>
        <a class="of-tab<?= $k === $type ? ' active' : '' ?>" href="register.php?type=<?= $k ?>"><?= office_h($t['label']) ?><?= $t['ready'] ? '' : ' <span class="soon">(به‌زودی)</span>' ?></a>
    <?php endforeach; ?>
</div>

<?php if ($savedAd): ?>
    <div class="of-alert ok">
        <?php if ($isPartnership): ?>
            <b>درخواست مشارکت در ساخت با موفقیت ثبت شد.</b><br>
            کد پیگیری: <b dir="ltr"><?= office_h((string)$savedAd['id']) ?></b>
            <?php if ($uploadErrors): ?><br>اما <?= office_fa((string)count($uploadErrors)) ?> فایل ذخیره نشد (جزئیات زیر).<?php endif; ?>
            <br><br>
            <a class="of-btn ghost" href="register.php?type=partnership">ثبت درخواست بعدی</a>
        <?php else: ?>
            <b>فایل با موفقیت ثبت شد.</b><br>
            کد فایل: <b dir="ltr"><?= office_h((string)$savedAd['id']) ?></b>
            <?php if ($uploadErrors): ?><br>اما <?= office_fa((string)count($uploadErrors)) ?> عکس ذخیره نشد (جزئیات زیر).<?php endif; ?>
            <br><br>
            <a class="of-btn" href="files.php?q=<?= urlencode((string)$savedAd['id']) ?>">مشاهده در لیست فایل‌ها</a>
            <a class="of-btn ghost" href="register.php?type=<?= $type ?>">ثبت فایل بعدی</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($errors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($uploadErrors && !$savedAd): ?>
    <div class="of-alert warn"><b>هشدار فایل‌ها:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($uploadErrors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!$typeReady): ?>
    <div class="of-panel"><b>ثبت <?= office_h($types[$type]['label']) ?></b><p class="of-muted">این فرم در حال آماده‌سازی است و به‌زودی با همان فیلدهای سایت اضافه می‌شود.</p></div>
<?php else: ?>
    <form method="post" action="register.php?type=<?= $type ?>" enctype="multipart/form-data" id="ofRegForm">
        <?= office_csrf_field() ?>
        <?php foreach (($isPartnership ? office_partnership_sections() : office_sections_for($type)) as $secTitle => $fields): ?>
            <div class="of-sec"><h3><?= office_h($secTitle) ?></h3><div class="of-fgrid">
                <?php foreach ($fields as $f): ?><?= office_render_field($f, $values) ?><?php endforeach; ?>
            </div></div>
        <?php endforeach; ?>
        <div class="of-actions">
            <button class="of-btn" type="submit" name="<?= $isPartnership ? 'submit_partnership' : 'submit_property' ?>" value="1"><?= $isPartnership ? 'ثبت درخواست' : 'ثبت فایل' ?></button>
            <a class="of-btn ghost" href="files.php">انصراف</a>
        </div>
    </form>
    <?php if (!$isPartnership): ?>
    <script>
    // نمایش/پنهان بلوک‌های قیمت بر اساس نوع معامله + رفتار عین سایت
    function ofPriceCond(el, other) {
        document.querySelectorAll('input[name="price_condition"]').forEach(function (c) { if (c !== el && c.value === other) c.checked = false; });
        if (!document.querySelector('input[name="price_condition"]:checked')) el.checked = true;
    }
    (function () {
        var form = document.getElementById('ofRegForm');
        if (!form) return;
        function syncTx() {
            var tx = (form.querySelector('input[name="transaction_type"]:checked') || {}).value || 'فروش';
            form.querySelectorAll('[data-tx]').forEach(function (el) {
                el.style.display = (el.getAttribute('data-tx') === tx) ? '' : 'none';
            });
        }
        form.querySelectorAll('input[name="transaction_type"]').forEach(function (r) { r.addEventListener('change', syncTx); });
        syncTx();
    })();
    </script>
    <?php endif; ?>
<?php endif; ?>

<?php office_shell_close(); ?>
