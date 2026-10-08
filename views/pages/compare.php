<?php
/** Melkino V2 — compare (spec §21). Vars: $items,$rows,$groups,$group */
use Melkino\UI\Card\PropertyCardRenderer;
use Melkino\UI\Icons\IconRegistry;

if (!$items):
    echo \Melkino\UI\EmptyStates::render('compare', 'ملکی برای مقایسه انتخاب نشده', 'از روی کارت هر ملک، دکمه «مقایسه» را بزنید.', 'properties.php', 'جستجوی ملک');
    return;
endif;
?>
<div class="mx-page-head"><h1>مقایسه ملک‌ها</h1><p class="mx-small"><?= fa(count($items)) ?> ملک انتخاب شده</p></div>
<?php if (count($groups) > 1): ?>
<div class="mx-chips mx-mb-4" role="group" aria-label="گروه مقایسه">
  <?php foreach ($groups as $g): $no = (int)($g['group_no'] ?? $g['id'] ?? 0); ?>
  <a class="mx-chip <?= $group === $no ? 'is-active' : '' ?>" href="compare-page.php?group=<?= $no ?>"><?= e((string)($g['name'] ?? ('گروه ' . $no))) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="mx-mb-6"><?= PropertyCardRenderer::grid($items) ?></div>
<div class="mx-compare-scroll"><table class="mx-compare-table">
  <thead><tr><th>ویژگی</th><?php foreach ($items as $i => $it): ?><th>ملک <?= fa($i + 1) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['label']) ?></td><?php foreach ($r['values'] as $v): ?><td><?= e((string)$v) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
