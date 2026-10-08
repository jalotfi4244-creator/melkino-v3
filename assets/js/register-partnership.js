/* Melkino V2 — register-partnership wizard, extracted VERBATIM (POST-success block stays in legacy; bindings appended). */
/* ---- legacy block 2 ---- */
/* ==========================================================
       منطق فرم — با همان سبک و اسامی توابع register-land.php
       ========================================================== */
    var currentStep = 1;
    var totalSteps = 4;
    var docFiles = { doc_deed: '', doc_permit: '', doc_endjob: '' };
    var otherDocs = [];

    var FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
    function toFa(n) { return String(n).replace(/\d/g, function (d) { return FA_DIGITS[+d]; }); }
    function toEn(s) {
        return String(s || '')
            .replace(/[۰-۹]/g, function (d) { return String(FA_DIGITS.indexOf(d)); })
            .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
    }

    /* ===================== رادیوهای مرحله ۱ و ۲ ===================== */
    document.querySelectorAll('input[name="property_type"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpPropertyTypeInput').value = this.value;
            updateCompleteness();
        });
    });
    document.querySelectorAll('input[name="current_status"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpCurrentStatusInput').value = this.value;
            applyConditions();
            updateCompleteness();
        });
    });
    document.querySelectorAll('input[name="br_count"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpBrCountInput').value = this.value;
            updateCompleteness();
        });
    });

    document.querySelectorAll('input[name="direction"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpDirectionInput').value = this.value;
            updateCompleteness();
        });
    });

    /* ===================== پروانه و ظرفیت ساخت (مرحله ۳) ===================== */
    document.querySelectorAll('input[name="permit_status"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpPermitStatusInput').value = this.value;
            applyConditions();
            updateCompleteness();
        });
    });

    /* ===================== سند و نوع سند (مرحله ۴) ===================== */
    document.querySelectorAll('input[name="deed_status"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpDeedInput').value = this.value;
            updateCompleteness();
        });
    });
    document.querySelectorAll('input[name="deed_kind"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('mkpDeedKindInput').value = this.value;
            updateCompleteness();
        });
    });

    /* ===================== مدیریت مراحل (مثل register-land) ===================== */
    function changeStep(direction) {
        if (direction === 1 && typeof melkinoValidateWizardStep === 'function') {
            if (!melkinoValidateWizardStep('step' + currentStep)) {
                return;
            }
        }
        /* اعتبارسنجی‌های خاص فرم مشارکت */
        if (direction === 1) {
            var err = stepError(currentStep);
            if (err) { alert(err); return; }
        }
        if (currentStep === totalSteps && direction === 1) {
            document.getElementById('prevBtn').style.display = 'none';
            document.getElementById('nextBtn').style.display = 'none';
            updatePreview();
            return;
        }

        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep += direction;
        if (currentStep < 1) currentStep = 1;
        if (currentStep > totalSteps) currentStep = totalSteps;
        document.getElementById('step' + currentStep).classList.add('active');
        document.getElementById('stepText').innerText = 'مرحله ' + toFa(currentStep) + ' از ' + toFa(totalSteps);
        for (var i = 1; i < totalSteps; i++) {
            var line = document.getElementById('line' + i);
            if (line) {
                if (i < currentStep) line.classList.add('active');
                else line.classList.remove('active');
            }
        }

        document.getElementById('prevBtn').style.display = (currentStep === 1) ? 'none' : 'flex';
        document.getElementById('nextBtn').innerText = (currentStep === totalSteps) ? 'پیش‌نمایش و ثبت' : 'مرحله بعد';
        document.getElementById('mainContent').scrollTop = 0;
    }

    function stepError(step) {
        if (step === 1) {
            if (!document.getElementById('mkpPropertyTypeInput').value) return 'نوع ملک را انتخاب کنید.';
            if (!(parseFloat(toEn(document.getElementById('mkpArea').value)) > 0)) return 'مساحت ملک را وارد کنید.';
            if (!document.getElementById('mkpCurrentStatusInput').value) return 'وضعیت فعلی ملک را انتخاب کنید.';
        }
        if (step === 2) {
            if (!document.getElementById('mkpNeighborhood').value.trim()) return 'محله را مشخص کنید.';
            if (document.getElementById('mkpAddress').value.trim().length < 8) return 'آدرس را کامل‌تر بنویسید.';
            var mapCfg = window.MELKINO_MAP_PICKER || {};
            var lat = document.getElementById('map_lat') ? document.getElementById('map_lat').value : '';
            var lng = document.getElementById('map_lng') ? document.getElementById('map_lng').value : '';
            if (mapCfg.require !== false && !(lat && lng)) return 'موقعیت ملک را روی نقشه مشخص کنید.';
        }
        if (step === 4) {
            if (!document.getElementById('mkpDeedInput').value) return 'وضعیت سند را انتخاب کنید.';
            if (!document.getElementById('mkpConfirm').checked) return 'تأیید صحت اطلاعات را علامت بزنید.';
        }
        return '';
    }

    /* ===================== آپلود مدارک ===================== */
    function handleDocUpload(input, key) {
        var file = input.files && input.files[0];
        if (!file) return;
        if (file.size > 8 * 1024 * 1024) {
            alert('حجم فایل بیش از ۸ مگابایت است.');
            input.value = '';
            return;
        }
        var formData = new FormData();
        formData.append('doc', file);
        formData.append('action', 'upload_doc');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.path) {
                        docFiles[key] = res.path;
                        var pv = document.getElementById('preview_' + key);
                        pv.innerHTML = '<div class="image-preview-item doc-item">' + file.name + ' ✓' +
                            '<button type="button" class="remove-btn" data-rmdoc="' + key + '"' >✕</button></div>';
                        updateCompleteness();
                    } else {
                        alert((res && res.message) || 'خطا در بارگذاری مدرک');
                    }
                } catch (e) {
                    alert('خطا در پردازش پاسخ سرور');
                }
            } else {
                alert('خطا در ارتباط با سرور');
            }
            input.value = '';
        };
        xhr.onerror = function () { alert('خطا در بارگذاری مدرک'); input.value = ''; };
        xhr.send(formData);
    }

    function removeDoc(key) {
        docFiles[key] = '';
        document.getElementById('preview_' + key).innerHTML = '';
        updateCompleteness();
    }

    function handleOtherDocs(input) {
        var files = Array.from(input.files || []);
        var room = 5 - otherDocs.length;
        if (room <= 0) { alert('حداکثر ۵ فایل.'); input.value = ''; return; }
        if (files.length > room) {
            alert('حداکثر ۵ فایل مجاز است؛ بقیه نادیده گرفته شد.');
            files = files.slice(0, room);
        }
        if (!files.length) { input.value = ''; return; }
        var formData = new FormData();
        files.forEach(function (f) { formData.append('images[]', f); });
        formData.append('action', 'upload_doc');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.paths) {
                        otherDocs = otherDocs.concat(res.paths);
                        renderOtherDocs();
                        if (res.errors && res.errors.length) alert(res.errors.join('\n'));
                    } else {
                        alert((res && res.message) || 'خطا در بارگذاری');
                    }
                } catch (e) { alert('خطا در پردازش پاسخ سرور'); }
            } else { alert('خطا در ارتباط با سرور'); }
            updateCompleteness();
            input.value = '';
        };
        xhr.onerror = function () { alert('خطا در بارگذاری'); input.value = ''; };
        xhr.send(formData);
    }

    function renderOtherDocs() {
        var pv = document.getElementById('preview_doc_other');
        pv.innerHTML = '';
        otherDocs.forEach(function (p, i) {
            var div = document.createElement('div');
            div.className = 'image-preview-item doc-item';
            div.textContent = p.split('_').pop();
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'remove-btn';
            btn.textContent = '✕';
            btn.onclick = function () { otherDocs.splice(i, 1); renderOtherDocs(); updateCompleteness(); };
            div.appendChild(btn);
            pv.appendChild(div);
        });
    }

    /* ===================== شرطی‌سازی ===================== */
    function isLand() {
        return document.getElementById('mkpCurrentStatusInput').value === 'زمین خالی';
    }
    function setGroupVisible(id, visible) {
        var g = document.getElementById(id);
        if (!g) return;
        g.classList.toggle('hide', !visible);
        g.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.disabled = !visible;
            if (!visible && el.type !== 'checkbox') el.value = '';
        });
    }
    function applyConditions() {
        var land = isLand();
        setGroupVisible('mkpOccupancyGroup', !land);
        var dj = document.getElementById('mkpDocEndjob');
        if (dj) {
            dj.classList.toggle('hide', land);
            if (land && docFiles.doc_endjob) removeDoc('doc_endjob');
        }
        /* ظرفیت ساخت فقط وقتی پروانه در حال اخذ یا صادرشده است نمایش داده می‌شود */
        var permit = document.getElementById('mkpPermitStatusInput').value;
        var showCapacity = (permit === 'در حال اخذ پروانه' || permit === 'پروانه صادر شده');
        setGroupVisible('mkpCapacityGroup', showCapacity);
        updateCompleteness();
    }
    function togglePassageUnknown() {
        var unk = document.getElementById('mkpPassageUnk').checked;
        var inp = document.getElementById('mkpPassage');
        inp.disabled = unk;
        if (unk) inp.value = '';
    }

    /* ===================== وضعیت حقوقی: انحصاری‌ها ===================== */
    function syncLegal(input) {
        var exclusive = (input.value === 'هیچ‌کدام' || input.value === 'نمی‌دانم');
        document.querySelectorAll('input[name="legal_status[]"]').forEach(function (c) {
            if (input.checked && exclusive) {
                if (c !== input) c.checked = false;
            } else if (input.checked && (c.value === 'هیچ‌کدام' || c.value === 'نمی‌دانم')) {
                c.checked = false;
            }
        });
    }

    /* ===================== کامل بودن اطلاعات (همان فرمول سرور) ===================== */
    function val(id) { var el = document.getElementById(id); return el ? String(el.value || '').trim() : ''; }
    function updateCompleteness() {
        var sc = 0;
        if (parseFloat(toEn(val('mkpArea'))) > 0) sc += 5;
        if (val('mkpPropertyTypeInput')) sc += 5;
        if (val('mkpCurrentStatusInput')) sc += 4;
        if (val('mkpNeighborhood')) sc += 6;
        if (val('mkpAddress').length >= 4) sc += 5;
        if (val('map_lat') && val('map_lng')) sc += 7;
        if (val('mkpBrCountInput')) sc += 4;
        var permit = val('mkpPermitStatusInput');
        if (permit) sc += 7;
        if (permit === 'پروانه ندارم') {
            sc += 4;
        } else if (val('mkpDensity') || val('mkpOccupancyRate') || val('mkpBuildFloors') || val('mkpBuildArea')) {
            sc += 8;
        }
        if (val('mkpDeedInput')) sc += 10;
        if (val('mkpDeedKindInput')) sc += 4;
        if (val('mkpOccupancy')) sc += 4;
        if (document.querySelectorAll('input[name="legal_status[]"]:checked').length) sc += 6;
        if (docFiles.doc_deed) sc += 9;
        if (docFiles.doc_permit) sc += 6;
        if (isLand() || docFiles.doc_endjob) sc += 4; // زمین خالی پایان‌کار ندارد
        if (otherDocs.length) sc += 6;
        sc = Math.max(0, Math.min(100, sc));
        document.getElementById('mkpMeterFill').style.width = sc + '%';
        document.getElementById('mkpMeterText').textContent = 'کامل بودن: ' + toFa(sc) + '٪';
        return sc;
    }

    /* ===================== جمع‌آوری داده و پیش‌نمایش ===================== */
    function collectLegal() {
        var out = [];
        document.querySelectorAll('input[name="legal_status[]"]:checked').forEach(function (c) { out.push(c.value); });
        return out;
    }
    function collect() {
        return {
            property_type: val('mkpPropertyTypeInput'),
            area: toEn(val('mkpArea')).replace(/[^\d.]/g, ''),
            current_status: val('mkpCurrentStatusInput'),
            neighborhood: val('mkpNeighborhood'),
            address: val('mkpAddress'),
            latitude: val('map_lat'),
            longitude: val('map_lng'),
            location_source: val('map_source') || 'map',
            passage_width: val('mkpPassage') ? toEn(val('mkpPassage')).replace(/[^\d.]/g, '') : '',
            land_width: toEn(val('mkpLandWidth')).replace(/[^\d.]/g, ''),
            br_count: val('mkpBrCountInput'),
            direction: val('mkpDirectionInput'),
            density: toEn(val('mkpDensity')).replace(/[^\d.]/g, ''),
            occupancy_rate: toEn(val('mkpOccupancyRate')).replace(/[^\d.]/g, ''),
            buildable_floors: toEn(val('mkpBuildFloors')).replace(/[^\d.]/g, ''),
            buildable_area: toEn(val('mkpBuildArea')).replace(/[^\d.]/g, ''),
            permit_status: val('mkpPermitStatusInput'),
            deed_status: val('mkpDeedInput'),
            deed_kind: val('mkpDeedKindInput'),
            owners_count: toEn(val('mkpOwnersCount')).replace(/[^\d.]/g, ''),
            occupancy: isLand() ? 'خالی' : val('mkpOccupancy'),
            legal_status: collectLegal(),
            doc_deed: docFiles.doc_deed,
            doc_permit: docFiles.doc_permit,
            doc_endjob: isLand() ? '' : docFiles.doc_endjob,
            doc_other: otherDocs.slice(0, 5)
        };
    }

    function editPreview() {
        document.getElementById('finalPreviewContainer').style.display = 'none';
        document.getElementById('prevBtn').style.display = (currentStep === 1) ? 'none' : 'flex';
        document.getElementById('nextBtn').style.display = 'flex';
        document.getElementById('nextBtn').innerText = 'مرحله بعد';
        changeStep(-totalSteps);
    }

    function previewHeading(p) {
        var parts = ['مشارکت در ساخت'];
        if (p.property_type) parts.push(p.property_type);
        if (p.area) parts.push(toFa(p.area) + ' متری');
        if (p.neighborhood) parts.push('— ' + p.neighborhood);
        return parts.join(' ');
    }

    function updatePreview() {
        var p = collect();
        document.getElementById('finalPreviewContainer').style.display = 'block';
        document.getElementById('mkpPreviewTitle').textContent = '📋 ' + previewHeading(p);

        function rows(items) {
            var html = '';
            items.forEach(function (it) {
                if (!it.v) return;
                html += '<div class="preview-row"><span class="preview-label">' + it.k + '</span><span class="preview-value rtl">' + it.v + '</span></div>';
            });
            return html;
        }
        var legalTxt = p.legal_status.length ? p.legal_status.join('، ') : 'هیچ‌کدام';
        document.getElementById('mkpPreviewBody').innerHTML =
            rows([
                { k: 'نوع ملک', v: p.property_type },
                { k: 'مساحت', v: p.area ? toFa(p.area) + ' متر مربع' : '' },
                { k: 'وضعیت فعلی', v: p.current_status },
                { k: 'بر', v: p.br_count },
                { k: 'جهت ملک', v: p.direction },
                { k: 'عرض زمین', v: p.land_width ? toFa(p.land_width) + ' متر' : '' }
            ]) +
            rows([
                { k: 'محله', v: p.neighborhood },
                { k: 'وضعیت پروانه', v: p.permit_status },
                { k: 'تراکم مجاز', v: p.density ? toFa(p.density) + '٪' : '' },
                { k: 'سطح اشغال مجاز', v: p.occupancy_rate ? toFa(p.occupancy_rate) + '٪' : '' },
                { k: 'طبقات قابل ساخت', v: p.buildable_floors ? toFa(p.buildable_floors) : '' },
                { k: 'زیربنای قابل ساخت', v: p.buildable_area ? toFa(p.buildable_area) + ' متر' : '' }
            ]) +
            rows([
                { k: 'وضعیت سند', v: p.deed_status },
                { k: 'نوع سند', v: p.deed_kind },
                { k: 'تعداد مالکین', v: p.owners_count ? toFa(p.owners_count) : '' },
                { k: 'وضعیت فعلی ملک', v: p.occupancy },
                { k: 'وضعیت حقوقی', v: legalTxt }
            ]);
    }

    /* ===================== ثبت نهایی (POST عادی مثل register-land) ===================== */
    document.getElementById('partnershipForm').addEventListener('submit', function (e) {
        var p = collect();
        if (!p.deed_status) {
            e.preventDefault();
            alert('وضعیت سند را انتخاب کنید.');
            return;
        }
        if (!document.getElementById('mkpConfirm').checked) {
            e.preventDefault();
            alert('تأیید صحت اطلاعات را علامت بزنید.');
            return;
        }
        document.getElementById('partnership_payload').value = JSON.stringify(p);
        try { localStorage.removeItem('melkino_partnership_draft_v4'); } catch (err) { }
    });

    /* ===================== پیش‌نویس ===================== */
    var DRAFT_KEY = 'melkino_partnership_draft_v4';
    var draftFields = ['mkpArea', 'mkpNeighborhood', 'mkpAddress', 'mkpPassage', 'mkpLandWidth',
        'mkpDensity', 'mkpOccupancyRate', 'mkpBuildFloors', 'mkpBuildArea',
        'mkpOwnersCount', 'mkpOccupancy'];
    var draftRadios = [['property_type', 'mkpPropertyTypeInput'], ['current_status', 'mkpCurrentStatusInput'],
        ['br_count', 'mkpBrCountInput'], ['direction', 'mkpDirectionInput'], ['permit_status', 'mkpPermitStatusInput'],
        ['deed_status', 'mkpDeedInput'], ['deed_kind', 'mkpDeedKindInput']];
    function saveDraft(silent) {
        try {
            var d = { checks: {}, radios: {}, legal: [], docs: docFiles, others: otherDocs, capUnk: false, passUnk: false };
            draftFields.forEach(function (id) { d.checks[id] = val(id); });
            draftRadios.forEach(function (pair) { d.radios[pair[0]] = val(pair[1]); });
            d.legal = collectLegal();
            d.passUnk = document.getElementById('mkpPassageUnk').checked;
            localStorage.setItem(DRAFT_KEY, JSON.stringify(d));
            if (!silent) alert('پیش‌نویس ذخیره شد ✓ — هر وقت خواستید ادامه دهید.');
        } catch (e) { }
    }
    function restoreDraft() {
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return;
            var d = JSON.parse(raw);
            if (!d || typeof d !== 'object') return;
            draftFields.forEach(function (id) { var el = document.getElementById(id); if (el && d.checks[id]) el.value = d.checks[id]; });
            (d.radios ? draftRadios : []).forEach(function (pair) {
                var v = d.radios[pair[0]];
                if (!v) return;
                var r = document.querySelector('input[name="' + pair[0] + '"][value="' + v.replace(/"/g, '\\"') + '"]');
                if (r) {
                    r.checked = true;
                    document.getElementById(pair[1]).value = v;
                }
            });
            (d.legal || []).forEach(function (v) {
                var c = document.querySelector('input[name="legal_status[]"][value="' + v.replace(/"/g, '\\"') + '"]');
                if (c) c.checked = true;
            });
            if (d.docs) {
                ['doc_deed', 'doc_permit', 'doc_endjob'].forEach(function (k) {
                    if (d.docs[k]) {
                        docFiles[k] = d.docs[k];
                        var pv = document.getElementById('preview_' + k);
                        if (pv) pv.innerHTML = '<div class="image-preview-item doc-item">' + d.docs[k].split('/').pop() + ' ✓' +
                            '<button type="button" class="remove-btn" data-rmdoc="' + k + '"' >✕</button></div>';
                    }
                });
            }
            if (Array.isArray(d.others)) { otherDocs = d.others.slice(0, 5); renderOtherDocs(); }
            if (d.passUnk) { document.getElementById('mkpPassageUnk').checked = true; togglePassageUnknown(); }
            applyConditions();
            updateCompleteness();
        } catch (e) { }
    }
    document.getElementById('mkpSaveDraft').onclick = function () {
        saveDraft(true);
        alert('پیش‌نویس ذخیره شد ✓');
        window.location.href = 'home.php';
    };
    /* فقط به‌روزرسانی متر — هیچ دست‌کاری زنده‌ای روی مقدار فیلدها انجام نمی‌شود */
    ['mkpArea', 'mkpNeighborhood', 'mkpAddress', 'mkpPassage', 'mkpLandWidth',
     'mkpDensity', 'mkpOccupancyRate', 'mkpBuildFloors', 'mkpBuildArea',
     'mkpOwnersCount'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', updateCompleteness);
    });
    ['mkpOccupancy'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', updateCompleteness);
    });

    /* شروع */
    document.addEventListener('DOMContentLoaded', function () {
        restoreDraft();
        applyConditions();
        updateCompleteness();
        document.getElementById('prevBtn').style.display = 'none';
        document.getElementById('nextBtn').innerText = 'مرحله بعد';
        document.getElementById('stepText').innerText = 'مرحله ' + toFa(1) + ' از ' + toFa(totalSteps);
    });

/* ---- V2 CSP-safe bindings (replaces inline onclick/onchange) ---- */
(function () {
    function on(id, ev, fn) {
        var el = document.getElementById(id);
        if (el) el.addEventListener(ev || 'click', fn);
    }
    function call(fn, ctx, args) {
        if (typeof fn === 'function') fn.apply(ctx, args || []);
    }
    on('prevBtn', 'click', function () { call(changeStep, null, [-1]); });
    on('nextBtn', 'click', function () { call(changeStep, null, [1]); });
    on('mkpPassageUnk', 'change', function () { call(togglePassageUnknown); });
    Array.prototype.forEach.call(document.querySelectorAll('[data-dz]'), function (el) {
        el.addEventListener('click', function () {
            var inp = document.getElementById(el.getAttribute('data-dz'));
            if (inp) inp.click();
        });
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-kind]'), function (el) {
        el.addEventListener('change', function () {
            call(handleDocUpload, null, [el, el.getAttribute('data-kind')]);
        });
    });
    on('docOtherInput', 'change', function () { call(handleOtherDocs, null, [this]); });
    Array.prototype.forEach.call(document.querySelectorAll('input[name="legal_status[]"]'), function (el) {
        el.addEventListener('change', function () { call(syncLegal, null, [el]); });
    });
    document.addEventListener('click', function (ev) {
        var t = ev.target && ev.target.closest ? ev.target.closest('[data-act="edit-preview"]') : null;
        if (t && typeof editPreview === 'function') editPreview();
        var r = ev.target && ev.target.closest ? ev.target.closest('[data-rmdoc]') : null;
        if (r && typeof removeDoc === 'function') removeDoc(r.getAttribute('data-rmdoc'));
    });
})();
