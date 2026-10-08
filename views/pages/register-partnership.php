<?php
/**
 * Melkino V2 — register partnership wizard (GET shell VERBATIM; POST + upload_doc served by legacy).
 * Vars: $mkOwnerName, $mkOwnerPhone, $opt.
 */
?>
<div class="stepper-container">
    <span class="step-text" id="stepText">مرحله ۱ از ۴</span>
    <div class="step-line active" id="line1"></div>
    <div class="step-line" id="line2"></div>
    <div class="step-line" id="line3"></div>
    <div class="mkp-meter" title="امتیاز کامل بودن اطلاعات">
        <div class="mkp-meter-track"><span class="mkp-meter-fill" id="mkpMeterFill"></span></div>
        <b id="mkpMeterText">کامل بودن: ۰٪</b>
    </div>
</div>

<div class="main-content" id="mainContent">
    <form method="POST" action="" id="partnershipForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(function_exists('melkinoCsrfToken') ? melkinoCsrfToken() : '', ENT_QUOTES, 'UTF-8') ?>">
        <!-- فیلدهای مخفی اطلاعات کاربر — دقیقاً مثل register-land -->
        <input type="hidden" name="owner_name" id="hidden_owner_name" value="<?= htmlspecialchars($mkOwnerName, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="phone" id="hidden_phone" value="<?= htmlspecialchars($mkOwnerPhone, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="partnership_payload" id="partnership_payload" value="">

        <!-- ===== STEP 1: معرفی ملک ===== -->
        <div class="step-content active" id="step1">
            <h2 class="step-title">معرفی ملک</h2>
            <p class="step-subtitle">مشخصات اصلی ملک خود را وارد کنید.</p>

            <div class="form-group">
                <label>نوع ملک *</label>
                <div class="radio-group" id="mkpPropertyType">
                    <?php foreach ($opt['property_types'] as $i => $pt): ?>
                        <label class="radio-label"><input type="radio" name="property_type" value="<?= htmlspecialchars($pt, ENT_QUOTES, 'UTF-8') ?>"<?= $i === 0 ? ' checked' : '' ?>> <?= htmlspecialchars($pt, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpPropertyTypeInput" value="<?= htmlspecialchars($opt['property_types'][0], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label>مساحت ملک (متر مربع) *</label>
                <input type="text" class="form-input" id="mkpArea" name="area" inputmode="numeric" maxlength="10" placeholder="مثلاً ۳۰۰" required>
            </div>

            <div class="form-group">
                <label>وضعیت فعلی ملک *</label>
                <div class="radio-group" id="mkpCurrentStatus">
                    <?php foreach ($opt['current_statuses'] as $cs): ?>
                        <label class="radio-label"><input type="radio" name="current_status" value="<?= htmlspecialchars($cs, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($cs, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpCurrentStatusInput" value="">
            </div>
        </div>

        <!-- ===== STEP 2: موقعیت و مشخصات ===== -->
        <div class="step-content" id="step2">
            <h2 class="step-title">موقعیت و مشخصات ملک</h2>

            <div class="form-group">
                <label>محله *</label>
                <input type="text" class="form-input" id="mkpNeighborhood" name="neighborhood" placeholder="مثلاً خ فردوسی" required>
            </div>

            <div class="form-group">
                <label>آدرس دقیق *</label>
                <textarea class="form-textarea" id="mkpAddress" name="address" rows="3" placeholder="نام خیابان، کوچه، پلاک و..." required></textarea>
                <p class="form-hint">آدرس دقیق فقط برای بررسی و ارتباط با متقاضیان استفاده می‌شود و در آگهی عمومی نمایش داده نمی‌شود.</p>
            </div>

            <?php require MELKINO_ROOT . '/map-location-fields.php'; ?>

            <div class="row-half">
                <div class="form-group">
                    <label>عرض کوچه یا گذر (متر)</label>
                    <input type="text" class="form-input" id="mkpPassage" name="passage_width" inputmode="numeric" maxlength="5" placeholder="مثلاً ۸">
                    <label class="checkbox-label"><input type="checkbox" id="mkpPassageUnk" > نمی‌دانم</label>
                </div>
                <div class="form-group">
                    <label>عرض زمین (متر)</label>
                    <input type="text" class="form-input" id="mkpLandWidth" name="land_width" inputmode="numeric" maxlength="5" placeholder="مثلاً ۱۰">
                </div>
            </div>

            <div class="form-group">
                <label>ملک چند بر دارد؟</label>
                <div class="radio-group" id="mkpBrCount">
                    <?php foreach ($opt['br_counts'] as $br): ?>
                        <label class="radio-label"><input type="radio" name="br_count" value="<?= htmlspecialchars($br, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($br, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpBrCountInput" value="">
            </div>

            <div class="form-group">
                <label>جهت ملک</label>
                <div class="radio-group" id="mkpDirection">
                    <?php foreach ($opt['directions'] as $dr): ?>
                        <label class="radio-label"><input type="radio" name="direction" value="<?= htmlspecialchars($dr, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($dr, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpDirectionInput" value="">
            </div>
        </div>

        <!-- ===== STEP 3: وضعیت ساخت ===== -->
        <div class="step-content" id="step3">
            <h2 class="step-title">وضعیت ساخت</h2>

            <div class="form-group">
                <label>وضعیت پروانه ساخت</label>
                <div class="radio-group" id="mkpPermitStatus">
                    <?php foreach ($opt['permit_statuses'] as $i => $ps): ?>
                        <label class="radio-label"><input type="radio" name="permit_status" value="<?= htmlspecialchars($ps, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($ps, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpPermitStatusInput" value="">
            </div>

            <div id="mkpCapacityGroup" class="hide">
                <p class="step-subtitle">ظرفیت ساخت ملک را مشخص کنید (اگر اطلاعاتی ندارید خالی بگذارید).</p>
                <div class="row-half">
                    <div class="form-group">
                        <label>تراکم مجاز (٪)</label>
                        <input type="text" class="form-input" id="mkpDensity" name="density" inputmode="numeric" maxlength="4" placeholder="مثلاً ۱۸۰">
                    </div>
                    <div class="form-group">
                        <label>سطح اشغال مجاز (٪)</label>
                        <input type="text" class="form-input" id="mkpOccupancyRate" name="occupancy_rate" inputmode="numeric" maxlength="4" placeholder="مثلاً ۶۰">
                    </div>
                </div>
                <div class="row-half">
                    <div class="form-group">
                        <label>تعداد طبقات قابل ساخت</label>
                        <input type="text" class="form-input" id="mkpBuildFloors" name="buildable_floors" inputmode="numeric" maxlength="3" placeholder="مثلاً ۵">
                    </div>
                    <div class="form-group">
                        <label>زیربنای قابل ساخت (متر مربع)</label>
                        <input type="text" class="form-input" id="mkpBuildArea" name="buildable_area" inputmode="numeric" maxlength="6" placeholder="مثلاً ۹۰۰">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== STEP 4: مالکیت، مدارک و انتشار ===== -->
        <div class="step-content" id="step4">
            <h2 class="step-title">مالکیت، مدارک و انتشار</h2>

            <div class="form-group">
                <label>وضعیت سند ملک *</label>
                <div class="radio-group" id="mkpDeed">
                    <?php foreach ($opt['deed_statuses'] as $ds): ?>
                        <label class="radio-label"><input type="radio" name="deed_status" value="<?= htmlspecialchars($ds, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($ds, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpDeedInput" value="">
            </div>

            <div class="form-group">
                <label>نوع سند</label>
                <div class="radio-group" id="mkpDeedKind">
                    <?php foreach ($opt['deed_kinds'] as $dk): ?>
                        <label class="radio-label"><input type="radio" name="deed_kind" value="<?= htmlspecialchars($dk, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($dk, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mkpDeedKindInput" value="">
            </div>

            <div class="row-half">
                <div class="form-group">
                    <label>تعداد مالکین</label>
                    <input type="text" class="form-input" id="mkpOwnersCount" name="owners_count" inputmode="numeric" maxlength="3" placeholder="مثلاً ۲">
                </div>
                <div class="form-group" id="mkpOccupancyGroup">
                    <label>وضعیت فعلی ملک</label>
                    <select class="form-select" id="mkpOccupancy" name="occupancy">
                        <option value="">— انتخاب کنید —</option>
                        <?php foreach ($opt['occupancies'] as $oc): ?>
                            <option value="<?= htmlspecialchars($oc, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($oc, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>آیا ملک وضعیت خاصی دارد؟</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-1);">
                    <?php foreach ($opt['legal_flags'] as $lg): ?>
                        <label class="checkbox-label"><input type="checkbox" name="legal_status[]" value="<?= htmlspecialchars($lg, ENT_QUOTES, 'UTF-8') ?>" > <?= htmlspecialchars($lg, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>تصویر سند (اختیاری)</label>
                <p class="form-hint">برای افزایش اعتماد سازندگان می‌توانید تصویر سند را بارگذاری کنید. (تصویر یا PDF، حداکثر ۸ مگابایت)</p>
                <div class="upload-area doc-area" data-dz="docDeedInput">
                    <span>🖼️ بارگذاری تصویر سند</span>
                </div>
                <input type="file" id="docDeedInput" accept="image/jpeg,image/png,image/webp,application/pdf" hidden data-kind="doc_deed">
                <div id="preview_doc_deed" class="image-preview-grid"></div>
            </div>

            <div class="form-group">
                <label>تصویر پروانه ساخت (اختیاری)</label>
                <div class="upload-area doc-area" data-dz="docPermitInput">
                    <span>📜 بارگذاری تصویر پروانه</span>
                </div>
                <input type="file" id="docPermitInput" accept="image/jpeg,image/png,image/webp,application/pdf" hidden data-kind="doc_permit">
                <div id="preview_doc_permit" class="image-preview-grid"></div>
            </div>

            <div class="form-group" id="mkpDocEndjob">
                <label>تصویر پایان کار (اختیاری)</label>
                <div class="upload-area doc-area" data-dz="docEndjobInput">
                    <span>✅ بارگذاری تصویر پایان کار</span>
                </div>
                <input type="file" id="docEndjobInput" accept="image/jpeg,image/png,image/webp,application/pdf" hidden data-kind="doc_endjob">
                <div id="preview_doc_endjob" class="image-preview-grid"></div>
            </div>

            <div class="form-group">
                <label>مدارک دیگر (حداکثر ۵ فایل — نقشه، پروانه و...)</label>
                <div class="upload-area doc-area" data-dz="docOtherInput">
                    <span>📎 افزودن فایل</span>
                </div>
                <input type="file" id="docOtherInput" accept="image/jpeg,image/png,image/webp,application/pdf" multiple hidden >
                <div id="preview_doc_other" class="image-preview-grid"></div>
            </div>

            <p class="mkp-privacy">🔒 آدرس دقیق، شماره تماس و مدارک شما فقط برای ملکینو ثبت می‌شود و در آگهی عمومی نمایش داده نمی‌شود؛ اطلاعات حساس فقط پس از تأیید، در اختیار متقاضی واقعی قرار می‌گیرد.</p>

            <!-- بخش پیش‌نمایش نهایی — دقیقاً مثل register-land -->
            <div id="finalPreviewContainer" style="display:none;">
                <div class="preview-card">
                    <div class="preview-card-title" id="mkpPreviewTitle">📋 پیش‌نمایش نهایی درخواست</div>
                    <div id="mkpPreviewBody"></div>
                </div>

                <div style="display:flex; gap:var(--space-2); flex-wrap:wrap; margin-top:var(--space-3);">
                    <button type="button" class="btn-secondary" data-act="edit-preview" style="flex:1;max-width:none;">ویرایش اطلاعات</button>
                    <button type="submit" name="submit_partnership" class="btn-primary-full" style="flex:1;max-width:none;">ثبت نهایی درخواست</button>
                </div>
            </div>

            <div class="form-group">
                <label class="radio-label"><input type="checkbox" id="mkpConfirm" required> اطلاعات واردشده را بررسی کرده‌ام و صحت آن را تأیید می‌کنم.</label>
            </div>
        </div>

        <button type="button" class="mkp-savedraft" id="mkpSaveDraft">💾 ذخیره و ادامه بعداً</button>

        <!-- دکمه‌های ناوبری — دقیقاً مانند سایر فرم‌ها -->
        <div class="bottom-actions" id="regBottomActions">
            <button type="button" class="btn-secondary" id="prevBtn"  style="display:none;">مرحله قبل</button>
            <button type="button" class="btn-primary-full" id="nextBtn" >مرحله بعد</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/
