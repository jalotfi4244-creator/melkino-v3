/* Melkino V2 — display admin tab: card-display IIFE VERBATIM from admin-new-tabs.js
 * + MK_IC block (panel). Requires ./admin-field-display.js (root, unchanged).
 * APIs: admin-card-display.php, field-display endpoints (untouched).
 * 14 fragment onclick -> data-act + delegation.
 */
window.MK_IC = Object.assign(window.MK_IC || {}, {
            megaphone: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10v4l11 5V5z"/><path d="M14 8a4 4 0 0 1 0 8"/><path d="M6 14v5"/></svg>',
            trash: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/></svg>',
            chat: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>',
            plus: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
            search: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
            send: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 3 3 10.5l7 3 3 7z"/><path d="M21 3 10 13.5"/></svg>',
            star: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.8 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 3 1.2-6.1L3 9.6l6.3-.8z"/></svg>',
            bank: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18M12 3 3 10h18z"/></svg>',
            coins: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>',
            home: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11l8-7 8 7"/><path d="M6 9.5V21h12V9.5"/></svg>',
    save:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h11l3 3v15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M8 3v5h7V3"/><path d="M8 21v-7h8v7"/></svg>',
    eye:      '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>',
    x:        '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    upload:   '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 20h14"/></svg>',
    download: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
    bulb:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-4 10.5c.8.7 1 1.5 1 2.5h6c0-1 .2-1.8 1-2.5A6 6 0 0 0 12 3Z"/></svg>',
    lock:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
    gear:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/></svg>',
    user:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>',
    users:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.4 2.9-5.5 6.5-5.5s6.5 2.1 6.5 5.5"/><circle cx="17" cy="9" r="3"/><path d="M17.5 14.6c2.4.5 4 2.2 4 4.4"/></svg>',
    phone:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>',
    headset:  '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/></svg>',
    puzzle:   '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg>',
    map:      '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/></svg>',
    image:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-4.5-4.5L7 21"/></svg>',
    list:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>',
    edit:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>',
    globe:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18Z"/></svg>',
    check:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 5-5.5"/></svg>'
});

/* ==============================================================
   تب «نمایش» — مدیریت میدان‌به‌میدان کارت‌های آگهی (راند ۲۱)
   هر فیلد آگهی: متن (چیپ) / حباب (پیل) / مخفی
   ============================================================== */
(function () {
    'use strict';

    const cdEsc = (x) => String(x == null ? '' : x)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    let CD_DEFS = null;
    let CD_SETTINGS = {};
    let CD_FILTER = '';

    const CD_SCOPE_LABELS = { home: 'صفحهٔ اصلی', list: 'فهرست آگهی‌ها', both: 'هر دو صفحه' };
    const CD_SPECIAL = { loan: ' loan', exchange: ' exchange', key_not_turned: ' key' };

    /* مقادیر نمونه برای پیش‌نمایش زنده */
    const CD_SAMPLE = {
        title: 'آپارتمان ۱۲۰ متری در مرکز شهر',
        code: 'AD-14050628-1234',
        price: '۵٬۰۰۰٬۰۰۰٬۰۰۰ تومان',
        loan_price_line: 'نقد: ۳٬۵۰۰٬۰۰۰٬۰۰۰ + وام: ۱٬۵۰۰٬۰۰۰٬۰۰۰ تومان',
        location: 'شاهرود، خیابان امام، کوچهٔ گل',
        date: '1405/06/28',
        transaction: 'خرید و فروش',
        property_type: 'آپارتمان',
        area: '۱۲۰ متر',
        rooms: '۳ اتاق',
        floor: 'طبقهٔ ۲',
        year: 'ساخت ۱۴۰۰',
        parking: 'پارکینگ',
        elevator: 'آسانسور',
        key_not_turned: 'کلید نخورده',
        loan: 'وام',
        exchange: 'مایل به معاوضه',
        tags: ['ویژه', 'بازسازی‌شده'],
        deed: 'طلق',
        deposit: 'رهن ۵۰۰٬۰۰۰٬۰۰۰ تومان',
        rent_monthly: 'اجاره ۱۵٬۰۰۰٬۰۰۰ تومان',
        full_rent: 'رهن کامل ۸۰۰٬۰۰٬۰۰۰ تومان'
    };

    const cdModeOf = (k) => {
        const v = CD_SETTINGS[k];
        if (v === true || v === 'on') return 'on';
        if (v === false || v === undefined || v === null) return 'off';
        return String(v);
    };
    const cdShowOf = (k) => cdModeOf(k) !== 'off';

    function cdSampleValue(k, def) {
        if (CD_SAMPLE[k] !== undefined) return CD_SAMPLE[k];
        if (k.indexOf('pd.') === 0) {
            const dk = k.slice(3);
            if (dk.indexOf('has_') === 0) return 'دارد';
            if (def && def.suffix) return '۱۲' + def.suffix;
        }
        return 'نمونهٔ ' + ((def && def.label) || k);
    }

    /* فهرست آیتم‌های مشخصات برای یک scope — آینهٔ رندر واقعی کارت */
    function cdItemsFor(scope) {
        const out = [];
        const items = (CD_DEFS && CD_DEFS.specs && CD_DEFS.specs.items) || {};
        Object.keys(items).forEach((k) => {
            const def = items[k] || {};
            const ds = def.scope || 'both';
            if (ds !== 'both' && ds !== scope) return;
            const mode = cdModeOf(k);
            if (mode === 'off' || mode === 'on') { if (mode !== 'text' && mode !== 'pill') return; }
            if (k === 'tags') {
                (CD_SAMPLE.tags || []).forEach((t) => out.push({ key: k, mode: mode, emoji: '', label: 'برچسب', value: t, cls: '' }));
                return;
            }
            out.push({ key: k, mode: mode, emoji: def.emoji || '', label: def.label || k, value: cdSampleValue(k, def), cls: CD_SPECIAL[k] || '' });
        });
        return out;
    }

    function cdCardHtml(scope, horizontal) {
        const mainItems = (CD_DEFS && CD_DEFS.main && CD_DEFS.main.items) || {};
        const show = (k) => {
            const def = mainItems[k];
            if (def) {
                const ds = def.scope || 'both';
                if (ds !== 'both' && ds !== scope) return false;
            }
            return cdShowOf(k);
        };
        const items = cdItemsFor(scope);
        const pills = items.filter((i) => i.mode === 'pill');
        const chips = items.filter((i) => i.mode === 'text');
        const pillsHtml = pills.map((i) =>
            '<span class="cdp-badge' + i.cls + '">' + cdEsc((i.emoji ? i.emoji + ' ' : '') + i.value) + '</span>').join('');
        const chipsHtml = chips.map((i) =>
            '<span class="cdp-chip">' + cdEsc((i.emoji ? i.emoji + ' ' : '') + (String(i.key).indexOf('pd.') === 0 ? i.label + ': ' : '') + i.value) + '</span>').join('');
        const titleHtml = show('title') ? '<div class="cdp-title">' + cdEsc(CD_SAMPLE.title) + '</div>' : '';
        const locHtml = show('location') ? '<div class="cdp-loc">📍 ' + cdEsc(CD_SAMPLE.location) + '</div>' : '';
        const chipsBlock = chipsHtml ? '<div class="cdp-details">' + chipsHtml + '</div>' : '';
        const pillsBlock = pillsHtml ? '<div class="cdp-badges"' + (horizontal ? ' style="padding:8px 0 0"' : '') + '>' + pillsHtml + '</div>' : '';
        const priceHtml = show('price') ? '<div class="cdp-price">' + cdEsc(CD_SAMPLE.price) + '</div>' : '';

        if (horizontal) {
            return '<div class="cdp-card cdp-h">' +
                '<div class="cdp-img">🖼️</div>' +
                '<div style="flex:1;min-width:0"><div class="cdp-body">' +
                (show('code') ? '<div style="font-size:10px;color:var(--text-secondary);margin-bottom:2px">' + cdEsc(CD_SAMPLE.code) + '</div>' : '') +
                titleHtml + locHtml + pillsBlock + chipsBlock + priceHtml +
                '</div></div></div>';
        }
        return '<div class="cdp-card">' +
            '<div class="cdp-img">🖼️ تصویر آگهی</div>' +
            pillsBlock +
            '<div class="cdp-body">' + titleHtml + locHtml + chipsBlock + priceHtml +
            (show('loan_price_line') ? '<div class="cdp-loanline">' + MK_IC.bank + ' ' + cdEsc(CD_SAMPLE.loan_price_line) + '</div>' : '') +
            '</div>' +
            '<div class="cdp-footer"><span>' + (show('date') ? cdEsc(CD_SAMPLE.date) : '') + '</span><span>🔍 مشاهده جزئیات</span></div>' +
            '</div>';
    }

    function renderPreview() {
        const box = document.getElementById('cardDisplayPreview');
        if (!box || !CD_DEFS) return;
        box.innerHTML =
            '<div class="cd-preview-col"><h4>کارت صفحهٔ اصلی</h4>' + cdCardHtml('home', false) + '</div>' +
            '<div class="cd-preview-col"><h4>کارت فهرست آگهی‌ها</h4>' + cdCardHtml('list', true) + '</div>';
    }

    function renderControls() {
        const box = document.getElementById('cardDisplayControls');
        if (!box || !CD_DEFS) return;
        const f = CD_FILTER.trim().toLowerCase();
        let html = '<input type="text" id="cdSearch" class="cd-search" placeholder="جست‌وجوی فیلد… (مثلاً: طبقه، سند، وام، متراژ)" value="' + cdEsc(CD_FILTER) + '">';
        Object.keys(CD_DEFS).forEach((gname) => {
            const group = CD_DEFS[gname] || {};
            const items = group.items || {};
            const isMain = gname === 'main';
            let rows = '';
            let shown = 0;
            Object.keys(items).forEach((k) => {
                const def = items[k] || {};
                const label = String(def.label || k);
                if (f && (label + ' ' + k).toLowerCase().indexOf(f) === -1) return;
                shown++;
                const cur = cdModeOf(k);
                const segs = isMain
                    ? [['on', 'نمایش'], ['off', 'مخفی']]
                    : [['text', 'متن'], ['pill', 'حباب'], ['off', 'مخفی']];
                const segHtml = segs.map((sg) =>
                    '<button type="button" class="' + (cur === sg[0] ? 'on' : '') + '" data-cd-key="' + cdEsc(k) + '" data-cd-mode="' + sg[0] + '">' + sg[1] + '</button>').join('');
                const scopeL = CD_SCOPE_LABELS[def.scope || 'both'] || '';
                const help = def.help || (k.indexOf('pd.') === 0
                    ? 'فیلد تخصصی فرم ثبت آگهی — فقط وقتی پر شده باشد روی کارت نمایش داده می‌شود.'
                    : '');
                rows += '<div class="cd-item">' +
                    '<div class="cd-item-info"><span>' + (def.emoji ? cdEsc(def.emoji) + ' ' : '') + cdEsc(label) +
                    ' <span class="cd-scope">' + cdEsc(scopeL) + '</span></span>' +
                    (help ? '<small>' + cdEsc(help) + '</small>' : '') +
                    '</div>' +
                    '<div class="cd-seg">' + segHtml + '</div>' +
                    '</div>';
            });
            if (!shown && f) return;
            html += '<div class="cd-group">' +
                '<div class="cd-group-title">' + cdEsc(group.label || gname) + ' <span class="cd-count">' + shown + ' فیلد</span></div>' +
                (group.help ? '<div class="cd-group-help">' + cdEsc(group.help) + '</div>' : '') +
                (rows || '<div class="admin-field-help">موردی یافت نشد.</div>') +
                '</div>';
        });
        box.innerHTML = html;

        const si = document.getElementById('cdSearch');
        if (si) {
            si.addEventListener('input', () => {
                CD_FILTER = si.value;
                renderControls();
                renderPreview();
                const n = document.getElementById('cdSearch');
                if (n) { n.focus(); try { n.setSelectionRange(n.value.length, n.value.length); } catch (e) {} }
            });
        }
        box.querySelectorAll('[data-cd-key]').forEach((b) => b.addEventListener('click', () => {
            const k = b.getAttribute('data-cd-key');
            const m = b.getAttribute('data-cd-mode');
            const isMain = !!(CD_DEFS.main && CD_DEFS.main.items && CD_DEFS.main.items[k]);
            CD_SETTINGS[k] = isMain ? (m === 'on') : m;
            renderControls();
            renderPreview();
        }));
    }

    function cdStatus(msg, ok) {
        const st = document.getElementById('cardDisplayStatus');
        if (!st) return;
        const clean = String(msg || '').replace(/^[\u2705\u274C\u2714\u26D4]\s*/u, '');
        st.innerHTML = (typeof mkStatusHtml === 'function') ? mkStatusHtml(ok, clean) : clean;
        st.style.color = ok === true ? 'var(--success)' : (ok === false ? 'var(--danger)' : 'var(--text-secondary)');
        st.dataset.mkMsg = clean;
        if (clean && ok !== undefined) setTimeout(() => { if (st.dataset.mkMsg === clean) st.innerHTML = ''; }, 4500);
    }

    window.melkinoSaveCardDisplay = async function () {
        if (!CD_DEFS) return;
        cdStatus('در حال ذخیره…');
        try {
            const payload = {};
            const all = Object.assign({}, (CD_DEFS.main || {}).items || {}, (CD_DEFS.specs || {}).items || {});
            Object.keys(all).forEach((k) => {
                const v = CD_SETTINGS[k];
                payload[k] = (v === undefined || v === null) ? all[k].default : v;
            });
            const res = await fetch('admin-card-display.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ settings: payload })
            });
            const data = await res.json();
            if (data && data.success) {
                CD_SETTINGS = payload;
                cdStatus('✅ تنظیمات نمایش ذخیره شد.', true);
            } else {
                cdStatus('❌ ذخیره ناموفق بود.' + ((data && data.message) ? ' ' + data.message : ''), false);
            }
        } catch (e) {
            cdStatus('❌ خطا در ذخیره: ' + (e.message || e), false);
        }
    };

    window.melkinoResetCardDisplay = function () {
        if (!CD_DEFS) return;
        const fresh = {};
        Object.keys(CD_DEFS).forEach((g) => {
            const items = (CD_DEFS[g] || {}).items || {};
            Object.keys(items).forEach((k) => { fresh[k] = items[k].default; });
        });
        CD_SETTINGS = fresh;
        renderControls();
        renderPreview();
        cdStatus('↺ به پیش‌فرض برگشت — برای اعمال، «ذخیره» را بزنید.');
    };

    window.melkinoInitDisplayTab = async function () {
        const box = document.getElementById('cardDisplayControls');
        try {
            const res = await fetch('admin-card-display.php?action=get', { cache: 'no-store' });
            const data = await res.json();
            if (!data || !data.success || !data.defs) throw new Error((data && data.message) || 'پاسخ نامعتبر');
            CD_DEFS = data.defs;
            CD_SETTINGS = data.settings || {};
            renderControls();
            renderPreview();
        } catch (e) {
            if (box) box.innerHTML = '<div class="admin-field-help">دریافت تنظیمات نمایش ناموفق بود: ' + cdEsc(e.message || e) + '</div>';
        }
    };
})();

/* V2: delegation for fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act'), scope = el.getAttribute('data-scope');
    if (act === 'fd-sub') { melkinoFdSwitchSub(el.getAttribute('data-sub'), el); }
    else if (act === 'fd-refresh') { melkinoFdRefreshPreview(scope); }
    else if (act === 'fd-save') { ev.preventDefault(); melkinoFdSave(scope); }
    else if (act === 'fd-cancel') { melkinoFdCancel(scope); }
    else if (act === 'fd-restore') { melkinoFdRestore(scope); }
    else if (act === 'cd-init') { melkinoInitDisplayTab(true); }
    else if (act === 'cd-reset') { melkinoResetCardDisplay(); }
    else if (act === 'cd-save') { melkinoSaveCardDisplay(); }
});
/* V2 boot: same as panel switchTab('display'). */
document.addEventListener('DOMContentLoaded', function () {
    try { if (typeof melkinoInitDisplayTab === 'function') melkinoInitDisplayTab(); } catch (e) {}
    try { if (typeof melkinoInitFieldDisplay === 'function') melkinoInitFieldDisplay(); } catch (e) {}
});
