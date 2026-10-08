<?php
/**
 * Melkino V2 — admin shell (spec §33): sidebar + topbar + content.
 * Existing admin-*.php pages keep working standalone; new/converted pages render inside this shell.
 * Vars: $title, $active, $content_view, $content_data, $kpis, $identity
 */
use Melkino\Support\Assets;
use Melkino\UI\Icons\IconRegistry;
$active = $active ?? 'dashboard';
$nav = [
    'dashboard' => ['admin.php', 'داشبورد', 'dashboard'],
    'ads' => ['admin.php?tab=ads', 'مدیریت آگهی‌ها', 'building'],
    'requests' => ['admin.php?tab=requests', 'درخواست‌ها', 'doc'],
    'visits' => ['admin.php?tab=visits', 'بازدیدها', 'calendar'],
    'users' => ['admin.php?tab=users', 'کاربران', 'user'],
    'stats' => ['admin.php?tab=stats', 'آمار', 'chart'],
    'compare' => ['compare.php', 'مقایسه‌ها', 'compare'],
    'notifications' => ['admin.php?tab=notifications', 'اعلان‌ها', 'bell'],
    'broadcast' => ['admin.php?tab=notifications', 'پیام همگانی', 'send'],
    'promotions' => ['admin.php?tab=promotions', 'تبلیغات', 'megaphone'],
    'support' => ['admin.php?tab=support', 'پشتیبانی', 'headset'],
    'contact' => ['admin.php?tab=contact', 'ارتباط با ما', 'phone'],
    'global' => ['admin.php?tab=global', 'تنظیمات عمومی', 'gear'],
    'password' => ['admin.php?tab=password', 'تغییر رمز', 'key'],
    'sms' => ['admin.php?tab=sms', 'برنامهٔ پیامک', 'chat'],
    'onboarding' => ['admin.php?tab=onboarding', 'صفحات هدایت', 'spark'],
    'assistant' => ['admin.php?tab=assistant', 'دستیار هوشمند', 'headset'],
    'comm' => ['admin.php?tab=comm', 'ارتباطات', 'megaphone'],
    'studio' => ['admin.php?tab=studio', 'استودیو طراحی', 'camera'],
    'display' => ['admin.php?tab=display', 'مدیریت نمایش', 'eye'],
    'forms' => ['admin.php?tab=forms', 'گزینه‌های فرم‌ها', 'list'],
    'diagnostics' => ['admin.php?tab=diagnostics', 'عیب‌یابی', 'activity'],
    'bots' => ['admin.php?tab=bots', 'ربات‌ها', 'bot'],
    'images' => ['admin.php?tab=images', 'تصاویر', 'image'],
    'theme' => ['admin-theme-manager.php', 'قالب', 'palette'],
    'backup' => ['admin-backup.php', 'پشتیبان‌گیری', 'db'],
    'audit' => ['admin-audit-log.php', 'گزارش حسابرسی', 'shield'],
    'home' => ['home.php', 'مشاهده سایت', 'eye'],
];
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'پنل مدیریت | ملکینو') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<?php if (!empty($head_extra)) echo (string)$head_extra; ?>
<link rel="stylesheet" href="<?= asset('assets/css/admin-publishing.css') ?>">
</head>
<body class="mx-admin" data-mx-color="dark">
<a class="mx-sr-only" href="#mxAdminMain">پرش به محتوا</a>
<div class="mx-admin__shell">
  <aside class="mx-admin__side" aria-label="منوی مدیریت">
    <a class="mx-admin__brand" href="admin.php"><?= IconRegistry::svg('logo', 30) ?><b>ملکینو <small>مدیریت</small></b></a>
    <nav><ul>
      <?php foreach ($nav as $key => [$href, $label, $icon]): ?>
      <li><a href="<?= e($href) ?>" class="<?= $key === $active ? 'is-active' : '' ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>><?= IconRegistry::svg($icon, 18) ?><span><?= e($label) ?></span></a></li>
      <?php endforeach; ?>
    </ul></nav>
    <div class="mx-admin__side-foot">
      <span class="mx-tiny"><?= e((string)(($identity['name'] ?? '') !== '' ? $identity['name'] : 'مدیر')) ?></span>
      <a class="mx-tiny" href="admin-logout.php">خروج</a>
    </div>
  </aside>
  <div class="mx-admin__main">
    <header class="mx-admin__top"><h1><?= e($title ?? 'داشبورد') ?></h1><time class="mx-tiny" datetime="<?= date('Y-m-d') ?>"><?= fa(date('Y/m/d')) ?></time></header>
    <?php if (!empty($kpis)): ?>
    <div class="mx-admin__kpis" role="list">
      <?php foreach ($kpis as $kpi): ?>
      <a class="mx-admin__kpi" role="listitem" href="<?= e((string)($kpi['href'] ?? '#')) ?>">
        <span class="mx-admin__kpi-n"><?= fa((string)($kpi['n'] ?? 0)) ?></span>
        <span class="mx-admin__kpi-l"><?= e((string)($kpi['label'] ?? '')) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <main id="mxAdminMain" class="mx-admin__content" tabindex="-1">
      <?= melkinoView($content_view, $content_data ?? []) ?>
    </main>
  </div>
</div>
<?php foreach (array_unique(array_map('strval', (array)($scripts ?? []))) as $js): ?>
<?php if (str_contains($js, '://')): ?>
<script src="<?= e($js) ?>"></script><?= "\n" ?>
<?php elseif (str_contains($js, '/')): ?>
<?= Assets::js($js, false) . "\n" ?>
<?php else: ?>
<?= Assets::js('assets/js/' . $js . '.js') . "\n" ?>
<?php endif; ?>
<?php endforeach; ?>
<?php require MELKINO_ROOT . '/csrf-shim.php'; ?>
</body>
</html>
