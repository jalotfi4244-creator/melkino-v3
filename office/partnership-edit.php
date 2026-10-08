<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — ویرایش مشارکت (?id=...)
 *--------------------------------------------------------------------------
 * همان فرم ثبت مشارکت، پرشده از دیتابیس + وضعیت؛ اعتبارسنجی با همان
 * کالکتور ثبت (melkinoPartSanitize/Validate)؛ ذخیره با UPDATE.
 * عکس/مدرک جدید به قبلی‌ها اضافه می‌شود.
 */

declare(strict_types=1);

require_once __DIR__ . '/_parts.php';

$id = (int)($_GET['id'] ?? 0);
$loaded = $id > 0 ? office_part_get(office_db(), $id) : null;

$errors = [];
$uploadErrors = [];
$values = $loaded ? office_part_load_for_edit($loaded) : [];
$updated = false;

if ($loaded !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values = is_array($_POST) ? $_POST : [];
    if (!isset($values['legal_status']) || !is_array($values['legal_status'])) {
        $values['legal_status'] = [];
    }
    if (!office_is_logged_in()) {
        office_redirect('login.php');
    } elseif (!office_csrf_valid()) {
        $errors[] = 'نشست منقضی شده است؛ لطفاً دوباره تلاش کنید.';
    } else {
        [$newRow, $errors, $uploadErrors] = office_update_partnership(
            office_db(), $id, is_array($_POST) ? $_POST : [], is_array($_FILES) ? $_FILES : []
        );
        if (!$errors && $newRow) {
            $updated = true;
            $loaded = office_part_get(office_db(), $id);
            $values = $loaded ? office_part_load_for_edit($loaded) : [];
        }
    }
}

$statuses = office_part_statuses();
office_shell_open('partnerships.php', 'ویرایش مشارکت');
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
    <div class="of-panel"><b>درخواست یافت نشد.</b><p class="of-muted">شناسهٔ «<?= office_h((string)($id > 0 ? $id : ($_GET['id'] ?? ''))) ?>» در مشارکت‌ها وجود ندارد.</p><p><a class="of-btn ghost" href="partnerships.php">→ بازگشت به لیست</a></p></div>
<?php else: ?>
    <?php if ($updated): ?>
        <div class="of-alert ok">تغییرات ذخیره شد.<?php if ($uploadErrors): ?><br>اما <?= office_fa((string)count($uploadErrors)) ?> فایل ذخیره نشد (جزئیات زیر).<?php endif; ?>
            <br><br>
            <a class="of-btn" href="partnerships.php?id=<?= $id ?>">مشاهده جزئیات</a>
            <a class="of-btn ghost" href="partnerships.php">بازگشت به لیست</a>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="of-alert err"><b>لطفاً خطاهای زیر را اصلاح کنید:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($errors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($uploadErrors && !$updated): ?>
        <div class="of-alert warn"><b>هشدار فایل‌ها:</b><ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($uploadErrors as $e): ?><li><?= office_h($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php
    $oldPhotos = office_part_json_list($loaded['photos'] ?? '[]');
    $oldOther = office_part_json_list($loaded['doc_other'] ?? '[]');
    $oldSingles = array_filter([
        'سند' => (string)($loaded['doc_deed'] ?? ''),
        'پروانه' => (string)($loaded['doc_permit'] ?? ''),
        'پایان‌کار' => (string)($loaded['doc_endjob'] ?? ''),
    ], static fn($v) => $v !== '');
    ?>
    <?php if ($oldPhotos || $oldOther || $oldSingles): ?>
    <div class="of-panel"><b>فایل‌های فعلی</b> <span class="of-muted">(فایل جدید به این‌ها اضافه می‌شود)</span><p>
        <?php foreach ($oldPhotos as $i => $p): ?><a class="of-btn ghost" style="padding:4px 10px" href="../<?= office_h($p) ?>" target="_blank" rel="noopener">عکس <?= office_fa((string)($i + 1)) ?></a> <?php endforeach; ?>
        <?php foreach ($oldSingles as $lb => $p): ?><a class="of-btn ghost" style="padding:4px 10px" href="../<?= office_h($p) ?>" target="_blank" rel="noopener"><?= office_h($lb) ?></a> <?php endforeach; ?>
        <?php foreach ($oldOther as $i => $p): ?><a class="of-btn ghost" style="padding:4px 10px" href="../<?= office_h($p) ?>" target="_blank" rel="noopener">مدرک <?= office_fa((string)($i + 1)) ?></a> <?php endforeach; ?>
    </p></div>
    <?php endif; ?>

    <form method="post" action="partnership-edit.php?id=<?= $id ?>" enctype="multipart/form-data">
        <?= office_csrf_field() ?>
        <?php foreach (office_partnership_sections() as $secTitle => $fields): ?>
            <div class="of-sec"><h3><?= office_h($secTitle) ?></h3><div class="of-fgrid">
                <?php foreach ($fields as $f): ?><?= office_render_field($f, $values) ?><?php endforeach; ?>
            </div></div>
        <?php endforeach; ?>
        <div class="of-sec"><h3>وضعیت</h3><div class="of-fgrid">
            <div class="of-field"><label>وضعیت درخواست</label>
                <select name="status">
                    <?php foreach ($statuses as $k => $lb): ?><option value="<?= office_h($k) ?>"<?= (string)($values['status'] ?? '') === $k ? ' selected' : '' ?>><?= office_h($lb) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div></div>
        <div class="of-actions" style="display:flex;gap:8px;margin:12px 0">
            <button class="of-btn" type="submit">ذخیره تغییرات</button>
            <a class="of-btn ghost" href="partnerships.php?id=<?= $id ?>">انصراف</a>
        </div>
    </form>
<?php endif; ?>

<?php office_shell_close(); ?>
