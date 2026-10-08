/* Melkino V2 — global-settings admin tab, extracted VERBATIM from admin-panel.php
 * (global section + MK_IC block). Requires admin-tab-shared.js (escapeHtml).
 * APIs: save_global_settings.php (untouched). No attribute handlers (delegated save).
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

// ==============================================
// تنظیمات عمومی
// ==============================================
async function loadGlobalSettings(){
    const c=document.getElementById('globalContainer'); if(!c)return;
    try{const r=await fetch('save_global_settings.php?action=get',{cache:'no-store'});const j=await r.json();renderGlobalSettings(j.settings||{});}catch(e){renderGlobalSettings({});}
}
function renderGlobalSettings(d){
    const c=document.getElementById('globalContainer'); if(!c)return;
    const bool=(key,label,help)=>`<div class="security-switch"><div><span>${label}</span><small>${help}</small></div><input id="g_${key}" type="checkbox" ${(key==='maintenance_mode'?!!d[key]:d[key]!==false)?'checked':''}></div>`;
    c.innerHTML=`<div class="admin-section-head"><div><div class="admin-section-title">${MK_IC.gear} تنظیمات عمومی</div><div class="admin-section-help">این تنظیمات رفتار عمومی ملکینو را کنترل می‌کنند.</div></div><span id="globalSaveState" class="admin-section-help"></span></div><div class="admin-grid-2"><div class="admin-field"><label>نام سایت</label><input id="g_site_name" value="${escapeHtml(d.site_name||'ملکینو')}"></div><div class="admin-field"><label>شهر</label><input id="g_city" value="${escapeHtml(d.city||'شاهرود')}"></div><div class="admin-field full"><label>شعار</label><input id="g_slogan" value="${escapeHtml(d.slogan||'ملکینو؛ انتخابی فراتر از یک ملک')}"></div><div class="admin-field"><label>تعداد فایل در هر صفحه</label><input id="g_items_per_page" type="number" min="4" max="100" value="${Number(d.items_per_page||12)}"></div><div class="admin-field"><label>تم پیش‌فرض</label><select id="g_default_theme"><option value="light" ${(d.default_theme||'light')==='light'?'selected':''}>روشن</option><option value="dark" ${d.default_theme==='dark'?'selected':''}>تیره</option></select></div></div><div class="password-security-grid" style="margin-top:12px">${bool('show_prices','نمایش قیمت‌ها','قیمت در کارت‌ها و صفحات عمومی نمایش داده شود.')}${bool('enable_favorites','علاقه‌مندی‌ها','قابلیت ذخیره آگهی برای کاربر فعال باشد.')}${bool('enable_property_requests','ثبت درخواست ملک','فرم درخواست برای کاربران فعال باشد.')}${bool('enable_notifications','اعلان‌ها','اعلان‌های تطبیق و رویدادها فعال باشند.')}${bool('maintenance_mode','حالت تعمیرات','سایت در حالت محدود قرار بگیرد.')}</div><div style="display:flex;gap:8px;margin-top:14px"><button type="button" id="globalSaveButton" class="btn-icon-sm primary">ذخیره تنظیمات عمومی</button></div>`;

    bindCalcRatesAdmin(d);

}

function mkCalcPctVal(rate, fallback){
    const n = Number(rate);
    const v = isFinite(n) ? n : fallback;
    return String(+(v * 100).toFixed(6)).replace(/\.?0+$/, '');
}
function mkCalcNumVal(v, fallback){
    const n = Number(v);
    return String(isFinite(n) ? n : fallback);
}
function mkCalcExtraRowHtml(ex, i){
    const id = escapeHtml(ex.id || ('x'+i));
    const label = escapeHtml(ex.label || '');
    const mode = ex.mode || 'area_ratio';
    const pct = mkCalcPctVal(ex.ratio, 0.5);
    const on = ex.enabled === false ? '' : 'checked';
    return `<div class="mk-calc-extra-row" data-id="${id}" style="display:grid;grid-template-columns:1.3fr 1.1fr .7fr auto auto;gap:8px;align-items:end;margin-bottom:8px">
        <div class="admin-field" style="margin:0"><label>نام آیتم</label><input class="mk-ex-label" value="${label}" placeholder="مثلاً انباری"></div>
        <div class="admin-field" style="margin:0"><label>نوع محاسبه</label><select class="mk-ex-mode">
            <option value="area_ratio" ${mode==='area_ratio'?'selected':''}>متراژ × قیمت متر × نسبت</option>
            <option value="percent_add" ${mode==='percent_add'?'selected':''}>افزایش درصدی از قیمت</option>
            <option value="percent_cut" ${mode==='percent_cut'?'selected':''}>کاهش درصدی از قیمت</option>
        </select></div>
        <div class="admin-field" style="margin:0"><label>مقدار (٪)</label><input class="mk-ex-pct" type="number" step="0.0001" min="0" max="200" value="${pct}"></div>
        <label class="admin-field" style="margin:0;display:flex;gap:6px;align-items:center;padding-bottom:10px"><input class="mk-ex-on" type="checkbox" ${on}> فعال</label>
        <button type="button" class="btn-icon-sm mk-ex-del" style="margin-bottom:8px">حذف</button>
    </div>`;
}
function mkBindCalcExtraButtons(){
    const extrasBox = document.getElementById('mkCalcExtrasBox');
    if (!extrasBox) return;
    extrasBox.querySelectorAll('.mk-ex-del').forEach(function(btn){
        btn.onclick = function(){ const row = btn.closest('.mk-calc-extra-row'); if (row) row.remove(); };
    });
    const addBtn = document.getElementById('mkCalcExtraAdd');
    if (addBtn && !addBtn.getAttribute('data-bound')) {
        addBtn.setAttribute('data-bound', '1');
        addBtn.onclick = function(){
            extrasBox.insertAdjacentHTML('beforeend', mkCalcExtraRowHtml({id:'x'+Date.now(), label:'', enabled:true, mode:'area_ratio', ratio:0.5}, Date.now()));
            mkBindCalcExtraButtons();
        };
    }
}
function bindCalcRatesAdmin(d){
    d = d || {};
    const R = d.calc_rates || {};
    const set = function(id, val){ const el = document.getElementById(id); if (el) el.value = val; };
    set('g_calc_estehklak', mkCalcPctVal(R.ESTEHKLAK_RATE, 0.015));
    set('g_calc_waqf', mkCalcPctVal(R.VAGHFI_DISCOUNT, 0.20));
    set('g_calc_park_min', mkCalcPctVal(R.NO_PARKING_DISCOUNT_MIN, 0.08));
    set('g_calc_park_def', mkCalcPctVal(R.NO_PARKING_DISCOUNT_DEFAULT, 0.09));
    set('g_calc_park_max', mkCalcPctVal(R.NO_PARKING_DISCOUNT_MAX, 0.10));
    set('g_calc_elev', mkCalcPctVal(R.NO_ELEVATOR_RATE, 0.025));
    set('g_calc_yard', mkCalcPctVal(R.YARD_RATIO, 1/3));
    set('g_calc_rent_div', mkCalcNumVal(R.FULL_RENT_DIVISOR, 8));
    set('g_calc_rent_base', mkCalcNumVal(R.RENT_BASE, 100000000));
    set('g_calc_rent_per', mkCalcNumVal(R.RENT_PER_100M, 3000000));
    const en = document.getElementById('g_enable_property_calculator');
    if (en) en.checked = d.enable_property_calculator !== false;
    const extrasBox = document.getElementById('mkCalcExtrasBox');
    if (extrasBox) {
        let extras = Array.isArray(R.EXTRAS) ? R.EXTRAS : [{id:'storage',label:'انباری',enabled:true,mode:'area_ratio',ratio:0.5}];
        if (!extras.length) extras = [{id:'storage',label:'انباری',enabled:true,mode:'area_ratio',ratio:0.5}];
        extrasBox.innerHTML = extras.map(mkCalcExtraRowHtml).join('');
        const addBtn = document.getElementById('mkCalcExtraAdd');
        if (addBtn) addBtn.removeAttribute('data-bound');
        mkBindCalcExtraButtons();
    }
}
function collectCalcRates(){
    const n = function(id){
        const el = document.getElementById(id);
        if (!el) return NaN;
        return parseFloat(el.value);
    };
    const extras = [];
    document.querySelectorAll('.mk-calc-extra-row').forEach(function(row){
        const label = (row.querySelector('.mk-ex-label')||{}).value || '';
        if (!String(label).trim()) return;
        const pct = parseFloat((row.querySelector('.mk-ex-pct')||{}).value);
        extras.push({
            id: row.getAttribute('data-id') || '',
            label: String(label).trim(),
            enabled: !!(row.querySelector('.mk-ex-on')||{}).checked,
            mode: (row.querySelector('.mk-ex-mode')||{}).value || 'area_ratio',
            ratio: (isFinite(pct) ? pct : 0) / 100
        });
    });
    const pct = function(id, fb){ const v = n(id); return (isFinite(v) ? v : fb) / 100; };
    return {
        ESTEHKLAK_RATE: pct('g_calc_estehklak', 1.5),
        VAGHFI_DISCOUNT: pct('g_calc_waqf', 20),
        NO_PARKING_DISCOUNT_MIN: pct('g_calc_park_min', 8),
        NO_PARKING_DISCOUNT_DEFAULT: pct('g_calc_park_def', 9),
        NO_PARKING_DISCOUNT_MAX: pct('g_calc_park_max', 10),
        NO_ELEVATOR_RATE: pct('g_calc_elev', 2.5),
        YARD_RATIO: pct('g_calc_yard', 33.333333),
        FULL_RENT_DIVISOR: isFinite(n('g_calc_rent_div')) ? n('g_calc_rent_div') : 8,
        RENT_BASE: isFinite(n('g_calc_rent_base')) ? n('g_calc_rent_base') : 100000000,
        RENT_PER_100M: isFinite(n('g_calc_rent_per')) ? n('g_calc_rent_per') : 3000000,
        EXTRAS: extras
    };
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof mkBindCalcExtraButtons === 'function') mkBindCalcExtraButtons();
});
document.addEventListener('click', function (e) {
    const b = e.target && (e.target.id === 'globalSaveButton' ? e.target : (e.target.closest ? e.target.closest('#globalSaveButton') : null));
    if (!b) return;
    e.preventDefault();
    saveGlobalSettings();
});
async function saveGlobalSettings(){
    const state=document.getElementById('globalSaveState');
    const btn=document.getElementById('globalSaveButton');
    const el=function(id){ return document.getElementById('g_'+id); };
    const txt=function(id, fb){ const n=el(id); return n ? String(n.value||'').trim() : (fb||''); };
    const num=function(id, fb){ const n=el(id); const v=n ? parseInt(n.value||fb,10) : fb; return isFinite(v)?v:fb; };
    const chk=function(id, fb){ const n=el(id); return n ? !!n.checked : (fb!==false); };

    try {
        const payload={
            site_name:txt('site_name','ملکینو'),
            city:txt('city',''),
            slogan:txt('slogan',''),
            items_per_page:num('items_per_page',12),
            default_theme:txt('default_theme','light')==='dark'?'dark':'light',
            show_prices:chk('show_prices',true),
            enable_favorites:chk('enable_favorites',true),
            enable_property_requests:chk('enable_property_requests',true),
            enable_notifications:chk('enable_notifications',true),
            enable_property_calculator:chk('enable_property_calculator',true),
            maintenance_mode:chk('maintenance_mode',false)
        };
        if (window.MELKINO_CSRF) payload.csrf_token = window.MELKINO_CSRF;
        if (document.getElementById('g_calc_estehklak') && typeof collectCalcRates === 'function') {
            payload.calc_rates = collectCalcRates();
        }
        if (state) state.textContent='در حال ذخیره...';
        if (btn) btn.disabled = true;
        const headers={'Content-Type':'application/json','Accept':'application/json'};
        if (window.MELKINO_CSRF) headers['X-CSRF-Token']=window.MELKINO_CSRF;
        const r=await fetch('save_global_settings.php',{
            method:'POST',
            headers:headers,
            credentials:'same-origin',
            cache:'no-store',
            body:JSON.stringify(payload)
        });
        const text=await r.text();
        let j={};
        try { j=JSON.parse(text); } catch(e) { throw new Error('پاسخ نامعتبر از سرور دریافت شد.'); }
        if (!r.ok || !j.success) throw new Error(j.message||'ذخیره تنظیمات انجام نشد.');
        if (state) state.textContent='با موفقیت ذخیره شد';
        if (j.settings) renderGlobalSettings(j.settings);
        const st2=document.getElementById('globalSaveState');
        if (st2) st2.textContent='با موفقیت ذخیره شد';
    } catch(e) {
        console.error('saveGlobalSettings error:',e);
        if (state) state.textContent=''+e.message;
        else alert(''+e.message);
    } finally {
        const b=document.getElementById('globalSaveButton');
        if (b) b.disabled = false;
    }
}



/* V2 boot: same as panel switchTab('global'). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadGlobalSettings === 'function') loadGlobalSettings();
});
