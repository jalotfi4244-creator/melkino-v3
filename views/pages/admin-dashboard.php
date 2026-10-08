<?php
/** Melkino V2 — admin dashboard content (spec §33): queues, health, quick actions. */
use Melkino\UI\Icons\IconRegistry;
$queues = $queues ?? [];
$health = $health ?? [];
$audit = $audit ?? [];
?>
<div class="mx-admin__grid">
  <section class="mx-card-surface mx-p-4">
    <h2 class="mx-h3 mx-mb-4">صف‌های نیازمند اقدام</h2>
    <?php if (!$queues): ?><p class="mx-muted">صفی خالی نیست — همه‌چیز به‌روز است.</p><?php endif; ?>
    <ul class="mx-admin__list">
      <?php foreach ($queues as $q): ?>
      <li><a href="<?= e((string)$q['href']) ?>"><b><?= fa((string)$q['n']) ?></b> <?= e((string)$q['label']) ?> <span aria-hidden="true">‹</span></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="mx-card-surface mx-p-4">
    <h2 class="mx-h3 mx-mb-4">سلامت سیستم</h2>
    <ul class="mx-admin__list">
      <?php foreach ($health as $h): ?>
      <li><span class="mx-badge <?= !empty($h['ok']) ? 'mx-badge--ok' : 'mx-badge--warn' ?>"><?= !empty($h['ok']) ? 'سالم' : 'نیازمند بررسی' ?></span> <?= e((string)$h['label']) ?></li>
      <?php endforeach; ?>
    </ul>
    <p class="mx-mt-4"><a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-diagnostics.php">اجرای عیب‌یابی کامل</a></p>
  </section>
</div>
<section class="mx-card-surface mx-p-4 mx-mt-4">
  <h2 class="mx-h3 mx-mb-4">آخرین رویدادهای حسابرسی</h2>
  <?php if (!$audit): ?><p class="mx-muted">رویدادی ثبت نشده است.</p><?php else: ?>
  <div class="mx-table-wrap"><table class="mx-spec-table">
    <thead><tr><th>زمان</th><th>رویداد</th><th>موجودیت</th><th>جزئیات</th></tr></thead>
    <tbody>
      <?php foreach ($audit as $row): ?>
      <tr><td class="mx-num"><?= fa((string)($row['created_at'] ?? '')) ?></td><td><?= e((string)($row['action'] ?? '')) ?></td><td><?= e(trim((string)($row['entity'] ?? '') . ' ' . (string)($row['entity_id'] ?? ''))) ?></td><td class="mx-tiny"><?= e(mb_substr((string)($row['details'] ?? ''), 0, 120)) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
  <p class="mx-mt-4"><a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-audit-log.php">مشاهده همه</a></p>
</section>
<section class="mx-card-surface mx-p-4 mx-mt-4">
  <h2 class="mx-h3 mx-mb-4">ابزارهای عملیاتی</h2>
  <p class="mx-flex" style="flex-wrap:wrap;gap:8px">
    <a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-backup.php">پشتیبان‌گیری / بازیابی</a>
    <a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-2fa.php">ورود دومرحله‌ای</a>
    <a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-bots.php">توکن ربات‌ها</a>
    <a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-theme-manager.php">قالب و بنر</a>
  </p>
  <p class="mx-tiny mx-muted mx-mt-2">ابزارهای CLI روی سرور: <code dir="ltr">php tools/db-doctor.php</code> و <code dir="ltr">php tools/migrate.php</code></p>
</section>
