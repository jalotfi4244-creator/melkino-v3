<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ویرایش فایل (?id=...)
 *--------------------------------------------------------------------------
 * همان فرم ثبت همان نوع ملک، پرشده از دیتابیس؛ اعتبارسنجی با همان
 * کالکتور ثبت؛ ذخیره با UPDATE معادل نگاشت هلپر سایت.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms.php';
require_once __DIR__ . '/_forms2.php';
require_once __DIR__ . '/_update.php';

$id = trim((string)($_GET['id'] ?? ''));
$loaded = $id !== '' ? office_load_ad_for_edit(office_db(), $id) : null;

$errors = [];
$uploadErrors = [];
$values = $loaded['values'] ?? [];
$updated = false;

if ($loaded !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values = is_array($_POST) ? $_POST : [];
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $errors[] = 'نشست منقضی شده است؛ لطفاً دوباره تلاش کنید.';
    } else {
        [$newAd, $errors, $uploadErrors] = office_collect_for($loaded['type'], $_POST, $_FILES['images'] ?? null, office_user()['id']);
        // وضعیت ویرایش: ۴ حالت مجاز؛ نامعتبر ← حفظ قبلی
        $st = trim((string)($_POST['status'] ?? ''));
        $newAd['status'] = in_array($st, ['published', 'pending', 'sold', 'expired'], true)
            ? $st
            : (string)($loaded['row']['status'] ?? 'pending');
        $newAd['id'] = $id;
        if (!$errors) {
            try {
                office_update_ad(office_db(), $id, $newAd);
                $updated = true;
                $loaded = office_load_ad_for_edit(office_db(), $id);
                $values = $loaded['values'] ?? [];
            } catch (Throwable $e) {
                $errors[] = 'خطا در ذخیره تغییرات: ' . $e->getMessage();
            }
        }
    }
}

office_shell_open('files.php', 'ویرایش فایل');
?>
<style>
.of-sec{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:12px}
.of-sec h3{margin:0 0 10px;font-size:15px}
.of-fgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.of-field{display:flex;flex-direction:column;gap:6px;font-size:13px}
.of-field input[type=text],.of-field input[type=date],.of-field select,.of-field textarea{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 10px;font-size:13px;font-family:inherit;color:var(--text);width:100%;box-sizing:border-box}
.of-radio{display:inline-flex;align-items:center;gap:5px;margin:2px 8px 2px 0;font-size:13px;white-space:nowrap}
.of-checks{display:flex;flex-wrap:wrap}
</style>

<?php if ($loaded === null): ?>
    <div class="of-alert err"><b>فایل یافت نشد</b> یا نوع ملک آن قابل ویرایش در دفتر نیست.<br><br><a class="of-btn ghost" href="files.php">بازگشت به فایل‌ها</a></div>
<?php else: ?>
    <p class="of-muted">کد فایل: <b dir="ltr"><?= office_h($id) ?></b> <a class="of-btn ghost" style="padding:4px 10px" href="file-history.php?id=<?= urlencode($id) ?>">🕘 تاریخچه</a></p>

    <?php if ($updated): ?>
        <div class="of-alert ok"><b>تغییرات با موفقیت ذخیره شد.</b><br><br>
            <a class="of-btn" href="files.php?q=<?= urlencode($id) ?>">مشاهده در لیست فایل‌ها</a>
            <a class="of-btn ghost" href="../property-details.php?id=<?= urlencode($id) ?>">👁 مشاهده آگهی</a>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($errors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($uploadErrors && !$updated): ?>
        <div class="of-alert warn"><b>هشدار عکس‌ها:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($uploadErrors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="file-edit.php?id=<?= urlencode($id) ?>" enctype="multipart/form-data" id="ofEditForm">
        <?= office_csrf_field() ?>
        <?php foreach (office_sections_for($loaded['type'], true) as $secTitle => $fields): ?>
            <div class="of-sec"><h3><?= office_h($secTitle) ?></h3><div class="of-fgrid">
                <?php foreach ($fields as $f): ?><?= office_render_field($f, $values) ?><?php endforeach; ?>
            </div></div>
        <?php endforeach; ?>
        <div class="of-actions">
            <button class="of-btn" type="submit" name="submit_property" value="1">ذخیره تغییرات</button>
            <a class="of-btn ghost" href="files.php">انصراف</a>
        </div>
    </form>
    <script>
    function ofPriceCond(el, other) {
        document.querySelectorAll('input[name="price_condition"]').forEach(function (c) { if (c !== el && c.value === other) c.checked = false; });
        if (!document.querySelector('input[name="price_condition"]:checked')) el.checked = true;
    }
    (function () {
        var form = document.getElementById('ofEditForm');
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

<?php office_shell_close(); ?>
