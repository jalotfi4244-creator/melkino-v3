<?php
/** Melkino V2 — requests shell (same ids/fields/options as legacy; CSP-safe). */
?>
<div class="mx-page-head"><h1>📋 درخواست‌های من</h1>
<p class="mx-small">درخواست‌های ثبت‌شده، ویرایش و فایل‌های مناسب شما</p></div>
<script type="application/json" id="mxReqData"><?= json_encode(['tg' => (string)($telegram_id ?? '')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<div id="notice" class="mx-notice mx-notice--info" hidden></div>
<div id="grid" class="mx-shell-grid mx-mt-4"></div>

<div class="mx-modal" id="editModal" aria-hidden="true" hidden>
  <div class="mx-modal__box" role="dialog" aria-modal="true" aria-labelledby="mxEditTitle">
    <h3 id="mxEditTitle">ویرایش درخواست ملک</h3>
    <form id="editForm">
      <input type="hidden" name="id" id="editId">
      <input type="hidden" name="telegram_id" value="<?= e((string)($telegram_id ?? '')) ?>">
      <div class="mx-calc-grid">
        <div class="mx-field"><label for="editTransaction">نوع معامله</label>
          <select class="mx-select" name="transaction_type" id="editTransaction"><option value="فروش">فروش</option><option value="رهن کامل">رهن کامل</option><option value="رهن و اجاره">رهن و اجاره</option><option value="اجاره">اجاره</option><option value="پیش‌فروش">پیش‌فروش</option></select></div>
        <div class="mx-field"><label for="editProperty">نوع ملک</label><input class="mx-input" name="property_type" id="editProperty" type="text"></div>
        <div class="mx-field"><label for="editLocation">محله / منطقه</label><input class="mx-input" name="location" id="editLocation" type="text"></div>
        <div class="mx-field"><label for="editMinArea">حداقل متراژ</label><input class="mx-input mx-num money" name="min_area" id="editMinArea" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMaxArea">حداکثر متراژ</label><input class="mx-input mx-num money" name="max_area" id="editMaxArea" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMinPrice">حداقل قیمت</label><input class="mx-input mx-num money" name="min_price" id="editMinPrice" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMaxPrice">حداکثر قیمت</label><input class="mx-input mx-num money" name="max_price" id="editMaxPrice" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMinDeposit">حداقل ودیعه</label><input class="mx-input mx-num money" name="min_deposit" id="editMinDeposit" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMaxDeposit">حداکثر ودیعه</label><input class="mx-input mx-num money" name="max_deposit" id="editMaxDeposit" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMinRent">حداقل اجاره</label><input class="mx-input mx-num money" name="min_rent" id="editMinRent" inputmode="numeric"></div>
        <div class="mx-field"><label for="editMaxRent">حداکثر اجاره</label><input class="mx-input mx-num money" name="max_rent" id="editMaxRent" inputmode="numeric"></div>
        <div class="mx-field"><label for="editDateNeeded">تاریخ نیاز</label><input class="mx-input" name="date_needed" id="editDateNeeded" type="date"></div>
        <div class="mx-field"><label for="editUrgency">فوریت</label>
          <select class="mx-select" name="urgency" id="editUrgency"><option value="فوری">فوری</option><option value="عادی">عادی</option><option value="کم‌فوری">کم‌فوری</option></select></div>
        <div class="mx-field"><label for="editRahn"><input type="checkbox" name="rahn_kamal" id="editRahn"> رهن کامل</label></div>
        <div class="mx-field"><label for="editNotKeyed"><input type="checkbox" name="is_not_keyed" id="editNotKeyed"> فایل کلیدی نباشد</label></div>
      </div>
      <div class="mx-flex mx-mt-4" style="gap:8px">
        <button type="button" class="mx-btn mx-btn--ghost" data-close-edit>انصراف</button>
        <button type="submit" class="mx-btn mx-btn--primary" id="saveRequestBtn">ذخیره تغییرات</button>
      </div>
    </form>
  </div>
</div>
