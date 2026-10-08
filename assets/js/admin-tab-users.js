/* Melkino V2 — users admin tab, extracted VERBATIM from admin-panel.php
 * helpers cluster (panel ~L4559-4654: mkTgLink/MK_IC/mkStatusHtml/mkTg-mkBale fns)
 * + users section (panel L7603-7738). Requires: admin-tab-shared.js (escapeHtml),
 * csrf-shim (window.MELKINO_CSRF). APIs: identity-sync.php, page-visits.php (untouched).
 * 2 template onclick -> data-act (user-clear-phone/user-history) + delegation.
 */
// راند ۳۵: Helper مشترک «باز کردن تلگرام» سمت کلاینت برای رندر جدول‌ها.
// اعتبارسنجی سخت‌گیرانه: فقط username مطابق الگوی تلگرام یا آیدی کاملاً عددی؛
// هیچ مقدار خامی از دیتابیس مستقیم داخل href نمی‌رود.
function mkTgLink(tgId, username){
    const u = String(username||'').trim().replace(/^@/,'');
    if (/^[A-Za-z][A-Za-z0-9_]{4,31}$/.test(u)) return 'https://t.me/' + encodeURIComponent(u);
    const id = String(tgId||'').trim();
    if (/^[1-9][0-9]{0,19}$/.test(id)) return 'tg://user?id=' + id;
    return null;
}
// راند ۶۴: عکس‌های پیش‌فرض انواع ملک برای انتخاب ادمین در مودال ویرایش
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
function mkStatusHtml(ok, msg){
    const clean = String(msg || '').replace(/^[\u2705\u274C\u2714\u26D4]\s*/u, '');
    if (!clean) return '';
    const m = escapeHtml(clean);
    if (ok === true)  return '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;margin-inline-end:4px;color:var(--success);" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 5-5.5"/></svg>' + m;
    if (ok === false) return '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;margin-inline-end:4px;color:var(--danger);" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>' + m;
    return m;
}
function mkTgSvg(){
    return '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/></svg>';
}
function mkTgIcon(tgId, username){
    const href = mkTgLink(tgId, username);
    if (!href) return '';
    const u = String(username||'').trim().replace(/^@/,'');
    const title = href.indexOf('https://t.me/') === 0 ? ('باز کردن تلگرام (@' + escapeHtml(u) + ')') : 'باز کردن تلگرام با آیدی عددی';
    return '<a class="mk-btn mk-btn--sm mk-btn--outline mk-tg-icon" data-tg-open="1" href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" title="' + title + '" aria-label="' + title + '">' + mkTgSvg() + '</a>';
}
function mkTgFull(tgId, username){
    const u = String(username||'').trim().replace(/^@/,'');
    const uOk = /^[A-Za-z][A-Za-z0-9_]{4,31}$/.test(u);
    const id = String(tgId||'').trim();
    const idOk = /^[1-9][0-9]{0,19}$/.test(id);
    if (!uOk && !idOk) return '<span class="mk-btn mk-btn--sm mk-btn--outline mk-tg-off" aria-disabled="true">' + mkTgSvg() + ' تلگرام متصل نیست</span>';
    if (uOk) {
        let out = '<a class="mk-btn mk-btn--sm mk-btn--outline mk-tg-btn" data-tg-open="1" href="https://t.me/' + encodeURIComponent(u) + '" target="_blank" rel="noopener noreferrer" title="باز کردن تلگرام (@' + escapeHtml(u) + ')">' + mkTgSvg() + ' باز کردن تلگرام (@' + escapeHtml(u) + ')</a>';
        if (idOk) out += ' <a class="mk-btn mk-btn--sm mk-btn--ghost mk-tg-icon" data-tg-open="1" href="tg://user?id=' + id + '" title="لینک عمیق با آیدی عددی" aria-label="لینک عمیق با آیدی عددی">' + mkTgSvg() + '</a>';
        return out;
    }
    return '<a class="mk-btn mk-btn--sm mk-btn--outline mk-tg-btn" data-tg-open="1" href="tg://user?id=' + id + '" target="_blank" rel="noopener noreferrer" title="باز کردن تلگرام با آیدی عددی">' + mkTgSvg() + ' باز کردن تلگرام</a>';
}
function mkBaleSvg(){
    return '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>';
}
function mkBaleLink(baleId, username){
    const u = String(username||'').trim().replace(/^@/,'');
    if (/^[A-Za-z][A-Za-z0-9_]{4,31}$/.test(u)) return 'https://ble.ir/' + encodeURIComponent(u);
    const id = String(baleId||'').trim();
    if (/^[1-9][0-9]{0,19}$/.test(id)) return 'https://ble.ir/' + encodeURIComponent(id);
    return null;
}
function mkBaleFull(baleId, username){
    const u = String(username||'').trim().replace(/^@/,'');
    const uOk = /^[A-Za-z][A-Za-z0-9_]{4,31}$/.test(u);
    const id = String(baleId||'').trim();
    const idOk = /^[1-9][0-9]{0,19}$/.test(id);
    if (!uOk && !idOk) return '<span class="mk-btn mk-btn--sm mk-btn--outline mk-tg-off" aria-disabled="true">' + mkBaleSvg() + ' بله متصل نیست</span>';
    if (uOk) {
        return '<a class="mk-btn mk-btn--sm mk-btn--outline mk-tg-btn" href="https://ble.ir/' + encodeURIComponent(u) + '" target="_blank" rel="noopener noreferrer" title="باز کردن پروفایل در بله (@' + escapeHtml(u) + ')">' + mkBaleSvg() + ' باز کردن در بله (@' + escapeHtml(u) + ')</a>';
    }
    return '<a class="mk-btn mk-btn--sm mk-btn--outline mk-tg-btn" href="https://ble.ir/' + encodeURIComponent(id) + '" target="_blank" rel="noopener noreferrer" title="باز کردن پروفایل در بله">' + mkBaleSvg() + ' باز کردن در بله</a>';
}

function mkEitaaFull(eitaaId, username){
    const id = String(eitaaId || '').trim();
    const un = String(username || '').trim().replace(/^@/, '');
    if (!id) return '<span class="mk-btn mk-btn--sm mk-btn--outline" aria-disabled="true">ایتا متصل نیست</span>';
    let html = '<span class="mk-btn mk-btn--sm mk-btn--outline">آیدی ایتا: <bdi dir="ltr">' + escapeHtml(id) + '</bdi></span>';
    // No undocumented numeric profile URLs. Link a username only when actually available.
    if (/^[A-Za-z][A-Za-z0-9_]{0,63}$/.test(un)) html += ' <a class="mk-btn mk-btn--sm mk-btn--outline" href="https://eitaa.com/' + encodeURIComponent(un) + '" target="_blank" rel="noopener noreferrer" dir="ltr">@' + escapeHtml(un) + '</a>';
    return html;
}

async function loadAdminUsers(){
    const container=document.getElementById('usersListContainer'); if(!container)return;
    container.innerHTML='<div class="consultant-empty">در حال بارگذاری کاربران...</div>';
    try{
        const r=await fetch('identity-sync.php?action=list',{cache:'no-store',credentials:'same-origin'});
        const raw=await r.text();
        let data={};
        try{data=JSON.parse(raw);}catch(e){container.innerHTML='<div class="consultant-empty">خطا در بارگذاری کاربران.</div>';return;}
        if(data && data.success===false){container.innerHTML='<div class="consultant-empty">'+escapeHtml(data.message||'خطا در بارگذاری کاربران.')+'</div>';return;}
        const users=Array.isArray(data.users)?data.users:[];
        if(!users.length){container.innerHTML='<div class="consultant-empty">هنوز کاربری ثبت نشده است.</div>';return;}
        users.sort((a,b)=>String(b.last_login||'').localeCompare(String(a.last_login||'')));
        container.innerHTML=`<div class="table-wrap"><table class="users-table"><thead><tr><th>وضعیت</th><th>Telegram ID</th><th>Bale ID</th><th>آیدی ایتا</th><th>Username</th><th>نام</th><th>شماره تماس</th><th>وضعیت شماره</th><th>پلتفرم آخر</th><th>IP آخر</th><th>اولین ورود</th><th>آخرین ورود</th><th>تعداد ورود</th><th></th></tr></thead><tbody>${
            users.map(u=>{
                const active = Number(u.is_active ?? 1) !== 0;
                const platformLabel = (u.last_platform === 'telegram') ? 'تلگرام'
                                    : (u.last_platform === 'bale') ? 'بله'
                                    : (u.last_platform === 'eitaa') ? 'ایتا'
                                    : (u.last_platform || '—');
                // راند ۳۰: وضعیت شمارهٔ تماس + دکمهٔ حذف (فقط ادمین)
                const phoneLocked = Number(u.phone_locked ?? 0) === 1 || Number(u.phone_verified ?? 0) === 1;
                const phoneState = (u.phone || '') === '' ? '<span style="color:#7F8A87;font-size:11px;">— ثبت نشده</span>'
                    : (phoneLocked ? '<span style="color:#4ADE80;font-size:11px;">✓ تأیید و قفل‌شده</span>'
                                   : '<span style="color:#FFD47E;font-size:11px;">تأییدنشده</span>');
                const clearPhoneBtn = (u.phone || '') === '' ? ''
                    : `<button type="button" class="btn-secondary" style="padding:4px 10px;font-size:12px;color:#ff8a8a;border-color:rgba(255,120,120,.35);" data-act="user-clear-phone" data-uid="${Number(u.id)}">${MK_IC.trash} حذف شماره</button>`;
                return `<tr>
                    <td><span class="user-status-dot" style="background:${active?'#4ADE80':'#7F8A87'}"></span>${active?'فعال':'غیرفعال'}</td>
                    <td dir="ltr">${escapeHtml(u.telegram_id||'—')}</td>
                    <td dir="ltr">${escapeHtml(u.bale_id||'—')}</td>
                    <td dir="ltr" class="user-eitaa-id">${escapeHtml(u.eitaa_id||'—')}${u.eitaa_username ? '<br><small>'+escapeHtml('@'+u.eitaa_username)+'</small>' : ''}</td>
                    <td dir="ltr">${escapeHtml(u.username||'—')}</td>
                    <td>${escapeHtml(u.name||'—')}</td>
                    <td dir="ltr">${escapeHtml(u.phone||'—')}</td>
                    <td>${phoneState} ${clearPhoneBtn}</td>
                    <td>${escapeHtml(platformLabel)}</td>
                    <td dir="ltr">${escapeHtml(u.last_ip||'—')}</td>
                    <td>${escapeHtml(u.first_login_fa||u.first_login||u.created_at||'—')}</td>
                    <td>${escapeHtml(u.last_login_fa||u.last_login||'—')}</td>
                    <td>${Number(u.login_count||0)}</td>
                    <td style="white-space:nowrap;">${mkTgIcon(u.telegram_id, u.telegram_username || (u.last_platform==='telegram' ? u.username : ''))} <button type="button" class="mk-btn mk-btn--sm mk-btn--outline" data-tgid="${escapeHtml(u.telegram_id||'')}" data-tgun="${escapeHtml(u.telegram_username || (u.last_platform==='telegram' ? (u.username||'') : ''))}" data-baleid="${escapeHtml(u.bale_id||'')}" data-eitaaid="${escapeHtml(u.eitaa_id||'')}" data-eitaaun="${escapeHtml(u.eitaa_username||'')}" data-baleun="${escapeHtml((u.last_platform==='bale' ? (u.username||'') : ''))}" data-act="user-history" data-uid="${Number(u.id)}"><svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/></svg> تاریخچه</button></td>
                </tr>
                <tr id="userHistoryRow${Number(u.id)}" style="display:none;">
                    <td colspan="14"><div id="userHistoryBox${Number(u.id)}" style="padding:10px;font-size:12px;"></div></td>
                </tr>`;
            }).join('')
        }</tbody></table></div>`;
    }catch(e){container.innerHTML='<div class="consultant-empty">خطا در بارگذاری کاربران.</div>';}
}

// راند ۳۰: حذف شمارهٔ تماس کاربر (تنها مسیر مجازِ تغییر شمارهٔ قفل‌شده)
// لاگ کامل در جدول admin_phone_audit ثبت می‌شود.
async function clearUserPhone(userId, btn){
    if (!confirm('شمارهٔ تماس کاربر #'+userId+' حذف و قفل آن باز شود؟\nپس از حذف، کاربر می‌تواند شمارهٔ جدیدی ثبت کند.')) return;
    const token = (typeof TOKEN !== 'undefined' && TOKEN) ? TOKEN : (window.MELKINO_CSRF || '');
    if (btn) { btn.disabled = true; }
    try{
        const r = await fetch('identity-sync.php?action=clear_phone', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            cache: 'no-store',
            body: JSON.stringify({ action: 'clear_phone', user_id: userId, csrf_token: token })
        });
        const data = await r.json();
        if (!r.ok || data.success !== true) {
            alert('' + ((data && data.message) || 'حذف شماره انجام نشد.'));
            if (btn) { btn.disabled = false; }
            return;
        }
        alert('✓ ' + (data.message || 'شماره حذف شد.'));
        loadAdminUsers();
    }catch(e){
        alert('خطا در حذف شماره.');
        if (btn) { btn.disabled = false; }
    }
}

async function toggleUserHistory(userId, btn){
    const row = document.getElementById('userHistoryRow'+userId);
    if (!row) return;

    if (row.style.display !== 'none') {
        row.style.display = 'none';
        return;
    }

    row.style.display = '';
    const box = document.getElementById('userHistoryBox'+userId);
    box.innerHTML = 'در حال بارگذاری تاریخچه...';

    try {
        const r = await fetch('identity-sync.php?action=history&user_id='+userId, {cache:'no-store'});
        const data = await r.json();
        const events = Array.isArray(data.events) ? data.events : [];
        events.forEach(function (ev) {
            var plat = String(ev.platform || '').toLowerCase();
            if ((plat === 'bale' || plat === 'بله') && !String(ev.bale_id || '').trim() && ev.telegram_id) {
                ev.bale_id = ev.telegram_id;
                ev.telegram_id = '';
            }
        });
        // راند ۳۵: دکمهٔ «باز کردن تلگرام» و «باز کردن در بله» بالای تاریخچه
        let tgHead = '';
        let baleId = btn ? (btn.getAttribute('data-baleid') || '') : '';
        let baleUn = btn ? (btn.getAttribute('data-baleun') || '') : '';
        if (events.length) {
            for (let i = 0; i < events.length; i++) {
                if (!baleId && events[i].bale_id) baleId = String(events[i].bale_id);
                if (!baleUn && events[i].platform === 'bale' && events[i].username) baleUn = String(events[i].username);
            }
        }
        const openBtns = mkTgFull(btn ? (btn.getAttribute('data-tgid') || '') : '', btn ? (btn.getAttribute('data-tgun') || '') : '')
            + ' ' + mkBaleFull(baleId, baleUn)
            + ' ' + mkEitaaFull(btn ? btn.getAttribute('data-eitaaid') : '', btn ? btn.getAttribute('data-eitaaun') : '');
        tgHead = '<div style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">' + openBtns + '</div>';
        if (!events.length) { box.innerHTML = tgHead + '<div class="mk-empty">رخدادی برای این کاربر ثبت نشده است.</div>'; return; }
        const pf = v => v === 'telegram' ? 'تلگرام' : (v === 'bale' ? 'بله' : (v === 'eitaa' ? 'ایتا' : (v || '—')));
        // راند ۳۳: همهٔ شناسه‌های هر رخداد (Telegram ID / Bale ID / یوزرنیم /
        // نام ثبت‌شده در همان ورود) به‌صورت کامل نمایش داده می‌شود.
        box.innerHTML = tgHead + '<div style="overflow-x:auto;"><table class="users-table" style="width:100%;min-width:860px;"><thead><tr><th>تاریخ و ساعت ورود</th><th>پلتفرم</th><th>Telegram ID</th><th>Bale ID</th><th>آیدی ایتا</th><th>Username</th><th>نام</th><th>IP</th><th>مرورگر / دستگاه</th></tr></thead><tbody>' +
            events.map(e => `<tr><td style="white-space:nowrap;">${escapeHtml(e.created_at_fa||e.created_at||'—')}</td><td>${escapeHtml(pf(e.platform))}</td><td dir="ltr">${escapeHtml(e.telegram_id||'—')}</td><td dir="ltr">${escapeHtml(e.bale_id||'—')}</td><td dir="ltr">${escapeHtml(e.eitaa_id||'—')}</td><td dir="ltr">${escapeHtml(e.username||'—')}</td><td>${escapeHtml(e.name||'—')}</td><td dir="ltr">${escapeHtml(e.ip_address||e.ip||'—')}</td><td dir="ltr" style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(e.user_agent||'—')}</td></tr>`).join('') +
            '</tbody></table></div>';
        const views = Array.isArray(data.ad_views) ? data.ad_views : [];
        if (views.length) {
            box.innerHTML += '<div style="margin-top:14px;font-weight:800;">آگهی‌های دیده‌شده</div>'
                + '<div style="overflow-x:auto;"><table class="users-table" style="width:100%;min-width:480px;"><thead><tr><th>تاریخ بازدید</th><th>کد آگهی</th><th>عنوان</th></tr></thead><tbody>'
                + views.map(function (v) {
                    const href = v.ad_id ? ('property-details.php?id=' + encodeURIComponent(v.ad_id)) : '#';
                    return '<tr><td style="white-space:nowrap;">' + escapeHtml(v.viewed_at_fa || v.viewed_at || '—')
                        + '</td><td dir="ltr"><a href="' + escapeHtml(href) + '" target="_blank" rel="noopener">' + escapeHtml(v.ad_id || '—') + '</a></td><td>'
                        + escapeHtml(v.ad_title || '—') + '</td></tr>';
                }).join('')
                + '</tbody></table></div>';
        }
    } catch (e) {
        box.innerHTML = '<div class="mk-error">خطا در بارگذاری تاریخچه.</div>';
    }
}

/* V2: delegation for template buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    if (act === 'user-clear-phone') { clearUserPhone(Number(el.getAttribute('data-uid')), el); }
    else if (act === 'user-history') { toggleUserHistory(Number(el.getAttribute('data-uid')), el); }
});
/* V2 boot: same as panel switchTab(users) + users-stats block (panel ~L8152). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadAdminUsers === 'function') loadAdminUsers();
    var usersEl = document.getElementById('usersStatTotal');
    var visitsEl = document.getElementById('usersStatVisits');
    if (!usersEl && !visitsEl) return;
    if (usersEl) usersEl.innerText = '\u2026'; if (visitsEl) visitsEl.innerText = '\u2026';
    Promise.all([
        fetch('identity-sync.php?action=list', {cache:'no-store'}).then(function(r){return r.ok?r.json():null;}).catch(function(){return null;}),
        fetch('page-visits.php?action=stats', {cache:'no-store'}).then(function(r){return r.ok?r.json():null;}).catch(function(){return null;})
    ]).then(function (res) {
        var u = res[0], v = res[1];
        if (usersEl) usersEl.innerText = Array.isArray(u && u.users) ? u.users.length : (Array.isArray(u) ? u.length : 0);
        if (visitsEl) visitsEl.innerText = Number((v && v.last_24h) || 0);
    });
});
