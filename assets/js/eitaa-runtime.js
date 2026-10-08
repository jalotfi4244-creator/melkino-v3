/* Authenticated Eitaa navigation only; no repeated login or contact request. */
(function () {
    'use strict';
    if (window.__melkinoEitaaRuntime) return;
    window.__melkinoEitaaRuntime = true;
    function boot() {
        var sdk = window.Eitaa && window.Eitaa.WebApp;
        if (!sdk) return;
        try { sdk.ready(); } catch (e) {}
        try { if (typeof sdk.expand === 'function') sdk.expand(); } catch (e) {}
        var page = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
        var isHome = page === 'index.php' || page === 'home.php';
        function back() {
            var localRef = false;
            try { localRef = new URL(document.referrer).origin === location.origin; } catch (e) {}
            if (localRef && history.length > 1 && !/\/(?:eitaa-app|login)\.php(?:[?#]|$)/.test(document.referrer)) {
                history.back();
            } else if (!isHome) {
                location.assign('home.php');
            } else {
                try { sdk.close(); } catch (e) {}
            }
        }
        try {
            if (sdk.BackButton) {
                sdk.BackButton.onClick(back);
                if (isHome) sdk.BackButton.hide(); else sdk.BackButton.show();
            }
        } catch (e) {}
        // The official SDK supplies safe-area CSS variables. Keep site theme/preferences.
        document.documentElement.classList.add('mk-eitaa-ready');
        document.addEventListener('click', function (event) {
            var a = event.target && event.target.closest ? event.target.closest('a[href]') : null;
            if (!a) return;
            try {
                var u = new URL(a.href, location.href);
                if (u.origin === location.origin && /\/logout\.php$/.test(u.pathname)) {
                    sessionStorage.removeItem('__eitaa__initParams');
                }
            } catch (e) {}
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
