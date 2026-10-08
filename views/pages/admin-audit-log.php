<?php
/**
 * Melkino V2 — audit-log viewer content (read-only, no scripts).
 * Vars: $rows, $total, $page, $pages, $filter, $actions.
 */
$rows = is_array($rows ?? null) ? $rows : [];
$total = (int)($total ?? 0);
$page = max(1, (int)($page ?? 1));
$pages = max(1, (int)($pages ?? 1));
$filter = (string)($filter ?? '');
$actions = is_array($actions ?? null) ? $actions : [];
$q = static function (int $p) use ($filter): string {
    $s = 'admin-audit-log.php?page=' . $p;
    return $filter !== '' ? $s . '&action=' . rawurlencode($filter) : $s;
};
?>
<section class="mx-card-surface mx-p-4">
  <h2 class="mx-h3 mx-mb-4">گزارش حسابرسی <?= $total > 0 ? '<span class="mx-tiny mx-muted">(' . fa((string)$total) . ' رویداد)</span>' : '' ?></h2>
  <form method="GET" action="admin-audit-log.php" class="mx-flex mx-mb-4" style="gap:8px;flex-wrap:wrap;align-items:end">
    <label class="mx-tiny">رویداد
      <select name="action" class="mx-input" style="min-width:220px">
        <option value="">همه‌ی رویدادها</option>
        <?php foreach ($actions as $a): ?>
        <option value="<?= e($a) ?>" <?= $a === $filter ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="mx-btn mx-btn--ghost mx-btn--sm">پالایش</button>
    <?php if ($filter !== ''): ?><a class="mx-btn mx-btn--ghost mx-btn--sm" href="admin-audit-log.php">حذف پالایش</a><?php endif; ?>
  </form>
  <?php if (!$rows): ?><p class="mx-muted">رویدادی ثبت نشده است.</p><?php else: ?>
  <div class="mx-table-wrap"><table class="mx-spec-table">
    <thead><tr><th>#</th><th>زمان</th><th>کنشگر</th><th>رویداد</th><th>موجودیت</th><th>جزئیات</th><th>IP</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr><td class="mx-num"><?= fa((string)($row['id'] ?? '')) ?></td><td class="mx-num" style="white-space:nowrap"><?= fa((string)($row['created_at'] ?? '')) ?></td><td><?= e(trim((string)($row['actor_name'] ?? '') !== '' ? (string)$row['actor_name'] : (string)($row['actor_type'] ?? '') . ' ' . (string)($row['actor_id'] ?? ''))) ?></td><td dir="ltr" style="text-align:right"><?= e((string)($row['action'] ?? '')) ?></td><td><?= e(trim((string)($row['entity'] ?? '') . ' ' . (string)($row['entity_id'] ?? ''))) ?></td><td class="mx-tiny" dir="ltr" style="text-align:right;max-width:320px;overflow:hidden;text-overflow:ellipsis;"><?= e(mb_substr((string)($row['details'] ?? ''), 0, 200)) ?></td><td class="mx-num" dir="ltr"><?= e((string)($row['ip_address'] ?? '')) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pages > 1): ?>
  <p class="mx-flex mx-mt-4" style="gap:8px;align-items:center">
    <?php if ($page > 1): ?><a class="mx-btn mx-btn--ghost mx-btn--sm" href="<?= e($q($page - 1)) ?>">‹ قبلی</a><?php endif; ?>
    <span class="mx-tiny mx-muted">صفحه‌ی <?= fa((string)$page) ?> از <?= fa((string)$pages) ?></span>
    <?php if ($page < $pages): ?><a class="mx-btn mx-btn--ghost mx-btn--sm" href="<?= e($q($page + 1)) ?>">بعدی ›</a><?php endif; ?>
  </p>
  <?php endif; ?>
  <?php endif; ?>
</section>
