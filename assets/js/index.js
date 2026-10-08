/* Melkino V2 — index landing, 4 head blocks concatenated VERBATIM (only the PHP bootstrap adapted). */
/* ---- legacy head block 1 ---- */
(function () {
    function go() {
        try {
            var w = window.Bale && window.Bale.WebApp;
            if (w) {
                try { if (typeof w.ready === 'function') w.ready(); } catch (e) {}
                try { if (typeof w.expand === 'function') w.expand(); } catch (e) {}
            }
        } catch (e) {}
        try {
            if (window.parent && window.parent !== window) {
                function send(t) {
                    window.parent.postMessage(JSON.stringify({ eventType: t, eventData: null }), '*');
                }
                send('iframe_ready');
                send('web_app_ready');
                send('web_app_expand');
            }
        } catch (e) {}
    }
    go();
    setTimeout(go, 0);
    setTimeout(go, 100);
    setTimeout(go, 400);
    setTimeout(go, 1200);
})();

/* ---- legacy head block 2 ---- */
/*
 * آمادگیِ مینی‌اپ (تلگرام و بله)
 * ========================================================
 * کلاینتِ تلگرام/بله تا وقتی پیامِ «آمادگی» را دریافت نکند، یک لایه‌ی
 * سفید روی صفحه نگه می‌دارد.
 *
 * سه اشکالِ پشتِ‌هم باعث می‌شد این پیام فرستاده نشود:
 *   ۱) تشخیص با User-Agent در سمتِ سرور؛
 *   ۲) وابستگی به ورودِ کاربر؛
 *   ۳) تشخیص در مرورگر بر اساسِ «بودن در قاب» یا User-Agent — که روی
 *      کلاینتِ بله در iOS هر دو شکست می‌خورند.
 *
 * عیب‌یابی روی دستگاهِ واقعی نشان داد User-Agentِ بله در iOS چیزی جز یک
 * Safari معمولی نیست و صفحه هم درونِ قاب نیست. بنابراین دیگر هیچ تشخیصی
 * انجام نمی‌شود: این بلوک همیشه اجرا می‌شود و اسکریپتِ بله همیشه
 * به‌صورت غیرِ مسدودکننده بارگیری می‌گردد.
 */
(function () {

    var sdkDone = { bale: false, telegram: false, eitaa: false };

    // ثبتِ رویدادها — فقط در صفحه‌ی عیب‌یاب استفاده می‌شود و در بقیه‌ی
    // صفحات بی‌اثر است.
    function note(text) {
        try {
            if (typeof window.__MK_MARK === 'function') { window.__MK_MARK(text); }
        } catch (e) {}
    }

    note('بلوکِ آمادگی اجرا شد (بدونِ نیاز به تشخیصِ پلتفرم)');

    // حالتِ عادی: ready() از طریقِ اسکریپتِ رسمی
    function announceSDK() {
        try {
            if (window.Bale && window.Bale.WebApp) {
                if (!sdkDone.bale) {
                    sdkDone.bale = true;
                    if (typeof window.Bale.WebApp.ready === 'function') window.Bale.WebApp.ready();
                    try { if (typeof window.Bale.WebApp.expand === 'function') window.Bale.WebApp.expand(); } catch (e) {}
                    note('Bale.WebApp پیدا شد و ready() فراخوانی شد ✅');
                }
                return true;
            }
        } catch (e) {}
        try {
            if (window.Telegram && window.Telegram.WebApp) {
                if (!sdkDone.telegram) {
                    sdkDone.telegram = true;
                    if (typeof window.Telegram.WebApp.ready === 'function') window.Telegram.WebApp.ready();
                    try { if (typeof window.Telegram.WebApp.expand === 'function') window.Telegram.WebApp.expand(); } catch (e) {}
                    note('Telegram.WebApp پیدا شد و ready() فراخوانی شد ✅');
                }
                return true;
            }
        } catch (e) {}
        try {
            if (window.Eitaa && window.Eitaa.WebApp) {
                if (!sdkDone.eitaa) {
                    sdkDone.eitaa = true;
                    if (typeof window.Eitaa.WebApp.ready === 'function') window.Eitaa.WebApp.ready();
                    try { if (typeof window.Eitaa.WebApp.expand === 'function') window.Eitaa.WebApp.expand(); } catch (e) {}
                    note('Eitaa.WebApp پیدا شد و ready() فراخوانی شد ✅');
                }
                return true;
            }
        } catch (e) {}
        return false;
    }

    function inFrame() {
        try { return (window.self !== window.top); } catch (e) { return true; }
    }

    // مسیرِ جایگزین: پروتکلِ خامِ مینی‌اپ (فقط وقتی درونِ قاب باشیم)
    function postReady() {
        if (!inFrame()) { return; }
        try {
            function send(eventType, eventData) {
                window.parent.postMessage(
                    JSON.stringify({ eventType: eventType, eventData: eventData }),
                    '*'
                );
            }
            send('iframe_ready', { reload_supported: true });
            send('web_app_ready', null);
            send('web_app_expand', null);
            note('مسیرِ جایگزین: پیام‌های آمادگی با postMessage فرستاده شد ✅');
        } catch (e) {}
    }

    // ۱) اگر شیء از پیش وجود داشت، همان لحظه اعلام کن
    if (announceSDK()) { note('SDK از پیش موجود بود'); }

    // ۲) بارگیریِ اسکریپتِ بله: همیشه و غیرِ مسدودکننده
    var ua = '';
    try { ua = navigator.userAgent || ''; } catch (e) {}

    var sources = ['https://tapi.bale.ai/miniapp.js?3'];
    var inBale = false;
    try { inBale = !!(window.Bale && window.Bale.WebApp) || /bale|ble\.ir/i.test(ua); } catch (e) {}
    if (!inBale && (inFrame() || /telegram/i.test(ua))) {
        sources.unshift('telegram-web-app.js');
    }

    for (var i = 0; i < sources.length; i++) {
        (function (src) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;                 // هرگز مسدودکننده نباشد
            s.onload = function () { note('اسکریپت لود شد: ' + src); announceSDK(); };
            s.onerror = function () { note('خطا در بارگیری: ' + src); };
            document.head.appendChild(s);
            note('درخواستِ بارگیری فرستاده شد: ' + src);
        })(sources[i]);
    }

    // ۳) پایشِ مداوم
    var tries = 0;
    var timer = setInterval(function () {
        tries++;
        announceSDK();
        if ((sdkDone.bale && sdkDone.telegram) || tries > 120) { clearInterval(timer); }
    }, 150);

    // ۴) مسیرِ جایگزین برای کلاینت‌هایی که صفحه را درونِ قاب نشان می‌دهند
    setTimeout(function () { postReady(); }, 400);
    setTimeout(function () { postReady(); }, 1500);
    setTimeout(function () { postReady(); }, 3000);
})();

/* ---- legacy head block 3 ---- */
window.MELKINO_LOGGED_IN = (function () {
        try {
            var el = document.getElementById('mxIndexData');
            return el ? !!JSON.parse(el.textContent || '{}').loggedIn : false;
        } catch (e) { return false; }
    })();
    window.MELKINO_PROFILE = (function () {
        try {
            var el = document.getElementById('mxIndexData');
            return el ? (JSON.parse(el.textContent || '{}').profile || null) : null;
        } catch (e) { return null; }
    })();

    (function () {
        if (window.MELKINO_LOGGED_IN) return;

        function uaHas(p) { try { return new RegExp(p, 'i').test(navigator.userAgent || ''); } catch (e) { return false; } }

        function fromHash() {
            // راند ۶۴: هش اولیهٔ ذخیره‌شده (SDK بله هش زنده را پاک می‌کند)، بعد هش زنده، بعد کوئری.
            var cands = [];
            try { if (window.__melkinoEarlyHash) cands.push(String(window.__melkinoEarlyHash).replace(/^#/, '')); } catch (e0) {}
            try { if (location.hash) cands.push(String(location.hash).replace(/^#/, '')); } catch (e1) {}
            for (var i = 0; i < cands.length; i++) {
                if (!cands[i]) continue;
                try {
                    var raw = new URLSearchParams(cands[i]).get('tgWebAppData') || '';
                    if (raw) return { data: raw, platform: uaHas('bale') ? 'bale' : 'telegram' };
                } catch (e2) {}
            }
            try {
                var q = new URLSearchParams(location.search).get('tgWebAppData') || '';
                if (q) return { data: q, platform: uaHas('bale') ? 'bale' : 'telegram' };
            } catch (e3) {}
            try {
                var saved = (sessionStorage.getItem('melkino_tg_hash') || '').replace(/^#/, '');
                if (saved) {
                    var sraw = new URLSearchParams(saved).get('tgWebAppData') || '';
                    if (sraw) return { data: sraw, platform: uaHas('bale') ? 'bale' : 'telegram' };
                }
            } catch (e4) {}
            return { data: '', platform: '' };
        }

        // راند ۶۴: محیط واقعی (نه حامل داده). داخل تلگرام، آبجکتِ بله هم ممکن
        // است دادهٔ تلگرام را داشته باشد (اسکریپت بله هش را می‌خواند و پاک می‌کند).
        function mxEnv() {
            if (uaHas('telegram')) return 'telegram';
            try { if (window.TelegramWebviewProxy || window.TelegramGameProxy || window.TelegramGameProxy_receiveEvent) return 'telegram'; } catch (e0) {}
            if (uaHas('bale')) return 'bale';
            try {
                if (window.Telegram && window.Telegram.WebApp) {
                    var tp = String(window.Telegram.WebApp.platform || '');
                    if (tp !== '' && tp !== 'unknown') return 'telegram';
                }
            } catch (e1) {}
            try {
                var ref = String(document.referrer || '').toLowerCase();
                if (ref.indexOf('web.telegram.org') !== -1 || ref.indexOf('telegram.org') !== -1 || ref.indexOf('t.me') !== -1) return 'telegram';
                if (ref.indexOf('bale.ai') !== -1 || ref.indexOf('ble.ir') !== -1) return 'bale';
            } catch (e2) {}
            return '';
        }

        function fromSdk() {
            var tg = '', bl = '';
            try { if (window.Telegram && window.Telegram.WebApp) tg = window.Telegram.WebApp.initData || ''; } catch (e) {}
            try { if (window.Bale && window.Bale.WebApp) bl = window.Bale.WebApp.initData || ''; } catch (e) {}
            return { telegram: tg, bale: bl };
        }

        function tryAutoLogin(platform, initData) {
            if (!initData) return;
            try {
                if (sessionStorage.getItem('melkino_autologin_done')) return;
                sessionStorage.setItem('melkino_autologin_done', '1');
            } catch (e) {}

            fetch(platform === 'bale' ? 'auth-bale.php' : 'auth-telegram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ init_data: initData })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) return;
                var url = location.href;
                try {
                    var u = new URL(location.href);
                    if (data.login_token) u.searchParams.set('t', data.login_token);
                    url = u.toString();
                } catch (e) {
                    if (data.login_token) url += (location.search ? '&' : '?') + 't=' + encodeURIComponent(data.login_token);
                }
                location.replace(url);
            })
            .catch(function () {});
        }

        function attempt() {
            var sdk = fromSdk();
            var h = fromHash();
            var env = mxEnv();
            if (env === 'telegram') {
                var d = sdk.telegram || h.data || sdk.bale || '';
                if (d) return tryAutoLogin('telegram', d);
                return;
            }
            if (env === 'bale') {
                var d2 = sdk.bale || h.data || sdk.telegram || '';
                if (d2) return tryAutoLogin('bale', d2);
                return;
            }
            if (sdk.telegram) return tryAutoLogin('telegram', sdk.telegram);
            if (sdk.bale) return tryAutoLogin('bale', sdk.bale);
            if (h.data) return tryAutoLogin(h.platform || 'telegram', h.data);
        }

        /*
        |------------------------------------------------------------------
        | نکته‌ی بسیار مهم (علتِ صفحه‌ی سفید در بله)
        |------------------------------------------------------------------
        | بارگیریِ اسکریپت‌های تلگرام/بله و فراخوانیِ ready() قبلاً همین‌جا
        | انجام می‌شد. اما این بلوک با عبارتِ
        |     if (window.MELKINO_LOGGED_IN) return;
        | شروع می‌شود؛ یعنی برای کاربری که از قبل وارد شده بود، هیچ‌وقت
        | اجرا نمی‌شد، اسکریپت‌ها لود نمی‌شدند و ready() صدا زده نمی‌شد.
        | چون کلاینتِ بله تا دریافتِ پیامِ آمادگی یک لایه‌ی سفید نشان
        | می‌دهد، نتیجه این بود: بار اول صفحه درست می‌آمد، ولی از دفعه‌ی
        | دوم به بعد فقط صفحه‌ی سفید دیده می‌شد.
        |
        | حالا آن کار در بلوکِ بالای <head> و برای همه انجام می‌شود.
        | اینجا فقط تا وقتی initData در دسترس قرار بگیرد، تلاشِ ورودِ
        | خودکار را چند بار تکرار می‌کنیم.
        |------------------------------------------------------------------
        */
        var attemptTries = 0;
        var attemptTimer = setInterval(function () {
            attemptTries++;
            try { attempt(); } catch (e) {}
            if (attemptTries > 40) { clearInterval(attemptTimer); }
        }, 250);

        setTimeout(attempt, 400);
        setTimeout(attempt, 1500);
    })();

/* ---- legacy head block 4 ---- */
(function () {
        try { if (sessionStorage.getItem('melkino_profile_synced')) return; } catch (e) {}

        function sdkData() {
            var tg = '', bl = '';
            try { if (window.Telegram && window.Telegram.WebApp) tg = window.Telegram.WebApp.initData || ''; } catch (e) {}
            try { if (window.Bale && window.Bale.WebApp) bl = window.Bale.WebApp.initData || ''; } catch (e) {}
            return { telegram: tg, bale: bl };
        }

        function hashData() {
            var h = (location.hash || '').replace(/^#/, '');
            if (!h) return '';
            try { var p = new URLSearchParams(h); return p.get('tgWebAppData') || ''; } catch (e) { return ''; }
        }

        function trySync() {
            var d = sdkData();
            var data = d.telegram || d.bale;
            var platform = d.bale ? 'bale' : 'telegram';
            if (!data) data = hashData();
            if (!data) return false;

            try { sessionStorage.setItem('melkino_profile_synced', '1'); } catch (e) {}

            fetch('profile-sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ init_data: data, platform: platform })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success && window.MELKINO_PROFILE) {
                    window.MELKINO_PROFILE.logged_in = true;
                    if (res.name) window.MELKINO_PROFILE.name = res.name;
                    if (res.phone) window.MELKINO_PROFILE.phone = res.phone;
                }
            })
            .catch(function () {});
            return true;
        }

        if (!trySync()) {
            var tries = 0;
            var timer = setInterval(function () {
                tries++;
                if (trySync() || tries > 40) clearInterval(timer);
            }, 150);
        }
    })();