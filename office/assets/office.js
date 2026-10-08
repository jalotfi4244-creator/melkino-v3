/* دفتر ملکینو شهر — تم، ساعت، سایدبار موبایل (بدون هیچ وابستگی) */
(function () {
    'use strict';

    // ---------- تم روشن/تیره ----------
    var root = document.documentElement;
    var themeBtn = document.getElementById('ofTheme');

    function paintThemeBtn() {
        if (!themeBtn) return;
        themeBtn.textContent = root.getAttribute('data-theme') === 'light' ? '☀️' : '🌙';
    }

    // اگر کاربر قبلاً انتخابی داشته و کوکی پاک شده، از localStorage بخوان
    try {
        var saved = localStorage.getItem('office_theme');
        if ((saved === 'light' || saved === 'dark') && root.getAttribute('data-theme') !== saved) {
            root.setAttribute('data-theme', saved);
            document.cookie = 'office_theme=' + saved + ';path=/;max-age=31536000;SameSite=Lax';
        }
    } catch (e) { /* ignore */ }
    paintThemeBtn();

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            root.setAttribute('data-theme', next);
            document.cookie = 'office_theme=' + next + ';path=/;max-age=31536000;SameSite=Lax';
            try { localStorage.setItem('office_theme', next); } catch (e) { /* ignore */ }
            paintThemeBtn();
        });
    }

    // ---------- ساعت زنده تهران ----------
    var clock = document.getElementById('ofClock');
    function tick() {
        if (!clock) return;
        try {
            clock.textContent = new Intl.DateTimeFormat('fa-IR', {
                timeZone: 'Asia/Tehran',
                weekday: 'long', day: 'numeric', month: 'long',
                hour: '2-digit', minute: '2-digit'
            }).format(new Date());
        } catch (e) {
            clock.textContent = new Date().toLocaleString();
        }
    }
    tick();
    setInterval(tick, 30000);

    // ---------- سایدبار موبایل ----------
    var burger = document.getElementById('ofBurger');
    var side = document.getElementById('ofSide');
    if (burger && side) {
        burger.addEventListener('click', function (ev) {
            ev.stopPropagation();
            side.classList.toggle('open');
        });
        document.addEventListener('click', function (ev) {
            if (side.classList.contains('open') && !side.contains(ev.target)) {
                side.classList.remove('open');
            }
        });
    }
})();

/* قانون مبالغ دفتر: جداکنندهٔ سه‌رقمی حین تایپ + حذف جداکننده هنگام ارسال */
(function () {
    'use strict';
    var FA = '۰۱۲۳۴۵۶۷۸۹';
    function groupDigits(s) {
        s = String(s)
            .replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); })
            .replace(/[^0-9]/g, '')
            .replace(/^0+(?=\d)/, '');
        return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || !el.matches || !el.matches('input[data-money]')) return;
        var pos = el.value.length;
        try { pos = (el.selectionStart === null ? el.value.length : el.selectionStart); } catch (err) { /* ignore */ }
        var digitsBefore = el.value.slice(0, pos).replace(/[^0-9۰-۹]/g, '').length;
        el.value = groupDigits(el.value);
        var i = 0, n = 0;
        while (i < el.value.length && n < digitsBefore) {
            if (/[0-9]/.test(el.value[i])) n++;
            i++;
        }
        try { el.setSelectionRange(i, i); } catch (err) { /* ignore */ }
    });
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || !f.querySelectorAll) return;
        f.querySelectorAll('input[data-money]').forEach(function (el) {
            el.value = el.value.replace(/,/g, '');
        });
    });
})();
