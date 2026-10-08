/* Melkino V2 — support admin tab, extracted VERBATIM from admin-panel.php
 * (SUPPORT TAB section). Self-contained (own supportEscapeHtml); needs only
 * csrf-shim (window.MELKINO_CSRF). API: support-api.php (untouched).
 * 1 template onclick + 9 fragment onclick -> data-act + delegation.
 */

/* =========================================================
   SUPPORT TAB
   ========================================================= */

let supportTicketsData = [];
let supportCurrentFilter = 'all';
let supportCurrentTicketId = null;

function supportEscapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function loadSupportTickets() {
    const listEl = document.getElementById('supportTicketsList');
    if (listEl) listEl.innerHTML = 'در حال بارگذاری…';

    fetch('support-api.php?action=admin_get_tickets', { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                if (listEl) listEl.innerHTML = '<div style="padding:12px;color:var(--danger);">' + supportEscapeHtml(data && data.message || 'خطا در دریافت تیکت‌ها') + '</div>';
                return;
            }
            supportTicketsData = Array.isArray(data.tickets) ? data.tickets : [];
            const countEl = document.getElementById('supportTicketCount');
            if (countEl) countEl.innerText = supportTicketsData.length + ' تیکت';
            const openTickets = supportTicketsData.filter(t => t.status === 'open' || t.status === 'answered').length;
            const openEl = document.getElementById('supportStatOpen');
            const totalEl = document.getElementById('supportStatTotal');
            if (openEl) openEl.innerText = openTickets;
            if (totalEl) totalEl.innerText = supportTicketsData.length;
            renderSupportTickets();
        })
        .catch(() => {
            if (listEl) listEl.innerHTML = '<div style="padding:12px;color:var(--danger);">خطا در ارتباط با سرور</div>';
        });
}

function filterSupportTickets(status, btnEl) {
    supportCurrentFilter = status;
    document.querySelectorAll('.support-filter-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    renderSupportTickets();
}

function renderSupportTickets() {
    const listEl = document.getElementById('supportTicketsList');
    if (!listEl) return;

    const rows = supportTicketsData.filter(t => supportCurrentFilter === 'all' || t.status === supportCurrentFilter);

    if (!rows.length) {
        listEl.innerHTML = '<div style="padding:12px;color:var(--text-secondary);">تیکتی برای نمایش وجود ندارد.</div>';
        return;
    }

    listEl.innerHTML = rows.map(t => `
        <div data-act="sup-open" data-tid="${t.id}" style="cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
            <div>
                <div style="font-weight:700;">${supportEscapeHtml(t.subject)} ${t.unread_count > 0 ? '<span style="background:var(--danger);color:#fff;border-radius:20px;padding:1px 8px;font-size:11px;margin-inline-start:6px;">' + t.unread_count + ' جدید</span>' : ''}</div>
                <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">${supportEscapeHtml(t.user_name)} • ${supportEscapeHtml(t.last_message || '')}</div>
            </div>
            <span class="support-status-badge status-${supportEscapeHtml(t.status)}" style="white-space:nowrap;font-size:12px;padding:4px 10px;border-radius:20px;background:var(--bg-secondary);">${supportEscapeHtml(t.status_label)}</span>
        </div>
    `).join('');
}

function openSupportTicket(ticketId) {
    supportCurrentTicketId = ticketId;

    fetch('support-api.php?action=admin_get_ticket&ticket_id=' + encodeURIComponent(ticketId), { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                alert(data && data.message || 'خطا در دریافت گفتگو');
                return;
            }

            const card = document.getElementById('supportConversationCard');
            if (card) card.style.display = '';

            const titleEl = document.getElementById('supportConversationTitle');
            if (titleEl) titleEl.innerText = data.ticket.subject + ' — ' + data.ticket.user_name;

            const closeBtn = document.getElementById('supportCloseBtn');
            const reopenBtn = document.getElementById('supportReopenBtn');
            const isClosed = data.ticket.status === 'closed';
            if (closeBtn) closeBtn.style.display = isClosed ? 'none' : '';
            if (reopenBtn) reopenBtn.style.display = isClosed ? '' : 'none';

            const msgEl = document.getElementById('supportMessages');
            if (msgEl) {
                msgEl.innerHTML = (data.messages || []).map(m => `
                    <div style="align-self:${m.sender_type === 'admin' ? 'flex-start' : 'flex-end'};max-width:80%;background:${m.sender_type === 'admin' ? 'var(--primary)' : 'var(--bg-secondary)'};color:${m.sender_type === 'admin' ? '#fff' : 'var(--text-primary)'};padding:10px 14px;border-radius:12px;">
                        <div style="font-size:11px;opacity:.75;margin-bottom:4px;">${supportEscapeHtml(m.sender_name)}</div>
                        <div style="white-space:pre-wrap;">${supportEscapeHtml(m.message)}</div>
                    </div>
                `).join('');
                msgEl.scrollTop = msgEl.scrollHeight;
            }

            card.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // شمارش تیکت‌های خوانده‌نشده در لیست به‌روزرسانی شود
            loadSupportTickets();
        })
        .catch(() => alert('خطا در ارتباط با سرور'));
}

function sendSupportReply() {
    const textEl = document.getElementById('supportReplyText');
    const message = (textEl?.value || '').trim();

    if (!supportCurrentTicketId) return;
    if (!message) { alert('لطفاً متن پاسخ را وارد کنید.'); return; }

    const body = new URLSearchParams({ action: 'admin_reply', ticket_id: supportCurrentTicketId, message });
    // توکن CSRF صریح فرستاده می‌شود؛ قبلاً فقط به هم‌مبدأ بودن تکیه می‌شد
    // و اگر مرورگر Referer را حذف می‌کرد پاسخ ۴۱۹ می‌گرفت.
    if (window.MELKINO_CSRF) body.append('csrf_token', window.MELKINO_CSRF);

    fetch('support-api.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                alert(data && data.message || 'خطا در ارسال پاسخ');
                return;
            }
            if (textEl) textEl.value = '';
            openSupportTicket(supportCurrentTicketId);
        })
        .catch(() => alert('خطا در ارتباط با سرور'));
}

function setSupportTicketStatus(action) {
    if (!supportCurrentTicketId) return;

    const body = new URLSearchParams({
        action: action === 'close' ? 'admin_close_ticket' : 'admin_reopen_ticket',
        ticket_id: supportCurrentTicketId
    });
    if (window.MELKINO_CSRF) body.append('csrf_token', window.MELKINO_CSRF);

    fetch('support-api.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                alert(data && data.message || 'خطا در به‌روزرسانی وضعیت');
                return;
            }
            openSupportTicket(supportCurrentTicketId);
        })
        .catch(() => alert('خطا در ارتباط با سرور'));
}

/* V2: delegation for template + fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    if (act === 'sup-open') { openSupportTicket(el.getAttribute('data-tid')); }
    else if (act === 'sup-filter') { filterSupportTickets(el.getAttribute('data-f'), el); }
    else if (act === 'sup-reload') { loadSupportTickets(); }
    else if (act === 'sup-status') { setSupportTicketStatus(el.getAttribute('data-st')); }
    else if (act === 'sup-hide') { document.getElementById('supportConversationCard').style.display = 'none'; }
    else if (act === 'sup-reply') { sendSupportReply(); }
});
/* V2 boot: same as panel switchTab('support'). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadSupportTickets === 'function') loadSupportTickets();
});
