/* Eitaa preload only. Identity is never read from initDataUnsafe/contact/client IDs. */
(function () {
    'use strict';
    var body = document.body;
    var status = document.getElementById('eitaaStatus');
    var spinner = document.getElementById('eitaaSpinner');
    var retry = document.getElementById('eitaaRetry');
    var close = document.getElementById('eitaaClose');
    var sdk = window.Eitaa && window.Eitaa.WebApp;
    var inflight = false;
    function ready() { try { if (sdk && typeof sdk.ready === 'function') sdk.ready(); } catch (e) {} }
    function message(text, error) {
        status.textContent = text;
        status.setAttribute('data-error', error ? '1' : '0');
        spinner.hidden = !!error;
    }
    if (sdk) {
        close.hidden = false;
        close.addEventListener('click', function () { try { sdk.close(); } catch (e) {} });
    }
    if (body.getAttribute('data-eitaa-login-enabled') !== '1') { ready(); return; }
    if (!sdk) {
        message('هویت ایتا دریافت نشد؛ کیت رسمی ایتا بارگیری نشده است. اتصال اینترنت را بررسی کنید و دوباره تلاش کنید.', true);
        retry.hidden = false;
        retry.addEventListener('click', function () { location.reload(); });
        return;
    }
    var initData = typeof sdk.initData === 'string' ? sdk.initData : '';
    if (!initData) {
        message('هویت ایتا دریافت نشد. این صفحه باید با لینک یا دکمهٔ برنامک، از داخل ایتا باز شود؛ لینک معمولی سایت کافی نیست.', true);
        ready();
        return;
    }
    // SDK has already captured its launch parameters. Do not leave auth data in the URL.
    try {
        var clean = new URL(location.href);
        Array.from(clean.searchParams.keys()).forEach(function (key) {
            if (/^tgWebApp/i.test(key)) clean.searchParams.delete(key);
        });
        clean.hash = '';
        history.replaceState(history.state, '', clean.pathname + clean.search);
    } catch (e) {}
    function login() {
        if (inflight) return;
        inflight = true;
        retry.hidden = true;
        spinner.hidden = false;
        message('در حال بررسی امن هویت شما…', false);
        var meta = document.querySelector('meta[name="csrf-token"]');
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timer = controller ? setTimeout(function () { controller.abort(); }, 15000) : null;
        var options = {
            method: 'POST', credentials: 'same-origin', cache: 'no-store',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-Token': meta ? meta.content : '' },
            body: JSON.stringify({ init_data: initData })
        };
        if (controller) options.signal = controller.signal;
        fetch('auth-eitaa.php', options).then(function (response) {
            return response.json().then(function (data) { return { response: response, data: data }; });
        }).then(function (result) {
            if (!result.response.ok || result.data.success !== true) {
                message(result.response.status === 419
                    ? 'نشست امن در دسترس نیست. کوکی‌ها را فعال کنید و برنامک را دوباره از ایتا باز کنید.'
                    : (result.data.message || 'ورود انجام نشد؛ برنامک را دوباره باز کنید.'), true);
                retry.hidden = [401,403,409,419].indexOf(result.response.status) !== -1;
                ready();
                return;
            }
            // Remove previous identity/contact hints. Verified DB values refill them;
            // unrelated ad drafts and theme preferences are not cleared.
            ['melkino_login_token','melkino_telegram_id','melkino_bale_id','melkino_eitaa_id','melkino_user_id','melkino_user_phone','melkino_user_name'].forEach(function (key) {
                try { localStorage.removeItem(key); } catch (e) {}
            });
            ['reg_telegram_id','reg_bale_id','reg_eitaa_id','reg_phone','reg_last_name','melkino_user_id','melkino_tg_hash','melkino_profile_synced','melkino_identified_ok'].forEach(function (key) {
                try { sessionStorage.removeItem(key); } catch (e) {}
            });
            message('ورود تأیید شد؛ در حال باز کردن ملکینو…', false);
            var next = new URL(body.getAttribute('data-next') || 'home.php', location.href);
            if (next.origin !== location.origin) next = new URL('home.php', location.href);
            // No initData or bearer/login token in a query string or localStorage.
            location.replace(next.href);
        }).catch(function () {
            message('ارتباط ورود برقرار نشد؛ اتصال اینترنت را بررسی و دوباره تلاش کنید.', true);
            retry.hidden = false;
            ready();
        }).then(function () {
            inflight = false;
            if (timer) clearTimeout(timer);
        });
    }
    retry.addEventListener('click', login);
    login();
})();
