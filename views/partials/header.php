<?php
/** Melkino V2 — top header (spec §27). Vars: $active_nav, $unread */
use Melkino\Core\Auth;
use Melkino\UI\Icons\IconRegistry;

$unread = (int)($unread ?? 0);
if ($unread <= 0 && Auth::check()) {
    try {
        $unread = \Melkino\Domain\Notifications\NotificationRepository::unreadCount(Auth::identity());
    } catch (\Throwable $ignored) {}
}
$logo = '';
try {
    if (function_exists('getSiteLogoUrl')) {
        $logo = (string)@getSiteLogoUrl();
    }
} catch (\Throwable $ignored) {}
?>
<header class="mx-header"><div class="mx-container mx-header__in">
  <a class="mx-logo" href="home.php" aria-label="ملکینو — صفحه اصلی">
    <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="ملکینو"><?php endif; ?>
    <span>ملکینو</span>
  </a>
  <span class="mx-header__spacer"></span>
  <a class="mx-icon-btn" href="properties.php" aria-label="جستجوی ملک"><?= IconRegistry::svg('search', 22) ?></a>
  <a class="mx-icon-btn" href="notifications.php" aria-label="اعلان‌ها">
    <?= IconRegistry::svg('bell', 22) ?>
    <?php if ($unread > 0): ?><span class="mx-dot" data-notif-count><?= fa($unread) ?></span><?php endif; ?>
  </a>
  <a class="mx-icon-btn" href="<?= Auth::check() ? 'profile.php' : 'login.php' ?>" aria-label="پروفایل"><?= IconRegistry::svg('user', 22) ?></a>
</div></header>
