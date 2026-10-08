<?php
/**
 * Melkino V2 — admin partnership console (standalone; head/body VERBATIM, style/script extracted).
 * Vars: $statuses, $open, $csrf.
 */
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<title>مدیریت درخواست‌های مشارکت در ساخت — ملکینو</title>

<?= \Melkino\Support\Assets::css('assets/css/admin-partnership-legacy.css') ?>
<script type="application/json" id="mxAdminPartData"><?= json_encode(['statuses' => ($statuses ?? []), 'open' => (int)($open ?? 0)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>

<body>
    <div id="mkpApiErr" style="display:none;max-width:1100px;margin:10px auto 0;padding:12px 16px;border-radius:12px;background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;font-size:13px;line-height:2;"></div>
<div class="top">
        <h1>🤝 درخواست‌های مشارکت در ساخت <small style="font-weight:600;font-size:11px;color:var(--text-secondary,#667);opacity:.8;">MKP-ADM/2026-09-30b</small></h1>
        <span class="spacer"></span>
        <input class="inp" id="q" placeholder="جستجو: کد، عنوان، شهر، تلفن...">
        <button class="btn btn-p" data-act="load-list">جستجو</button>
        <a class="btn btn-g" href="admin-panel.php">بازگشت به پنل</a>
    </div>

    <div class="wrap">
        <div class="filters" id="filters">
            <button class="chip on" data-st="">همه</button>
            <?php foreach ($statuses as $k => $label): ?>
                <button class="chip" data-st="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> <span id="cnt-<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"></span></button>
            <?php endforeach; ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>کد</th><th>عنوان</th><th>موقعیت</th><th>سهم (مالک/سازنده)</th>
                    <th>تلفن</th><th>کامل بودن</th><th>وضعیت</th><th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody id="rows"><tr><td colspan="8" class="empty">در حال بارگذاری...</td></tr></tbody>
        </table>
    </div>

    <div class="modal-bg" id="modalBg" >
        <div class="modal" id="modalBox"></div>
    </div>


<?= \Melkino\Support\Assets::js('assets/js/admin-partnership.js', false) ?>
</body>
</html>
