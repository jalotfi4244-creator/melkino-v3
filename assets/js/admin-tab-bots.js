/* Melkino V2 — bots admin tab, extracted VERBATIM from admin-new-tabs.js
 * (helpers L13-38 + bots region L40-551: settings/publish/bale/channel tests).
 * API: admin-bots.php (untouched). 15 fragment onclick -> data-act + delegation.
 */
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
}[c]));

function setStatus(id, message, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    // راند ۳۶: بدون ایموجی؛ آیکون SVG مشترک
    const clean = String(message || '').replace(/^[\u2705\u274C\u2714\u26D4]\s*/u, '');
        if (id === 'botSettingsStatus' && window.MelkinoBots) { window.MelkinoBots.notice('bale', clean, ok); return; }
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
       ربات و کانال
       ===================================================== */

    window.loadBotSettings = async function () {
        if (!window.MelkinoBots) return;
        await window.MelkinoBots.load();
        if (!window.__melkinoPublishSettingsLoaded) {
            window.__melkinoPublishSettingsLoaded = true;
            try { loadPublishSettings('telegram'); } catch (e) {}
            try { loadPublishSettings('bale'); } catch (e) {}
        }
    };
    window.saveBotSettings = function (scope) { return window.MelkinoBots && window.MelkinoBots.save(scope || 'all'); };
    window.testSmsSend = function () { return window.MelkinoBots && window.MelkinoBots.test('sms','message'); };

    /* =====================================================
       محتوای انتشار در کانال (فیلدها + متن ثابت بالا/پایین)
       ===================================================== */

    function publishIds(platform) {
        const cap = platform === 'bale' ? 'Bale' : 'Telegram';
        return {
            box: 'publishFields' + cap,
            header: 'publishHeader' + cap,
            footer: 'publishFooter' + cap,
            status: 'publishStatus' + cap,
            preview: 'publishPreview' + cap,
            type: 'publishType' + cap,
            trans: 'publishTrans' + cap,
            reset: 'publishReset' + cap,
            comboStatus: 'publishComboStatus' + cap
        };
    }

    function publishCombo(platform) {
        const ids = publishIds(platform);
        const t = document.getElementById(ids.type);
        const r = document.getElementById(ids.trans);
        return {
            property_type: t ? t.value : '',
            transaction_type: r ? r.value : ''
        };
    }

    function fillComboSelect(sel, items, allLabel) {
        if (!sel) return;
        const cur = sel.value;
        sel.innerHTML = '<option value="">' + allLabel + '</option>'
            + items.map(v => '<option value="' + esc(v) + '">' + esc(v) + '</option>').join('');
        if (cur && items.indexOf(cur) !== -1) sel.value = cur;
    }

    window.melkinoReloadPublish = function (platform) {
        loadPublishSettings(platform === 'bale' ? 'bale' : 'telegram');
    };

    async function loadPublishSettings(platform) {
        const ids = publishIds(platform);
        const box = document.getElementById(ids.box);
        try {
            const combo = publishCombo(platform);
            const data = await postJson('admin-bots.php?action=publish_get', Object.assign({ platform }, combo));
            if (!data || !data.success) {
                if (box) box.innerHTML = '<span style="font-size:12px;color:var(--danger);">خطا در بارگذاری.</span>';
                return;
            }

            // پر کردن سلکت‌های ترکیب (فقط مقادیر؛ انتخاب فعلی حفظ می‌شود)
            const combos = data.combos || {};
            fillComboSelect(document.getElementById(ids.type), combos.property_types || [], 'همهٔ انواع ملک');
            fillComboSelect(document.getElementById(ids.trans), combos.transactions || [], 'همهٔ معاملات');

            // وضعیتِ ترکیبِ انتخاب‌شده
            const cs = document.getElementById(ids.comboStatus);
            const resetBtn = document.getElementById(ids.reset);
            const isComboSel = !!(combo.property_type || combo.transaction_type);
            const overridden = !!(data.settings || {}).is_override;
            if (cs) {
                if (!isComboSel) {
                    cs.textContent = 'تنظیمات عمومی (برای همهٔ آگهی‌هایی که ترکیبشان تنظیم اختصاصی ندارد)';
                } else if (overridden) {
                    cs.textContent = '✔ این ترکیب تنظیم اختصاصی دارد';
                } else {
                    cs.textContent = 'این ترکیب تنظیم اختصاصی ندارد — از تنظیمات عمومی استفاده می‌کند؛ با «ذخیره» تنظیم اختصاصی ساخته می‌شود';
                }
            }
            if (resetBtn) resetBtn.style.display = (isComboSel && overridden) ? '' : 'none';

            const defs = data.defs || {};
            const on = Array.isArray((data.settings || {}).fields) ? (data.settings.fields) : Object.keys(defs);
            if (box) {
                box.innerHTML = Object.keys(defs).map(key => {
                    const d = defs[key] || {};
                    const checked = on.indexOf(key) !== -1 ? 'checked' : '';
                    return '<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-primary);cursor:pointer;border:1px solid var(--border);border-radius:10px;padding:8px 10px;">'
                        + '<input type="checkbox" data-publish-field="' + esc(key) + '" ' + checked + ' style="width:16px;height:16px;accent-color:var(--primary);">'
                        + '<span>' + esc(d.emoji || '') + ' ' + esc(d.label || key) + '</span>'
                        + '</label>';
                }).join('');
            }
            const h = document.getElementById(ids.header);
            const f = document.getElementById(ids.footer);
            if (h) h.value = (data.settings || {}).header || '';
            if (f) f.value = (data.settings || {}).footer || '';
        } catch (e) {
            if (box) box.innerHTML = '<span style="font-size:12px;color:var(--danger);">خطا در بارگذاری.</span>';
        }
    }

    window.savePublishSettings = async function (platform) {
        const ids = publishIds(platform === 'bale' ? 'bale' : 'telegram');
        platform = platform === 'bale' ? 'bale' : 'telegram';
        const box = document.getElementById(ids.box);
        const fields = [];
        if (box) {
            box.querySelectorAll('[data-publish-field]:checked').forEach(el => {
                fields.push(el.getAttribute('data-publish-field'));
            });
        }
        const h = document.getElementById(ids.header);
        const f = document.getElementById(ids.footer);
        setStatus(ids.status, 'در حال ذخیره...', null);
        try {
            const combo = publishCombo(platform);
            const data = await postJson('admin-bots.php?action=publish_save', Object.assign({
                platform,
                fields,
                header: h ? h.value : '',
                footer: f ? f.value : ''
            }, combo));
            setStatus(ids.status, data.message || '', data.success);
            if (data.success) loadPublishSettings(platform);
        } catch (e) {
            setStatus(ids.status, 'خطا در ارتباط با سرور.', false);
        }
    };

    window.resetPublishCombo = async function (platform) {
        platform = platform === 'bale' ? 'bale' : 'telegram';
        const ids = publishIds(platform);
        const combo = publishCombo(platform);
        if (!combo.property_type && !combo.transaction_type) return;
        setStatus(ids.status, 'در حال حذف تنظیم این ترکیب...', null);
        try {
            const data = await postJson('admin-bots.php?action=publish_save', Object.assign({
                platform,
                reset_combo: true
            }, combo));
            setStatus(ids.status, data.message || '', data.success);
            if (data.success) loadPublishSettings(platform);
        } catch (e) {
            setStatus(ids.status, 'خطا در ارتباط با سرور.', false);
        }
    };

    window.previewPublish = async function (platform) {
        const ids = publishIds(platform === 'bale' ? 'bale' : 'telegram');
        platform = platform === 'bale' ? 'bale' : 'telegram';
        const pre = document.getElementById(ids.preview);
        setStatus(ids.status, 'در حال ساخت پیش‌نمایش...', null);
        try {
            const data = await postJson('admin-bots.php?action=publish_preview', Object.assign({ platform }, publishCombo(platform)));
            if (!data || !data.success) {
                setStatus(ids.status, (data && data.message) || 'خطا در ساخت پیش‌نمایش.', false);
                return;
            }
            setStatus(ids.status, '', null);
            if (pre) {
                pre.style.display = 'block';
                pre.textContent = (data.is_sample ? '— پیش‌نمایش روی آگهی نمونه (هنوز آگهی منتشرشده‌ای نیست) —\n\n' : '')
                    + (data.text || '(متن خالی)');
            }
        } catch (e) {
            setStatus(ids.status, 'خطا در ارتباط با سرور.', false);
        }
    };

    /**
     * تست اتصال به تلگرام/بله
     *
     * ابتدا از «مرورگرِ ادمین» امتحان می‌شود؛ این برای هاست‌هایی که خروجیِ
     * سرورشان به api.telegram.org مسدود است (مثل InfinityFree) ضروری است،
     * چون در آن حالت تستِ سمتِ سرور همیشه خطا می‌دهد هرچند توکن سالم باشد.
     * اگر مرورگر هم موفق نشد، در نهایت از سرور امتحان می‌شود.
     */
    window.testBotConnection = function (which) { return window.MelkinoBots && window.MelkinoBots.test(which,which==='eitaa'?'configuration':'connection'); };

    /**
     * تست دسترسی به کانالِ بله
     *
     * ابتدا از مرورگرِ ادمین تلاش می‌شود (برای هاست‌هایی که ارتباطِ
     * خروجی‌شان با پیام‌رسان‌ها مسدود است) و در صورت عدم موفقیت از سرور.
     *
     * نکته: شناسه‌ی خالی یا پیش‌فرض پیش از ارسال بررسی می‌شود، چون در غیر
     * این صورت بله پاسخِ گمراه‌کننده‌ی
     * «chat_id: must be a valid value» را برمی‌گرداند.
     */
    /* =========================================================
   یافتنِ شناسه‌ی کانال بله
   =========================================================
   وقتی انتشار با خطای «no such group or user» شکست می‌خورد، یعنی
   شناسه‌ای که ادمین وارد کرده برای ربات قابلِ شناسایی نیست. این تابع
   از سرور می‌خواهد گفتگوهایی را که ربات اخیراً دیده است فهرست کند تا
   ادمین شناسه‌ی عددیِ درست را مستقیماً انتخاب کند.
========================================================= */
/* =========================================================
   بررسیِ توکنِ بله
   =========================================================
   خطای «Unauthorized» مربوط به خودِ توکن است، نه شناسه‌ی کانال.
   این تابع از سرور می‌پرسد آیا بله این توکن را قبول دارد یا نه، و
   کدام ربات به آن وصل است.
========================================================= */
    window.baleWhoAmI = function () { return window.MelkinoBots && window.MelkinoBots.test('bale','connection'); };

window.findBaleChats = async function () {

    var box = document.getElementById('baleChatsBox');
    if (!box) { return; }

    box.style.display = 'block';
    box.innerHTML = '<div style="font-size:12px;color:#666">در حال دریافتِ گفتگوها از بله…</div>';

    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function render(list, note) {
        if (!list || !list.length) {
            box.innerHTML = '<div style="font-size:12px;line-height:1.9">' + esc(note || 'موردی پیدا نشد.') + '</div>';
            return;
        }

        var html = '<div style="font-size:12px;margin-bottom:8px">'
                 + esc(note || 'روی شناسه‌ی عددی بزن تا در فیلدِ بالا قرار بگیرد:')
                 + '</div>';

        list.forEach(function (c) {
            var label = esc(c.title || (c.username ? '@' + c.username : c.id));
            html += '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:7px 0;border-bottom:1px solid #eee">'
                  +   '<code dir="ltr" data-id="' + esc(c.id) + '" style="background:#eef3f2;border:1px solid #cfdedc;'
                  +   'border-radius:6px;padding:3px 8px;font-size:12px;cursor:pointer">' + esc(c.id) + '</code>'
                  +   '<span style="font-size:12px">' + label + '</span>'
                  +   (c.type ? '<span style="font-size:11px;color:#888">' + esc(c.type) + '</span>' : '')
                  +   (c.username ? '<span dir="ltr" style="font-size:11px;color:#888">@' + esc(c.username) + '</span>' : '')
                  + '</div>';
        });

        box.innerHTML = html;

        var codes = box.querySelectorAll('code[data-id]');
        Array.prototype.forEach.call(codes, function (el) {
            el.onclick = function () {
                var input = document.getElementById('botBaleChannel');
                if (input) { input.value = el.getAttribute('data-id'); }
                var done = document.createElement('div');
                done.style.cssText = 'font-size:12px;color:#0b5d59;margin-top:8px';
                done.textContent = 'شناسه در فیلد قرار گرفت. حالا دکمه‌ی «ذخیره» را بزن، سپس دوباره انتشار را امتحان کن.';
                box.appendChild(done);
            };
        });
    }

    try {
        var r = await fetch('admin-bots.php?action=bale_find_chats', { cache: 'no-store' });
        var j = await r.json();
        render(j && j.chats, (j && j.message) || '');
    } catch (e) {
        box.innerHTML = '<div style="font-size:12px;color:#b00020">خطا در ارتباط با سرور.</div>';
    }
};

    window.testBaleChannelConnection = function () { return window.MelkinoBots && window.MelkinoBots.test('bale','channel'); };

    window.testChannelConnection = function () { return window.MelkinoBots && window.MelkinoBots.test('telegram','channel'); };

/* V2: delegation for fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act'), pf = el.getAttribute('data-pf');
    if (act === 'bots-save') { saveBotSettings(); }
    else if (act === 'bots-test') { testBotConnection(pf); }
    else if (act === 'bots-sms') { testSmsSend(); }
    else if (act === 'bots-ch') { testChannelConnection(); }
    else if (act === 'bots-bale-ch') { testBaleChannelConnection(); }
    else if (act === 'bots-bale-who') { baleWhoAmI(); }
    else if (act === 'bots-bale-find') { findBaleChats(); }
    else if (act === 'bots-pub-save') { savePublishSettings(pf); }
    else if (act === 'bots-pub-prev') { previewPublish(pf); }
    else if (act === 'bots-pub-reset') { resetPublishCombo(pf); }
});
/* V2 boot: same as panel switchTab('bots'). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadBotSettings === 'function') loadBotSettings();
});
