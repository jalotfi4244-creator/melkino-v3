<?php
/**
 * Melkino V2 — map view. DOM ids/classes kept EXACT (map.js/map-markers.js bind to them);
 * legacy map.css is page-scoped (mk-map-*) and never collides with mx-* V2 CSS.
 */
$json = static fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<div class="mx-page-head"><h1>نقشه املاک</h1><p class="mx-small">ملک‌ها را روی نقشه ببینید و فیلتر کنید.</p></div>
<div class="mk-map-page">
    <div class="mk-map-toolbar">
        <div class="mk-map-tx" id="mkMapTx">
            <button type="button" class="mk-chip is-on" data-tx="">همه</button>
            <button type="button" class="mk-chip" data-tx="فروش">فروش</button>
            <button type="button" class="mk-chip" data-tx="اجاره">اجاره</button>
            <button type="button" class="mk-chip" data-tx="پیش فروش">پیش‌فروش</button>
        </div>
        <details class="mk-map-types" id="mkMapTypes">
            <summary id="mkMapTypesSummary">نوع ملک: همه</summary>
            <div class="mk-map-types-list">
                <button type="button" class="mk-chip is-on" data-pt="">همه</button>
                <button type="button" class="mk-chip" data-pt="آپارتمان">آپارتمان</button>
                <button type="button" class="mk-chip" data-pt="ویلایی">ویلایی</button>
                <button type="button" class="mk-chip" data-pt="باغ">باغ</button>
                <button type="button" class="mk-chip" data-pt="زمین">زمین</button>
                <button type="button" class="mk-chip" data-pt="تجاری">تجاری</button>
                <button type="button" class="mk-chip" data-pt="اداری">اداری</button>
            </div>
        </details>
    </div>
    <div class="mk-map-body">
        <div id="mkMapCanvas"></div>
    </div>
    <div id="mkMapSheet" class="mk-map-sheet" hidden></div>
</div>
<style id="mk-map-studio-css"><?= str_ireplace('</style', '<\/style', (string)($studioCss ?? '')) ?></style>
<script<?= csp_nonce_attr() ?>>
window.MELKINO_MAP = <?= $json($settings ?? []) ?>;
window.MELKINO_MAP_THEME = <?= $json($mapTheme ?? []) ?>;
window.MELKINO_MARKER_SHAPES = <?= $json($shapes ?? []) ?>;
</script>
