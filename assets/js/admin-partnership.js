/* Melkino V2 — admin-partnership console, extracted VERBATIM (only the PHP bootstrap adapted; bindings appended). */
(function () {
    'use strict';
    var statuses = (function(){try{var el=document.getElementById('mxAdminPartData');return el?JSON.parse(el.textContent||'{}').statuses||{}:{};}catch(e){return {};}})();
    var currentStatus = '';
    var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function token() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return window.MELKINO_CSRF || (m && m.content) || '';
    }
    function api(params, body) {
        var headers = { 'X-CSRF-Token': token() };
        var opts = { method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: headers };
        if (body) { opts.body = JSON.stringify(body); headers['Content-Type'] = 'application/json'; }
        return fetch('admin-partnership.php' + (params ? '?' + params : ''), opts).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).catch(function (err) {
            var b = document.getElementById('mkpApiErr');
            if (b) {
                b.style.display = 'block';
                b.innerHTML = '⚠️ ارتباط با سرور برقرار نشد (' + (err && err.message ? err.message : 'خطای نامشخص') + '). ' +
                    'صفحه را با Ctrl+F5 رفرش کنید؛ اگر تکرار شد یعنی فایل <b>admin-partnership.php</b> روی هاست قدیمی یا ناقص است.';
            }
            return null;
        });
    }
    function humanToman(v) {
        v = parseFloat(v);
        if (!isFinite(v) || v <= 0) return '';
        if (v >= 1e9) return fa(String(Math.round(v / 1e8) / 10).replace(/\.0$/, '')) + ' میلیارد تومان';
        if (v >= 1e6) return fa(String(Math.round(v / 1e6))) + ' میلیون تومان';
        return fa(Math.round(v).toLocaleString('en-US')) + ' تومان';
    }
    function jlist(v) { try { var a = JSON.parse(v || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function dateFa(dt) {
        if (!dt) return '—';
        try { return new Date(dt.replace(' ', 'T')).toLocaleDateString('fa-IR'); } catch (e) { return dt; }
    }

    function loadList() {
        var q = document.getElementById('q').value.trim();
        var qs = 'action=list&status=' + encodeURIComponent(currentStatus) + '&q=' + encodeURIComponent(q);
        api(qs).then(function (res) {
            if (!res || !res.success) return;
            renderCounts(res.counts || {});
            var tb = document.getElementById('rows');
            var rows = res.rows || [];
            if (!rows.length) {
                tb.innerHTML = '<tr><td colspan="8" class="empty">هنوز درخواستی ثبت نشده است.</td></tr>';
                return;
            }
            tb.innerHTML = rows.map(function (r) {
                var st = statuses[r.status] || r.status;
                return '<tr class="row" data-mkpopen="' + parseInt(r.id, 10) + '">' +
                    '<td class="code">' + esc(r.code || '—') + '</td>' +
                    '<td>' + esc((r.title || '').slice(0, 46)) + '</td>' +
                    '<td>' + esc([r.city, r.neighborhood].filter(Boolean).join('، ')) + '</td>' +
                    '<td>' + (r.owner_share ? fa(r.owner_share) + '/' + fa(r.builder_share || Math.round(100 - parseFloat(r.owner_share))) : '—') + '</td>' +
                    '<td class="tel" dir="ltr">' + esc(r.phone || '—') + '</td>' +
                    '<td><div class="bar"><i style="width:' + parseInt(r.completeness || 0, 10) + '%"></i></div></td>' +
                    '<td><span class="st st-' + esc(r.status) + '">' + esc(st) + '</span></td>' +
                    '<td>' + dateFa(r.created_at) + '</td></tr>';
            }).join('');
        });
    }
    function renderCounts(counts) {
        Object.keys(statuses).forEach(function (k) {
            var el = document.getElementById('cnt-' + k);
            if (el) el.textContent = counts[k] ? '(' + fa(counts[k]) + ')' : '';
        });
    }
    document.querySelectorAll('#filters .chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('#filters .chip').forEach(function (c) { c.classList.remove('on'); });
            chip.classList.add('on');
            currentStatus = chip.getAttribute('data-st');
            loadList();
        });
    });

    window.__mkpOpen = function (id) {
        api('action=get&id=' + id).then(function (res) {
            if (!res || !res.success) return;
            renderModal(res.row);
        });
    };

    function sec(title, items) {
        var lis = items.filter(function (x) { return x.v; })
            .map(function (x) { return '<span>' + (x.k ? '<i>' + x.k + ': </i>' : '') + esc(x.v) + '</span>'; }).join('');
        return lis ? '<div class="sec"><b>' + title + '</b><div class="kv">' + lis + '</div></div>' : '';
    }

    function renderModal(r) {
        var photos = jlist(r.photos);
        var legal = jlist(r.legal_status);
        var units = jlist(r.unit_shares);
        var others = jlist(r.doc_other);
        var docs = [];
        if (r.doc_deed) docs.push('<a href="' + esc(r.doc_deed) + '" target="_blank">سند 📎</a>');
        if (r.doc_permit) docs.push('<a href="' + esc(r.doc_permit) + '" target="_blank">جواز 📎</a>');
        if (r.doc_endjob) docs.push('<a href="' + esc(r.doc_endjob) + '" target="_blank">پایان‌کار 📎</a>');
        others.forEach(function (p, i) { docs.push('<a href="' + esc(p) + '" target="_blank">سایر ' + fa(i + 1) + ' 📎</a>'); });

        var stOpts = Object.keys(statuses).map(function (k) {
            return '<option value="' + k + '"' + (k === r.status ? ' selected' : '') + '>' + esc(statuses[k]) + '</option>';
        }).join('');

        document.getElementById('modalBox').innerHTML =
            '<button class="close-x" data-act="close-modal">✕</button>' +
            '<h2>' + esc(r.title || 'بدون عنوان') + '</h2>' +
            '<div class="sub">کد ' + esc(r.code || '—') + ' · ثبت: ' + dateFa(r.created_at) +
            ' · مالک: ' + esc(r.owner_name || '—') + ' · <a class="tel" dir="ltr" href="tel:' + esc(r.phone) + '">' + esc(r.phone || '') + '</a></div>' +
            sec('ملک', [
                { k: 'نوع', v: r.property_type }, { k: 'مساحت', v: r.area ? fa(r.area) + ' متر' : '' },
                { k: 'وضعیت', v: r.current_status }, { k: 'بر', v: r.br_count },
                { k: 'سن بنا', v: r.building_age ? fa(r.building_age) + ' سال' : '' },
                { k: 'طبقات فعلی', v: r.current_floors ? fa(r.current_floors) : '' },
                { k: 'واحد فعلی', v: r.current_units ? fa(r.current_units) : '' },
                { k: 'پارکینگ فعلی', v: r.current_parkings ? fa(r.current_parkings) : '' }
            ]) +
            sec('موقعیت', [
                { k: 'شهر', v: r.city }, { k: 'محله', v: r.neighborhood },
                { k: 'آدرس', v: r.address }, { k: 'عرض گذر', v: r.passage_width ? fa(r.passage_width) + ' متر' : '' },
                { k: 'عرض زمین', v: r.land_width ? fa(r.land_width) + ' متر' : '' },
                { k: 'جهت ملک', v: r.direction },
                (r.latitude ? { k: 'مختصات', v: r.latitude + ' , ' + r.longitude } : { v: '' })
            ]) +
            sec('ظرفیت ساخت', [
                (String(r.capacity_known) === '0' ? { v: 'پروانه ندارد — ظرفیت ساخت نیاز به بررسی دارد 🔎' } : { v: '' }),
                { k: 'تراکم', v: r.density ? fa(r.density) + '٪' : '' },
                { k: 'اشغال', v: r.occupancy_rate ? fa(r.occupancy_rate) + '٪' : '' },
                { k: 'طبقات قابل ساخت', v: r.buildable_floors ? fa(r.buildable_floors) : '' },
                { k: 'زیربنا', v: r.buildable_area ? fa(r.buildable_area) + ' متر' : '' },
                { k: 'واحد قابل ساخت', v: r.buildable_units ? fa(r.buildable_units) : '' }
            ]) +
            sec('جواز', [
                { v: r.permit_status },
                { k: 'شماره', v: r.permit_number }, { k: 'تاریخ', v: r.permit_date },
                { k: 'طبقات', v: r.permit_floors ? fa(r.permit_floors) : '' },
                { k: 'زیربنا', v: r.permit_area ? fa(r.permit_area) : '' }
            ]) +
            sec('شرایط مشارکت', [
                { k: 'سهم', v: r.owner_share ? 'مالک ' + fa(r.owner_share) + '٪ / سازنده ' + fa(r.builder_share || Math.round(100 - parseFloat(r.owner_share))) + '٪' : '' },
                { k: 'بلاعوض', v: r.balaghz ? (r.balaghz === 'بله' ? humanToman(r.balaghz_amount) || fa(r.balaghz_amount) : r.balaghz) : '' },
                { k: 'تقسیم', v: r.division_method }, { k: 'مدت', v: r.duration }, { k: 'تأمین هزینه', v: r.funding },
                { k: 'پارکینگ مالک', v: r.partner_parkings ? fa(r.partner_parkings) : '' },
                { k: 'انباری', v: r.partner_storage ? fa(r.partner_storage) : '' },
                { k: 'بازه ارزش', v: (r.value_from || r.value_to) ? (humanToman(r.value_from) + (r.value_to ? ' تا ' + humanToman(r.value_to) : '')) : '' },
                { k: 'واحدهای مالک', v: units.length ? units.join(' · ') : '' },
                { k: 'توضیحات', v: r.notes }
            ]) +
            sec('مالکیت و وضعیت', [
                { k: 'سند', v: r.deed_status }, { k: 'نوع سند', v: r.deed_kind }, { k: 'تعداد مالکین', v: r.owners_count ? fa(r.owners_count) : '' },
                { k: 'سکونت', v: r.occupancy },
                { k: 'وضعیت حقوقی', v: legal.length ? legal.join(' · ') : '—' }
            ]) +
            (photos.length ? '<div class="sec"><b>عکس‌ها (' + fa(photos.length) + ')</b><div class="photos">' +
                photos.map(function (p) { return '<a href="' + esc(p) + '" target="_blank"><img src="' + esc(p) + '" alt="" loading="lazy"></a>'; }).join('') + '</div></div>' : '') +
            (docs.length ? '<div class="sec"><b>مدارک</b><div class="kv">' + docs.join('') + '</div></div>' : '') +
            '<div class="sec"><b>یادداشت ادمین</b><textarea id="mkpNote" class="inp" style="width:100%;min-height:60px">' + esc(r.admin_note || '') + '</textarea></div>' +
            '<div class="modal-actions">' +
            '<select id="mkpStatusSel" class="inp">' + stOpts + '</select>' +
            '<button class="btn btn-p" data-savestatus="' + parseInt(r.id, 10) + '">ذخیره وضعیت</button>' +
            '<button class="btn btn-g" data-savenote="' + parseInt(r.id, 10) + '">ذخیره یادداشت</button>' +
            '<button class="btn btn-d" data-delrow="' + parseInt(r.id, 10) + '">حذف درخواست</button>' +
            '</div>';
        document.getElementById('modalBg').classList.add('open');
    }

    window.saveStatus = function (id) {
        var status = document.getElementById('mkpStatusSel').value;
        api('', { action: 'status', id: id, status: status }).then(function (res) {
            if (res && res.success) { closeModal(); loadList(); }
        });
    };
    window.saveNote = function (id) {
        var note = document.getElementById('mkpNote').value;
        api('', { action: 'note', id: id, note: note }).then(function (res) {
            if (res && res.success) alert('یادداشت ذخیره شد.');
        });
    };
    window.delRow = function (id) {
        if (!confirm('این درخواست برای همیشه حذف شود؟')) return;
        api('', { action: 'delete', id: id }).then(function (res) {
            if (res && res.success) { closeModal(); loadList(); }
        });
    };
    window.closeModal = function () {
        document.getElementById('modalBg').classList.remove('open');
    };
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    document.getElementById('q').addEventListener('keydown', function (e) { if (e.key === 'Enter') loadList(); });
    loadList();

    // [PARTNERSHIP] باز کردن خودکار یک درخواست (?open=ID) — از جدول آگهی‌ها
    var mkpAutoOpen = parseInt('(function(){try{var el=document.getElementById('mxAdminPartData');return el?parseInt(JSON.parse(el.textContent||'{}').open||0,10)||0:0;}catch(e){return 0;}})()', 10);
    if (mkpAutoOpen > 0 && typeof window.__mkpOpen === 'function') {
        try { window.__mkpOpen(mkpAutoOpen); } catch (e) {}
    }
})();


/* ---- V2 CSP-safe bindings (replaces inline onclick) ---- */
(function () {
    function num(v) { var n = parseInt(v, 10); return isNaN(n) ? 0 : n; }
    Array.prototype.forEach.call(document.querySelectorAll('[data-act="load-list"]'), function (el) {
        el.addEventListener('click', function () { if (typeof loadList === 'function') loadList(); });
    });
    function bindClose(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('[data-act="close-modal"]'), function (el) {
            if (el.__mkBound) return;
            el.__mkBound = true;
            el.addEventListener('click', function () { if (typeof closeModal === 'function') closeModal(); });
        });
    }
    bindClose(document);
    var mb = document.getElementById('modalBg');
    if (mb) mb.addEventListener('click', function (ev) {
        if (ev.target === this && typeof closeModal === 'function') closeModal();
    });
    document.addEventListener('click', function (ev) {
        var g = function (sel) { return ev.target && ev.target.closest ? ev.target.closest(sel) : null; };
        var c = g('[data-act="close-modal"]');
        if (c && typeof closeModal === 'function') { closeModal(); return; }
        var o = g('[data-mkpopen]');
        if (o && typeof window.__mkpOpen === 'function') window.__mkpOpen(num(o.getAttribute('data-mkpopen')));
        var st = g('[data-savestatus]');
        if (st && typeof saveStatus === 'function') saveStatus(num(st.getAttribute('data-savestatus')));
        var nt = g('[data-savenote]');
        if (nt && typeof saveNote === 'function') saveNote(num(nt.getAttribute('data-savenote')));
        var d = g('[data-delrow]');
        if (d && typeof delRow === 'function') delRow(num(d.getAttribute('data-delrow')));
    });
})();
