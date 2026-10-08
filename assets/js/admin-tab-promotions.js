/* Melkino V2 — promotions admin tab, extracted VERBATIM from admin-new-tabs.js
 * (helpers L13-38 + promotions section L553-729) + MK_IC block (panel L4572-4602).
 * Needs only csrf-shim (TOKEN via XHR/fetch default? no — postJson has no CSRF;
 * same as panel). API: admin-promotions.php list/save/delete/toggle/upload (untouched).
 * 5 fragment onclick -> data-act + delegation.
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
   تبلیغات
   ===================================================== */

window.loadPromotions = async function () {
    const box = document.getElementById('promotionsListContainer');
    if (!box) return;
    box.innerHTML = '<div class="mk-empty"><span class="mk-spinner"></span> در حال بارگذاری...</div>';

    try {
        const data = await getJson('admin-promotions.php?action=list');
        const rows = data.promotions || [];

        if (!rows.length) {
            box.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">هنوز تبلیغی ثبت نشده است.</div>';
            return;
        }

        const placementLabel = {
            all: 'همه صفحه‌ها', home: 'صفحه اصلی', properties: 'فهرست املاک',
            search: 'نتایج جستجو', vip: 'ملک‌های ویژه'
        };

        box.innerHTML = rows.map(p => `
            <div style="border:1px solid var(--border);border-radius:14px;padding:13px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:1;min-width:180px;">
                        ${p.image_url ? `<img src="${esc(p.image_url)}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:10px;display:block;margin-bottom:8px;">` : ''}
                        <div style="font-size:14px;font-weight:800;color:var(--text-primary);">${esc(p.title || (p.link_url ? 'تبلیغ با لینک' : 'تبلیغ تصویری'))}</div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;line-height:1.8;">
                            محل: ${esc(placementLabel[p.placement] || p.placement)} ·
                            بعد از کارت ${esc(p.position_after)}${Number(p.repeat_every) > 0 ? ' · تکرار هر ' + esc(p.repeat_every) + ' کارت' : ''}
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            بازدید: ${esc(p.views || 0)} · کلیک: ${esc(p.clicks || 0)}
                            ${p.start_date ? ' · شروع: ' + esc(p.start_date) : ''}
                            ${p.end_date ? ' · پایان: ' + esc(p.end_date) : ''}
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="mk-btn mk-btn--sm mk-btn--outline" data-promo-toggle="${esc(p.id)}" data-active="${p.is_active == 1 ? 1 : 0}">
                            ${p.is_active == 1 ? MK_IC.pause + ' غیرفعال' : MK_IC.play + ' فعال'}
                        </button>
                        <button type="button" class="mk-btn mk-btn--sm mk-btn--outline" data-promo-edit="${esc(p.id)}">${MK_IC.edit} ویرایش</button>
                        <button type="button" class="mk-btn mk-btn--sm mk-btn--danger" data-promo-delete="${esc(p.id)}">${MK_IC.trash} حذف</button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div style="color:var(--danger);font-size:13px;">خطا در بارگذاری تبلیغ‌ها.</div>';
    }
};

document.addEventListener('click', async function (e) {
    const t = e.target;

    const del = t.closest('[data-promo-delete]');
    if (del) {
        if (!confirm('این تبلیغ حذف شود؟')) return;
        await postJson('admin-promotions.php?action=delete', { id: del.getAttribute('data-promo-delete') });
        loadPromotions();
        return;
    }

    const tog = t.closest('[data-promo-toggle]');
    if (tog) {
        const active = tog.getAttribute('data-active') === '1' ? 0 : 1;
        await postJson('admin-promotions.php?action=toggle', { id: tog.getAttribute('data-promo-toggle'), is_active: active });
        loadPromotions();
        return;
    }

    const ed = t.closest('[data-promo-edit]');
    if (ed) {
        const data = await getJson('admin-promotions.php?action=list');
        const promo = (data.promotions || []).find(x => String(x.id) === String(ed.getAttribute('data-promo-edit')));
        if (promo) openPromotionEditor(promo);
    }
});

/* بستنِ مطمئنِ مودال تبلیغ:
   هم کلاس active را برمی‌دارد و هم display را مستقیماً none می‌کند،
   تا به هیچ تابع یا استایل بیرونی وابسته نباشد. */
window.closePromotionModal = function () {
    const modal = document.getElementById('promotionModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.style.display = 'none';
};

window.openPromotionEditor = function (promo) {
    const modal = document.getElementById('promotionModal');
    if (!modal) return;
    // مودال‌های این پنل با کلاس active نمایش داده می‌شوند (نه style.display)
    modal.style.display = 'flex';
    modal.classList.add('active');

    const set = (id, v) => { const e = document.getElementById(id); if (e) e.value = v ?? ''; };
    set('promoId', promo ? promo.id : '');
    set('promoTitle', promo ? promo.title : '');
    set('promoDescription', promo ? promo.description : '');
    set('promoImageUrl', promo ? promo.image_url : '');
    set('promoLinkUrl', promo ? promo.link_url : '');
    set('promoButtonText', promo ? promo.button_text || 'مشاهده' : 'مشاهده');
    set('promoPlacement', promo ? promo.placement : 'all');
    set('promoPosition', promo ? promo.position_after : 3);
    set('promoRepeat', promo ? promo.repeat_every : 0);
    set('promoActive', promo ? String(promo.is_active) : '1');

    const toLocal = v => v ? String(v).replace(' ', 'T').slice(0, 16) : '';
    set('promoStart', promo ? toLocal(promo.start_date) : '');
    set('promoEnd', promo ? toLocal(promo.end_date) : '');

    const title = document.getElementById('promotionModalTitle');
    if (title) title.textContent = promo ? 'ویرایش تبلیغ' : 'تبلیغ جدید';
    setStatus('promoFormStatus', '', null);
};

window.savePromotion = async function () {
    const val = id => { const e = document.getElementById(id); return e ? e.value.trim() : ''; };
    setStatus('promoFormStatus', 'در حال ذخیره...', null);

    try {
        const data = await postJson('admin-promotions.php?action=save', {
            id: val('promoId'),
            title: val('promoTitle'),
            description: val('promoDescription'),
            image_url: val('promoImageUrl'),
            link_url: val('promoLinkUrl'),
            button_text: val('promoButtonText'),
            placement: val('promoPlacement'),
            position_after: val('promoPosition'),
            repeat_every: val('promoRepeat'),
            start_date: val('promoStart').replace('T', ' '),
            end_date: val('promoEnd').replace('T', ' '),
            is_active: val('promoActive') === '1'
        });
        setStatus('promoFormStatus', data.message || '', data.success);
        if (data.success) {
            loadPromotions();
            setTimeout(function () {
                window.closePromotionModal();
                setStatus('promoFormStatus', '', null);
            }, 500);
        }
    } catch (e) {
        setStatus('promoFormStatus', 'خطا در ارتباط با سرور.', false);
    }
};

window.uploadPromotionImage = async function () {
    const input = document.getElementById('promoImageFile');
    if (!input || !input.files || !input.files[0]) {
        setStatus('promoFormStatus', 'ابتدا یک تصویر انتخاب کن.', false);
        return;
    }
    setStatus('promoFormStatus', 'در حال آپلود...', null);

    const form = new FormData();
    form.append('image', input.files[0]);
    form.append('action', 'upload');

    try {
        const res = await fetch('admin-promotions.php?action=upload', { method: 'POST', body: form });
        const data = await res.json();
        if (data.success && data.url) {
            const urlEl = document.getElementById('promoImageUrl');
            if (urlEl) urlEl.value = data.url;
            setStatus('promoFormStatus', 'تصویر آپلود شد.', true);
        } else {
            setStatus('promoFormStatus', data.message || 'آپلود ناموفق بود.', false);
        }
    } catch (e) {
        setStatus('promoFormStatus', 'خطا در ارتباط با سرور.', false);
    }
};

/* V2: delegation for fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    if (act === 'promo-new') { openPromotionEditor(); }
    else if (act === 'promo-close') { closePromotionModal(); }
    else if (act === 'promo-upload') { uploadPromotionImage(); }
    else if (act === 'promo-save') { savePromotion(); }
});
/* V2 boot: same as panel switchTab('promotions'). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadPromotions === 'function') loadPromotions();
});
