/* Melkino V2 — forms admin tab: combos IIFE VERBATIM from admin-panel.php inline
 * <script> (self-booting). APIs: admin-panel.php?forms_combos=1, save_form_options.php,
 * form-options.php (all untouched, absolute URLs). 3 template onclick -> data-act.
 */
    (function () {
        function esc(s) {
            return String(s || '').replace(/[&<>"]/g, function (c) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]);
            });
        }
        window.melkinoFormsRenderCombos = function (data) {
            var box = document.getElementById('formsCombosBox');
            if (!box) return;
            var html = '';
            var keys = Object.keys(data || {});
            keys.forEach(function (k) {
                var d = data[k] || {};
                var items = d.items || [];
                html += '<div class="admin-field" data-combo="' + esc(k) + '" style="border:1px solid rgba(212,175,55,.22);border-radius:12px;padding:12px;">';
                html += '<label style="font-weight:800;margin-bottom:8px;display:block;">' + esc(d.label || k) + '</label>';
                html += '<div class="forms-combo-list">';
                items.forEach(function (it) {
                    html += '<div style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">';
                    html += '<input type="text" value="' + esc(it) + '" style="flex:1;">';
                    html += '<button type="button" class="btn-icon-sm" data-act="forms-del">حذف</button>';
                    html += '</div>';
                });
                html += '</div>';
                html += '<button type="button" class="btn-icon-sm" data-act="forms-add">+ گزینه</button>';
                html += '</div>';
            });
            box.innerHTML = html || '<div class="admin-section-help">فهرستی پیدا نشد.</div>';
        };
        window.melkinoFormsAddItem = function (btn) {
            var list = btn.parentNode.querySelector('.forms-combo-list');
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;align-items:center;';
            row.innerHTML = '<input type="text" value="" placeholder="گزینه جدید" style="flex:1;"><button type="button" class="btn-icon-sm" data-act="forms-del">حذف</button>';
            list.appendChild(row);
            var inp = row.querySelector('input');
            if (inp) inp.focus();
        };
        window.melkinoFormsSaveCombos = function () {
            var box = document.getElementById('formsCombosBox');
            var msg = document.getElementById('formsCombosMsg');
            var payload = {};
            box.querySelectorAll('[data-combo]').forEach(function (card) {
                var k = card.getAttribute('data-combo');
                var items = [];
                card.querySelectorAll('.forms-combo-list input').forEach(function (inp) {
                    var v = String(inp.value || '').trim();
                    if (v) items.push(v);
                });
                payload[k] = items;
            });
            msg.textContent = 'در حال ذخیره…';
            var body = JSON.stringify({ action: 'save', combos: payload });
            var opts = {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: body
            };
            function ok(j) {
                msg.textContent = (j && j.message) ? j.message : 'در دیتابیس ذخیره شد. فرم ثبت ملک را یک‌بار رفرش کنید.';
                if (j && j.combos) melkinoFormsRenderCombos(j.combos);
            }
            function fail(j) {
                msg.textContent = (j && j.message) ? j.message : 'ذخیره در دیتابیس نشد.';
            }
            fetch('save_form_options.php', opts).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); }).then(function (x) {
                if (x.j && x.j.success) { ok(x.j); return; }
                return fetch('admin-panel.php?forms_combos=1', opts).then(function (r2) { return r2.json(); }).then(function (j2) {
                    if (j2 && j2.success) ok(j2); else fail(j2 || x.j);
                });
            }).catch(function () {
                fetch('admin-panel.php?forms_combos=1', opts).then(function (r) { return r.json(); }).then(function (j) {
                    if (j && j.success) ok(j); else fail(j);
                }).catch(function () { msg.textContent = 'خطا در ذخیره. فایل save_form_options.php را هم آپلود کنید.'; });
            });
        };
        fetch('save_form_options.php', { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
            if (j && j.combos) melkinoFormsRenderCombos(j.combos);
        }).catch(function () {
            fetch('form-options.php', { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
                if (j && j.combos) melkinoFormsRenderCombos(j.combos);
            }).catch(function () {});
        });
    })();

/* V2: delegation for template + fragment buttons (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    if (act === 'forms-del') { el.parentNode.remove(); }
    else if (act === 'forms-add') { melkinoFormsAddItem(el); }
    else if (act === 'forms-save') { melkinoFormsSaveCombos(); }
});
