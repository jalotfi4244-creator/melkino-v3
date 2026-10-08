<?php
/** Melkino V2 — favorites (spec §20). Vars: $identity,$items,$tx */
use Melkino\UI\Card\PropertyCardRenderer;
?>
<div class="mx-page-head"><h1>ملک‌های ذخیره‌شده من</h1></div>
<div class="mx-chips mx-mb-4" role="group" aria-label="فیلتر معامله">
  <a class="mx-chip <?= $tx === '' ? 'is-active' : '' ?>" href="favorites.php">همه</a>
  <a class="mx-chip <?= $tx === 'فروش' ? 'is-active' : '' ?>" href="favorites.php?tx=فروش">فروش</a>
  <a class="mx-chip <?= $tx === 'اجاره' ? 'is-active' : '' ?>" href="favorites.php?tx=اجاره">اجاره</a>
  <a class="mx-chip <?= $tx === 'رهن' ? 'is-active' : '' ?>" href="favorites.php?tx=رهن">رهن</a>
</div>
<?= PropertyCardRenderer::grid($items, [
  'empty_title' => 'هنوز ملکی ذخیره نکرده‌اید',
  'empty_text' => 'ملک‌های موردعلاقه‌تان را با دکمه ذخیره نگه دارید.',
  'empty_action_url' => 'properties.php',
  'empty_action_label' => 'جستجوی ملک',
]) ?>
