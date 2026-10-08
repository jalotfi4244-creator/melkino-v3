<?php
/**
 * Melkino V2 — register office wizard (GET shell VERBATIM; POST + upload_images served by legacy).
 */
?>
<?php require_once MELKINO_ROOT . '/jalali-picker.php'; ?>

<div class="stepper-container">
    <span class="step-text" id="stepText">مرحله ۱ از ۶</span>
    <div class="step-line active" id="line1"></div>
    <div class="step-line" id="line2"></div>
    <div class="step-line" id="line3"></div>
    <div class="step-line" id="line4"></div>
    <div class="step-line" id="line5"></div>
</div>

<div class="main-content" id="mainContent">
    <form method="POST" action="" id="propertyForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(melkinoCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <!-- ===== فیلدهای مخفی برای اطلاعات کاربر ===== -->
        <input type="hidden" name="gender" id="hidden_gender" value="">
        <input type="hidden" name="last_name" id="hidden_last_name" value="">
        <input type="hidden" name="phone" id="hidden_phone" value="">
        <input type="hidden" name="telegram_id" id="hidden_telegram_id" value="">
        <input type="hidden" name="transaction_type" id="hidden_transaction_type" value="">
        <input type="hidden" name="uploaded_images" id="uploaded_images" value="">
        
        <!-- STEP 1: اطلاعات پایه و موقعیت -->
        <div class="step-content active" id="step1">
            <h2 class="step-title">اطلاعات پایه و موقعیت</h2>
            <div class="form-group"><label>عنوان آگهی</label><input type="text" class="form-input" id="regTitle" name="title" placeholder="دفتر کار ۱۲۰ متری در مرکز شهر" required></div>
            <div class="form-group"><label>محله / خیابان اصلی</label><input type="text" class="form-input" id="regLocation" name="location" placeholder="خیابان بهار" required></div>
            <div class="form-group"><label>آدرس دقیق</label><input type="text" class="form-input" id="regAddress" name="address" placeholder="خ بهار کوچه بیستم..." required></div>
            <?php require MELKINO_ROOT . '/map-location-fields.php'; ?>
        </div>

        <!-- STEP 2: مشخصات واحد اداری -->
        <div class="step-content" id="step2">
            <h2 class="step-title">مشخصات واحد اداری</h2>
            
            <div id="step2Dynamic">
                <div class="row-half">
                    <div class="form-group"><label>متراژ واحد (متر مربع)</label><input type="text" class="form-input numeric-input" id="regOfficeArea" name="office_area" placeholder="۱۲۰" required></div>
                    <div class="form-group"><label>طبقه</label><input type="text" class="form-input numeric-input" id="regOfficeFloor" name="office_floor" placeholder="۳" required></div>
                </div>
                
                <div class="row-half">
                    <div class="form-group"><label>تعداد واحد در طبقه <span style="color:#c0392b">*</span></label><input type="text" class="form-input numeric-input" id="regOfficeUnitsPerFloor" name="office_units_per_floor" placeholder="۴" required></div>
                    <div class="form-group"><label>تعداد اتاق</label><select class="form-select" id="regOfficeRooms" name="office_rooms" required>
                        <?= function_exists('melkinoFormSelectOptions') ? melkinoFormSelectOptions('rooms') : '<option value="">انتخاب کنید</option>' ?>
                    </select></div>
                </div>

                <!-- گزینه کلید نخورده (فقط در فروش و اجاره) -->
                <div id="notKeyedContainer">
                    <div class="form-group" style="display:flex; flex-direction:column; gap:var(--space-1); margin-bottom:var(--space-1);">
                        <label style="font-size:14px;font-weight:600;color:var(--text-primary);">کلید نخورده</label>
                        <div style="display:flex; align-items:center; gap:var(--space-1);">
                            <input type="checkbox" id="regIsNotKeyed" name="is_not_keyed" value="1" style="width:18px;height:18px;accent-color:var(--primary);">
                            <span style="font-size:12px;color:var(--text-secondary);">این واحد کلید نخورده است</span>
                        </div>
                    </div>
                </div>

                <div class="row-half" id="yearRow">
                    <div class="form-group"><label>سال ساخت</label><input type="text" class="form-input numeric-input" id="regOfficeYear" name="office_year" placeholder="۱۴۰۲" required></div>
                    <div class="form-group"><label>سن بنا</label><input type="text" class="form-input" id="regBuildingAge" name="building_age" readonly tabindex="-1" placeholder="خودکار"></div>
                </div>
                <div class="form-group" id="deliveryDateContainer" style="display:none;">
                        <label>تاریخ تحویل</label>
                        <input type="text" class="form-input" id="regDeliveryDateJalali" placeholder="انتخاب تاریخ شمسی" autocomplete="off" inputmode="none">
                        <input type="hidden" id="regDeliveryDate" name="delivery_date">
                </div>

                <!-- فیلدهای اجاره (تاریخ تخلیه و واحد تخلیه است) -->
                <div id="rentalFieldsContainer" style="display:none;">
                    <div class="row-half">
                        <div class="form-group">
                            <label>تاریخ تخلیه</label>
                            <input type="date" class="form-input" id="regVacancyDate" name="vacancy_date">
                        </div>
                        <div class="form-group" style="display:flex; flex-direction:column; gap:var(--space-1);">
                            <label style="font-size:14px;font-weight:600;color:var(--text-primary);">واحد تخلیه است</label>
                            <div style="display:flex; align-items:center; gap:var(--space-1);">
                                <input type="checkbox" id="regIsVacant" name="is_vacant" value="1" style="width:18px;height:18px;accent-color:var(--primary);">
                                <span style="font-size:12px;color:var(--text-secondary);">در حال حاضر واحد تخلیه است</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>وضعیت واحد <span style="color:#c0392b">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="office_condition" value="نوساز" required> نوساز</label>
                        <label class="radio-label"><input type="radio" name="office_condition" value="بازسازی‌شده"> بازسازی‌شده</label>
                        <label class="radio-label"><input type="radio" name="office_condition" value="قدیمی"> قدیمی</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>موقعیت واحد <span style="color:#c0392b">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="office_orientation" value="شمالی" required> شمالی</label>
                        <label class="radio-label"><input type="radio" name="office_orientation" value="جنوبی"> جنوبی</label>
                        <label class="radio-label"><input type="radio" name="office_orientation" value="شرقی"> شرقی</label>
                        <label class="radio-label"><input type="radio" name="office_orientation" value="غربی"> غربی</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>کاربری <span style="color:#c0392b">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="office_usage" value="اداری" required> اداری</label>
                        <label class="radio-label"><input type="radio" name="office_usage" value="دفتر کار"> دفتر کار</label>
                        <label class="radio-label"><input type="radio" name="office_usage" value="تجاری-اداری"> تجاری-اداری</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: امکانات واحد اداری -->
        <div class="step-content" id="step3">
            <h2 class="step-title">امکانات واحد اداری</h2>
            <div class="amenities-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-1);">
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="آسانسور"> آسانسور</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="پارکینگ"> پارکینگ</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="انباری"> انباری</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="لابی"> لابی</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="نگهبانی"> نگهبانی</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="دوربین مداربسته"> دوربین مداربسته</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سیستم اعلام حریق"> سیستم اعلام حریق</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="اطفای حریق"> اطفای حریق</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سیستم سرمایش"> سیستم سرمایش</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سیستم گرمایش"> سیستم گرمایش</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="برق اختصاصی"> برق اختصاصی</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سه‌فاز"> سه‌فاز</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="آب"> آب</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="گاز"> گاز</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="اینترنت"> اینترنت</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="تلفن"> تلفن</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="آبدارخانه"> آبدارخانه</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سرویس بهداشتی"> سرویس بهداشتی</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="اتاق مدیریت"> اتاق مدیریت</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="اتاق جلسات"> اتاق جلسات</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="پارتیشن‌بندی"> پارتیشن‌بندی</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="سیستم هوشمند"> سیستم هوشمند</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="درب ضدسرقت"> درب ضدسرقت</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="تابلوخور مناسب"> تابلوخور مناسب</label>
                <label class="checkbox-label"><input type="checkbox" name="office_amenities[]" value="دسترسی به حمل‌ونقل عمومی"> دسترسی به حمل‌ونقل عمومی</label>
            </div>
        </div>

        <!-- STEP 4: قیمت -->
        <div class="step-content" id="step4">
            <h2 class="step-title">قیمت</h2>
            
            <div id="price-sell" style="display:none;">
                <div class="form-group"><label>قیمت (تومان)</label><input type="text" class="form-input price-input" name="price_sell" placeholder="۲,۸۰۰,۰۰۰,۰۰۰"></div>
                <div style="display:flex;gap:var(--space-2);margin-top:var(--space-1);">
                    <label><input type="checkbox" name="price_condition" value="negotiable" data-pc="fixed"> قابل مذاکره</label>
                    <label><input type="checkbox" name="price_condition" value="fixed" data-pc="negotiable"> مقطوع</label>
                </div>
                <div style="margin-top:var(--space-2);">
                    <label style="display:flex;align-items:center;gap:var(--space-1);">
                        <input type="checkbox" id="exchangeCheckbox" name="exchange_interested" value="1">
                        مایل به معاوضه هستم
                    </label>
                    <div id="exchangeGroup" style="margin-top:var(--space-1);">
                            <div class="form-group">
                                <label>معاوضه با (می‌توانید چند مورد انتخاب کنید):</label>
                                <div style="display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:6px;">
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="آپارتمان"> آپارتمان</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="باغ"> باغ</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="ویلایی"> ویلایی</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="اداری"> اداری</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="مغازه"> مغازه</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="زمین"> زمین</label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;"><input type="checkbox" name="exchange_types[]" value="خودرو"> خودرو</label>
                                </div>
                            </div>
                        <div class="form-group">
                            <label>معاوضه با:</label>
                            <textarea class="form-textarea" name="exchange_with" placeholder="مثلاً: معاوضه با واحد بزرگ‌تر یا معاوضه با ملک دیگر یا ... برای ما شرایط خود را توضیح دهید." rows="3" disabled></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div id="price-rent" style="display:none;">
                <div class="row-half">
                    <div class="form-group"><label>ودیعه</label><input type="text" class="form-input price-input" name="deposit" placeholder="۵۰۰,۰۰۰,۰۰۰"></div>
                    <div class="form-group"><label>اجاره ماهانه</label><input type="text" class="form-input price-input" name="rent_monthly" placeholder="۳۰,۰۰۰,۰۰۰"></div>
                </div>
                <div style="margin-top:var(--space-1);"><label><input type="checkbox" id="fullRentCheckbox"> رهن کامل</label></div>
                <div id="fullRentGroup"><div class="form-group"><label>مبلغ رهن کامل</label><input type="text" class="form-input price-input" name="full_rent" placeholder="۱,۰۰۰,۰۰۰,۰۰۰"></div></div>
            </div>

            <div id="price-pre-sell" style="display:none;">
                <div class="form-group"><label>قیمت کل</label><input type="text" class="form-input price-input" name="total_price" placeholder="۳,۰۰۰,۰۰۰,۰۰۰"></div>
                <div class="form-group"><label>پیش‌پرداخت</label><input type="text" class="form-input price-input" name="down_payment" placeholder="۹۰۰,۰۰۰,۰۰۰"></div>
                <div class="form-group"><label>شرایط پرداخت</label><textarea class="form-textarea" name="payment_terms" rows="3" placeholder="مثال: ۳۰٪ قرارداد، ۲۰٪ اسکلت..."></textarea></div>
            </div>

            <!-- ===== وام (فقط فروش و پیش‌فروش) ===== -->
            <div id="loanSection" style="display:none; margin-top:var(--space-3); border:1px solid var(--border); border-radius:12px; padding:var(--space-2);">
                <div style="font-size:13px; color:var(--text-secondary); line-height:2; margin-bottom:var(--space-1); background:var(--gold-bg); border-radius:8px; padding:8px 10px;">
                    ℹ️ مبلغ وام از قیمت درج شده کسر می‌شود؛ در کارت‌ها و صفحهٔ آگهی هر دو قیمت نمایش داده می‌شود:
                    قیمت کامل و «قیمت منهای مبلغ وام + وام»
                    (مثلاً: ۱۵,۰۰۰,۰۰۰,۰۰۰ تومان ← ۱۴,۷۰۰,۰۰۰,۰۰۰ تومان + ۳۰۰,۰۰۰,۰۰۰ تومان وام).
                </div>
                <label style="display:flex;align-items:center;gap:var(--space-1);font-weight:600;">
                    <input type="checkbox" id="hasLoanCheckbox" name="has_loan" value="1">
                    وام دارد
                </label>
                <div id="loanGroup" style="display:none; margin-top:var(--space-2);">
                    <div class="row-half">
                        <div class="form-group"><label>مبلغ وام (تومان)</label><input type="text" class="form-input price-input" name="loan_amount" placeholder="۳۰۰,۰۰۰,۰۰۰"></div>
                        <div class="form-group"><label>نوع وام</label><input type="text" class="form-input" name="loan_type" placeholder="مثلاً: وام مسکن / اوراق"></div>
                    </div>
                    <div class="row-half">
                        <div class="form-group"><label>مدت وام</label><input type="text" class="form-input" name="loan_duration" placeholder="مثلاً: ۱۲ سال"></div>
                        <div class="form-group"><label>بانک</label><input type="text" class="form-input" name="loan_bank" placeholder="مثلاً: بانک مسکن"></div>
                    </div>
                    <div class="row-half">
                        <div class="form-group"><label>مبلغ هر قسط (تومان)</label><input type="text" class="form-input price-input" name="loan_installment" placeholder="۵,۰۰۰,۰۰۰"></div>
                        <div class="form-group"><label>تعداد اقساط پرداخت شده</label><input type="text" class="form-input" name="loan_installments_paid" placeholder="مثلاً: ۲۴"></div>
                    </div>
                    <div class="form-group"><label>توضیحات تکمیلی وام</label><textarea class="form-textarea" name="loan_notes" rows="2" placeholder="توضیحات بیشتر دربارهٔ وام (اختیاری)"></textarea></div>
                </div>
            </div>

            
        </div>

                <!-- ===== STEP 5: نوع سند (راند ۱۴) ===== -->
        <div class="step-content" id="step5">
            <h2 class="step-title">نوع سند</h2>

            <div class="form-group">
                <label>نوع سند ملک</label>
                <div class="radio-group" style="gap:var(--space-2);">
                    <label class="radio-label"><input type="radio" name="deed_type" value="طلق"> طلق</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="وقفی"> وقفی</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="مشاعی"> مشاعی</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="عرصه"> عرصه</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="اعیان"> اعیان</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="رهنی"> رهنی</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="قولنامه عادی"> قولنامه عادی</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="قولنامه شورایی"> قولنامه شورایی</label>
                    <label class="radio-label"><input type="radio" name="deed_type" value="برگه واگذاری"> برگه واگذاری</label>
                </div>
            </div>

            <div class="form-group">
                <label>توضیحات</label>
                <textarea class="form-textarea" name="deed_notes" rows="3" placeholder="اگر سند نیازمند توضیح است در این کادر بنویسید"></textarea>
            </div>
        </div>

        <!-- ===== STEP 6: تصاویر، توضیحات و ثبت نهایی ===== -->
        <div class="step-content" id="step6">
            <h2 class="step-title">تصاویر، توضیحات و ثبت نهایی</h2>
            
            <!-- بخش اول: آپلود تصاویر با AJAX -->
            <div class="form-group">
                <label>آپلود تصاویر (حداکثر ۵ مگابایت، فرمت JPG, PNG, WEBP)</label>
                <div id="dropZone" class="upload-area" style="cursor:pointer; text-align:center; padding:20px;" >
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <br>
                    <span>برای آپلود تصاویر کلیک کنید یا بکشید و رها کنید</span>
                    <br>
                    <small style="color:var(--text-secondary);">حداکثر ۵ مگابایت - فرمت‌های مجاز: JPG, PNG, WEBP</small>
                </div>
                <input type="file" id="fileInput" name="images[]" accept="image/jpeg,image/png,image/webp" multiple style="display:none;" >
                <div id="uploadProgress" style="display:none; margin-top:8px;">
                    <div style="width:100%; height:6px; background:var(--border); border-radius:3px; overflow:hidden;">
                        <div id="progressBar" style="width:0%; height:100%; background:var(--primary); transition:width 0.3s;"></div>
                    </div>
                    <span id="progressText" style="font-size:13px; color:var(--text-secondary);">در حال آپلود...</span>
                </div>
                <div id="imagePreviewContainer" class="image-preview-grid"></div>
            </div>

            <!-- ===== گزینه جدید: انتخاب حالت انتشار تصاویر (بعد از آپلود و فقط در صورت وجود عکس) ===== -->
            <div id="publishOptionsContainer" style="display: none; margin-top: var(--space-2);">
                <div class="form-group">
                    <label>انتخاب حالت انتشار تصاویر</label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="publish_photos" value="yes" checked> مایل به انتشار عکس‌ها در آگهی هستم</label>
                        <label class="radio-label"><input type="radio" name="publish_photos" value="no"> مایل به انتشار عکس‌ها در آگهی نیستم و فقط عکس‌ها به مشتریان حضوری نمایش داده شود</label>
                    </div>
                </div>
            </div>

            <!-- بخش دوم: توضیحات تکمیلی -->
            <div class="form-group">
                <label>توضیحات تکمیلی (حداکثر ۱۰۰۰ کاراکتر)</label>
                <textarea class="form-textarea" id="regFullDesc" name="full_description" placeholder="شرح کامل امکانات و شرایط واحد اداری..." maxlength="1000"></textarea>
                <small style="color:var(--text-secondary);">کاراکتر وارد شده: <span id="charCount">0</span> / ۱۰۰۰</small>
            </div>

            <!-- بخش جدید: زمان بازدید -->
            <div class="form-group" style="margin-top:var(--space-1);">
                <label>چه زمان‌هایی میشه بازدید کرد؟</label>
                <textarea class="form-textarea" name="visit_hours" rows="3" placeholder="مثال: فقط عصرها میشه بازدید کرد یا همیشه میشه بازدید کرد با هماهنگی قبلی یک ساعت زودتر یا ..."></textarea>
            </div>

            <!-- بخش سوم: پیش‌نمایش نهایی -->
            <div id="finalPreviewContainer" style="display:none;">
                <div class="preview-card">
                    <div class="preview-card-title">📋 پیش‌نمایش نهایی آگهی</div>
                    <div id="previewContent"></div>
                </div>

                <div style="display:flex; gap:var(--space-2); flex-wrap:wrap; margin-top:var(--space-3);">
                    <button type="button" class="btn-secondary" data-act="edit-preview" style="flex:1;max-width:none;">ویرایش اطلاعات</button>
                    <button type="submit" name="submit_property" class="btn-primary-full" style="flex:1;max-width:none;">ثبت نهایی آگهی</button>
                </div>
            </div>
        </div>

        <!-- دکمه‌های ناوبری -->
    </form>
</div>

<div class="bottom-actions" id="regBottomActions">
    <button type="button" class="btn-secondary" id="prevBtn"  style="display:none;">مرحله قبل</button>
    <button type="button" class="btn-primary-full" id="nextBtn" >مرحله بعد</button>
</div>

<?php
if (function_exists('melkinoBuildingAgeBindScript')) {
    echo melkinoBuildingAgeBindScript('regOfficeYear', 'regBuildingAge');
}
require_once __DIR__ . '/
