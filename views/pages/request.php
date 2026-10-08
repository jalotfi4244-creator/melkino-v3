<?php
/**
 * Melkino V2 — request wizard (spec §23). Posts IDENTICAL field names AND values to
 * property-request.php (POST) — the legacy processor validates/matches/stores unchanged.
 * Exact contract: transaction_type ∈ {فروش، پیش فروش، اجاره، سرمایه‌گذاری}، property_type ∈
 * {آپارتمان،ویلا،زمین،باغ،اداری،تجاری}، priority_1..3 + no_priority، location، map_poly،
 * min/max_area، min/max_price، min/max_deposit، min/max_rent، min/max_age، amenities[] (by NAME)،
 * urgency ∈ {فوری،ظرف یک‌ماه،بدون عجله}، date_needed، gender، last_name، additional_notes،
 * rahn_kamal، is_not_keyed، request_form_submit=1، csrf_token.
 */
use Melkino\UI\Icons\IconRegistry;
?>
<div class="mx-page-head"><h1>درخواست ملک</h1><p class="mx-small">در ۸ قدم کوتاه، دقیقاً بگویید چه می‌خواهید.</p></div>
<?php if (!empty($mine)): ?>
<div class="mx-req-summary mx-mb-4"><b>درخواست‌های قبلی شما:</b>
  <?php foreach ($mine as $m): ?>
  <a class="mx-chip mx-mt-2" href="my-request-matches.php?request_id=<?= (int)$m['id'] ?>"><?= e((string)($m['tracking_code'] ?? '')) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<form action="property-request.php" method="post" data-guard>
<?= \Melkino\Core\Csrf::field() ?>
<input type="hidden" name="request_form_submit" value="1">
<input type="hidden" name="date_needed_gregorian" value="">
<div data-wizard data-autosave="mx_req_draft_v2">
  <div class="mx-steps" data-steps aria-hidden="true"><span class="is-current"></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <p class="mx-tiny mx-mb-4" data-autosave-indicator></p>

  <!-- STEP 1: transaction -->
  <section data-step>
    <h2 class="mx-h3 mx-mb-4">دنبال چه نوع معامله‌ای هستید؟</h2>
    <input type="hidden" name="transaction_type" required>
    <div class="mx-options" data-option-group-required>
      <button type="button" class="mx-option" data-option="فروش" data-option-name="transaction_type"><?= IconRegistry::svg('key', 26) ?><span>خرید (فروش)</span></button>
      <button type="button" class="mx-option" data-option="پیش فروش" data-option-name="transaction_type"><?= IconRegistry::svg('doc', 26) ?><span>پیش‌فروش</span></button>
      <button type="button" class="mx-option" data-option="اجاره" data-option-name="transaction_type"><?= IconRegistry::svg('building', 26) ?><span>اجاره</span></button>
      <button type="button" class="mx-option" data-option="سرمایه‌گذاری" data-option-name="transaction_type"><?= IconRegistry::svg('chart', 26) ?><span>سرمایه‌گذاری</span></button>
    </div>
    <div class="mx-wizard__nav"><span></span><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 2: property type -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">چه نوع ملکی؟</h2>
    <input type="hidden" name="property_type">
    <div class="mx-options">
      <button type="button" class="mx-option" data-option="آپارتمان" data-option-name="property_type"><?= IconRegistry::svg('building', 26) ?><span>آپارتمان</span></button>
      <button type="button" class="mx-option" data-option="ویلا" data-option-name="property_type"><?= IconRegistry::svg('villa', 26) ?><span>ویلا</span></button>
      <button type="button" class="mx-option" data-option="زمین" data-option-name="property_type"><?= IconRegistry::svg('land', 26) ?><span>زمین</span></button>
      <button type="button" class="mx-option" data-option="باغ" data-option-name="property_type"><?= IconRegistry::svg('land', 26) ?><span>باغ</span></button>
      <button type="button" class="mx-option" data-option="تجاری" data-option-name="property_type"><?= IconRegistry::svg('store', 26) ?><span>تجاری</span></button>
      <button type="button" class="mx-option" data-option="اداری" data-option-name="property_type"><?= IconRegistry::svg('office', 26) ?><span>اداری</span></button>
    </div>
    <p class="mx-small mx-mt-4">برای سرمایه‌گذاری می‌توانید اولویت‌بندی کنید:</p>
    <div class="mx-field mx-mt-2"><label for="rq-np"><input type="checkbox" id="rq-np" name="no_priority" value="1" checked> بدون اولویت (همه نوع ملک)</label></div>
    <?php foreach ([1 => 'اولویت ۱', 2 => 'اولویت ۲', 3 => 'اولویت ۳'] as $pn => $pl): ?>
    <div class="mx-field"><label for="rq-p<?= $pn ?>"><?= $pl ?></label>
      <select class="mx-select" id="rq-p<?= $pn ?>" name="priority_<?= $pn ?>"><option value="">انتخاب کنید</option><option>آپارتمان</option><option>ویلا</option><option>زمین</option><option>باغ</option><option>اداری</option><option>تجاری</option></select></div>
    <?php endforeach; ?>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 3: location -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">کجا؟</h2>
    <div class="mx-field"><label for="rq-loc">شهر، منطقه یا محله</label>
      <input class="mx-input" id="rq-loc" name="location" required placeholder="مثلاً شاهرود، خیابان…"></div>
    <input type="hidden" name="map_poly" value="">
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 4: area/age -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">متراژ و سن بنا</h2>
    <div class="mx-field"><label for="rq-mina">حداقل متراژ</label><input class="mx-input mx-num" id="rq-mina" type="number" name="min_area" min="0"></div>
    <div class="mx-field"><label for="rq-maxa">حداکثر متراژ</label><input class="mx-input mx-num" id="rq-maxa" type="number" name="max_area" min="0"></div>
    <div class="mx-field"><label for="rq-minage">حداقل سن بنا (سال)</label><input class="mx-input mx-num" id="rq-minage" type="number" name="min_age" min="0"></div>
    <div class="mx-field"><label for="rq-maxage">حداکثر سن بنا (سال)</label><input class="mx-input mx-num" id="rq-maxage" type="number" name="max_age" min="0"></div>
    <div class="mx-field"><label for="rq-keyed"><input type="checkbox" id="rq-keyed" name="is_not_keyed" value="1"> کلیدنخورده / نوساز باشد</label></div>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 5: budget -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">بودجه (تومان)</h2>
    <div class="mx-field"><label for="rq-minp">حداقل قیمت</label><input class="mx-input mx-num" id="rq-minp" type="number" name="min_price" min="0"></div>
    <div class="mx-field"><label for="rq-maxp">حداکثر قیمت</label><input class="mx-input mx-num" id="rq-maxp" type="number" name="max_price" min="0"></div>
    <div class="mx-field"><label for="rq-mind">حداقل ودیعه (رهن/اجاره)</label><input class="mx-input mx-num" id="rq-mind" type="number" name="min_deposit" min="0"></div>
    <div class="mx-field"><label for="rq-maxd">حداکثر ودیعه (رهن/اجاره)</label><input class="mx-input mx-num" id="rq-maxd" type="number" name="max_deposit" min="0"></div>
    <div class="mx-field"><label for="rq-minr">حداقل اجاره ماهانه</label><input class="mx-input mx-num" id="rq-minr" type="number" name="min_rent" min="0"></div>
    <div class="mx-field"><label for="rq-maxr">حداکثر اجاره ماهانه</label><input class="mx-input mx-num" id="rq-maxr" type="number" name="max_rent" min="0"></div>
    <div class="mx-field"><label for="rq-rk"><input type="checkbox" id="rq-rk" name="rahn_kamal" value="1"> رهن کامل هم باشد</label></div>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 6: amenities + urgency -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">امکانات و فوریت</h2>
    <div class="mx-field"><label>امکانات مهم</label>
      <div class="mx-flex" style="flex-wrap:wrap;gap:8px">
        <?php foreach (($amenities ?? []) as $a): ?>
        <label class="mx-chip"><input type="checkbox" name="amenities[]" value="<?= e((string)$a['name']) ?>"> <?= e((string)$a['name']) ?></label>
        <?php endforeach; ?>
      </div></div>
    <div class="mx-field"><label for="rq-urg">فوریت</label>
      <select class="mx-select" id="rq-urg" name="urgency"><option value="">عادی</option><option>فوری</option><option>ظرف یک‌ماه</option><option>بدون عجله</option></select></div>
    <div class="mx-field"><label for="rq-date">تاریخ موردنیاز (اختیاری)</label><input class="mx-input" id="rq-date" name="date_needed" placeholder="مثلاً ۱۴۰۵/۰۲/۱۵"></div>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>ادامه</button></div>
  </section>

  <!-- STEP 7: contact review -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">مشخصات تماس</h2>
    <p class="mx-small mx-mb-4">نام و شماره از حساب تأییدشده شما خوانده می‌شود.</p>
    <div class="mx-field"><label for="rq-g">جنسیت</label>
      <select class="mx-select" id="rq-g" name="gender"><option value="">—</option><option>آقا</option><option>خانم</option></select></div>
    <div class="mx-field"><label for="rq-ln">نام خانوادگی</label>
      <input class="mx-input" id="rq-ln" name="last_name" value="<?= e((string)($identity['name'] ?? '')) ?>"></div>
    <div class="mx-field"><label for="rq-notes">توضیح اضافه</label>
      <textarea class="mx-textarea" id="rq-notes" name="additional_notes"></textarea></div>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="button" class="mx-btn mx-btn--primary" data-next>بازبینی</button></div>
  </section>

  <!-- STEP 8: review + submit -->
  <section data-step hidden>
    <h2 class="mx-h3 mx-mb-4">بازبینی و ثبت</h2>
    <p class="mx-small mx-mb-4">با ثبت درخواست، کد پیگیری دریافت می‌کنید و ملک‌های مناسب به شما معرفی می‌شوند.</p>
    <div class="mx-wizard__nav"><button type="button" class="mx-btn mx-btn--ghost" data-prev>قبلی</button><button type="submit" class="mx-btn mx-btn--primary">ثبت درخواست</button></div>
  </section>
</div>
</form>
