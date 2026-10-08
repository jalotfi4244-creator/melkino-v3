<?php
/** Melkino V2 — profile hub (spec §30). Vars: $identity,$stats,$requests */
use Melkino\UI\Icons\IconRegistry;

$name = trim((string)($identity['name'] ?? ''));
?>
<div class="mx-page-head"><h1>سلام<?= $name !== '' ? '، ' . e($name) : '' ?></h1></div>
<div class="mx-stats mx-mb-6">
  <a href="favorites.php"><b><?= fa($stats['favorites']) ?></b><span>ملک ذخیره‌شده</span></a>
  <a href="my-request-matches.php"><b><?= fa($stats['matches']) ?></b><span>ملک مناسب من</span></a>
  <a href="my-request-matches.php"><b><?= fa($stats['requests']) ?></b><span>درخواست فعال</span></a>
  <a href="my-properties.php"><b>›</b><span>آگهی‌های من</span></a>
</div>
<nav class="mx-menu" aria-label="پروفایل">
  <a href="my-request-matches.php"><?= IconRegistry::svg('doc', 20) ?><span>درخواست‌های من</span></a>
  <a href="my-request-matches.php"><?= IconRegistry::svg('spark', 20) ?><span>فایل‌های مناسب من</span></a>
  <a href="favorites.php"><?= IconRegistry::svg('heart', 20) ?><span>ملک‌های ذخیره‌شده</span></a>
  <a href="compare-page.php"><?= IconRegistry::svg('compare', 20) ?><span>مقایسه</span></a>
  <a href="notifications.php"><?= IconRegistry::svg('bell', 20) ?><span>اعلان‌ها<?= $stats['unread'] > 0 ? ' (' . fa($stats['unread']) . ')' : '' ?></span></a>
  <a href="support.php"><?= IconRegistry::svg('headset', 20) ?><span>پشتیبانی</span></a>
  <a href="profile.php?legacy=1"><?= IconRegistry::svg('user', 20) ?><span>اطلاعات حساب</span></a>
  <a href="logout.php" class="is-danger"><?= IconRegistry::svg('logout', 20) ?><span>خروج</span></a>
</nav>
<?php if ($requests): ?>
<section class="mx-section"><div class="mx-section__head"><h2>آخرین درخواست‌ها</h2><a class="mx-link" href="my-request-matches.php">مشاهده همه</a></div>
<div class="mx-flex" style="flex-direction:column;gap:12px">
<?php foreach ($requests as $r): ?>
  <a class="mx-card-surface mx-flex mx-justify-between mx-items-center" href="my-request-matches.php?request_id=<?= (int)$r['id'] ?>">
    <span><b><?= e((string)($r['property_type'] ?? '')) ?></b> · <?= e((string)($r['location'] ?? '')) ?><br>
    <span class="mx-small">کد <?= e((string)($r['tracking_code'] ?? '')) ?> · <?= e((string)($r['status'] ?? '')) ?></span></span>
    <?= IconRegistry::svg('chevron-left', 20) ?>
  </a>
<?php endforeach; ?>
</div></section>
<?php endif; ?>
