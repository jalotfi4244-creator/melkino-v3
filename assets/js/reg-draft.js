/* Melkino — «ذخیره و ادامه بعداً» + بهبود کیبورد موبایل برای فرم‌های ثبت ملک/درخواست.
 * نسخه ۱ — ذخیره‌سازی محلی (localStorage) همین دستگاه. همهٔ خواندن/نوشتن
 * از طریق DraftStore انجام می‌شود تا اگر بعداً «ادامه در دستگاه دیگر»
 * لازم شد، فقط همین لایه با API سمت‌سرور جایگزین شود (رابط کاربری عوض نمی‌شود).
 * این ماژول هیچ‌وقت فرم را خراب نمی‌کند: همه‌چیز در try/catch است.
 */
(function () {
    'use strict';

    var cfg = window.MELKINO_DRAFT || {};
    var FORM_ID = cfg.formId || 'propertyForm';
    var FORM_KEY = String(cfg.key || 'form').replace(/[^a-zA-Z0-9_-]/g, '');
    var UID = String(window.MELKINO_DRAFT_UID || 'shared').replace(/[^0-9a-zA-Z]/g, '') || 'shared';
    var LS_KEY = 'mkd_' + FORM_KEY + '_' + UID;

    /* ================= لایه ذخیره‌سازی ================= */
    var DraftStore = {
        save: function (data) {
            try {
                window.localStorage.setItem(LS_KEY, JSON.stringify(data));
                return true;
            } catch (e) { return false; }
        },
        load: function () {
            try {
                var raw = window.localStorage.getItem(LS_KEY);
                if (!raw) return null;
                var d = JSON.parse(raw);
                return (d && d.v === 1) ? d : null;
            } catch (e) { return null; }
        },
        clear: function () {
            try { window.localStorage.removeItem(LS_KEY); } catch (e) {}
        }
    };

    /* ================= ابزار ================= */
    function $(sel, root) {
        try { return (root || document).querySelector(sel); } catch (e) { return null; }
    }
    function $all(sel, root) {
        try { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); } catch (e) { return []; }
    }
    function toast(msg, ok) {
        try {
            var old = document.getElementById('mkDraftToast');
            if (old && old.parentNode) old.parentNode.removeChild(old);
            var t = document.createElement('div');
            t.id = 'mkDraftToast';
            t.style.cssText = 'position:fixed;left:50%;bottom:26px;transform:translateX(-50%);' +
                'background:' + (ok === false ? '#b91c1c' : '#064e4e') + ';color:#fff;' +
                'padding:12px 22px;border-radius:12px;font-size:14px;z-index:99999;' +
                'box-shadow:0 6px 24px rgba(0,0,0,.25);max-width:88vw;text-align:center;';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 2600);
        } catch (e) {}
    }
    function fire(el) {
        // اجرای دوبارهٔ منطق‌های فرم (فرمت قیمت، فیلدهای شرطی، پیش‌نمایش) بعد از بازیابی
        try { el.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
        try { el.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {}
    }
    var SKIP_NAMES = { csrf_token: 1, reg_submit_token: 1 };

    /* ================= جمع‌آوری و بازیابی ================= */
    function panels() {
        var form = document.getElementById(FORM_ID);
        var list = $all('.step-content', form || document);
        if (!list.length && form) list = [form];
        return list;
    }
    function currentIndex() {
        var ps = panels();
        for (var i = 0; i < ps.length; i++) {
            try { if (ps[i].classList.contains('active')) return i + 1; } catch (e) {}
        }
        return 1;
    }
    function collect(form) {
        var fields = {};
        var els = form.elements || [];
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            var name = el.name || '';
            if (!name || SKIP_NAMES[name]) continue;
            var type = (el.type || '').toLowerCase();
            if (type === 'submit' || type === 'button' || type === 'file' || type === 'password' || type === 'reset') continue;
            if (type === 'checkbox' || type === 'radio') {
                if (!el.checked) continue;
                if (!fields[name]) fields[name] = [];
                fields[name].push(el.value);
            } else if (el.tagName === 'SELECT' && el.multiple) {
                var vals = [];
                for (var o = 0; o < el.options.length; o++) {
                    if (el.options[o].selected) vals.push(el.options[o].value);
                }
                fields[name] = vals;
            } else {
                fields[name] = el.value;
            }
        }
        return { v: 1, savedAt: Date.now(), step: currentIndex(), total: panels().length, fields: fields };
    }
    function meaningfulCount(d) {
        var n = 0;
        var fs = (d && d.fields) || {};
        for (var k in fs) {
            if (!Object.prototype.hasOwnProperty.call(fs, k)) continue;
            var v = fs[k];
            if (Array.isArray(v)) { if (v.length) n++; }
            else if (String(v).trim() !== '') n++;
        }
        return n;
    }
    function restore(form, d) {
        var fs = (d && d.fields) || {};
        // اول همهٔ چک‌باکس/رادیوها خاموش تا وضعیت ذخیره‌شده دقیق بازسازی شود
        $all('input[type="checkbox"],input[type="radio"]', form).forEach(function (el) {
            try { el.checked = false; } catch (e) {}
        });
        Object.keys(fs).forEach(function (name) {
            var v = fs[name];
            var arr = Array.isArray(v) ? v : [v];
            var group = [];
            try {
                group = Array.prototype.slice.call(form.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]'));
            } catch (e) { return; }
            group.forEach(function (el) {
                try {
                    var type = (el.type || '').toLowerCase();
                    if (type === 'checkbox' || type === 'radio') {
                        el.checked = arr.map(String).indexOf(String(el.value)) >= 0;
                    } else if (el.tagName === 'SELECT' && el.multiple) {
                        for (var o = 0; o < el.options.length; o++) {
                            el.options[o].selected = arr.map(String).indexOf(String(el.options[o].value)) >= 0;
                        }
                    } else {
                        el.value = String(arr[0] !== undefined ? arr[0] : '');
                    }
                    fire(el);
                } catch (e) {}
            });
        });
    }

    /* ================= پرش به مرحله ================= */
    function nativeStepFn() {
        try {
            if (typeof window.changeReqStep === 'function' && FORM_ID === 'requestForm') return 'changeReqStep';
            if (typeof window.changeStep === 'function') return 'changeStep';
        } catch (e) {}
        return null;
    }
    function afterLand(n, total) {
        // همگام‌سازی پیش‌نمایش/خلاصه در مرحلهٔ آخر (از روی DOM بازیابی‌شده می‌خوانند)
        if (n >= total) {
            try { if (typeof window.updatePreview === 'function') window.updatePreview(); } catch (e) {}
            try { if (typeof window.updateSummary === 'function') window.updateSummary(); } catch (e) {}
        }
        try {
            var mc = document.getElementById('mainContent');
            if (mc) mc.scrollTop = 0; else window.scrollTo(0, 0);
        } catch (e) {}
    }
    function syncGlobals(n) {
        // تلاش برای همگام‌سازی متغیر مرحلهٔ خود صفحه (var روی window است؛ let با eval غیرمستقیم)
        try { if ('currentStep' in window) window.currentStep = n; } catch (e) {}
        try { if ('currentReqStep' in window) window.currentReqStep = n; } catch (e) {}
        try { (0, eval)('currentStep=' + n); } catch (e) {}
        try { (0, eval)('currentReqStep=' + n); } catch (e) {}
        // بازپخش وضعیت روی دکمه‌ها/متن/خط‌کشی با منطق خود صفحه (جهت صفر = بدون اعتبارسنجی)
        try { if (typeof window.changeStep === 'function') window.changeStep(0); } catch (e) {}
        try { if (typeof window.updateRequestNavigation === 'function') window.updateRequestNavigation(); } catch (e) {}
    }
    function gotoStep(n) {
        try {
            var ps = panels();
            if (ps.length < 2) return true;
            n = Math.max(1, Math.min(ps.length, n | 0 || 1));
            var fn = nativeStepFn();
            if (fn) {
                var guard = 0;
                while (currentIndex() !== n && guard++ < 15) {
                    var c = currentIndex();
                    window[fn](n > c ? 1 : -1);
                    if (currentIndex() === c) break; // اعتبارسنجی صفحه جلوی حرکت را گرفت
                }
                if (currentIndex() === n) { afterLand(n, ps.length); return true; }
            }
            // مسیر جایگزین دستی
            ps.forEach(function (el, i) {
                try { el.classList.toggle('active', i === n - 1); } catch (e) {}
            });
            syncGlobals(n);
            afterLand(n, ps.length);
            return true;
        } catch (e) { return false; }
    }

    /* ================= بنر ادامهٔ پیش‌نویس ================= */
    function faDate(ts) {
        try {
            var d = new Date(ts);
            return d.toLocaleDateString('fa-IR') + ' ساعت ' +
                d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
        } catch (e) { return ''; }
    }
    function showBanner(form, d) {
        try {
            if (!form || form.querySelector('[data-draft-banner]')) return;
            var b = document.createElement('div');
            b.setAttribute('data-draft-banner', '1');
            b.style.cssText = 'background:#fffbeb;border:2px solid #f59e0b;border-radius:14px;' +
                'padding:14px 16px;margin:12px;display:flex;flex-direction:column;gap:10px;';
            var info = document.createElement('div');
            info.style.cssText = 'font-size:14px;color:#92400e;line-height:1.9;';
            info.textContent = '💾 یک پیش‌نویس ذخیره‌شده از ' + faDate(d.savedAt) +
                ' (مرحلهٔ ' + d.step + ' از ' + d.total + ') پیدا شد.';
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;gap:10px;';
            var btnGo = document.createElement('button');
            btnGo.type = 'button';
            btnGo.style.cssText = 'flex:1;padding:11px;border:none;border-radius:10px;background:#064e4e;' +
                'color:#fff;font-size:14px;font-weight:700;cursor:pointer;';
            btnGo.textContent = '▶ ادامه دادن';
            var btnDel = document.createElement('button');
            btnDel.type = 'button';
            btnDel.style.cssText = 'flex:1;padding:11px;border-radius:10px;background:#fff;' +
                'color:#b91c1c;border:1.5px solid #fecaca;font-size:14px;cursor:pointer;';
            btnDel.textContent = '🗑 حذف پیش‌نویس';
            btnGo.addEventListener('click', function () {
                try {
                    restore(form, d);
                    gotoStep(d.step || 1);
                    if (b.parentNode) b.parentNode.removeChild(b);
                    toast('پیش‌نویس بازیابی شد ✓');
                } catch (e) { toast('بازیابی انجام نشد', false); }
            });
            btnDel.addEventListener('click', function () {
                DraftStore.clear();
                if (b.parentNode) b.parentNode.removeChild(b);
                toast('پیش‌نویس حذف شد');
            });
            row.appendChild(btnGo);
            row.appendChild(btnDel);
            b.appendChild(info);
            b.appendChild(row);
            form.insertBefore(b, form.firstChild);
        } catch (e) {}
    }

    /* ================= بهبود کیبورد موبایل ================= */
    function keyboardUX(form) {
        try { form.setAttribute('data-mk-kb', '1'); } catch (e) {}
        // ۱) استایل تزریقی: فضای خالی به‌اندازهٔ ارتفاع کیبورد + جلوگیری از زوم ناخواسته
        try {
            var st = document.createElement('style');
            st.setAttribute('data-mk-kb-style', '1');
            st.textContent =
                'form[data-mk-kb]{padding-bottom:var(--mk-kb,0px);}' +
                '@media (pointer:coarse){' +
                'form[data-mk-kb] input[type="text"],form[data-mk-kb] input[type="tel"],' +
                'form[data-mk-kb] input[type="number"],form[data-mk-kb] input[type="search"],' +
                'form[data-mk-kb] input[type="email"],form[data-mk-kb] textarea,' +
                'form[data-mk-kb] select{font-size:16px !important;}}';
            document.head.appendChild(st);
        } catch (e) {}
        // ۲) اسکرول فیلد فوکوس‌شده به وسط دید (بعد از بالا آمدن کیبورد)
        try {
            document.addEventListener('focusin', function (ev) {
                var el = ev.target;
                if (!el || !form.contains(el)) return;
                var tag = (el.tagName || '').toUpperCase();
                var type = ((el.type || '').toLowerCase());
                if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') return;
                if (tag === 'INPUT' && /^(checkbox|radio|file|button|submit|reset|range|hidden|color)$/.test(type)) return;
                setTimeout(function () {
                    try {
                        if (document.activeElement === el && el.scrollIntoView) {
                            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }
                    } catch (e) {}
                }, 350);
            });
        } catch (e) {}
        // ۳) ارتفاع واقعی کیبورد از visualViewport
        try {
            var vv = window.visualViewport;
            if (vv) {
                var applyKb = function () {
                    try {
                        var kb = Math.round(window.innerHeight - vv.height - vv.offsetTop);
                        form.style.setProperty('--mk-kb', (kb > 120 ? kb : 0) + 'px');
                    } catch (e) {}
                };
                vv.addEventListener('resize', applyKb);
                vv.addEventListener('scroll', applyKb);
            }
        } catch (e) {}
        // ۴) کیبورد مناسب هر فیلد (فقط اگر خود فرم چیزی تعیین نکرده باشد)
        try {
            var inputs = form.querySelectorAll('input');
            for (var i = 0; i < inputs.length; i++) {
                (function (el) {
                    try {
                        if (el.hasAttribute('inputmode')) return;
                        var t = (el.type || 'text').toLowerCase();
                        if (t !== 'text' && t !== 'tel' && t !== 'number' && t !== 'search') return;
                        var idn = ((el.name || '') + ' ' + (el.id || '')).toLowerCase();
                        if (/(phone|mobile|tel|شماره)/.test(idn)) el.setAttribute('inputmode', 'tel');
                        else if (/(price|deposit|rent|area|meter|metraj|room|floor|count|year|age|code|postal|otp|متر|قیمت|مبلغ|تعداد|طبقه|اتاق|سال)/.test(idn)) el.setAttribute('inputmode', 'numeric');
                        else if (/(email|mail)/.test(idn)) el.setAttribute('inputmode', 'email');
                        if (!el.hasAttribute('enterkeyhint') && (t === 'text' || t === 'search')) {
                            el.setAttribute('enterkeyhint', 'next');
                        }
                    } catch (e) {}
                })(inputs[i]);
            }
        } catch (e) {}
    }

    /* ================= راه‌اندازی ================= */
    function init() {
        var form = null;
        try { form = document.getElementById(FORM_ID); } catch (e) {}
        if (!form) return;
        keyboardUX(form);
        // دکمه‌های «ذخیره و ادامه بعداً»
        $all('[data-draft-save]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                try {
                    var d = collect(form);
                    if (meaningfulCount(d) < 1 && d.step <= 1) {
                        toast('هنوز چیزی وارد نشده که ذخیره شود', false);
                        return;
                    }
                    if (DraftStore.save(d)) {
                        toast('💾 پیش‌نویس ذخیره شد — هر وقت برگردی ادامه می‌دهی');
                        setTimeout(function () {
                            try { window.location.href = 'home.php'; } catch (e) {}
                        }, 1100);
                    } else {
                        toast('ذخیره نشد (حافظهٔ مرورگر)', false);
                    }
                } catch (e) { toast('ذخیره نشد', false); }
            });
        });
        // ذخیرهٔ خودکار آرام (بدون پیام و بدون خروج)
        var t = null;
        var auto = function () {
            try {
                if (t) clearTimeout(t);
                t = setTimeout(function () {
                    try {
                        var d = collect(form);
                        if (meaningfulCount(d) >= 2 || d.step > 1) DraftStore.save(d);
                    } catch (e) {}
                }, 1200);
            } catch (e) {}
        };
        try {
            form.addEventListener('input', auto);
            form.addEventListener('change', auto);
            window.addEventListener('pagehide', function () {
                try {
                    var d = collect(form);
                    if (meaningfulCount(d) >= 2 || d.step > 1) DraftStore.save(d);
                } catch (e) {}
            });
        } catch (e) {}
        // بنر ادامه (کمی دیرتر تا ویزارد صفحه کامل بالا بیاید)
        setTimeout(function () {
            try {
                var d = DraftStore.load();
                if (d && meaningfulCount(d) >= 1) showBanner(form, d);
            } catch (e) {}
        }, 600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // برای تست خودکار
    window.MelkinoDraft = { collect: collect, restore: restore, gotoStep: gotoStep, store: DraftStore };
})();
