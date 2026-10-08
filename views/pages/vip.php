<?php
/** Melkino V2 — VIP files (same rows + same shared renderer; V2 shell + filters). */
$ads = $ads ?? [];
?>
<div class="mx-page-head"><h1>فایل‌های VIP</h1>
<p class="mx-small">مجموعه‌ای از فایل‌های منتخب و ویژه که توسط کارشناسان ملکینو برای شما انتخاب شده‌اند.</p></div>
<div class="mx-flex mx-mb-4" style="gap:10px;flex-wrap:wrap;align-items:end">
  <div class="mx-field" style="min-width:150px"><label for="vipPropertyFilter">نوع ملک</label>
    <select class="mx-select" id="vipPropertyFilter">
      <option value="">همه املاک</option><option>آپارتمان</option><option>ویلا</option>
      <option>تجاری</option><option>زمین</option><option>باغ</option><option>اداری</option>
    </select></div>
  <div class="mx-field" style="min-width:150px"><label for="vipTransactionFilter">نوع معامله</label>
    <select class="mx-select" id="vipTransactionFilter">
      <option value="">همه معاملات</option><option value="فروش">خرید و فروش</option>
      <option value="پیش فروش">پیش فروش</option><option value="اجاره">رهن و اجاره</option>
      <option value="رهن کامل">رهن کامل</option>
    </select></div>
  <button type="button" class="mx-btn mx-btn--ghost mx-btn--sm" id="vipFilterReset">حذف فیلتر</button>
  <span class="mx-small mx-muted" id="mxVipCount"><?= fa(count($ads)) ?> فایل VIP</span>
</div>
<?php if (!$ads): ?>
<?= melkinoPartial('empty-state.php', ['icon' => 'star', 'title' => 'هنوز فایل VIP ثبت نشده است.']) ?>
<?php else: ?>
<div id="mxVipAdsList"></div>
<div class="mx-notice mx-notice--info mx-mt-4" id="mxVipEmpty" hidden>برای فیلتر انتخاب‌شده فایل VIP موجود نیست.</div>
<script type="application/json" id="mxVipData"><?= json_encode(array_values($ads), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
