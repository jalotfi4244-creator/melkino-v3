<?php
/** Melkino V2 — Home (spec §10): search-first. Vars: $identity,$latest,$for_you,$promos */
use Melkino\UI\Card\PropertyCardRenderer;
use Melkino\UI\Icons\IconRegistry;
?>
<section class="mx-hero" aria-label="جستجوی ملک">
  <div>
    <h1>دنبال چه ملکی هستید؟</h1>
    <p>خرید، فروش، رهن و اجاره — هزاران آگهی به‌روز</p>
  </div>
  <form class="mx-hero__card" action="properties.php" method="get" role="search">
    <div class="mx-tx-tabs" data-tx-tabs role="tablist" aria-label="نوع معامله">
      <button type="button" data-tx-tab="فروش" class="is-active">خرید / فروش</button>
      <button type="button" data-tx-tab="اجاره">اجاره</button>
      <button type="button" data-tx-tab="رهن">رهن</button>
    </div>
    <input type="hidden" name="tx" value="فروش">
    <div class="mx-hero__grid">
      <input class="mx-input" type="search" name="district" placeholder="شهر، منطقه یا محله…" aria-label="شهر، منطقه یا محله" autocomplete="off">
      <select class="mx-select" name="property_type" aria-label="نوع ملک">
        <option value="">نوع ملک</option>
        <option>آپارتمان</option><option>خانه</option><option>ویلا</option>
        <option>زمین</option><option>باغ</option><option>تجاری</option><option>اداری</option>
      </select>
      <select class="mx-select" name="max_price" aria-label="محدوده قیمت">
        <option value="">محدوده قیمت</option>
        <option value="2000000000">تا ۲ میلیارد</option>
        <option value="5000000000">تا ۵ میلیارد</option>
        <option value="10000000000">تا ۱۰ میلیارد</option>
        <option value="20000000000">تا ۲۰ میلیارد</option>
      </select>
      <button class="mx-btn mx-btn--primary" type="submit"><?= IconRegistry::svg('search', 18) ?><span>جستجوی ملک</span></button>
    </div>
  </form>
</section>

<section class="mx-section" aria-label="دسترسی سریع">
  <div class="mx-quick">
    <a href="properties.php?property_type=آپارتمان"><?= IconRegistry::svg('building', 26) ?><span>آپارتمان</span></a>
    <a href="properties.php?property_type=ویلا"><?= IconRegistry::svg('villa', 26) ?><span>خانه و ویلا</span></a>
    <a href="properties.php?property_type=زمین"><?= IconRegistry::svg('land', 26) ?><span>زمین</span></a>
    <a href="properties.php?property_type=تجاری"><?= IconRegistry::svg('store', 26) ?><span>تجاری</span></a>
    <a href="properties.php?property_type=اداری"><?= IconRegistry::svg('office', 26) ?><span>اداری</span></a>
  </div>
</section>

<?php if (!empty($for_you)): ?>
<section class="mx-section" aria-label="برای شما">
  <div class="mx-section__head">
    <div><h2>برای شما</h2><p class="mx-small">ملک‌هایی که به نیاز شما نزدیک‌اند</p></div>
    <a class="mx-link" href="my-request-matches.php">مشاهده همه</a>
  </div>
  <?= PropertyCardRenderer::grid($for_you) ?>
</section>
<?php endif; ?>

<section class="mx-section" aria-label="آخرین آگهی‌ها">
  <div class="mx-section__head">
    <h2>آخرین آگهی‌ها</h2>
    <a class="mx-link" href="properties.php">مشاهده همه</a>
  </div>
  <?= PropertyCardRenderer::grid($latest, ['empty_title' => 'هنوز آگهی منتشر نشده است', 'empty_text' => '', 'empty_action_url' => '', 'empty_action_label' => '']) ?>
</section>

<section class="mx-section"><div class="mx-card-surface mx-text-center">
  <h2>ملکی که می‌خواهی پیدا نکردی؟</h2>
  <p class="mx-small mx-mb-4">نیازت را ثبت کن؛ به‌محض پیدا شدن ملک مناسب خبرت می‌کنیم.</p>
  <a class="mx-btn mx-btn--primary" href="property-request.php">درخواست ملک</a>
</div></section>
