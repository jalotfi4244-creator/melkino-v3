<?php
/** Melkino V2 — admin promotions tab. Fragment VERBATIM from admin-promotions.php (after API branch); wrapper from panel. Root file untouched (still serves API). */
?>
<div
    role="tabpanel"
    class="tab-content"
    id="tab-promotions"
>

<div class="admin-card">
    <div class="card-header">
        <span class="card-title"><?= melkinoSvgIcon('megaphone') ?> مدیریت تبلیغات</span>
        <button type="button" class="btn-primary" style="padding:6px 14px;font-size:12px;" data-act="promo-new">
            <?= melkinoSvgIcon('plus') ?> تبلیغ جدید
        </button>
    </div>
    <div style="padding:0 16px 8px;color:var(--text-secondary);font-size:12px;line-height:1.9;">
        فقط عکس آپلود کن. اگر لینک بگذاری، با کلیک روی عکس باز می‌شود؛ اگر نگذاری فقط نمایش داده می‌شود. هیچ متنی روی کارت نمی‌آید.
    </div>
    <div id="promotionsListContainer" style="padding:0 16px 16px;"></div>
</div>

<!-- ویرایشگر تبلیغ -->
<div class="modal-overlay" id="promotionModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="promotionModalTitle">تبلیغ جدید</h3>
            <button class="modal-close" data-act="promo-close" aria-label="بستن">✕</button>
        </div>

        <div id="promotionFormBody" style="display:flex;flex-direction:column;gap:var(--space-2);">
            <input type="hidden" id="promoId" value="">

            <input type="hidden" id="promoTitle" value="">
            <input type="hidden" id="promoDescription" value="">
            <input type="hidden" id="promoButtonText" value="">

            <label class="admin-field-label">تصویر تبلیغ (الزامی)</label>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="file" id="promoImageFile" accept="image/*" style="font-size:12px;">
                <button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;" data-act="promo-upload"><?= melkinoSvgIcon('upload') ?> آپلود</button>
            </div>
            <input type="text" id="promoImageUrl" class="admin-input" dir="ltr" placeholder="uploads/promotions/... یا آدرس کامل">

            <label class="admin-field-label">لینک (اختیاری — اگر خالی باشد فقط عکس نشان داده می‌شود)</label>
            <input type="text" id="promoLinkUrl" class="admin-input" dir="ltr" placeholder="https://...">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="admin-field-label">محل نمایش</label>
                    <select id="promoPlacement" class="admin-input">
                        <option value="all">همه صفحه‌ها</option>
                        <option value="home">صفحه اصلی</option>
                        <option value="properties">فهرست املاک</option>
                        <option value="search">نتایج جستجو</option>
                        <option value="vip">ملک‌های ویژه</option>
                    </select>
                </div>
                <div>
                    <label class="admin-field-label">نمایش بعد از کارت شماره</label>
                    <input type="number" id="promoPosition" class="admin-input" min="1" value="3">
                </div>
                <div>
                    <label class="admin-field-label">تکرار هر چند کارت</label>
                    <input type="number" id="promoRepeat" class="admin-input" min="0" value="0" title="۰ یعنی فقط یک‌بار">
                </div>
                <div>
                    <label class="admin-field-label">وضعیت</label>
                    <select id="promoActive" class="admin-input">
                        <option value="1">فعال</option>
                        <option value="0">غیرفعال</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="admin-field-label">شروع نمایش</label>
                    <input type="datetime-local" id="promoStart" class="admin-input" dir="ltr">
                </div>
                <div>
                    <label class="admin-field-label">پایان نمایش</label>
                    <input type="datetime-local" id="promoEnd" class="admin-input" dir="ltr">
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="button" class="btn-primary" data-act="promo-save"><?= melkinoSvgIcon('save') ?> ذخیره</button>
                <button type="button" class="btn-secondary" data-act="promo-close">انصراف</button>
                <span id="promoFormStatus" class="admin-status-msg"></span>
            </div>
        </div>
    </div>
</div>

</div>
