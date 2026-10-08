/* Melkino V2 — diagnostics admin tab: lazy inject() VERBATIM from admin-panel.php.
 * API: admin-diagnostics.php?action=view (untouched; its scripts execute same as panel).
 * Deviation: panel boots inject() via switchTab-wrap/navBtn/hash listeners (absent in
 * V2 — the switchTab probe would poll forever); V2 boots on DOMContentLoaded.
 */
(function () {
    var host = document.getElementById('diagnosticsLazyHost');
    if (!host) return;

    var loaded = false;

    function inject() {
        if (loaded) return;
        loaded = true;

        fetch('admin-diagnostics.php?action=view', { cache: 'no-store' })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                // اجرایِ اسکریپت‌های همراهِ پاسخ
                var tmp = document.createElement('div');
                tmp.innerHTML = html;

                var scripts = tmp.querySelectorAll('script');
                var codes = [];
                for (var i = 0; i < scripts.length; i++) {
                    codes.push(scripts[i].textContent);
                    scripts[i].parentNode.removeChild(scripts[i]);
                }

                host.innerHTML = tmp.innerHTML;

                for (var j = 0; j < codes.length; j++) {
                    try {
                        var sc = document.createElement('script');
                        sc.textContent = codes[j];
                        document.body.appendChild(sc);
                    } catch (e) {}
                }
            })
            .catch(function () {
                host.innerHTML =
                    '<div class="admin-field-help" style="color:var(--danger)">' +
                    'خطا در بارگیریِ بخش عیب‌یاب.</div>';
            });
    }



document.addEventListener('DOMContentLoaded', function () {
    try { inject(); } catch (e) {}
});
})();
