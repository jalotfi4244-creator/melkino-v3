<?php
/** Melkino V2 — mobile bottom nav, exactly 5 items (spec §29). Vars: $active_nav */
use Melkino\UI\Icons\IconRegistry;

$active = (string)($active_nav ?? '');
$items = [
    ['home', 'home.php', 'خانه', 'home'],
    ['search', 'properties.php', 'جستجو', 'search'],
    ['favorites', 'favorites.php', 'ذخیره‌ها', 'heart'],
    ['requests', 'my-request-matches.php', 'درخواست من', 'doc'],
    ['profile', 'profile.php', 'پروفایل', 'user'],
];
?>
<nav class="mx-bottom-nav" aria-label="ناوبری اصلی"><div class="mx-bottom-nav__in">
<?php foreach ($items as [$key, $url, $label, $icon]): ?>
  <a href="<?= e($url) ?>" class="<?= $active === $key ? 'is-active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
    <?= IconRegistry::svg($icon, 22) ?><span><?= e($label) ?></span>
  </a>
<?php endforeach; ?>
</div></nav>
