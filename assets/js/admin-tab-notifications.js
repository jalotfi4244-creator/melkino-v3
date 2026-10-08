/* Melkino V2 — admin notifications tab: helpers + section extracted VERBATIM from admin-new-tabs.js
 * (only template onclick/onchange converted to data-*); V2 boot appended. API stays in root admin-notifications.php. */
(function () {
    'use strict';

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
       اعلان‌ها (ارسال عمومی + مدیریت)
       ===================================================== */

    const notifTypeFa = {
        broadcast: 'عمومی',
        welcome: 'خوش‌آمد',
        match: 'تطبیق',
        property_match: 'تطبیق',
        ad_submitted: 'ثبت آگهی',
        ad_published: 'انتشار',
        ad_rejected: 'رد آگهی',
        ad_revision_approved: 'تأیید ویرایش',
        ad_revision_rejected: 'رد ویرایش',
        ad_status: 'وضعیت آگهی',
        request_submitted: 'ثبت درخواست',
        request_status: 'وضعیت درخواست',
        system: 'سیستمی'
    };

    window.loadAdminNotifications = async function () {
        loadNotifStats();
        loadBroadcasts();
        loadRecentNotifs();
        if (typeof window.loadAdminNotifEvents === 'function') {
            window.loadAdminNotifEvents();
        }
    };

    /* ==============================================
       رویدادهای اعلان سیستمی (راند ۲۰)
       ============================================== */
    const notifEvEsc = (x) => String(x == null ? '' : x)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    window.loadAdminNotifEvents = async function () {
        const box = document.getElementById('notifEventsContainer');
        if (!box) return;
        try {
            const data = await getJson('admin-notifications.php?action=events_get');
            if (!data || !data.success) {
                box.innerHTML = '<div class="admin-field-help">دریافت رویدادها ناموفق بود.</div>';
                return;
            }
            const note = document.getElementById('notifEventsMasterNote');
            if (note) {
                note.innerHTML = data.master_enabled
                    ? '(سوئیچ اصلی «اعلان‌ها» روشن است.)'
                    : ((window.MK_IC ? MK_IC.warn : '') + ' سوئیچ اصلی «اعلان‌ها» در تنظیمات عمومی خاموش است — هیچ اعلان خودکاری ارسال نمی‌شود!');
                note.style.color = data.master_enabled ? '' : '#dc2626';
            }
            box.innerHTML = (data.events || []).map(ev => `
                <div class="security-switch" style="margin-bottom:8px;">
                    <div>
                        <span>${ev.emoji} ${notifEvEsc(ev.title)} <small style="display:inline;opacity:.75">— ${notifEvEsc(ev.channel)}</small></span>
                        <small>${notifEvEsc(ev.desc)}<br>${MK_IC.pin} محل ارسال: ${notifEvEsc(ev.where)}</small>
                    </div>
                    ${ev.toggleable
                        ? `<input type="checkbox" data-notif-event="${ev.id}" ${ev.enabled ? 'checked' : ''} title="فعال/غیرفعال">`
                        : `<span style="font-size:10px;color:var(--text-secondary);white-space:nowrap;">${ev.id === 'sms_otp' ? 'مدیریت در کارت پیامک' : 'ارسال دستی'}</span>`}
                </div>`).join('');
        } catch (e) {
            box.innerHTML = '<div class="admin-field-help">دریافت رویدادها ناموفق بود.</div>';
        }
    };

    window.saveAdminNotifEvents = async function () {
        const status = document.getElementById('notifEventsStatus');
        const events = {};
        document.querySelectorAll('[data-notif-event]').forEach(el => {
            events[el.getAttribute('data-notif-event')] = el.checked;
        });
        try {
            if (status) status.textContent = 'در حال ذخیره…';
            const res = await postJson('admin-notifications.php?action=events_save', { events });
            if (status) {
                status.innerHTML = (typeof mkStatusHtml === 'function') ? mkStatusHtml(!!(res && res.success), (res && res.success) ? 'ذخیره شد.' : 'ذخیره ناموفق بود.') : ((res && res.success) ? 'ذخیره شد.' : 'ذخیره ناموفق بود.');
                setTimeout(() => { status.innerHTML = ''; }, 4000);
            }
        } catch (e) {
            if (status) status.innerHTML = (typeof mkStatusHtml === 'function') ? mkStatusHtml(false, 'ذخیره ناموفق بود.') : 'ذخیره ناموفق بود.';
        }
    };

    async function loadNotifStats() {
        const box = document.getElementById('notifStatsRow');
        if (!box) return;
        try {
            const data = await getJson('admin-notifications.php?action=stats');
            if (!data || !data.success) return;
            const s = data.stats || {};
            const card = (num, label) =>
                '<div style="border:1px solid var(--border);border-radius:12px;padding:10px;text-align:center;">' +
                '<div style="font-size:20px;font-weight:800;color:var(--text-primary);">' + esc(s[num] ?? 0) + '</div>' +
                '<div style="font-size:11px;color:var(--text-secondary);margin-top:4px;">' + label + '</div></div>';
            box.innerHTML =
                card('users', 'کاربران') +
                card('total', 'کل اعلان‌ها') +
                card('unread', 'خوانده‌نشده') +
                card('broadcasts', 'اعلان عمومی');
        } catch (e) { /* ignore */ }
    }

    async function loadBroadcasts() {
        const box = document.getElementById('broadcastsListContainer');
        if (!box) return;
        box.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-secondary);font-size:13px;">در حال بارگذاری...</div>';
        try {
            const data = await getJson('admin-notifications.php?action=broadcast_list');
            const rows = (data && data.broadcasts) || [];
            if (!rows.length) {
                box.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">هنوز اعلان عمومی ارسال نشده است.</div>';
                return;
            }
            box.innerHTML = rows.map(b =>
                '<div style="border:1px solid var(--border);border-radius:14px;padding:12px;margin-bottom:10px;">' +
                '<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;">' +
                '<div style="min-width:0;flex:1;">' +
                '<div style="font-size:13px;font-weight:800;color:var(--text-primary);">' + MK_IC.megaphone + esc(b.title) + '</div>' +
                '<div style="font-size:12px;color:var(--text-secondary);margin-top:6px;line-height:1.9;">' + esc(b.message) + '</div>' +
                (b.url ? '<div style="font-size:11px;margin-top:4px;" dir="ltr"><a href="' + esc(b.url) + '" target="_blank" rel="noopener">' + esc(b.url) + '</a></div>' : '') +
                '<div style="font-size:11px;color:var(--text-muted);margin-top:6px;">' + esc(b.created_at) + ' · ارسال به ' + esc(b.sent_count) + ' کاربر · ' + esc(b.unread_count) + ' خوانده‌نشده</div>' +
                '</div>' +
                '<button type="button" class="btn-secondary" style="padding:5px 10px;font-size:11px;color:var(--danger);flex-shrink:0;" data-delb="' + Number(b.id) + '">' + MK_IC.trash + ' حذف</button>' +
                '</div></div>'
            ).join('');
        } catch (e) {
            box.innerHTML = '<div style="color:var(--danger);font-size:13px;">خطا در بارگذاری.</div>';
        }
    }

    window.sendBroadcast = async function () {
        const t = document.getElementById('broadcastTitle');
        const m = document.getElementById('broadcastMessage');
        const u = document.getElementById('broadcastUrl');
        const title = t ? t.value.trim() : '';
        const message = m ? m.value.trim() : '';
        const url = u ? u.value.trim() : '';
        if (!title || !message) {
            setStatus('broadcastStatus', 'عنوان و متن اعلان الزامی است.', false);
            return;
        }
        if (!confirm('این اعلان برای همه کاربران ارسال شود؟')) return;
        setStatus('broadcastStatus', 'در حال ارسال...', null);
        try {
            const data = await postJson('admin-notifications.php?action=broadcast_send', { title, message, url });
            setStatus('broadcastStatus', data.message || '', data.success);
            if (data.success) {
                if (t) t.value = '';
                if (m) m.value = '';
                if (u) u.value = '';
                loadNotifStats();
                loadBroadcasts();
            }
        } catch (e) {
            setStatus('broadcastStatus', 'خطا در ارتباط با سرور.', false);
        }
    };

    window.deleteBroadcast = async function (id) {
        if (!confirm('این اعلان عمومی و همه نسخه‌هایش حذف شود؟')) return;
        try {
            const data = await postJson('admin-notifications.php?action=broadcast_delete', { id });
            alert(data.message || (data.success ? 'حذف شد.' : 'خطا.'));
            if (data.success) { loadNotifStats(); loadBroadcasts(); }
        } catch (e) {
            alert('خطا در ارتباط با سرور.');
        }
    };

    /* فهرست آخرین اعلان‌ها + نوار ابزار حذف گروهی/بر اساس نوع */
    let recentNotifsCache = [];
    let recentNotifFilterValue = '';

    const recentNotifTypeOptions = [
        ['', 'همهٔ انواع'],
        ['welcome', 'خوش‌آمد'],
        ['match', 'فایل مناسب (تطبیق)'],
        ['broadcast', 'اطلاعیه عمومی'],
        ['ad_submitted', 'ثبت آگهی'],
        ['ad_published', 'انتشار آگهی'],
        ['ad_rejected', 'رد آگهی'],
        ['ad_revision_approved', 'تأیید ویرایش مالک'],
        ['ad_revision_rejected', 'رد ویرایش مالک'],
        ['ad_status', 'وضعیت آگهی'],
        ['request_submitted', 'ثبت درخواست'],
        ['request_status', 'وضعیت درخواست'],
        ['system', 'سیستمی'],
    ];

    window.setRecentNotifFilter = function (value) {
        recentNotifFilterValue = String(value || '');
        renderRecentNotifs();
    };

    function renderRecentNotifs() {
        const box = document.getElementById('recentNotifsContainer');
        if (!box) return;
        const filter = recentNotifFilterValue;
        const rows = filter
            ? recentNotifsCache.filter(n => n.type === filter)
            : recentNotifsCache;

        if (!recentNotifsCache.length) {
            box.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">اعلانی وجود ندارد.</div>';
            return;
        }

        const selectHtml =
            '<select id="recentNotifTypeFilter" class="admin-input" style="width:auto;padding:6px 10px;font-size:12px;" data-rnf="1">' +
            recentNotifTypeOptions.map(o =>
                '<option value="' + esc(o[0]) + '"' + (o[0] === filter ? ' selected' : '') + '>' + esc(o[1]) + '</option>'
            ).join('') +
            '</select>';

        if (!rows.length) {
            box.innerHTML =
                '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;padding:10px;border:1px dashed var(--border);border-radius:12px;">' +
                selectHtml +
                '<button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--danger);" data-act="notif-del-type">' + MK_IC.trash + ' حذف همهٔ نوع «' + esc(notifTypeFa[filter] || filter) + '»</button>' +
                '<button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--danger);" data-act="notif-del-all">' + MK_IC.trash + '</button>' +
                '</div>' +
                '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">اعلانی از این نوع در ۵۰ اعلان اخیر نیست (اما ممکن است در کلِ جدول باشد؛ دکمهٔ حذف نوع همان‌ها را پاک می‌کند).</div>';
            return;
        }

        box.innerHTML =
            '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;padding:10px;border:1px dashed var(--border);border-radius:12px;">' +
            '<label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);cursor:pointer;">' +
            '<input type="checkbox" id="recentNotifSelectAll" style="width:16px;height:16px;accent-color:var(--primary);" data-rnfall="1"> انتخاب همه</label>' +
            selectHtml +
            '<button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--danger);" data-act="notif-del-sel">' + MK_IC.trash + '</button>' +
            '<button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--danger);" data-act="notif-del-type">' + MK_IC.trash + ' حذف همهٔ نوع «' + esc(notifTypeFa[filter] || filter || '—') + '»</button>' +
            '<button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--danger);" data-act="notif-del-all">' + MK_IC.trash + '</button>' +
            '</div>' +
            rows.map(n => {
                const who = n.user_name || n.user_phone
                    ? esc(n.user_name || '') + (n.user_phone ? ' · ' + esc(n.user_phone) : '')
                    : (n.telegram_id ? 'تلگرام: ' + esc(n.telegram_id) : 'کاربر #' + esc(n.user_id || '?'));
                return '<div style="border:1px solid var(--border);border-radius:14px;padding:12px;margin-bottom:10px;' +
                    (Number(n.is_read) === 0 ? 'border-color:rgba(11,93,91,.35);' : '') + '">' +
                    '<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;">' +
                    '<div style="min-width:0;flex:1;">' +
                    '<div style="display:flex;align-items:center;gap:8px;font-size:12px;">' +
                    '<input type="checkbox" class="recent-notif-check" data-id="' + Number(n.id) + '" style="width:16px;height:16px;accent-color:var(--primary);flex-shrink:0;">' +
                    '<span>' + (notifTypeFa[n.type] || esc(n.type)) +
                    ' <span style="color:var(--text-muted);">· ' + who + '</span>' +
                    (Number(n.is_read) === 0 ? ' <span style="color:var(--primary);font-weight:800;">· خوانده‌نشده</span>' : '') + '</span></div>' +
                    '<div style="font-size:13px;font-weight:800;color:var(--text-primary);margin-top:4px;">' + esc(n.title) + '</div>' +
                    '<div style="font-size:12px;color:var(--text-secondary);margin-top:4px;line-height:1.8;">' + esc((n.message || '').substring(0, 200)) + '</div>' +
                    '<div style="font-size:11px;color:var(--text-muted);margin-top:4px;">' + esc(n.created_at) + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn-secondary" style="padding:5px 10px;font-size:11px;color:var(--danger);flex-shrink:0;" data-deln="' + Number(n.id) + '">🗑</button>' +
                    '</div></div>';
            }).join('');
    }

    async function loadRecentNotifs() {
        const box = document.getElementById('recentNotifsContainer');
        if (!box) return;
        box.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-secondary);font-size:13px;">در حال بارگذاری...</div>';
        try {
            const data = await getJson('admin-notifications.php?action=recent_list');
            recentNotifsCache = (data && data.notifications) || [];
            renderRecentNotifs();
        } catch (e) {
            box.innerHTML = '<div style="color:var(--danger);font-size:13px;">خطا در بارگذاری.</div>';
        }
    }

    window.toggleAllRecentNotifs = function (checked) {
        document.querySelectorAll('.recent-notif-check').forEach(c => { c.checked = !!checked; });
    };

    function selectedRecentNotifIds() {
        const ids = [];
        document.querySelectorAll('.recent-notif-check:checked').forEach(c => {
            const id = Number(c.getAttribute('data-id'));
            if (id > 0) ids.push(id);
        });
        return ids;
    }

    window.deleteSelectedNotifs = async function () {
        const ids = selectedRecentNotifIds();
        if (!ids.length) { alert('ابتدا یک یا چند اعلان را انتخاب کن.'); return; }
        if (!confirm(ids.length + ' اعلان انتخاب‌شده حذف شود؟')) return;
        try {
            const data = await postJson('admin-notifications.php?action=notif_delete_bulk', { ids });
            alert(data.message || (data.success ? 'حذف شد.' : 'خطا.'));
            if (data.success) { loadNotifStats(); loadRecentNotifs(); }
        } catch (e) { alert('خطا در ارتباط با سرور.'); }
    };

    window.deleteNotifsByType = async function () {
        const t = recentNotifFilterValue;
        if (!t) {
            alert('ابتدا از فهرست کنار دکمه، نوع اعلان را انتخاب کن (مثلاً خوش‌آمد یا فایل مناسب).');
            return;
        }
        const faName = (notifTypeFa[t] || t);
        if (!confirm('همهٔ اعلان‌های نوع «' + faName + '» (در کلِ جدول، نه فقط ۵۰ تای اخیر) حذف شود؟')) return;
        try {
            const data = await postJson('admin-notifications.php?action=notif_delete_filtered', { type: t });
            alert(data.message || (data.success ? 'حذف شد.' : 'خطا.'));
            if (data.success) { loadNotifStats(); loadRecentNotifs(); }
        } catch (e) { alert('خطا در ارتباط با سرور.'); }
    };

    window.deleteAllNotifs = async function () {
        if (!confirm('همهٔ اعلان‌های همهٔ کاربران حذف شود؟ این عمل برگشت‌پذیر نیست.')) return;
        if (!confirm('مطمئنی؟ تمام اعلان‌های کاربران برای همیشه پاک می‌شوند.')) return;
        try {
            const data = await postJson('admin-notifications.php?action=notif_delete_filtered', { type: 'all' });
            alert(data.message || (data.success ? 'حذف شد.' : 'خطا.'));
            if (data.success) { loadNotifStats(); loadRecentNotifs(); }
        } catch (e) { alert('خطا در ارتباط با سرور.'); }
    };

    window.deleteAdminNotif = async function (id) {
        if (!confirm('این اعلان حذف شود؟')) return;
        try {
            const data = await postJson('admin-notifications.php?action=notif_delete', { id });
            if (data.success) { loadNotifStats(); loadRecentNotifs(); }
            else alert(data.message || 'خطا.');
        } catch (e) {
            alert('خطا در ارتباط با سرور.');
        }
    };

    /* ---- V2 boot + CSP-safe bindings (replaces inline onclick/onchange) ---- */
    document.addEventListener('click', function (ev) {
        var g = function (sel) { return ev.target && ev.target.closest ? ev.target.closest(sel) : null; };
        var num = function (v) { var n = parseInt(v, 10); return isNaN(n) ? 0 : n; };
        var a = g('[data-act]');
        if (a) {
            var act = a.getAttribute('data-act');
            if (act === 'notif-reload' && typeof window.loadAdminNotifications === 'function') window.loadAdminNotifications();
            else if (act === 'notif-events-reload' && typeof window.loadAdminNotifEvents === 'function') window.loadAdminNotifEvents();
            else if (act === 'notif-events-save' && typeof window.saveAdminNotifEvents === 'function') window.saveAdminNotifEvents();
            else if (act === 'notif-broadcast' && typeof window.sendBroadcast === 'function') window.sendBroadcast();
            else if (act === 'notif-del-all' && typeof window.deleteAllNotifs === 'function') window.deleteAllNotifs();
            else if (act === 'notif-del-type' && typeof window.deleteNotifsByType === 'function') window.deleteNotifsByType();
            else if (act === 'notif-del-sel' && typeof window.deleteSelectedNotifs === 'function') window.deleteSelectedNotifs();
        }
        var dn = g('[data-deln]');
        if (dn && typeof window.deleteAdminNotif === 'function') window.deleteAdminNotif(num(dn.getAttribute('data-deln')));
        var db = g('[data-delb]');
        if (db && typeof window.deleteBroadcast === 'function') window.deleteBroadcast(num(db.getAttribute('data-delb')));
    });
    document.addEventListener('change', function (ev) {
        var t = ev.target;
        if (t && t.hasAttribute && t.hasAttribute('data-rnf') && typeof window.setRecentNotifFilter === 'function') {
            window.setRecentNotifFilter(t.value);
        }
        if (t && t.hasAttribute && t.hasAttribute('data-rnfall') && typeof window.toggleAllRecentNotifs === 'function') {
            window.toggleAllRecentNotifs(t.checked);
        }
    });
    function mxNotifInit() {
        if (typeof window.loadAdminNotifications === 'function') window.loadAdminNotifications();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mxNotifInit);
    } else {
        mxNotifInit();
    }
})();
