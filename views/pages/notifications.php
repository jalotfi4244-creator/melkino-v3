<?php
/** Melkino V2 — notification center (spec §26). Vars: $result,$tab,$page */
use Melkino\UI\Icons\IconRegistry;

$tabs = ['all' => 'همه', 'property' => 'ملک‌ها', 'request' => 'درخواست‌ها', 'account' => 'حساب'];
?>
<div class="mx-page-head mx-flex mx-justify-between mx-items-center">
  <h1>اعلان‌ها</h1>
  <?php if ($result['unread'] > 0): ?>
  <form action="notifications.php<?= $tab !== 'all' ? '?tab=' . e($tab) : '' ?>" method="post">
    <?= \Melkino\Core\Csrf::field() ?>
    <input type="hidden" name="action" value="mark_all_read">
    <button class="mx-btn mx-btn--ghost mx-btn--sm" type="submit">خواندن همه (<?= fa($result['unread']) ?>)</button>
  </form>
  <?php endif; ?>
</div>
<div class="mx-tabs" role="tablist">
  <?php foreach ($tabs as $k => $v): ?>
  <a href="notifications.php<?= $k !== 'all' ? '?tab=' . e($k) : '' ?>" class="<?= $tab === $k ? 'is-active' : '' ?>" role="tab"><?= e($v) ?></a>
  <?php endforeach; ?>
</div>
<?php if (!$result['items']): ?>
<?= \Melkino\UI\EmptyStates::render('bell', 'اعلانی ندارید', 'خبرهای ملک‌ها و درخواست‌هایتان اینجا نمایش داده می‌شود.', '', '') ?>
<?php else: ?>
<div class="mx-flex" style="flex-direction:column;gap:12px">
<?php foreach ($result['items'] as $n): $unread = empty($n['is_read']); ?>
  <div class="mx-notif <?= $unread ? 'is-unread' : '' ?>">
    <div class="mx-notif__icon"><?= IconRegistry::svg(str_starts_with((string)($n['type'] ?? ''), 'request') ? 'doc' : 'bell', 20) ?></div>
    <div class="mx-notif__body">
      <h4><?= e((string)($n['title'] ?? 'اعلان')) ?></h4>
      <p><?= e((string)($n['message'] ?? '')) ?></p>
      <?php if (!empty($n['match_percent'])): ?><p class="mx-mt-2"><span class="mx-match"><?= fa((int)$n['match_percent']) ?>٪ تطابق</span></p><?php endif; ?>
      <p class="mx-mt-2 mx-flex mx-gap-2">
        <?php if (!empty($n['url'])): ?><a class="mx-btn mx-btn--primary mx-btn--sm" href="<?= e((string)$n['url']) ?>">مشاهده</a><?php endif; ?>
        <?php if ($unread): ?>
        <form action="notifications.php<?= $tab !== 'all' ? '?tab=' . e($tab) : '' ?>" method="post">
          <?= \Melkino\Core\Csrf::field() ?>
          <input type="hidden" name="action" value="mark_read">
          <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
          <button class="mx-btn mx-btn--ghost mx-btn--sm" type="submit">خواندم</button>
        </form>
        <?php endif; ?>
      </p>
    </div>
    <span class="mx-notif__time"><?= fa(substr((string)($n['created_at'] ?? ''), 0, 16)) ?></span>
  </div>
<?php endforeach; ?>
</div>
<?= melkinoPartial('pagination.php', ['page' => $page, 'total_pages' => max(1, (int)ceil($result['total'] / $result['per_page'])), 'base' => 'notifications.php?' . ($tab !== 'all' ? 'tab=' . $tab . '&' : '')]) ?>
<?php endif; ?>
