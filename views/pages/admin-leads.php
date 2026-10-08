<?php
/**
 * Melkino V2 — admin leads (standalone; head/body VERBATIM, style extracted).
 */
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پیگیری لید و پیامک — ملکینو</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="design-pro.css">
    <?php require_once MELKINO_ROOT . '/csrf-shim.php'; ?>
    
    <?= \Melkino\Support\Assets::css('assets/css/admin-leads-legacy.css') ?>
</head>
<body>
<div class="leads-wrap">
    <div class="leads-head">
        <h1>پیگیری لید و پیامک</h1>
        <a class="btn-sec" href="admin-panel.php">بازگشت به پنل ادمین</a>
    </div>
    <p class="muted">از همین پنل پیامک سیستم، فایل‌های منطبق با درخواست را برای مشتری بفرست یا فقط تعداد تطبیق را اطلاع بده. بازدید تکراری آگهی (۳ بار، ۴ بار، …) هم برای پیگیری اینجاست.</p>

    <div class="admin-card">
        <div class="card-header"><span class="card-title">درخواست‌های دارای تطبیق</span><span id="matchCount" class="muted"></span></div>
        <div class="leads-body" id="matchList">در حال بارگذاری…</div>
    </div>

    <div class="admin-card">
        <div class="card-header"><span class="card-title">بازدید تکراری آگهی</span></div>
        <div class="leads-body">
            <div class="leads-tools">
                <label>حداقل بازدید
                    <select id="minViews">
                        <option value="2">۲ بار</option>
                        <option value="3" selected>۳ بار</option>
                        <option value="4">۴ بار</option>
                        <option value="5">۵ بار</option>
                        <option value="8">۸ بار</option>
                    </select>
                </label>
                <button type="button" class="btn-sec" id="reloadViewers">بارگذاری دوباره</button>
            </div>
            <div id="viewerList">در حال بارگذاری…</div>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header"><span class="card-title">آخرین پیامک‌های لید</span></div>
        <div class="leads-body" id="logList">در حال بارگذاری…</div>
    </div>
</div>
<script src="admin-leads.js"></script>
</body>
</html>
