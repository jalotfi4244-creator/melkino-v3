<?php
/** Melkino V2 — admin onboarding tab. Fragment VERBATIM from admin-panel.php (ONBOARDING
 * section). Deviations: __DIR__ -> MELKINO_ROOT (3 spots; views run from another dir) +
 * tour boot call (panel emits it at page end; same output here).
 */
?>
<!-- =========================================================
     ONBOARDING
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-onboarding"
>

    <div class="admin-card">

        <div class="card-header">

            <span class="card-title">
                صفحات هدایت
            </span>

        </div>

        <div id="onboardingEditor"></div>

    </div>

    <?php
    if (is_file(MELKINO_ROOT . '/onboarding-tour.php')) {
        require_once MELKINO_ROOT . '/onboarding-tour.php';
        melkinoProductTourAdminCard();
    }
    ?>

    <!-- =========================================================
         [NEW] کارت جدید: لوگوی اصلی ملکینو
         ========================================================= -->
    <div class="admin-card" style="margin-top: 20px; border: 2px solid var(--primary);">
        <div class="card-header">
            <span class="card-title"><?= melkinoSvgIcon('image') ?> لوگوی اصلی ملکینو</span>
        </div>
        <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 25px; align-items: center; padding: 15px 0;">
            <!-- ستون پیش‌نمایش -->
            <div style="flex: 0 0 200px; text-align: center;">
                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">پیش‌نمایش فعلی</div>
                <div id="logoPreviewContainer" style="background: #fff; border-radius: 12px; padding: 12px; border: 1px solid var(--border); min-height: 110px; display: flex; align-items: center; justify-content: center;">
                    <img id="logoPreview" src="<?php
                        require_once MELKINO_ROOT . '/melkino-logo.php';
                        echo melkinoSiteLogoUrl() ?: 'assets/images/melkino-logo.png';
                    ?>" alt="لوگوی ملکینو" style="max-width: 100%; max-height: 100px; object-fit: contain;">
                </div>
                <div id="logoStatus" style="margin-top: 6px; font-size: 0.85rem; color: var(--text-secondary);"></div>
            </div>

            <!-- ستون فرم آپلود -->
            <div style="flex: 1; min-width: 250px;">
                <form id="logoUploadForm" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label for="logoFileInput" style="display: block; margin-bottom: 4px; color: var(--text-primary); font-weight: 500;">انتخاب فایل لوگو</label>
                        <input type="file" id="logoFileInput" accept=".png,.jpg,.jpeg,.webp" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text-primary);">
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">فرمت‌های مجاز: PNG, JPG, JPEG, WEBP – حداکثر ۵ مگابایت</div>
                        <div style="font-size: 12px; color: var(--text-secondary);"><?= melkinoSvgIcon('bulb') ?> پیشنهاد: PNG با پس‌زمینه شفاف</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <button type="button" id="uploadLogoBtn" class="btn-icon-sm primary" style="background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s;">
                            <?= melkinoSvgIcon('upload') ?> آپلود و ذخیره لوگو
                        </button>
                        <span id="fileNameDisplay" style="color: var(--text-secondary); font-size: 0.9rem;"></span>
                    </div>
                </form>
                <div id="uploadProgress" style="display: none; margin-top: 10px;">
                    <span style="color: var(--text-secondary);">در حال آپلود...</span>
                    <div style="width: 100%; height: 6px; background: var(--border); border-radius: 3px; margin-top: 4px; overflow: hidden;">
                        <div id="progressBar" style="width: 0%; height: 100%; background: var(--primary); transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding: 10px 16px; background: var(--surface); border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-secondary); border-radius: 0 0 var(--radius-md) var(--radius-md);">
            <?= melkinoSvgIcon('bulb') ?> لوگوی اصلی سایت از این قسمت مدیریت می‌شود. پس از آپلود، هر صفحه‌ای که از لوگوی مرکزی استفاده کند، همین لوگو را نمایش خواهد داد.
        </div>
    </div>
    <!-- =========================================================
         پایان کارت جدید لوگو
         ========================================================= -->

</div>
<?php
if (is_file(MELKINO_ROOT . '/onboarding-tour.php')) {
    require_once MELKINO_ROOT . '/onboarding-tour.php';
    melkinoProductTourBoot('admin');
}
?>
