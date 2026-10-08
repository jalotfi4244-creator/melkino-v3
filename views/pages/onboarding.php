<?php
/**
 * Melkino V2 — onboarding splash (standalone, same data/flow as legacy).
 * Vars: $steps, $logo, $firstLogo, $firstTitle, $firstText.
 */
$payload = [
    'steps' => $steps ?? [], 'logo' => (string)($logo ?? ''),
    'firstLogo' => (string)($firstLogo ?? ''), 'firstTitle' => (string)($firstTitle ?? ''), 'firstText' => (string)($firstText ?? ''),
];
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>ملکینو - خوش آمدید</title>
<meta name="robots" content="noindex">
<?php foreach (['tokens','reset','typography','layout','components','cards','forms','modals','responsive','utilities','pages','pages2'] as $css): ?>
<?= \Melkino\Support\Assets::css('assets/css/' . $css . '.css') ?>
<?php endforeach; ?>
<style>
.mx-onboard{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--mx-bg);color:var(--mx-text);font-family:var(--mx-font)}
.mx-onboard__card{background:var(--mx-surface);border:1px solid var(--mx-border);border-radius:20px;padding:32px 24px;max-width:400px;width:100%;text-align:center;box-shadow:var(--mx-shadow-md)}
.mx-onboard__logo img{width:80px;height:80px;object-fit:contain}
.mx-onboard__icon{font-size:64px;display:block}
.mx-onboard h1{font-size:24px;font-weight:800;margin:12px 0 8px}
.mx-onboard p{font-size:14px;line-height:2;color:var(--mx-text-muted);margin:0 0 16px}
</style>
</head>
<body>
<div class="mx-onboard"><div class="mx-onboard__card">
  <div class="mx-onboard__logo" id="logoContainer"></div>
  <h1 id="stepTitle">به ملکینو خوش آمدید</h1>
  <p id="stepText"></p>
  <div class="mx-onboard__dots" id="dotsContainer"></div>
  <button type="button" class="mx-btn mx-btn--primary mx-btn--block mx-mt-4" id="actionBtn">مرحله بعد</button>
</div></div>
<script type="application/json" id="mxOnboardData"><?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?= \Melkino\Support\Assets::js('assets/js/onboarding.js', false) ?>
</body>
</html>
