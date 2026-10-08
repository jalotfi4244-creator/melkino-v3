<?php
/** Melkino V2 — admin images tab. Fragment VERBATIM from admin-images.php (after API branch);
 * wrapper from panel. Root file untouched (still serves API). */
?>
<div
    role="tabpanel"
    class="tab-content"
    id="tab-images"
>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('image') ?> مدیریت تصاویر آگهی‌ها</span>
        <button type="button" class="btn-secondary" style="padding:6px 14px;font-size:12px;" data-act="img-reload">
            ↻ بروزرسانی
        </button>
    </div>

    <div style="padding:0 16px 12px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label class="admin-field-label">جستجو (نام فایل، عنوان آگهی یا شناسه آگهی)</label>
            <input type="text" id="imagesSearch" class="admin-input" placeholder="مثال: AD-2024 یا آپارتمان" oninput="loadAdminImages()">
        </div>
        <div>
            <label class="admin-field-label">فقط تصاویرِ فایل‌ندار</label>
            <select id="imagesMissingOnly" class="admin-input" onchange="loadAdminImages()">
                <option value="0">همه</option>
                <option value="1">فقط ردیف‌های بدون فایل</option>
            </select>
        </div>
    </div>

    <div style="padding:0 16px 8px;color:var(--text-secondary);font-size:12px;line-height:1.9;">
        حذف هر تصویر، هم ردیف آن را از دیتابیس پاک می‌کند و هم فایل اصلی را از پوشه‌ی <code dir="ltr">uploads</code> روی سرور.
    </div>

    <div id="adminImagesContainer" style="padding:0 16px 16px;"></div>
</div>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('trash') ?> پاکسازی فایل‌های بدون استفاده</span>
        <button type="button" class="btn-secondary" style="padding:6px 14px;font-size:12px;" data-act="img-scan">
            <?= melkinoSvgIcon('search') ?> اسکن پوشه uploads
        </button>
    </div>

    <div style="padding:0 16px 8px;color:var(--text-secondary);font-size:12px;line-height:1.9;">
        فایل‌هایی که در پوشه‌ی آپلود مانده‌اند اما به هیچ آگهی‌ای وصل نیستند (مثلاً به‌خاطر حذف آگهی) را پیدا و حذف می‌کند.
        فایل لوگو و تصاویر تبلیغات هرگز حذف نمی‌شوند.
    </div>

    <div id="orphanImagesContainer" style="padding:0 16px 16px;"></div>
</div>


<div class="admin-card" style="margin-top:18px;">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('image') ?> عکس‌های پیش‌فرض انواع ملک</span>
    </div>
    <div style="padding:0 16px 16px;">
        <p style="font-size:13px; color:var(--text-secondary); margin:0 0 10px;">
            اگر آگهی عکس آپلودشده نداشته باشد، یکی از این عکس‌های تزیینی روی کارت و صفحهٔ جزئیات نمایش داده می‌شود.
            برای هر نوع ملک ۵ جایگاه وجود دارد؛ از همین‌جا می‌توانید عکس بگذارید، جایگزین یا حذف کنید (png / jpg / webp تا ۵ مگابایت).
        </p>
        <details id="mkDefaultsDetails">
            <summary style="cursor:pointer; font-weight:700; padding:8px 0;">باز کردن مدیریت عکس‌های پیش‌فرض</summary>
            <div style="margin:12px 0; padding:12px; border:1px dashed var(--border,#bbb); border-radius:12px; background:rgba(128,128,128,.06);">
                <div style="font-weight:800; margin-bottom:6px;">آپلود گروهی یک‌مرحله‌ای</div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">
                    همهٔ فایل‌های پوشهٔ assets/defaults زیپ (۳۰ عکس) را یک‌جا انتخاب کنید؛ جایگاه هر عکس از روی نام فایل تشخیص داده می‌شود
                    (مانند apartment-1.jpg ، villa-3.jpg ، shop-2.webp ، office-5.png ، land-4.jpg ، garden-1.jpg).
                </div>
                <input type="file" id="mkDefaultsBulk" multiple accept=".png,.jpg,.jpeg,.webp" style="font-size:12px;">
                <div id="mkDefaultsBulkStatus" style="margin-top:8px; font-size:12px; color:var(--text-secondary);"></div>
            </div>
            <div id="mkDefaultsHost" style="margin-top:12px;">در حال بارگذاری…</div>
        </details>
    </div>
</div>

<script>
(function () {
    var mkLoaded = false;
    var details = document.getElementById('mkDefaultsDetails');
    if (!details) return;
    details.addEventListener('toggle', function () {
        if (details.open && !mkLoaded) { mkLoaded = true; mkLoadDefaults(); }
    });

    var mkBulk = document.getElementById('mkDefaultsBulk');
    if (mkBulk) {
        mkBulk.addEventListener('change', function () {
            var files = Array.prototype.slice.call(mkBulk.files || []);
            if (!files.length) return;
            var status = document.getElementById('mkDefaultsBulkStatus');
            var jobs = [];
            var skipped = [];
            files.forEach(function (f) {
                var m = /^(apartment|villa|shop|office|land|garden)[-_](\d{1})\.(png|jpe?g|webp)$/i.exec(f.name);
                if (!m) { skipped.push(f.name); return; }
                var slot = parseInt(m[2], 10);
                if (slot < 1 || slot > 5) { skipped.push(f.name); return; }
                jobs.push({ file: f, slug: m[1].toLowerCase(), slot: slot });
            });
            var done = 0;
            status.textContent = 'در حال آپلود ' + jobs.length + ' فایل…';
            function next(lastTypes) {
                if (!jobs.length) {
                    status.textContent = 'آپلود گروهی تمام شد' + (skipped.length ? ' · نادیده گرفته شد (نام نامعتبر): ' + skipped.join('، ') : '') + '.';
                    mkBulk.value = '';
                    if (lastTypes) { mkRenderDefaults(lastTypes); } else { mkLoadDefaults(); }
                    return;
                }
                var j = jobs.shift();
                var fd = new FormData();
                fd.append('slug', j.slug);
                fd.append('slot', String(j.slot));
                fd.append('image', j.file);
                fetch('admin-images.php?mk_defaults=upload', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        done++;
                        status.textContent = 'آپلود ' + done + ' از ' + (done + jobs.length) + (d.success ? '' : ' · خطا: ' + (d.message || ''));
                        next(d.success ? d.types : null);
                    })
                    .catch(function () { done++; next(null); });
            }
            next(null);
        });
    }

    window.mkLoadDefaults = function () {
        fetch('admin-images.php?mk_defaults=state', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success) throw new Error(d.message || 'خطا');
                mkRenderDefaults(d.types);
            })
            .catch(function (e) {
                document.getElementById('mkDefaultsHost').textContent = 'خطا در بارگذاری: ' + e.message;
            });
    };

    function mkRenderDefaults(types) {
        var host = document.getElementById('mkDefaultsHost');
        host.innerHTML = '';
        types.forEach(function (t) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; padding:10px 0; border-bottom:1px solid var(--border,#eee);';
            var label = document.createElement('div');
            label.style.cssText = 'width:110px; font-weight:800; padding-top:14px;';
            label.textContent = t.label;
            row.appendChild(label);
            for (var s = 1; s <= 5; s++) {
                (function (slot) {
                    var url = t.slots[String(slot)] || '';
                    var cell = document.createElement('div');
                    cell.style.cssText = 'text-align:center;';
                    var box = document.createElement('div');
                    box.style.cssText = 'width:104px; height:68px; border-radius:10px; overflow:hidden; border:2px dashed var(--border,#ccc); display:flex; align-items:center; justify-content:center; background:var(--surface,#fff); margin-bottom:6px;';
                    if (url) {
                        var img = document.createElement('img');
                        img.src = url + '?t=' + Date.now();
                        img.alt = t.label + ' ' + slot;
                        img.style.cssText = 'width:100%; height:100%; object-fit:cover; display:block;';
                        box.appendChild(img);
                        box.style.borderStyle = 'solid';
                    } else {
                        var plus = document.createElement('span');
                        plus.textContent = 'خالی';
                        plus.style.cssText = 'font-size:12px; opacity:.6;';
                        box.appendChild(plus);
                    }
                    cell.appendChild(box);
                    var up = document.createElement('input');
                    up.type = 'file';
                    up.accept = '.png,.jpg,.jpeg,.webp';
                    up.style.cssText = 'width:104px; font-size:10px;';
                    up.addEventListener('change', function () {
                        if (!up.files || !up.files[0]) return;
                        var fd = new FormData();
                        fd.append('slug', t.slug);
                        fd.append('slot', String(slot));
                        fd.append('image', up.files[0]);
                        fetch('admin-images.php?mk_defaults=upload', { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                if (!d.success) { alert(d.message || 'خطا در آپلود'); return; }
                                mkRenderDefaults(d.types);
                            })
                            .catch(function () { alert('خطا در آپلود'); });
                    });
                    cell.appendChild(up);
                    if (url) {
                        var del = document.createElement('button');
                        del.type = 'button';
                        del.textContent = 'حذف';
                        del.className = 'btn-secondary';
                        del.style.cssText = 'margin-top:4px; font-size:11px; padding:2px 10px;';
                        del.addEventListener('click', function () {
                            if (!confirm('عکس جایگاه ' + slot + ' از «' + t.label + '» حذف شود؟')) return;
                            var fd = new FormData();
                            fd.append('slug', t.slug);
                            fd.append('slot', String(slot));
                            fetch('admin-images.php?mk_defaults=delete', { method: 'POST', body: fd, credentials: 'same-origin' })
                                .then(function (r) { return r.json(); })
                                .then(function (d) { if (d.success) { mkRenderDefaults(d.types); } else { alert(d.message || 'خطا'); } })
                                .catch(function () { alert('خطا در حذف'); });
                        });
                        cell.appendChild(del);
                    }
                    row.appendChild(cell);
                })(s);
            }
            host.appendChild(row);
        });
    }
})();
</script>

</div>
