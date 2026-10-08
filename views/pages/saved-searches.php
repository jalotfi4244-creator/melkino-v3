<?php
/** Melkino V2 — saved searches (same actions/copy as legacy; POST adds CSRF). */
use Melkino\UI\Icons\IconRegistry;
$searches = $searches ?? [];
$optedOut = !empty($optedOut);
?>
<div class="mx-page-head"><h1>جستجوهای ذخیره‌شده</h1>
<p class="mx-small">هر ملک جدیدی که با فیلترهای شما بسازد، همان لحظه پیامک اطلاع‌رسانی می‌گیرید.
<a class="mx-link" href="properties.php">ثبت جستجوی جدید از صفحهٔ همه آگهی‌ها ‹</a></p></div>

<?php if ($optedOut): ?>
<div class="mx-notice mx-notice--warn mx-mb-4">
  <span>⛔ دریافت پیامک‌های اطلاع‌رسانی برای شمارهٔ شما لغو شده است.</span>
  <form method="post"><<?= \Melkino\Core\Csrf::field() ?><input type="hidden" name="action" value="resub">
  <button class="mx-btn mx-btn--ghost mx-btn--sm" type="submit">فعال‌سازی دوباره</button></form>
</div>
<?php endif; ?>

<?php if (!$searches): ?>
<?= melkinoPartial('empty-state.php', ['icon' => 'search', 'title' => 'هنوز جستجویی ذخیره نکرده‌اید.', 'text' => 'از صفحهٔ «همه آگهی‌ها» فیلترها را بزنید و «ذخیرهٔ این جستجو» را بزنید.', 'action_href' => 'properties.php', 'action_label' => 'رفتن به همه آگهی‌ها']) ?>
<?php else: ?>
<div class="mx-list">
  <?php foreach ($searches as $s): ?>
  <?php $on = !empty($s['notify']) && !$optedOut; ?>
  <div class="mx-card-surface mx-p-4 mx-flex" style="align-items:center">
    <span style="font-size:24px" aria-hidden="true"><?= $on ? '🔔' : '🔕' ?></span>
    <div style="flex:1;min-width:0">
      <b><?= e((string)($s['_summary'] ?? '')) ?></b>
      <p class="mx-tiny mx-muted mx-mt-2">ثبت: <?= fa(substr((string)($s['created_at'] ?? ''), 0, 10)) ?>
        <?php if (!empty($s['last_notified_at'])): ?> · آخرین اطلاع‌رسانی: <?= fa(substr((string)$s['last_notified_at'], 0, 16)) ?><?php endif; ?>
        · <span class="<?= $on ? 'mx-text-ok' : 'mx-text-warn' ?>"><?= $on ? 'اطلاع‌رسانی روشن' : 'اطلاع‌رسانی خاموش' ?></span></p>
    </div>
    <div class="mx-flex" style="gap:8px">
      <form method="post"><?= \Melkino\Core\Csrf::field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
      <button class="mx-btn mx-btn--ghost mx-btn--sm" type="submit"><?= !empty($s['notify']) ? 'قطع' : 'وصل' ?></button></form>
      <form method="post" data-confirm="این جستجو حذف شود؟"><?= \Melkino\Core\Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
      <button class="mx-btn mx-btn--danger mx-btn--sm" type="submit">حذف</button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php if (!$optedOut): ?>
<div class="mx-notice mx-notice--warn mx-mt-4">
  <span>نمی‌خواهید دیگر پیامک اطلاع‌رسانی بگیرید؟</span>
  <form method="post" data-confirm="دریافت همه پیامک‌های اطلاع‌رسانی لغو شود؟"><?= \Melkino\Core\Csrf::field() ?><input type="hidden" name="action" value="unsub_all">
  <button class="mx-btn mx-btn--danger mx-btn--sm" type="submit">لغو دریافت همهٔ پیامک‌های اطلاع‌رسانی</button></form>
</div>
<?php endif; ?>
<?php endif; ?>
