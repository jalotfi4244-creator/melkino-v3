<?php
/**
 * Melkino V2 — register step 1 (contact + deal + property type).
 * Storage contract IDENTICAL to legacy: sessionStorage reg_telegram_id/reg_gender/
 * reg_last_name/reg_phone/reg_transaction_type + localStorage melkino_user_phone,
 * same profile-sync.php update_contact call, same register-*.php links.
 */
$identity = $identity ?? [];
?>
<div class="mx-page-head"><h1>ثبت ملک</h1><p class="mx-small">مرحله <b id="stepCounter">۱</b> از ۳</p></div>
<form id="step1Form" novalidate>
  <section data-rstep="1">
    <h2 class="mx-h3 mx-mb-4">اطلاعات تماس</h2>
    <p class="mx-small mx-mb-4">لطفاً اطلاعات خود را وارد کنید.</p>
    <input type="hidden" id="regTelegramId" value="">
    <div class="mx-field"><label>جنسیت</label>
      <div class="mx-options" id="regGender">
        <button type="button" class="mx-option is-selected" data-value="آقا">آقا</button>
        <button type="button" class="mx-option" data-value="خانم">خانم</button>
      </div>
      <input type="hidden" id="genderInput" value="آقا"></div>
    <div class="mx-field"><label for="regLastName">نام خانوادگی</label>
      <input class="mx-input" id="regLastName" placeholder="نام خانوادگی خود را وارد کنید" value="<?= e((string)($identity['name'] ?? '')) ?>" autocomplete="family-name"></div>
    <div class="mx-field"><label for="regPhone">شماره تماس</label>
      <div class="mx-flex" style="gap:8px">
        <input class="mx-input mx-num" id="regPhone" inputmode="tel" placeholder="مثلاً ۰۹۱۲۳۴۵۶۷۸۹" value="<?= e((string)($identity['phone'] ?? '')) ?>" autocomplete="tel">
        <button type="button" class="mx-btn mx-btn--ghost" id="requestContactBtn">دریافت شماره</button>
      </div></div>
  </section>
  <section data-rstep="2" hidden>
    <h2 class="mx-h3 mx-mb-4">نوع معامله</h2>
    <p class="mx-small mx-mb-4">لطفاً نوع معامله‌ی مورد نظر خود را انتخاب کنید.</p>
    <div class="mx-field"><label>نوع معامله</label>
      <div class="mx-options" id="transactionType">
        <button type="button" class="mx-option is-selected" data-value="فروش">خرید و فروش</button>
        <button type="button" class="mx-option" data-value="پیش فروش">پیش فروش</button>
        <button type="button" class="mx-option" data-value="اجاره">اجاره</button>
        <button type="button" class="mx-option" data-value="مشارکت در ساخت">مشارکت در ساخت</button>
      </div>
      <input type="hidden" id="transactionTypeInput" value="فروش"></div>
  </section>
  <section data-rstep="3" hidden>
    <h2 class="mx-h3 mx-mb-4">نوع ملک</h2>
    <p class="mx-small mx-mb-4">نوع ملک خود را انتخاب کنید.</p>
    <div class="mx-options mx-options--links">
      <a class="mx-option" href="register-apartment.php">آپارتمان</a>
      <a class="mx-option" href="register-villa.php">ویلا</a>
      <a class="mx-option" href="register-land.php">زمین</a>
      <a class="mx-option" href="register-garden.php">باغ</a>
      <a class="mx-option" href="register-office.php">اداری</a>
      <a class="mx-option" href="register-commercial.php">تجاری</a>
    </div>
  </section>
  <div class="mx-wizard__nav">
    <button type="button" class="mx-btn mx-btn--ghost" id="prevBtn" hidden>مرحله قبل</button>
    <button type="button" class="mx-btn mx-btn--primary" id="nextBtn">مرحله بعد</button>
  </div>
</form>
