/* Melkino V2 — admin ads tab boot.
 * MK_IC block + progressive-load IIFE extracted VERBATIM from admin-panel.php;
 * globals use the panel's exact names (admin-ads.js reads bare `adsData`).
 * Requires: admin-tab-shared.js, ./telegram-relay.js, ./admin-ads-map.js,
 * ./admin-ads.js (root, unchanged). API: admin-panel.php?action=ads_chunk.
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

/* Panel bootstrap (L6060-6085) via JSON blob (view emits #mxAdminAdsData). */
let adsData = [];
window.MELKINO_ADS_META = { loaded: 0, total: 0, hasMore: false, error: '' };
window.MELKINO_AD_TOTALS = {};
(function () {
    try {
        var el = document.getElementById('mxAdminAdsData');
        if (!el) return;
        var j = JSON.parse(el.textContent || '{}');
        adsData = Array.isArray(j.rows) ? j.rows : [];
        window.MELKINO_ADS_META = {
            loaded: Number(j.loaded || 0),
            total: Number(j.total || 0),
            hasMore: !!j.hasMore,
            error: String(j.error || '')
        };
        window.MELKINO_AD_TOTALS = j.totals || {};
        window.MELKINO_RATING_ENABLED = !!j.ratingEnabled;
        window.MELKINO_DEFAULT_IMAGES = j.defaultImages || {};
    } catch (e) { /* panel shows mock/empty the same way when bootstrap fails */ }
})();

(function () {
    var M = window.MELKINO_ADS_META || {};

    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function makeBox(bg, html) {
        var d = document.createElement('div');
        d.setAttribute(
            'style',
            'position:fixed;left:14px;bottom:14px;z-index:10050;max-width:min(430px,calc(100vw - 28px));' +
            'background:' + bg + ';color:#fff;border-radius:14px;padding:12px 14px;' +
            'font-family:inherit;font-size:13px;line-height:1.8;direction:rtl;' +
            'box-shadow:0 12px 34px rgba(0,0,0,.28)'
        );
        d.innerHTML = html;
        document.body.appendChild(d);
        return d;
    }

    function loadRest(box, btn) {
        btn.disabled = true;
        btn.textContent = 'در حال بارگذاری…';

        function step() {
            var offset = adsData.length;
            fetch('admin-panel.php?action=ads_chunk&offset=' + offset + '&limit=200', { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j || !j.success) {
                        btn.disabled = false;
                        btn.textContent = 'خطا: ' + ((j && j.message) || 'پاسخ نامعتبر');
                        return;
                    }
                    var arr = j.ads || [];
                    for (var i = 0; i < arr.length; i++) { adsData.push(arr[i]); }
                    window.MELKINO_ADS_META.loaded = adsData.length;

                    try { if (typeof renderDashboard === 'function') renderDashboard(); } catch (e) {}
                    try { if (typeof renderAds === 'function') renderAds(); } catch (e) {}

                    if (j.hasMore) {
                        btn.textContent = 'در حال بارگذاری… (' + adsData.length + ')';
                        step();
                        return;
                    }

                    window.MELKINO_ADS_META.hasMore = false;
                    if (box.parentNode) { box.parentNode.removeChild(box); }
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'خطا در ارتباط با سرور';
                });
        }

        step();
    }

    document.addEventListener('DOMContentLoaded', function () {

        // اگر بارگذاریِ آگهی‌ها با خطا مواجه شده باشد، دیگر پنهانش نمی‌کنیم؛
        // ادمین باید بداند پنل به داده‌ی واقعی وصل نیست.
        if (M.error) {
            makeBox(
                '#b00020',
                '<b>' + MK_IC.warn + ' آگهی‌ها از پایگاه داده خوانده نشدند</b><br>' +
                'پنل در حال نمایشِ داده‌ی نمونه است. علت فنی:' +
                '<div style="margin-top:6px;font-size:11px;opacity:.9;direction:ltr;text-align:left">' +
                esc(M.error) + '</div>'
            );
            return;
        }

        if (!M.hasMore) { return; }

        var box = makeBox(
            '#0b5d59',
            '<b>تنها ' + Number(M.loaded) + ' آگهی از ' + Number(M.total) + ' آگهی لود شده است.</b><br>' +
            '<span style="font-size:11px;opacity:.85">' +
            'برای اینکه پنل سریع بالا بیاید، فقط جدیدترین‌ها همراه صفحه آمده‌اند.' +
            '</span>'
        );

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = MK_IC.download + ' بارگذاری بقیه (' + Number(Number(M.total) - Number(M.loaded)) + ')';
        btn.setAttribute(
            'style',
            'margin-top:9px;border:0;background:#fff;color:#0b5d59;border-radius:9px;' +
            'padding:7px 13px;font-family:inherit;font-weight:800;cursor:pointer;font-size:12px'
        );
        box.appendChild(btn);
        btn.onclick = function () { loadRest(box, btn); };
    });
})();

/* Fragment buttons (replaces 15 inline onclick in the fragments). */
document.addEventListener('click', function (ev) {
    var t = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!t) return;
    var act = t.getAttribute('data-act');
    if (act === 'ads-create' && typeof adminCreateNewAd === 'function') {
        adminCreateNewAd();
    } else if (act === 'ads-filter' && typeof filterAds === 'function') {
        filterAds(t.getAttribute('data-f'), t);
    } else if (act === 'ads-reset' && typeof resetAdFilters === 'function') {
        resetAdFilters();
    } else if (act === 'ads-bulk' && typeof bulkChangeStatus === 'function') {
        bulkChangeStatus(t.getAttribute('data-st'));
    } else if (act === 'ads-bulk-delete' && typeof bulkDeleteAds === 'function') {
        bulkDeleteAds();
    } else if (act === 'ads-close' && typeof closeModal === 'function') {
        closeModal(t.getAttribute('data-modal'));
    } else if (act === 'ads-excel-export' && typeof adminAdsExcelExport === 'function') {
        adminAdsExcelExport();
    } else if (act === 'ads-excel-template' && typeof adminAdsExcelTemplate === 'function') {
        adminAdsExcelTemplate();
    } else if (act === 'ads-excel-import' && typeof adminAdsExcelImportPick === 'function') {
        adminAdsExcelImportPick();
    }
});

/* Panel switchTab('ads') calls renderAds(); the V2 tab boots it directly. */
document.addEventListener('DOMContentLoaded', function () {
    try { if (typeof renderAds === 'function') renderAds(); } catch (e) {}
    /* تب V2 هندلر inline ندارد؛ change ورودی مخفی اکسل این‌جا وصل می‌شود. */
    try {
        var f = document.getElementById('adsExcelFile');
        if (f && typeof adminAdsExcelImportChanged === 'function') {
            f.addEventListener('change', function () { adminAdsExcelImportChanged(f); });
        }
    } catch (e2) {}
});
