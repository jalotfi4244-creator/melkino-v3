<?php
/** Melkino V2 — my visits (same card fields/copy as legacy; delete via visit-request-api). */
$cards = $cards ?? [];
?>
<div class="mx-page-head"><h1>بازدیدهای من</h1><p class="mx-small">درخواست‌های بازدید ثبت‌شده شما.</p></div>
<?php if (empty($logged)): ?>
<?= melkinoPartial('empty-state.php', ['icon' => 'calendar', 'title' => 'برای دیدن درخواست‌های بازدید وارد حساب شوید.', 'action_href' => 'login.php', 'action_label' => 'ورود']) ?>
<?php elseif (!$cards): ?>
<?= melkinoPartial('empty-state.php', ['icon' => 'calendar', 'title' => 'هنوز درخواست بازدیدی ثبت نکرده‌اید.']) ?>
<?php else: ?>
<div class="mx-list" id="mxVisits">
  <?php foreach ($cards as $c): ?>
  <article class="mx-card-surface mx-p-4 mx-visit" id="mxVisit<?= (int)$c['id'] ?>">
    <div class="mx-visit__head">
      <span class="mx-badge"><?= e((string)$c['status']) ?></span>
      <span class="mx-tiny mx-muted"><?= e((string)$c['track']) ?></span>
    </div>
    <h2 class="mx-h3 mx-mt-2"><?= e((string)$c['title']) ?></h2>
    <div class="mx-flex mx-mt-2" style="gap:16px;flex-wrap:wrap">
      <span class="mx-small"><b>روز:</b> <?= e((string)($c['when']['weekday'] ?? '—')) ?></span>
      <span class="mx-small"><b>تاریخ:</b> <?= e((string)($c['when']['date'] ?? '—')) ?></span>
      <span class="mx-small"><b>زمان:</b> <?= e((string)($c['when']['slot'] ?? '—')) ?></span>
    </div>
    <?php $facts = array_values(array_filter($c['facts'], static function ($f) {
        $v = trim((string)($f[1] ?? ''));
        return $v !== '' && $v !== '—' && $v !== '-' && !preg_match('/^(متر|تومان|اتاق|سال)$/u', $v);
    })); ?>
    <?php if ($facts): ?>
    <dl class="mx-facts mx-mt-2">
      <?php foreach ($facts as $f): ?><div><dt><?= e((string)$f[0]) ?></dt><dd><?= e((string)$f[1]) ?></dd></div><?php endforeach; ?>
    </dl>
    <?php endif; ?>
    <details class="mx-mt-2"><summary class="mx-small">جزئیات</summary>
      <?php if (!empty($c['amenities'])): ?>
      <div class="mx-flex mx-mt-2" style="flex-wrap:wrap;gap:6px">
        <?php foreach ($c['amenities'] as $am): ?><span class="mx-chip"><?= e((string)$am) ?></span><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php $extra = array_values(array_filter($c['extra'], static fn($f) => trim((string)($f[1] ?? '')) !== '' && trim((string)($f[1] ?? '')) !== '—')); ?>
      <?php if ($extra): ?>
      <dl class="mx-facts mx-mt-2">
        <?php foreach ($extra as $f): ?><div><dt><?= e((string)$f[0]) ?></dt><dd><?= e((string)$f[1]) ?></dd></div><?php endforeach; ?>
      </dl>
      <?php endif; ?>
      <?php if (empty($c['amenities']) && empty($extra)): ?><p class="mx-tiny mx-muted mx-mt-2">امکاناتی برای این ملک ثبت نشده است.</p><?php endif; ?>
    </details>
    <?php if ($c['alt'] !== ''): ?><p class="mx-small mx-mt-2">زمان جایگزین: <?= nl2br(e((string)$c['alt'])) ?></p><?php endif; ?>
    <div class="mx-flex mx-mt-4" style="gap:8px">
      <?php if ($c['ad_id'] !== ''): ?><a class="mx-btn mx-btn--primary mx-btn--sm" href="property-details.php?id=<?= e(urlencode((string)$c['ad_id'])) ?>">مشاهده آگهی</a><?php endif; ?>
      <?php if (!empty($c['can_delete'])): ?><button type="button" class="mx-btn mx-btn--ghost mx-btn--sm" data-visit-del="<?= (int)$c['id'] ?>">حذف درخواست</button><?php endif; ?>
    </div>
    <?php if (empty($c['can_delete'])): ?><p class="mx-tiny mx-muted mx-mt-2">به علت انجام پیگیری امکان حذف سیستمی نمی باشد جهت حذف درخواست بازدید با ملکینو تماس بگیرید.</p><?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
