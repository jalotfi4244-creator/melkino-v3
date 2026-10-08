<?php
/** Melkino V2 — admin design-studio tab. Thin require wrapper: the root
 * design-studio.php fragment (PHP bootstrap + <link> CSS + markup + catalog
 * <script> + map-markers.js) is owned by the panel and stays untouched.
 * Clone of the panel's <div id="tab-studio"> wrapper + require. */
?>
<div
    role="tabpanel"
    class="tab-content"
    id="tab-studio"
>
<?php require MELKINO_ROOT . '/design-studio.php'; ?>
</div>
