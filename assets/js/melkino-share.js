/* Melkino — اشتراک‌گذاری آگهی (همهٔ صفحات).
 * استفاده:
 *   <button type="button" data-share-ad="AD-..." data-share-title="...">اشتراک‌گذاری</button>
 * یا مستقیم: window.melkinoShareAd('AD-...', 'عنوان')
 * موبایل/مرورگرهای جدید: پنجرهٔ اشتراک‌گذاری سیستمی؛ بقیه: کپی خودکار پیوند + پیام.
 */
(function () {
    'use strict';

    if (window.melkinoShareAd) return;

    function shareUrlFor(adId) {
        var base = window.location.origin || (window.location.protocol + '//' + window.location.host);
        var path = String(window.location.pathname || '/').replace(/[^/]*$/, '');
        if (path.charAt(0) !== '/') path = '/' + path;
        return base + path + 'property-details.php?id=' + encodeURIComponent(adId);
    }

    function ensureToastCss() {
        if (document.getElementById('mk-share-style')) return;
        var st = document.createElement('style');
        st.id = 'mk-share-style';
        st.textContent =
            '.mk-share-toast{position:fixed;left:50%;bottom:96px;transform:translateX(-50%) translateY(12px);' +
            'background:rgba(20,24,28,.94);color:#fff;font-size:13px;font-weight:700;line-height:1.9;' +
            'padding:9px 18px;border-radius:999px;box-shadow:0 8px 26px rgba(0,0,0,.28);' +
            'z-index:99999;opacity:0;pointer-events:none;transition:opacity .22s ease,transform .22s ease;' +
            'max-width:min(92vw,480px);text-align:center;direction:rtl;}' +
            '.mk-share-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}' +
            '.mk-share-toast .mk-share-link{color:#ffd97a;word-break:break-all;font-weight:400;font-size:12px;}';
        document.head.appendChild(st);
    }

    var toastTimer = null;
    function toast(html) {
        try {
            ensureToastCss();
            var el = document.getElementById('mk-share-toast');
            if (!el) {
                el = document.createElement('div');
                el.id = 'mk-share-toast';
                el.className = 'mk-share-toast';
                el.setAttribute('role', 'status');
                document.body.appendChild(el);
            }
            el.innerHTML = html;
            /* رندر دوباره برای اجرای ترنزیشن */
            void el.offsetWidth;
            el.classList.add('show');
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2600);
        } catch (e) { /* هرگز صفحه را نمی‌شکند */ }
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function legacyCopy(text, done) {
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, ta.value.length);
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            done(!!ok);
        } catch (e) { done(false); }
    }

    function copyText(text, done) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(
                function () { done(true); },
                function () { legacyCopy(text, done); }
            );
        } else {
            legacyCopy(text, done);
        }
    }

    window.melkinoShareAd = function (adId, title) {
        adId = String(adId == null ? '' : adId).trim();
        if (!adId) return false;
        /* ثبت سمت‌سرور برای تب «آمار» — fire-and-forget، هرگز اشتراک را نمی‌شکند */
        try {
            if (typeof fetch === 'function') {
                var __fd = new FormData();
                __fd.append('ad_id', adId);
                __fd.append('title', String(title == null ? '' : title).substring(0, 200));
                fetch('share-log.php', { method: 'POST', body: __fd, credentials: 'same-origin', keepalive: true })
                    .catch(function () {});
            }
        } catch (__e) { /* ignore */ }
        var url = shareUrlFor(adId);
        var label = String(title == null ? '' : title).trim();
        var text = (label !== '' ? label + ' — ملکینو' : 'آگهی ملکینو') + '\n' + url;
        if (typeof navigator.share === 'function') {
            try {
                navigator.share({ title: 'ملکینو', text: text, url: url }).catch(function () { /* انصراف کاربر */ });
            } catch (e) { /* رفتار مرورگرهای قدیمی */ }
            return true;
        }
        copyText(url, function (ok) {
            if (ok) {
                toast('پیوند آگهی کپی شد ✅');
            } else {
                toast('کپی خودکار نشد — پیوند:<br><span class="mk-share-link">' + escapeHtml(url) + '</span>');
            }
        });
        return true;
    };

    /* دکمه‌های [data-share-ad] — delegation تا کارت‌های داینامیک هم پوشش داده شوند */
    document.addEventListener('click', function (e) {
        var t = e && e.target;
        var btn = (t && t.closest) ? t.closest('[data-share-ad]') : null;
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        window.melkinoShareAd(
            btn.getAttribute('data-share-ad') || '',
            btn.getAttribute('data-share-title') || ''
        );
    }, false);
})();
