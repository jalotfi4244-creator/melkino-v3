/* Melkino V2 — admin map tab boot (panel calls initMapTab via tab-switch hook; the V2 shell boots it here). */
(function () {
    function boot() {
        if (typeof initMapTab === 'function') {
            initMapTab();
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
