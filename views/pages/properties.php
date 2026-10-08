<?php
/** Melkino V2 — listing (spec §12). Vars: $identity,$result,$page,$total_pages,$share_query */
use Melkino\UI\Card\PropertyCardRenderer;
use Melkino\UI\Icons\IconRegistry;

$filters = $result['filters'] ?? [];
$base = 'properties.php?' . ($share_query !== '' ? $share_query . '&' : '');
function mk_qs(array $f, array $over = []): string {
    $f = array_merge($f, $over);
    foreach ($f as $k => $v) { if ($v === '' || $v === null) unset($f[$k]); }
    return 'properties.php' . ($f ? '?' . http_build_query($f) : '');
}
$tx = (string)($filters['tx'] ?? '');
$pt = (string)($filters['property_type'] ?? '');
?>
<div class="mx-page-head"><h1>جستجوی ملک</h1></div>

<form class="mx-listing-bar" action="properties.php" method="get" role="search">
  <?php foreach (['tx','property_type','sort'] as $k): if (!empty($filters[$k])): ?>
    <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$filters[$k]) ?>">
  <?php endif; endforeach; ?>
  <input class="mx-input" type="search" name="district" value="<?= e((string)($filters['district'] ?? '')) ?>" placeholder="شهر، منطقه یا محله…" aria-label="جستجو">
  <button class="mx-btn mx-btn--primary" type="submit"><?= IconRegistry::svg('search', 18) ?></button>
  <button class="mx-btn mx-btn--ghost mx-show-md" type="button" data-modal-open="mkFilters">فیلترها</button>
</form>

<div class="mx-chips mx-mb-4" role="group" aria-label="فیلتر سریع">
  <a class="mx-chip <?= $tx === '' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['tx' => ''])) ?>">همه</a>
  <a class="mx-chip <?= $tx === 'فروش' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['tx' => 'فروش'])) ?>">خرید</a>
  <a class="mx-chip <?= $tx === 'اجاره' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['tx' => 'اجاره'])) ?>">اجاره</a>
  <a class="mx-chip <?= $tx === 'رهن' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['tx' => 'رهن'])) ?>">رهن</a>
  <a class="mx-chip <?= $pt === 'آپارتمان' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['property_type' => 'آپارتمان'])) ?>">آپارتمان</a>
  <a class="mx-chip <?= $pt === 'ویلا' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['property_type' => 'ویلا'])) ?>">ویلا</a>
  <a class="mx-chip <?= $pt === 'زمین' ? 'is-active' : '' ?>" href="<?= e(mk_qs($filters, ['property_type' => 'زمین'])) ?>">زمین</a>
</div>

<div class="mx-listing-meta">
  <span><?= fa(number_format((int)$result['total'])) ?> آگهی</span>
  <span class="mx-flex mx-gap-2 mx-items-center">
    <button class="mx-btn mx-btn--ghost mx-btn--sm mx-hide-md" type="button" data-modal-open="mkFilters"><?= IconRegistry::svg('filter', 16) ?><span>فیلترها</span></button>
    <button class="mx-btn mx-btn--ghost mx-btn--sm" type="button" data-save-search="<?= e($share_query) ?>" title="ذخیره این جستجو"><?= IconRegistry::svg('bell', 16) ?><span>ذخیره جستجو</span></button>
    <form action="properties.php" method="get" class="mx-flex">
      <?php foreach ($filters as $k => $v): if ($k !== 'sort' && $v !== ''): ?>
        <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
      <?php endif; endforeach; ?>
      <select class="mx-select" name="sort" data-sort-select aria-label="مرتب‌سازی" style="width:auto;min-height:40px">
        <?php $sort = (string)($filters['sort'] ?? 'newest'); ?>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>جدیدترین</option>
        <option value="cheapest" <?= $sort === 'cheapest' ? 'selected' : '' ?>>ارزان‌ترین</option>
        <option value="expensive" <?= $sort === 'expensive' ? 'selected' : '' ?>>گران‌ترین</option>
        <option value="area_desc" <?= $sort === 'area_desc' ? 'selected' : '' ?>>بزرگ‌ترین متراژ</option>
      </select>
    </form>
  </span>
</div>

<?= PropertyCardRenderer::grid($result['items']) ?>
<?= melkinoPartial('pagination.php', ['page' => $page, 'total_pages' => $total_pages, 'base' => $base]) ?>

<div class="mx-modal" id="mkFilters" role="dialog" aria-modal="true" aria-label="فیلترها">
  <div class="mx-modal__backdrop"></div>
  <form class="mx-modal__dialog" action="properties.php" method="get">
    <div class="mx-modal__header"><h3>فیلترها</h3>
      <button type="button" class="mx-icon-btn" data-modal-close aria-label="بستن"><?= IconRegistry::svg('x', 20) ?></button>
    </div>
    <div class="mx-modal__body">
      <div class="mx-field"><label for="f-tx">نوع معامله</label>
        <select class="mx-select" id="f-tx" name="tx">
          <option value="">همه</option>
          <?php foreach (['فروش','اجاره','رهن'] as $o): ?>
          <option <?= $tx === $o ? 'selected' : '' ?>><?= e($o) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="mx-field"><label for="f-pt">نوع ملک</label>
        <select class="mx-select" id="f-pt" name="property_type">
          <option value="">همه</option>
          <?php foreach (['آپارتمان','خانه','ویلا','زمین','باغ','تجاری','اداری'] as $o): ?>
          <option <?= $pt === $o ? 'selected' : '' ?>><?= e($o) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="mx-field"><label for="f-minp">حداقل قیمت (تومان)</label>
        <input class="mx-input mx-num" id="f-minp" type="number" name="min_price" min="0" value="<?= e((string)($filters['min_price'] ?? '')) ?>"></div>
      <div class="mx-field"><label for="f-maxp">حداکثر قیمت (تومان)</label>
        <input class="mx-input mx-num" id="f-maxp" type="number" name="max_price" min="0" value="<?= e((string)($filters['max_price'] ?? '')) ?>"></div>
      <div class="mx-field"><label for="f-mina">حداقل متراژ</label>
        <input class="mx-input mx-num" id="f-mina" type="number" name="min_area" min="0" value="<?= e((string)($filters['min_area'] ?? '')) ?>"></div>
      <div class="mx-field"><label for="f-rooms">تعداد خواب</label>
        <input class="mx-input mx-num" id="f-rooms" type="number" name="rooms" min="0" max="20" value="<?= e((string)($filters['rooms'] ?? '')) ?>"></div>
    </div>
    <div class="mx-modal__footer">
      <a class="mx-btn mx-btn--ghost" href="properties.php">حذف فیلترها</a>
      <button class="mx-btn mx-btn--primary" type="submit">اعمال فیلترها</button>
    </div>
  </form>
</div>
