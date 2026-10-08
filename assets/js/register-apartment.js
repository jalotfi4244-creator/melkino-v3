/* Melkino V2 — register-apartment wizard, extracted VERBATIM (POST-success block stays in legacy; bindings appended). */
/* ---- legacy block 2 ---- */
const step4TransType = sessionStorage.getItem('reg_transaction_type') || 'فروش';
                if (step4TransType === 'فروش') {
                    document.getElementById('price-sell').style.display = 'block';
                    document.getElementById('exchangeGroup').classList.remove('visible');
                    document.querySelector('#exchangeGroup textarea').disabled = true;
                } else if (step4TransType === 'اجاره') {
                    document.getElementById('price-rent').style.display = 'block';
                    const fullRentCheckbox = document.getElementById('fullRentCheckbox');
                    if (fullRentCheckbox) {
                        toggleFullRent();
                    }
                } else if (step4TransType === 'پیش فروش') {
                    document.getElementById('price-pre-sell').style.display = 'block';
                }

                // بخش وام فقط برای فروش و پیش‌فروش نمایش داده می‌شود
                (function () {
                    const loanSection = document.getElementById('loanSection');
                    if (!loanSection) return;
                    if (step4TransType === 'فروش' || step4TransType === 'پیش فروش') {
                        loanSection.style.display = 'block';
                    } else {
                        loanSection.style.display = 'none';
                    }
                    if (typeof toggleLoan === 'function') toggleLoan();
                })();

                function toggleLoan() {
                    const chk = document.getElementById('hasLoanCheckbox');
                    const grp = document.getElementById('loanGroup');
                    if (!chk || !grp) return;
                    const inputs = grp.querySelectorAll('input, textarea');
                    if (chk.checked) {
                        grp.style.display = 'block';
                        inputs.forEach(el => { el.disabled = false; });
                    } else {
                        grp.style.display = 'none';
                        inputs.forEach(el => { el.disabled = true; el.value = ''; });
                    }
                }

/* ---- legacy block 3 ---- */
// ==============================================
    // خواندن اطلاعات کاربر از sessionStorage
    // ==============================================
        document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.melkinoInitJalaliPicker === 'function') {
            var jalaliDelivery = document.getElementById('regDeliveryDateJalali');
            if (jalaliDelivery) {
                window.melkinoInitJalaliPicker(jalaliDelivery, { hiddenGregorianId: 'regDeliveryDate', maxMonths: 36 });
            }
        }
        const gender = sessionStorage.getItem('reg_gender') || 'آقا';
        const MELKINO_P = window.MELKINO_PROFILE || {};
        // راند ۳۰: مقادیر سمت سرور (MELKINO_PROFILE) بر sessionStorage
        // اولویت دارند — دادهٔ نمایشی تماس باید از بک‌اند بیاید.
        const lastName = (MELKINO_P.logged_in && MELKINO_P.name) ? MELKINO_P.name : (sessionStorage.getItem('reg_last_name') || '');
        const phone = (MELKINO_P.logged_in && MELKINO_P.phone) ? MELKINO_P.phone : (sessionStorage.getItem('reg_phone') || '');
        const telegramId = sessionStorage.getItem('reg_telegram_id') || '';
        const transactionType = sessionStorage.getItem('reg_transaction_type') || 'فروش';
        
        document.getElementById('hidden_gender').value = gender;
        document.getElementById('hidden_last_name').value = lastName;
        document.getElementById('hidden_phone').value = phone;
        document.getElementById('hidden_telegram_id').value = telegramId;
        document.getElementById('hidden_transaction_type').value = transactionType;
        
        console.log('اطلاعات کاربر:', { gender, lastName, phone, transactionType });

        // راند ۳۰: اگر شمارهٔ تأییدشده ندارد، هشدار + لینک پروفایل
        // (بک‌اند هم ثبت نهایی را مسدود می‌کند؛ این فقط راهنمای کاربر است)
        if (MELKINO_P.logged_in && !MELKINO_P.phone_verified) {
            const warn30 = document.createElement('div');
            warn30.style.cssText = 'margin:10px 0 14px;padding:12px 14px;border-radius:12px;background:rgba(217,119,6,.12);border:1px solid rgba(217,119,6,.35);font-size:12.5px;line-height:2.1;color:#FFD47E;text-align:center;';
            warn30.innerHTML = '📵 برای ثبت آگهی، ابتدا باید شمارهٔ تماس خود را در پروفایل ثبت و تأیید کنید. <a href="profile.php" style="color:#fff;background:#064e4e;padding:5px 14px;border-radius:8px;text-decoration:none;display:inline-block;margin-inline-start:6px;">تکمیل شماره تماس</a>';
            const mc30 = document.getElementById('mainContent');
            if (mc30) { mc30.insertBefore(warn30, mc30.firstChild); }
        }
        
        // راند ۱۸: کارت «📇 تکمیل اطلاعات تماس» طبق درخواست از فرم حذف شد.
        // نام و شماره همچنان از پروفایل کاربر خوانده و در فیلدهای مخفی
        // (hidden_last_name / hidden_phone) قرار می‌گیرد.
        
        // ==============================================
        // Drag and Drop
        // ==============================================
        const dropZone = document.getElementById('dropZone');
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--primary)';
            this.style.background = 'var(--gold-bg)';
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border)';
            this.style.background = 'var(--bg)';
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border)';
            this.style.background = 'var(--bg)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('fileInput');
                fileInput.files = files;
                handleImageUpload(fileInput);
            }
        });

        // ==============================================
        // به‌روزرسانی فیلدهای استپ ۲ بر اساس نوع معامله
        // ==============================================
        updateStep2Fields(transactionType);
    });

    let currentStep = 1;
    const totalSteps = 6;
    const transType = sessionStorage.getItem('reg_transaction_type') || 'فروش';
    let uploadedImages = [];

    // ===================== توابع قیمت =====================
    function togglePriceCondition(el, otherVal) {
        const parent = el.closest('div');
        const other = parent.querySelector(`input[value="${otherVal}"]`);
        if (el.checked && other) other.checked = false;
    }

    function toggleFullRent() {
        const chk = document.getElementById('fullRentCheckbox');
        const grp = document.getElementById('fullRentGroup');
        const inp = grp.querySelector('input');
        const depositInput = document.querySelector('input[name="deposit"]');
        const rentInput = document.querySelector('input[name="rent_monthly"]');

        if (chk.checked) {
            grp.classList.add('visible');
            inp.disabled = false;
            depositInput.required = false;
            rentInput.required = false;
        } else {
            grp.classList.remove('visible');
            inp.disabled = true;
            inp.required = false;
            inp.value = '';
            depositInput.required = false;
            rentInput.required = false;
        }
    }

    function toggleExchange() {
        var chk = document.getElementById('exchangeCheckbox');
        var grp = document.getElementById('exchangeGroup');
        var inp = grp.querySelector('textarea');
        var boxes = grp.querySelectorAll('input[type="checkbox"]');

        if (chk.checked) {
            grp.classList.add('visible');
            inp.disabled = false;
            inp.required = false;
            boxes.forEach(function (b) { b.disabled = false; });
        } else {
            grp.classList.remove('visible');
            inp.disabled = true;
            inp.required = false;
            inp.value = '';
            boxes.forEach(function (b) { b.disabled = true; b.checked = false; });
        }
    }

    // ===================== توابع استپ ۲ =====================
    function updateStep2Fields(transactionType) {
        const notKeyedContainer = document.getElementById('notKeyedContainer');
        const deliveryDateContainer = document.getElementById('deliveryDateContainer');
        const rentalFieldsContainer = document.getElementById('rentalFieldsContainer');
        const yearRow = document.getElementById('yearRow');

        // مخفی کردن همه‌ی بخش‌های اضافی
        notKeyedContainer.style.display = 'none';
        deliveryDateContainer.style.display = 'none';
        rentalFieldsContainer.style.display = 'none';

        // نمایش بر اساس نوع معامله
        if (transactionType === 'فروش') {
            notKeyedContainer.style.display = 'block';
            yearRow.style.gridTemplateColumns = '1fr 1fr';
        } else if (transactionType === 'پیش فروش') {
            deliveryDateContainer.style.display = 'block';
            yearRow.style.gridTemplateColumns = '1fr 1fr';
        } else if (transactionType === 'اجاره') {
            rentalFieldsContainer.style.display = 'block';
            yearRow.style.gridTemplateColumns = '1fr'; // سال ساخت تنها در یک ستون
        }
    }

    function toggleVacancy() {
        const chk = document.getElementById('regIsVacant');
        const dateInput = document.getElementById('regVacancyDate');
        
        if (chk.checked) {
            dateInput.disabled = true;
            dateInput.value = '';
            dateInput.required = false;
        } else {
            dateInput.disabled = false;
            dateInput.required = false;
        }
    }

    // ===================== مدیریت نمایش گزینه‌های انتشار بر اساس وجود عکس =====================
    function togglePublishOptions() {
        const container = document.getElementById('publishOptionsContainer');
        if (uploadedImages.length > 0) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }

    // ==============================================
    // آپلود تصاویر با AJAX (مطمئن‌ترین روش)
    // ==============================================
    function handleImageUpload(input) {
        const files = Array.from(input.files);
        const maxSize = 5 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (files.length === 0) {
            alert('لطفاً حداقل یک تصویر انتخاب کنید.');
            return;
        }

        // بررسی فایل‌ها
        const validFiles = [];
        files.forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                alert(`فرمت ${file.type} مجاز نیست. فقط JPG, PNG, WEBP مجاز است.`);
                return;
            }
            if (file.size > maxSize) {
                alert(`حجم فایل ${file.name} بیش از ۵ مگابایت است.`);
                return;
            }
            validFiles.push(file);
        });

        if (validFiles.length === 0) return;

        // نمایش پیش‌نمایش
        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.getElementById('imagePreviewContainer');
                const div = document.createElement('div');
                div.className = 'image-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="تصویر ${uploadedImages.length + 1}">
                    <button class="remove-btn" data-rmimg="${uploadedImages.length}">✕</button>
                `;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        // ارسال فایل‌ها با AJAX
        const formData = new FormData();
        validFiles.forEach(file => {
            formData.append('images[]', file);
        });
        formData.append('action', 'upload_images');

        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.innerText = 'در حال آپلود...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.innerText = 'آپلود: ' + percent + '%';
            }
        };
        
        xhr.onload = function() {
            progressDiv.style.display = 'none';
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        uploadedImages = uploadedImages.concat(response.images);
                        document.getElementById('uploaded_images').value = uploadedImages.join(',');
                        togglePublishOptions();
                        console.log('✅ تصاویر آپلود شدند:', uploadedImages);
                    } else {
                        alert('خطا در آپلود تصاویر');
                    }
                } catch (e) {
                    alert('خطا در پردازش پاسخ سرور');
                }
            } else {
                alert('خطا در ارتباط با سرور');
            }
            input.value = '';
        };
        
        xhr.onerror = function() {
            progressDiv.style.display = 'none';
            alert('خطا در آپلود تصاویر');
            input.value = '';
        };
        
        xhr.send(formData);
    }

    function removeImage(index) {
        uploadedImages.splice(index, 1);
        document.getElementById('uploaded_images').value = uploadedImages.join(',');
        
        // حذف از پیش‌نمایش
        const container = document.getElementById('imagePreviewContainer');
        const items = container.querySelectorAll('.image-preview-item');
        if (items[index]) {
            items[index].remove();
        }
        
        togglePublishOptions();
    }

    // ===================== پیش‌نمایش نهایی =====================
    function updatePreview() {
        const previewContainer = document.getElementById('finalPreviewContainer');
        previewContainer.style.display = 'block';

        const gender = document.getElementById('hidden_gender').value || '-';
        const lastName = document.getElementById('hidden_last_name').value || '-';
        const phone = document.getElementById('hidden_phone').value || '-';
        const transTypeLabel = transType === 'فروش' ? 'خرید و فروش' : transType === 'پیش فروش' ? 'پیش فروش' : 'اجاره';
        const propertyType = 'آپارتمان';
        const title = document.getElementById('regTitle').value || '-';
        const location = document.getElementById('regLocation').value || '-';
        const address = document.getElementById('regAddress').value || '-';
        const description = document.getElementById('regFullDesc').value || '-';
        
        const publishPhotos = document.querySelector('input[name="publish_photos"]:checked');
        const publishStatus = publishPhotos ? (publishPhotos.value === 'yes' ? '✅ منتشر شود' : '❌ منتشر نشود (فقط حضوری)') : '-';

        let specsHtml = '';
        document.querySelectorAll('#step2 .form-group').forEach(group => {
            const label = group.querySelector('label')?.innerText || '';
            let value = '';
            const input = group.querySelector('input, select');
            if (input) {
                if (input.tagName === 'SELECT') {
                    value = input.options[input.selectedIndex]?.innerText || '-';
                } else {
                    value = input.value || '-';
                }
            }
            specsHtml += `<div class="preview-row"><span class="preview-label">${label}</span><span class="preview-value rtl">${value}</span></div>`;
        });

        // اضافه کردن کلید نخورده به پیش‌نمایش
        const isNotKeyed = document.getElementById('regIsNotKeyed');
        if (isNotKeyed && isNotKeyed.checked) {
            specsHtml += `<div class="preview-row"><span class="preview-label">کلید نخورده</span><span class="preview-value rtl">بله</span></div>`;
        }

        // اضافه کردن تاریخ تحویل (پیش‌فروش)
        const deliveryDateJalali = document.getElementById('regDeliveryDateJalali');
        const deliveryDate = document.getElementById('regDeliveryDate');
        const deliveryShown = (deliveryDateJalali && deliveryDateJalali.value) ? deliveryDateJalali.value : (deliveryDate && deliveryDate.value ? deliveryDate.value : '');
        if (deliveryShown) {
            specsHtml += `<div class="preview-row"><span class="preview-label">تاریخ تحویل</span><span class="preview-value rtl">${deliveryShown}</span></div>`;
        }

        // اضافه کردن تاریخ تخلیه و واحد تخلیه است (اجاره)
        const vacancyDate = document.getElementById('regVacancyDate');
        const isVacant = document.getElementById('regIsVacant');
        if (vacancyDate && vacancyDate.value) {
            specsHtml += `<div class="preview-row"><span class="preview-label">تاریخ تخلیه</span><span class="preview-value rtl">${vacancyDate.value}</span></div>`;
        }
        if (isVacant && isVacant.checked) {
            specsHtml += `<div class="preview-row"><span class="preview-label">واحد تخلیه است</span><span class="preview-value rtl">بله</span></div>`;
        }

        let amenitiesHtml = '';
        document.querySelectorAll('#step3 input[type="checkbox"]:checked').forEach(cb => {
            amenitiesHtml += `<span style="display:inline-block;background:var(--bg);padding:2px 8px;border-radius:4px;border:1px solid var(--border);margin:2px;">${cb.value}</span> `;
        });
        if (!amenitiesHtml) amenitiesHtml = 'هیچکدام';

        let priceHtml = '';
        if (transType === 'فروش') {
            const price = document.querySelector('input[name="price_sell"]').value || '-';
            const condition = document.querySelector('input[name="price_condition"]:checked')?.value || 'negotiable';
            const exchangeChk = document.getElementById('exchangeCheckbox');
            const exchangeVal = exchangeChk && exchangeChk.checked ? document.querySelector('textarea[name="exchange_with"]').value : '-';
            priceHtml = `<div class="preview-row"><span class="preview-label">قیمت</span><span class="preview-value">${price} تومان</span></div>`;
            priceHtml += `<div class="preview-row"><span class="preview-label">وضعیت</span><span class="preview-value rtl">${condition === 'negotiable' ? 'قابل مذاکره' : 'مقطوع'}</span></div>`;
            if (exchangeChk && exchangeChk.checked) {
                priceHtml += `<div class="preview-row"><span class="preview-label">معاوضه با</span><span class="preview-value rtl">${exchangeVal}</span></div>`;
            }
        } else if (transType === 'اجاره') {
            const deposit = document.querySelector('input[name="deposit"]').value || '-';
            const rent = document.querySelector('input[name="rent_monthly"]').value || '-';
            const fullRentChk = document.getElementById('fullRentCheckbox');
            const fullRentVal = fullRentChk.checked ? document.querySelector('input[name="full_rent"]').value : '-';
            priceHtml = `<div class="preview-row"><span class="preview-label">ودیعه</span><span class="preview-value">${deposit} تومان</span></div>`;
            priceHtml += `<div class="preview-row"><span class="preview-label">اجاره ماهانه</span><span class="preview-value">${rent} تومان</span></div>`;
            if (fullRentChk.checked) {
                priceHtml += `<div class="preview-row"><span class="preview-label">رهن کامل</span><span class="preview-value">${fullRentVal} تومان</span></div>`;
            }
        } else if (transType === 'پیش فروش') {
            const total = document.querySelector('input[name="total_price"]').value || '-';
            const down = document.querySelector('input[name="down_payment"]').value || '-';
            const terms = document.querySelector('textarea[name="payment_terms"]').value || '-';
            priceHtml = `<div class="preview-row"><span class="preview-label">قیمت کل</span><span class="preview-value">${total} تومان</span></div>`;
            priceHtml += `<div class="preview-row"><span class="preview-label">پیش‌پرداخت</span><span class="preview-value">${down} تومان</span></div>`;
            priceHtml += `<div class="preview-row"><span class="preview-label">شرایط پرداخت</span><span class="preview-value rtl">${terms}</span></div>`;
        }

        // اضافه کردن ساعت بازدید به پیش‌نمایش
        const visitHours = document.querySelector('textarea[name="visit_hours"]').value || '-';

                // ===== سند، معاوضه و وام در پیش‌نمایش (راند ۱۴) =====
        const deedCheckedBox = document.querySelector('input[name="deed_type"]:checked');
        const deedTypeVal = deedCheckedBox ? deedCheckedBox.value : '-';
        const deedNotesEl = document.querySelector('textarea[name="deed_notes"]');
        const deedNotesVal = (deedNotesEl && deedNotesEl.value) ? deedNotesEl.value : '-';
        const exTypesChecked = Array.from(document.querySelectorAll('input[name="exchange_types[]"]:checked')).map(c => c.value);
        const exMainChk = document.getElementById('exchangeCheckbox');
        const wantsExchange = !!(exMainChk && exMainChk.checked);
        const exWithEl = document.querySelector('textarea[name="exchange_with"]');
        const exWithVal = (wantsExchange && exWithEl && exWithEl.value) ? exWithEl.value : '';
        let exchangePreviewVal = 'خیر';
        if (wantsExchange) {
            const parts = [];
            if (exTypesChecked.length) parts.push(exTypesChecked.join('، '));
            if (exWithVal) parts.push(exWithVal);
            exchangePreviewVal = 'بله' + (parts.length ? ' — ' + parts.join(' — ') : '');
        }
        let loanPreviewHtml = '';
        const hasLoanChk = document.getElementById('hasLoanCheckbox');
        if (hasLoanChk && hasLoanChk.checked) {
            const lv = function (n) { const el = document.querySelector('[name="' + n + '"]'); return (el && el.value) ? el.value : '-'; };
            loanPreviewHtml = `
            <div class="preview-row"><span class="preview-label">وام دارد</span><span class="preview-value rtl">بله</span></div>
            <div class="preview-row"><span class="preview-label">مبلغ وام</span><span class="preview-value">${lv('loan_amount')} تومان</span></div>
            <div class="preview-row"><span class="preview-label">نوع وام</span><span class="preview-value rtl">${lv('loan_type')}</span></div>
            <div class="preview-row"><span class="preview-label">مدت وام</span><span class="preview-value rtl">${lv('loan_duration')}</span></div>
            <div class="preview-row"><span class="preview-label">بانک</span><span class="preview-value rtl">${lv('loan_bank')}</span></div>
            <div class="preview-row"><span class="preview-label">مبلغ هر قسط</span><span class="preview-value">${lv('loan_installment')} تومان</span></div>
            <div class="preview-row"><span class="preview-label">تعداد اقساط پرداخت شده</span><span class="preview-value rtl">${lv('loan_installments_paid')}</span></div>
            <div class="preview-row"><span class="preview-label">توضیحات وام</span><span class="preview-value rtl" style="font-weight:normal;">${lv('loan_notes')}</span></div>`;
        }

        document.getElementById('previewContent').innerHTML = `
            <div class="preview-card-title" style="font-size:14px;margin-top:0;">اطلاعات تماس</div>
            <div class="preview-row"><span class="preview-label">جنسیت</span><span class="preview-value rtl">${gender}</span></div>
            <div class="preview-row"><span class="preview-label">نام خانوادگی</span><span class="preview-value rtl">${lastName}</span></div>
            <div class="preview-row"><span class="preview-label">شماره تماس</span><span class="preview-value">${phone}</span></div>
            
            <div class="preview-card-title" style="font-size:14px;">نوع معامله و ملک</div>
            <div class="preview-row"><span class="preview-label">نوع معامله</span><span class="preview-value rtl">${transTypeLabel}</span></div>
            <div class="preview-row"><span class="preview-label">نوع ملک</span><span class="preview-value rtl">${propertyType}</span></div>

            <div class="preview-card-title" style="font-size:14px;">اطلاعات پایه</div>
            <div class="preview-row"><span class="preview-label">عنوان</span><span class="preview-value rtl">${title}</span></div>
            <div class="preview-row"><span class="preview-label">محله</span><span class="preview-value rtl">${location}</span></div>
            <div class="preview-row"><span class="preview-label">آدرس دقیق</span><span class="preview-value rtl">${address}</span></div>

            <div class="preview-card-title" style="font-size:14px;">مشخصات ملک</div>
            ${specsHtml}

            <div class="preview-card-title" style="font-size:14px;">امکانات</div>
            <div class="preview-row"><span class="preview-label">انتخاب شده</span><span class="preview-value rtl" style="font-weight:normal;">${amenitiesHtml}</span></div>

            <div class="preview-card-title" style="font-size:14px;">قیمت</div>
            ${priceHtml}${loanPreviewHtml}

            <div class="preview-card-title" style="font-size:14px;">سند و معاوضه</div>
            <div class="preview-row"><span class="preview-label">نوع سند</span><span class="preview-value rtl">${deedTypeVal}</span></div>
            <div class="preview-row"><span class="preview-label">توضیحات سند</span><span class="preview-value rtl" style="font-weight:normal;white-space:pre-wrap;">${deedNotesVal}</span></div>
            <div class="preview-row"><span class="preview-label">مایل به معاوضه</span><span class="preview-value rtl" style="font-weight:normal;">${exchangePreviewVal}</span></div>

            <div class="preview-card-title" style="font-size:14px;">تصاویر</div>
            <div class="preview-row"><span class="preview-label">وضعیت انتشار</span><span class="preview-value rtl">${publishStatus}</span></div>
            <div id="previewImages" class="image-preview-grid"></div>

            <div class="preview-card-title" style="font-size:14px;">توضیحات و زمان بازدید</div>
            <div class="preview-row"><span class="preview-label">توضیحات</span><span class="preview-value rtl" style="font-weight:normal;white-space:pre-wrap;">${description}</span></div>
            <div class="preview-row"><span class="preview-label">زمان‌های بازدید</span><span class="preview-value rtl" style="font-weight:normal;">${visitHours}</span></div>
        `;
        renderPreviewImages();
    }

    function renderPreviewImages() {
        const container = document.getElementById('previewImages');
        container.innerHTML = '';
        uploadedImages.forEach((imgPath, index) => {
            const div = document.createElement('div');
            div.className = 'image-preview-item';
            div.innerHTML = `<img src="${imgPath}" alt="تصویر ${index+1}">`;
            container.appendChild(div);
        });
    }

    // ===================== دکمه‌های گام ۵ =====================
    function editPreview() {
        document.getElementById('finalPreviewContainer').style.display = 'none';
        document.getElementById('prevBtn').style.display = (currentStep === 1) ? 'none' : 'flex';
        document.getElementById('nextBtn').style.display = 'flex';
        document.getElementById('nextBtn').innerText = 'مرحله بعد';
        changeStep(-5);
    }

    // ===================== تغییر گام‌ها =====================
    function changeStep(direction) {
        if (direction === 1 && typeof melkinoValidateWizardStep === 'function') {
            if (!melkinoValidateWizardStep('step' + currentStep)) {
                return;
            }
        }
        if (currentStep === totalSteps && direction === 1) {
            document.getElementById('prevBtn').style.display = 'none';
            document.getElementById('nextBtn').style.display = 'none';
            updatePreview();
            return;
        }

        document.getElementById(`step${currentStep}`).classList.remove('active');
        currentStep += direction;
        if(currentStep < 1) currentStep = 1;
        if(currentStep > totalSteps) currentStep = totalSteps;
        document.getElementById(`step${currentStep}`).classList.add('active');
        document.getElementById('stepText').innerText = `مرحله ${currentStep} از ${totalSteps}`;
        for(let i=1; i<totalSteps; i++) {
            let line = document.getElementById(`line${i}`);
            if(i < currentStep) line.classList.add('active');
            else line.classList.remove('active');
        }

        document.getElementById('prevBtn').style.display = (currentStep === 1) ? 'none' : 'flex';
        document.getElementById('nextBtn').innerText = (currentStep === totalSteps) ? 'پیش‌نمایش و ثبت' : 'مرحله بعد';
        document.getElementById('mainContent').scrollTop = 0;
    }

    // ===================== تبدیل اعداد فارسی به انگلیسی =====================
    function toEnglishDigits(str) {
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        for (let i = 0; i < 10; i++) {
            str = str.replaceAll(persianDigits[i], i);
            str = str.replaceAll(arabicDigits[i], i);
        }
        // راند ۲۷: ممیز عربی ٫ → نقطه و جداکنندهٔ هزارگان ٬ → کاما
        str = str.replaceAll('\u066B', '.').replaceAll('\u066C', ',');
        return str;
    }

    function formatPrice(input) {
        let val = toEnglishDigits(input.value);
        // راند ۲۷: قیمت تومان عدد صحیح است — از اولین ممیز به بعد حذف می‌شود
        // (قبلاً ۱۲٫۵ به «۱۲۵» تبدیل می‌شد!)
        val = val.split(/[.]/)[0];
        val = val.replace(/,/g, '').replace(/[^0-9]/g, '');
        if(val === '') { input.value = ''; return; }
        let num = parseInt(val, 10);
        input.value = num.toLocaleString('en-US');
    }

    function normalizeNumeric(input) {
        let val = toEnglishDigits(input.value);
        val = val.replace(/[^0-9.]/g, '');
        if(val === '') { input.value = ''; return; }
        if ((val.match(/\./g) || []).length > 1) {
            val = val.replace(/\.(?=.*\.)/g, '');
        }
        input.value = val;
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('price-input')) {
            formatPrice(e.target);
        }
        if (e.target.classList.contains('numeric-input')) {
            normalizeNumeric(e.target);
        }
    });

    (function () {
        function syncRegNavH() {
            var nav = document.querySelector('.bottom-nav');
            var h = nav ? Math.round(nav.getBoundingClientRect().height) : 74;
            if (h < 48) h = 74;
            document.documentElement.style.setProperty('--reg-bottom-nav-h', h + 'px');
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', syncRegNavH);
        } else {
            syncRegNavH();
        }
        window.addEventListener('load', syncRegNavH);
        window.addEventListener('resize', syncRegNavH);
        window.addEventListener('orientationchange', syncRegNavH);
    })();

/* ---- V2 CSP-safe bindings (replaces 5 inline onclick + 7 onchange + 1 oninput) ---- */
(function () {
    function on(id, ev, fn) {
        var el = document.getElementById(id);
        if (el) el.addEventListener(ev || 'click', fn);
    }
    function call(fn, ctx, args) {
        if (typeof fn === 'function') fn.apply(ctx, args || []);
    }
    on('dropZone', 'click', function () {
        var fi = document.getElementById('fileInput');
        if (fi) fi.click();
    });
    on('prevBtn', 'click', function () { call(changeStep, null, [-1]); });
    on('nextBtn', 'click', function () { call(changeStep, null, [1]); });
    on('regIsVacant', 'change', function () { call(toggleVacancy); });
    on('exchangeCheckbox', 'change', function () { call(toggleExchange); });
    on('fullRentCheckbox', 'change', function () { call(toggleFullRent); });
    on('hasLoanCheckbox', 'change', function () { call(toggleLoan); });
    on('fileInput', 'change', function () { call(handleImageUpload, null, [this]); });
    on('regFullDesc', 'input', function () {
        var cc = document.getElementById('charCount');
        if (cc) cc.innerText = this.value.length;
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-pc]'), function (el) {
        el.addEventListener('change', function () {
            call(togglePriceCondition, null, [el, el.getAttribute('data-pc')]);
        });
    });
    document.addEventListener('click', function (ev) {
        var t = ev.target && ev.target.closest ? ev.target.closest('[data-act="edit-preview"]') : null;
        if (t && typeof editPreview === 'function') editPreview();
        var r = ev.target && ev.target.closest ? ev.target.closest('[data-rmimg]') : null;
        if (r && typeof removeImage === 'function') removeImage(parseInt(r.getAttribute('data-rmimg'), 10));
    });
})();
