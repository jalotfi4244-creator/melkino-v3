<?php
/** Melkino V2 — admin global-settings tab. Fragment VERBATIM from admin-panel.php (GLOBAL section). */
?>
<!-- =========================================================
     GLOBAL
     ========================================================= -->

<div
    class="tab-content"
    id="tab-global"
>

    <div class="admin-card" id="mkCalcAdminCard">
        <div class="card-header">
            <span class="card-title">ماشین‌حساب قیمت ملک</span>
        </div>
        <div style="padding:14px 16px 18px">
            <div class="security-switch" style="margin-bottom:16px">
                <div>
                    <span>فعال بودن ماشین‌حساب</span>
                    <small>اگر خاموش باشد آیکون هدر، میانبر خانه و صفحهٔ محاسبه برای عموم دیده نمی‌شود.</small>
                </div>
                <input id="g_enable_property_calculator" type="checkbox" checked>
            </div>
            <div class="admin-section-help" style="margin-bottom:10px">ضرایب فرمول — درصد را با اعشار دقیق وارد کنید.</div>
            <div class="admin-grid-2">
                <div class="admin-field"><label>استهلاک سالانه (٪ از قیمت هر متر)</label><input id="g_calc_estehklak" type="number" step="0.0001" min="0" max="100" value="1.5"></div>
                <div class="admin-field"><label>کسر وقفی (٪)</label><input id="g_calc_waqf" type="number" step="0.0001" min="0" max="100" value="20"></div>
                <div class="admin-field"><label>کسر بدون پارکینگ — حداقل (٪)</label><input id="g_calc_park_min" type="number" step="0.0001" min="0" max="100" value="8"></div>
                <div class="admin-field"><label>کسر بدون پارکینگ — پیش‌فرض (٪)</label><input id="g_calc_park_def" type="number" step="0.0001" min="0" max="100" value="9"></div>
                <div class="admin-field"><label>کسر بدون پارکینگ — حداکثر (٪)</label><input id="g_calc_park_max" type="number" step="0.0001" min="0" max="100" value="10"></div>
                <div class="admin-field"><label>کسر آسانسور به ازای هر طبقه بالای اول (٪)</label><input id="g_calc_elev" type="number" step="0.0001" min="0" max="100" value="2.5"></div>
                <div class="admin-field"><label>نسبت قیمت حیاط به قیمت هر متر (٪)</label><input id="g_calc_yard" type="number" step="0.0001" min="0" max="100" value="33.3333"></div>
                <div class="admin-field"><label>مقسوم‌علیه رهن کامل</label><input id="g_calc_rent_div" type="number" step="0.0001" min="0.0001" value="8"></div>
                <div class="admin-field"><label>مبنای رهن (تومان)</label><input id="g_calc_rent_base" type="number" step="1" min="1" value="100000000"></div>
                <div class="admin-field"><label>اجاره ماهانه به ازای هر واحد مبنا (تومان)</label><input id="g_calc_rent_per" type="number" step="1" min="0" value="3000000"></div>
            </div>
            <div style="margin-top:16px">
                <div class="admin-section-title" style="font-size:14px;margin-bottom:6px">آیتم‌های اضافی مثل انباری</div>
                <div class="admin-section-help" style="margin-bottom:8px">برای انباری معمولاً «متراژ × قیمت متر × نسبت» را بگذارید. می‌توانید آیتم جدید هم اضافه کنید.</div>
                <div id="mkCalcExtrasBox">
                    <div class="mk-calc-extra-row" data-id="storage" style="display:grid;grid-template-columns:1.3fr 1.1fr .7fr auto auto;gap:8px;align-items:end;margin-bottom:8px">
                        <div class="admin-field" style="margin:0"><label>نام آیتم</label><input class="mk-ex-label" value="انباری" placeholder="مثلاً انباری"></div>
                        <div class="admin-field" style="margin:0"><label>نوع محاسبه</label><select class="mk-ex-mode">
                            <option value="area_ratio" selected>متراژ × قیمت متر × نسبت</option>
                            <option value="percent_add">افزایش درصدی از قیمت</option>
                            <option value="percent_cut">کاهش درصدی از قیمت</option>
                        </select></div>
                        <div class="admin-field" style="margin:0"><label>مقدار (٪)</label><input class="mk-ex-pct" type="number" step="0.0001" min="0" max="200" value="50"></div>
                        <label class="admin-field" style="margin:0;display:flex;gap:6px;align-items:center;padding-bottom:10px"><input class="mk-ex-on" type="checkbox" checked> فعال</label>
                        <button type="button" class="btn-icon-sm mk-ex-del" style="margin-bottom:8px">حذف</button>
                    </div>
                </div>
                <button type="button" class="btn-icon-sm" id="mkCalcExtraAdd">افزودن آیتم</button>
            </div>
        </div>
    </div>

    <div class="admin-card">

        <div class="card-header">

            <span class="card-title">
                گزینه‌های عمومی
            </span>

        </div>

        <div id="globalContainer"></div>

    </div>

</div>

