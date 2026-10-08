<?php
/** Vars: $page,$total_pages,$base (query prefix incl. ? or &), Vars escaped. */
$page = max(1, (int)($page ?? 1));
$total = max(1, (int)($total_pages ?? 1));
$base = (string)($base ?? '?');
if ($total > 1):
    $window = [];
    foreach (array_unique([1, 2, $page - 1, $page, $page + 1, $total - 1, $total]) as $p) {
        if ($p >= 1 && $p <= $total) $window[] = $p;
    }
    sort($window);
?>
<nav class="mx-pagination" aria-label="صفحه‌بندی">
  <?php if ($page > 1): ?><a href="<?= e($base . 'page=' . ($page - 1)) ?>" aria-label="صفحه قبل">‹</a><?php endif; ?>
  <?php $prev = 0; foreach ($window as $p): ?>
    <?php if ($p - $prev > 1): ?><span aria-hidden="true">…</span><?php endif; ?>
    <?php if ($p === $page): ?><span class="is-current" aria-current="page"><?= fa($p) ?></span>
    <?php else: ?><a href="<?= e($base . 'page=' . $p) ?>"><?= fa($p) ?></a><?php endif; ?>
    <?php $prev = $p; endforeach; ?>
  <?php if ($page < $total): ?><a href="<?= e($base . 'page=' . ($page + 1)) ?>" aria-label="صفحه بعد">›</a><?php endif; ?>
</nav>
<?php endif; ?>
