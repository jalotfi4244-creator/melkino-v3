/* دفتر ملکینو شهر — نقشه فایل‌ها (خودکفا).
 * تایل و رنگ‌ها عین سایت؛ پین‌ها روی مختصات دقیق دیتابیس (بدون حریم سایت).
 * برخلاف کپی قبلی، اگر Leaflet لود نشود پیام خطای واضح نشان می‌دهد نه صفحه خالی.
 */
(function () {
    var cfg = window.OFFICE_MAP || {};
    var el = document.getElementById('ofMapCanvas');
    if (!el) return;
    var points = cfg.points || [];
    if (!window.L) {
        el.innerHTML = '<p style="padding:40px;text-align:center">خطا: کتابخانه نقشه (Leaflet) لود نشد. پوشه assets/leaflet را روی هاست بررسی کنید.</p>';
        return;
    }
    if (!points.length) {
        el.innerHTML = '<p style="padding:40px;text-align:center">فایل مختصات‌داری با این مشخصات یافت نشد.</p>';
        return;
    }
    var tile = cfg.tiles || {};
    var map = L.map('ofMapCanvas').setView(cfg.center || [36.4182, 54.9763], 13);
    L.tileLayer(tile.url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: tile.attr || '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var COLORS = cfg.colors || {};
    function pinColor(type) {
        var t = String(type || '').trim();
        var c = COLORS[t];
        if (!c) {
            var keys = Object.keys(COLORS);
            for (var i = 0; i < keys.length; i++) {
                if (keys[i] && t.indexOf(keys[i]) !== -1) { c = COLORS[keys[i]]; break; }
            }
        }
        return c || '#0e7c6e';
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }
    function pinIcon(type) {
        var color = pinColor(type);
        return L.divIcon({
            className: 'of-pin-wrap',
            html: '<span style="display:block;width:26px;height:26px;border-radius:50% 50% 50% 4px;'
                + 'transform:rotate(-45deg);background:' + color + ';border:3px solid #fff;'
                + 'box-shadow:0 2px 6px rgba(0,0,0,.45)"></span>',
            iconSize: [26, 26], iconAnchor: [13, 24], popupAnchor: [0, -20]
        });
    }

    var bounds = [];
    points.forEach(function (p) {
        var lat = parseFloat(p.lat);
        var lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) return;
        var mk = L.marker([lat, lng], { icon: pinIcon(p.type), title: String(p.code || '') + ' — ' + String(p.title || '') });
        var meta = [p.tx, p.type, p.area ? (p.area + ' متر') : ''].filter(Boolean).join(' · ');
        mk.bindPopup(
            '<div style="min-width:200px;font-family:inherit;text-align:right" dir="rtl">' +
            '<strong>' + esc(p.title || p.code) + '</strong><br>' +
            '<span style="color:#666;font-size:12px">' + esc(meta) + '</span><br>' +
            '<span style="font-size:12px" dir="ltr">' + esc(String(lat)) + ' ، ' + esc(String(lng)) + '</span><br>' +
            '<a href="file-edit.php?id=' + encodeURIComponent(p.code || '') + '">✏️ ویرایش</a>' +
            ' | <a href="../property-details.php?id=' + encodeURIComponent(p.code || '') + '">👁 مشاهده</a>' +
            '</div>'
        );
        mk.addTo(map);
        bounds.push([lat, lng]);
    });
    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }
})();
