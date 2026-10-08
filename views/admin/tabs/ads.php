<?php
/**
 * Melkino V2 — admin ads tab (fragments admin-ads.php + admin-ads-modals.php VERBATIM;
 * only onclick converted to data-act + JSON bootstrap added; root files kept for the panel).
 * Vars: $ads (rows/loaded/total/hasMore/totals/error from AdminTabController::adsDataset).
 */
$adsTotals = ($ads['totals'] ?? []);
$mxAdsBoot = [
    'rows' => ($ads['rows'] ?? []),
    'loaded' => ($ads['loaded'] ?? 0),
    'total' => ($ads['total'] ?? 0),
    'hasMore' => ($ads['hasMore'] ?? false),
    'totals' => $adsTotals,
    'error' => (string)($ads['error'] ?? ''),
    // Panel sets these as separate inline globals (L4569/L6061); bundled here.
    // melkinoRatingFeatureEnabled() needs the PDO passed explicitly (no global $pdo in V2).
    'ratingEnabled' => (function_exists('melkinoRatingFeatureEnabled') && melkinoRatingFeatureEnabled(\Melkino\Core\Database::pdo())),
    'defaultImages' => (function_exists('melkinoDefaultImagesMap') ? melkinoDefaultImagesMap() : []),
];
?>
<script type="application/json" id="mxAdminAdsData"><?= json_encode($mxAdsBoot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<!-- =========================================================
     ADS
     ========================================================= -->

<div
    class="tab-content"
    id="tab-ads"
>

    <div class="admin-card">

        <div class="card-header">

            <span class="card-title">
                مدیریت آگهی‌ها
            </span>

            <span
                style="font-size:12px;color:var(--text-secondary);"
                id="adsResultCount"
            >
                0 آگهی
            </span>

            <button
                type="button"
                class="btn-secondary"
                style="margin-inline-start:auto;"
                data-act="ads-excel-export"
            >
                <?= melkinoSvgIcon('download') ?> خروجی اکسل
            </button>

            <button
                type="button"
                class="btn-secondary"
                data-act="ads-excel-template"
            >
                <?= melkinoSvgIcon('save') ?> فایل نمونه
            </button>

            <button
                type="button"
                class="btn-secondary"
                data-act="ads-excel-import"
            >
                <?= melkinoSvgIcon('upload') ?> بارگذاری اکسل
            </button>
            <input type="file" id="adsExcelFile" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display:none">

            <button
                type="button"
                class="btn-primary"
                data-act="ads-create"
            >
                <?= melkinoSvgIcon('plus') ?> ثبت آگهی جدید
            </button>

        </div>


        <div class="stats-grid" style="padding:0 16px;">

            <div class="stat-card">
                <div class="number" id="adsStatTotal"><?= (int)($adsTotals['total'] ?? 0) ?></div>
                <div class="label">کل آگهی‌ها</div>
            </div>

            <div class="stat-card">
                <div class="number" id="adsStatPending"><?= (int)($adsTotals['pending'] ?? 0) ?></div>
                <div class="label">در انتظار تایید</div>
            </div>

            <div class="stat-card">
                <div class="number" id="adsStatPublished"><?= (int)($adsTotals['published'] ?? 0) ?></div>
                <div class="label">منتشر شده</div>
            </div>

            <div class="stat-card">
                <div class="number" id="adsStatVip"><?= (int)($adsTotals['vip'] ?? 0) ?></div>
                <div class="label">فایل‌های VIP</div>
            </div>

            <div class="stat-card">
                <div class="number" id="adsStatPublishedVip"><?= (int)($adsTotals['published_vip'] ?? 0) ?></div>
                <div class="label">VIP منتشرشده</div>
            </div>

        </div>

        <div style="padding:0 16px 16px;">
            <div id="adminAdsMap" style="height:280px;border-radius:14px;overflow:hidden;border:1px solid var(--border);"></div>
            <p style="font-size:12px;color:var(--text-secondary);margin:8px 0 0;">Markerها موقعیت دقیق فایل‌ها هستند. برای ویرایش روی Marker بزنید.</p>
        </div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

        <div class="ads-toolbar">

            <input
                id="adsSearch"
                type="search"
                placeholder="جستجو بر اساس عنوان، کد، محله، تلفن یا نام مالک..."
                autocomplete="off"
            >


            <select id="adsPropertyFilter">

                <option value="all">
                    همه نوع ملک
                </option>

                <option value="آپارتمان">
                    آپارتمان
                </option>

                <option value="ویلا">
                    ویلا
                </option>

                <option value="زمین">
                    زمین
                </option>

                <option value="باغ">
                    باغ
                </option>

                <option value="تجاری">
                    تجاری
                </option>

                <option value="اداری">
                    اداری
                </option>

                <option value="مغازه">
                    مغازه
                </option>

            </select>


            <select id="adsTransactionFilter">

                <option value="all">
                    همه معاملات
                </option>

                <option value="فروش">
                    فروش
                </option>

                <option value="اجاره">
                    اجاره
                </option>

                <option value="رهن کامل">
                    رهن کامل
                </option>

                <option value="رهن و اجاره">
                    رهن و اجاره
                </option>

                <option value="پیش فروش">
                    پیش فروش
                </option>

            </select>


            <select id="adsSort">

                <option value="newest">
                    جدیدترین
                </option>

                <option value="oldest">
                    قدیمی‌ترین
                </option>

                <option value="priceHigh">
                    قیمت بیشتر
                </option>

                <option value="priceLow">
                    قیمت کمتر
                </option>

                <option value="title">
                    عنوان
                </option>

            </select>

        </div>


        <div class="ads-toolbar-actions">

            <button
                class="btn-filter active"
                data-act="ads-filter" data-f="all"
            >
                همه
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="pending"
            >
                در انتظار تایید
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="published"
            >
                فعال
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="vip"
            >
                <?= melkinoSvgIcon('star') ?> VIP
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="suspended"
            >
                معلق
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="sold"
            >
                فروخته شده
            </button>

            <button
                class="btn-filter"
                data-act="ads-filter" data-f="rejected"
            >
                رد شده
            </button>

            <button
                class="btn-secondary"
                data-act="ads-reset"
            >
                پاک کردن فیلترها
            </button>

        </div>


        <div
            class="bulk-bar"
            id="bulkBar"
        >

            <div>
                <strong id="selectedCount">
                    0
                </strong>

                آگهی انتخاب شده
            </div>


            <div class="bulk-actions">

                <button
                    class="btn-icon-sm success"
                    data-act="ads-bulk" data-st="published"
                >
                    <?= melkinoSvgIcon('check') ?> انتشار
                </button>

                <button
                    class="btn-icon-sm gold"
                    data-act="ads-bulk" data-st="suspended"
                >
                    <?= melkinoSvgIcon('pause') ?> تعلیق
                </button>

                <button
                    class="btn-icon-sm danger"
                    data-act="ads-bulk-delete"
                >
                    <?= melkinoSvgIcon('trash') ?> حذف
                </button>

            </div>

        </div>


        <div id="adsListContainer"></div>


        <div class="pagination-bar">

            <div
                style="font-size:12px;color:var(--text-secondary);"
                id="adsPaginationInfo"
            ></div>

            <div
                class="pagination"
                id="adsPagination"
            ></div>

        </div>

    </div>

</div>



<style>
/* نوار تب ادمین sticky با z-index:8000 روی مودال می‌افتاد */
.modal-overlay {
    z-index: 20000 !important;
}
#adEditModal.modal-overlay,
#adDetailModal.modal-overlay,
#publishComposerModal.modal-overlay {
    z-index: 20000 !important;
}
body:has(.modal-overlay.active) .tabs-container,
body:has(.modal-overlay.active) .admin-body .tabs-container {
    z-index: 1 !important;
}
body:has(.modal-overlay.active) {
    overflow: hidden;
}
</style>
<!-- =========================================================
     DETAIL MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="adDetailModal"
>

    <div class="modal-box">

        <div class="modal-header">

            <h3>
                جزئیات کامل آگهی
            </h3>

            <button
                class="modal-close"
                data-act="ads-close" data-modal="adDetailModal"
            >
                ✕
            </button>

        </div>


        <div
            id="adDetailContent"
            style="
                display:flex;
                flex-direction:column;
                gap:var(--space-2);
                flex:1 1 auto;
                min-height:0;
            "
        ></div>

    </div>

</div>


<!-- =========================================================
     EDIT MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="adEditModal"
>

    <div class="modal-box">

        <div class="modal-header">

            <h3>
                <?= melkinoSvgIcon('edit') ?> ویرایش آگهی
            </h3>

            <button
                class="modal-close"
                data-act="ads-close" data-modal="adEditModal"
            >
                ✕
            </button>

        </div>


        <div
            id="adEditContent"
            style="
                display:flex;
                flex-direction:column;
                flex:1 1 auto;
                min-height:0;
            "
        ></div>

    </div>

</div>

<div class="modal-overlay" id="publishComposerModal">
    <div class="modal-box" style="max-width:920px;width:96%;">
        <div class="modal-header">
            <h3 id="pubCompTitle">پیش‌نمایش انتشار</h3>
            <button class="modal-close" type="button" data-act="ads-close" data-modal="publishComposerModal">✕</button>
        </div>
        <div id="pubCompBody" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);gap:14px;padding:4px 2px 8px;">
            <div>
                <div style="font-size:12px;font-weight:800;margin-bottom:8px;">فیلدهای پیام — کم و زیاد کنید</div>
                <div id="pubCompFields" style="display:flex;flex-wrap:wrap;gap:6px;max-height:220px;overflow:auto;"></div>
                <label style="display:flex;gap:8px;align-items:center;margin:12px 0 8px;font-size:13px;">
                    <input type="checkbox" id="pubCompPhoto" checked> ارسال تصویر آگهی
                </label>
                <div style="font-size:12px;font-weight:800;margin-bottom:6px;">متن نهایی (قابل ویرایش)</div>
                <textarea id="pubCompText" rows="12" style="width:100%;box-sizing:border-box;padding:10px;border-radius:12px;border:1px solid var(--border);background:var(--bg);color:var(--text-primary);font-family:inherit;line-height:1.8;"></textarea>
            </div>
            <div>
                <div style="font-size:12px;font-weight:800;margin-bottom:8px;">پیش‌نمایش واقعی کانال</div>
                <div style="border:8px solid #111;border-radius:28px;background:#0e1715;min-height:420px;padding:14px;color:#e8efe9;max-width:360px;margin:0 auto;">
                    <div id="pubCompPhotoWrap" style="display:none;margin-bottom:10px;"><img id="pubCompImg" alt="" style="width:100%;border-radius:12px;max-height:180px;object-fit:cover;"></div>
                    <pre id="pubCompPreview" style="white-space:pre-wrap;font-family:inherit;font-size:13px;line-height:1.85;margin:0;"></pre>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;padding:8px 2px 4px;">
            <button type="button" class="btn-secondary" data-act="ads-close" data-modal="publishComposerModal">انصراف</button>
            <button type="button" class="btn-primary" id="pubCompConfirm">تأیید و انتشار</button>
        </div>
    </div>
</div>
