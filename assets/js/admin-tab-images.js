/* Melkino V2 — images admin tab, extracted VERBATIM from admin-new-tabs.js
 * (helpers L13-38 + images region + MK_IC block from panel).
 * API: admin-images.php list/delete/delete_orphans/orphans (untouched).
 * 1 template + 2 fragment onclick -> data-act + delegation.
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

const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
}[c]));

function setStatus(id, message, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    // راند ۳۶: بدون ایموجی؛ آیکون SVG مشترک
    const clean = String(message || '').replace(/^[\u2705\u274C\u2714\u26D4]\s*/u, '');
    el.innerHTML = (typeof mkStatusHtml === 'function') ? mkStatusHtml(ok, clean) : clean;
    el.style.color = ok === true ? 'var(--success)' : (ok === false ? 'var(--danger)' : 'var(--text-secondary)');
}

async function postJson(url, payload) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {})
    });
    return res.json();
}

async function getJson(url) {
    const res = await fetch(url, { cache: 'no-store' });
    return res.json();
}

/* =====================================================
   مدیریت تصاویر آگهی‌ها
   ===================================================== */

window.loadAdminImages = async function () {
    const box = document.getElementById('adminImagesContainer');
    if (!box) return;
    box.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-secondary);font-size:13px;">در حال بارگذاری تصاویر...</div>';

    const q = (document.getElementById('imagesSearch') || {}).value || '';
    const missing = (document.getElementById('imagesMissingOnly') || {}).value || '0';
    const url = 'admin-images.php?action=list&q=' + encodeURIComponent(q) + '&missing=' + encodeURIComponent(missing);

    try {
        const data = await getJson(url);
        const rows = data.images || [];

        if (!rows.length) {
            box.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">تصویری با این فیلتر پیدا نشد.</div>';
            return;
        }

        const esc2 = esc;
        box.innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">' +
            rows.map(im => `
                <div style="border:1px solid var(--border);border-radius:14px;overflow:hidden;background:var(--surface);display:flex;flex-direction:column;">
                    <div style="position:relative;height:110px;background:var(--bg-secondary);display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        ${im.exists
                            ? `<img src="${esc2(im.url)}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;">`
                            : '<span style="font-size:11px;color:var(--danger);padding:8px;text-align:center;">فایل روی سرور نیست</span>'}
                        ${im.is_primary == 1 ? '<span style="position:absolute;top:6px;right:6px;background:var(--gold-gradient, #D4AF37);color:#111827;font-size:10px;font-weight:800;padding:3px 8px;border-radius:999px;">اصلی</span>' : ''}
                    </div>
                    <div style="padding:9px 10px 11px;display:flex;flex-direction:column;gap:4px;flex:1;">
                        <div style="font-size:11px;color:var(--text-primary);font-weight:700;line-height:1.6;word-break:break-word;">${esc2(im.ad_title || ('آگهی ' + im.ad_id))}</div>
                        <div style="font-size:10px;color:var(--text-muted);word-break:break-all;" dir="ltr">${esc2(im.filename)}</div>
                        <div style="font-size:10px;color:var(--text-muted);">${esc2(im.size_human)}${im.exists ? '' : ' · ' + 'بدون فایل'}</div>
                        <button type="button" class="mk-btn mk-btn--sm mk-btn--danger" style="margin-top:auto;" data-image-delete="${esc2(im.id)}">
                            ${MK_IC.trash} حذف تصویر
                        </button>
                    </div>
                </div>
            `).join('') + '</div>';
    } catch (e) {
        box.innerHTML = '<div style="color:var(--danger);font-size:13px;">خطا در بارگذاری تصاویر.</div>';
    }
};

window.scanOrphanImages = async function () {
    const box = document.getElementById('orphanImagesContainer');
    if (!box) return;
    box.innerHTML = '<div class="mk-empty"><span class="mk-spinner"></span> در حال اسکن پوشه uploads...</div>';

    try {
        const data = await getJson('admin-images.php?action=orphans');
        const rows = data.orphans || [];

        if (!rows.length) {
            box.innerHTML = '<div class="mk-empty" style="color:var(--success);">هیچ فایل بدون استفاده‌ای پیدا نشد.</div>';
            return;
        }

        box.innerHTML = `
            <div style="border:1px solid var(--border);border-radius:14px;padding:12px;margin-bottom:10px;">
                <div style="font-size:13px;font-weight:800;color:var(--text-primary);margin-bottom:4px;">${rows.length} فایل بدون استفاده (${esc(data.total_size_human)})</div>
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:10px;">این فایل‌ها به هیچ آگهی‌ای وصل نیستند.</div>
                <div style="max-height:190px;overflow:auto;border:1px solid var(--border-light);border-radius:10px;padding:8px;margin-bottom:10px;">
                    ${rows.slice(0, 200).map(f => `<div style="font-size:11px;color:var(--text-secondary);padding:2px 0;" dir="ltr">${esc(f.name)} <span style="color:var(--text-muted);">(${Math.round(f.size/1024)} کیلوبایت)</span></div>`).join('')}
                </div>
                <button type="button" class="mk-btn mk-btn--sm mk-btn--danger" data-act="img-del-orphans">
                    ${MK_IC.trash} حذف همه (${rows.length} فایل)
                </button>
            </div>`;

        window.__melkinoOrphans = rows.map(f => f.name);
    } catch (e) {
        box.innerHTML = '<div style="color:var(--danger);font-size:13px;">خطا در اسکن پوشه.</div>';
    }
};

window.deleteOrphanImages = async function () {
    const files = window.__melkinoOrphans || [];
    if (!files.length) return;
    if (!confirm(files.length + ' فایل برای همیشه حذف شود؟ این کار قابل بازگشت نیست.')) return;
    if (!confirm('تأیید دوباره: فایل‌ها از روی سرور پاک می‌شوند. ادامه بدهیم؟')) return;

    try {
        const data = await postJson('admin-images.php?action=delete_orphans', { files: files });
        alert(data.message || (data.success ? 'پاکسازی انجام شد.' : 'پاکسازی انجام نشد.'));
        window.__melkinoOrphans = [];
        scanOrphanImages();
    } catch (e) {
        alert('خطا در ارتباط با سرور.');
    }
};

document.addEventListener('click', async function (e) {
    const del = e.target.closest('[data-image-delete]');
    if (!del) return;
    if (!confirm('این تصویر از دیتابیس و از روی سرور حذف شود؟')) return;

    const btn = del;
    btn.disabled = true;
    btn.textContent = '⏳ در حال حذف...';

    try {
        const data = await postJson('admin-images.php?action=delete', { id: del.getAttribute('data-image-delete') });
        alert(data.message || (data.success ? 'حذف شد.' : 'حذف انجام نشد.'));
        loadAdminImages();
    } catch (err) {
        alert('خطا در ارتباط با سرور.');
        loadAdminImages();
    }
});

/* V2: delegation for template + fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    if (act === 'img-del-orphans') { deleteOrphanImages(); }
    else if (act === 'img-reload') { loadAdminImages(); }
    else if (act === 'img-scan') { scanOrphanImages(); }
});
/* V2 boot: same as panel switchTab('images'). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadAdminImages === 'function') loadAdminImages();
});
