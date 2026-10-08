<?php
/**
 * Melkino V2 — public layout (spec §27, §28).
 * Vars: $title,$description,$canonical,$og_image,$og_type,$active_nav,$scripts,$head_extra,$body_class,$content_view,$content_data
 */
use Melkino\Core\Csrf;
use Melkino\Studio\StudioService;
use Melkino\Support\Assets;
use Melkino\UI\Toasts;

$title = (string)($title ?? 'ملکینو');
$description = (string)($description ?? 'ملکینو؛ انتخابی فراتر از یک ملک');
$theme = StudioService::current();
$ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
$isBale = (strpos($ua, 'bale') !== false) || (strpos($ua, 'ble.ir') !== false);
require_once MELKINO_ROOT . '/messenger-login-lib.php';
$messenger = melkinoMessengerContext();
$isEitaa = $messenger === 'eitaa';
if ($messenger !== '') $isBale = false;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" id="melkinoRoot"<?= $messenger !== '' ? ' data-melkino-messenger="' . e($messenger) . '"' : '' ?><?= $isEitaa ? ' data-melkino-eitaa="1"' : '' ?>>
<head>
<?php if ($messenger !== ''): ?>
<script src="<?= e(melkinoMessengerSdk($messenger)) ?>" data-mk-sdk="<?= e($messenger) ?>" async></script>
<?= Assets::js('assets/js/messenger-bridge.js') ?>
<?= Assets::js('assets/js/messenger-runtime.js') ?>
<?php else: ?>
<script>/* FIRST (r64): stash messenger hash before any SDK wipes it. */try{window.__melkinoEarlyHash=location.hash||'';if(location.hash){try{sessionStorage.setItem('melkino_tg_hash',location.hash);}catch(e2){}}}catch(e){}</script>
<?php endif; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e((string)$canonical) ?>"><?php endif; ?>
<meta property="og:type" content="<?= e((string)($og_type ?? 'website')) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if (!empty($og_image)): ?><meta property="og:image" content="<?= e((string)$og_image) ?>"><?php endif; ?>
<meta name="theme-color" content="#0b5d59">
<?= Csrf::meta() . "\n" ?>
<?php foreach (['tokens','reset','typography','layout','components','cards','forms','modals','responsive','utilities','pages','pages2'] as $css): ?>
<?= Assets::css('assets/css/' . $css . '.css') . "\n" ?>
<?php endforeach; ?>
<?= StudioService::cssVars() . "\n" ?>
<?php if (!empty($head_extra)) echo (string)$head_extra . "\n"; ?>
<?php if ($isBale): ?>
<script src="<?= e('https://tapi.bale.ai/' . 'miniapp.js?3') ?>"></script>
<?php endif; ?>
<script>
(function(){try{var w=window.Bale&&window.Bale.WebApp;if(w){if(typeof w.ready==='function')w.ready();if(typeof w.expand==='function')w.expand();}}catch(e){}})();
</script>
</head>
<body data-mx-style="<?= e($theme['style']) ?>" data-mx-color="<?= e($theme['color']) ?>" class="<?= e((string)($body_class ?? '')) ?>">
<?= melkinoView('partials/header.php', ['active_nav' => ($active_nav ?? ''), 'unread' => (int)($unread ?? 0)]) ?>
<main class="mx-main"><div class="mx-container">
<?= melkinoView((string)$content_view, is_array($content_data ?? null) ? $content_data : []) ?>
</div></main>
<?= melkinoView('partials/footer.php', []) ?>
<?= melkinoView('partials/bottom-nav.php', ['active_nav' => ($active_nav ?? '')]) ?>
<?= Toasts::render() ?>
<?= Assets::js('assets/js/core.js') ?>
<?= Assets::js('assets/js/ui.js') ?>
<?= Assets::js('assets/js/melkino-share.js') ?>
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
