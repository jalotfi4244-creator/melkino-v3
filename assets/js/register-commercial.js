/* Melkino V2 — register-commercial wizard, extracted VERBATIM (POST-success block stays in legacy; bindings appended). */
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
        var gender = sessionStorage.getItem('reg_gender') || 'آقا';
        var MELKINO_P = window.MELKINO_PROFILE || {};
        // راند ۳۰: مقادیر سمت سرور (MELKINO_PROFILE) بر sessionStorage

        // اولویت دارند — دادهٔ نمایشی تماس باید از بک‌اند بیاید.

        var lastName = (MELKINO_P.logged_in && MELKINO_P.name) ? MELKINO_P.name : (sessionStorage.getItem('reg_last_name') || '');

        var phone = (MELKINO_P.logged_in && MELKINO_P.phone) ? MELKINO_P.phone : (sessionStorage.getItem('reg_phone') || '');
        var telegramId = sessionStorage.getItem('reg_telegram_id') || '';
        var transactionType = sessionStorage.getItem('reg_transaction_type') || 'فروش';
        
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
        var dropZone = document.getElementById('dropZone');
        
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
            
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                var fileInput = document.getElementById('fileInput');
                fileInput.files = files;
                handleImageUpload(fileInput);
            }
        });
    });

    var currentStep = 1;
    var totalSteps = 6;
    var transType = sessionStorage.getItem('reg_transaction_type') || 'فروش';
    var uploadedImages = [];

    // ===================== توابع قیمت =====================
    function togglePriceCondition(el, otherVal) {
        var parent = el.closest('div');
        var other = parent.querySelector('input[value="' + otherVal + '"]');
        if (el.checked && other) other.checked = false;
    }

    function toggleFullRent() {
        var chk = document.getElementById('fullRentCheckbox');
        var grp = document.getElementById('fullRentGroup');
        var inp = grp.querySelector('input');
        var depositInput = document.querySelector('input[name="deposit"]');
        var rentInput = document.querySelector('input[name="rent_monthly"]');

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
        var deliveryDateContainer = document.getElementById('deliveryDateContainer');
        var rentalFieldsContainer = document.getElementById('rentalFieldsContainer');

        // مخفی کردن همه‌ی بخش‌های اضافی
        deliveryDateContainer.style.display = 'none';
        rentalFieldsContainer.style.display = 'none';

        // نمایش بر اساس نوع معامله
        if (transactionType === 'فروش') {
            // بدون تغییر خاص
        } else if (transactionType === 'پیش فروش') {
            deliveryDateContainer.style.display = 'block';
        } else if (transactionType === 'اجاره') {
            rentalFieldsContainer.style.display = 'block';
        }
    }

    function toggleVacancy() {
        var chk = document.getElementById('regIsVacant');
        var dateInput = document.getElementById('regVacancyDate');
        
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
        var container = document.getElementById('publishOptionsContainer');
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
        var files = Array.from(input.files);
        var maxSize = 5 * 1024 * 1024;
        var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (files.length === 0) {
            alert('لطفاً حداقل یک تصویر انتخاب کنید.');
            return;
        }

        // بررسی فایل‌ها
        var validFiles = [];
        files.forEach(function(file) {
            if (allowedTypes.indexOf(file.type) === -1) {
                alert('فرمت ' + file.type + ' مجاز نیست. فقط JPG, PNG, WEBP مجاز است.');
                return;
            }
            if (file.size > maxSize) {
                alert('حجم فایل ' + file.name + ' بیش از ۵ مگابایت است.');
                return;
            }
            validFiles.push(file);
        });

        if (validFiles.length === 0) return;

        // نمایش پیش‌نمایش
        validFiles.forEach(function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var container = document.getElementById('imagePreviewContainer');
                var div = document.createElement('div');
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
        var formData = new FormData();
        validFiles.forEach(function(file) {
            formData.append('images[]', file);
        });
        formData.append('action', 'upload_images');

        var progressDiv = document.getElementById('uploadProgress');
        var progressBar = document.getElementById('progressBar');
        var progressText = document.getElementById('progressText');
        
        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.innerText = 'در حال آپلود...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.innerText = 'آپلود: ' + percent + '%';
            }
        };
        
        xhr.onload = function() {
            progressDiv.style.display = 'none';
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
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
        var container = document.getElementById('imagePreviewContainer');
        var items = container.querySelectorAll('.image-preview-item');
        if (items[index]) {
            items[index].remove();
        }
        
        togglePublishOptions();
    }

    // ===================== پیش‌نمایش نهایی =====================
    function updatePreview() {
        var previewContainer = document.getElementById('finalPreviewContainer');
        previewContainer.style.display = 'block';

        var gender = document.getElementById('hidden_gender').value || '-';
        var lastName = document.getElementById('hidden_last_name').value || '-';
        var phone = document.getElementById('hidden_phone').value || '-';
        var transTypeLabel = transType === 'فروش' ? 'خرید و فروش' : transType === 'پیش فروش' ? 'پیش فروش' : 'اجاره';
        var propertyType = 'تجاری';
        var title = document.getElementById('regTitle').value || '-';
        var location = document.getElementById('regLocation').value || '-';
        var address = document.getElementById('regAddress').value || '-';
        var description = document.getElementById('regFullDesc').value || '-';
        
        var publishPhotos = document.querySelector('input[name="publish_photos"]:checked');
        var publishStatus = publishPhotos ? (publishPhotos.value === 'yes' ? '✅ منتشر شود' : '❌ منتشر نشود (فقط حضوری)') : '-';

        // جمع‌آوری مشخصات
        var specsHtml = '';
        document.querySelectorAll('#step2 .form-group').forEach(function(group) {
            var label = group.querySelector('label') ? group.querySelector('label').innerText : '';
            var value = '';
            var input = group.querySelector('input, select');
            if (input) {
                if (input.tagName === 'SELECT') {
                    value = input.options[input.selectedIndex] ? input.options[input.selectedIndex].innerText : '-';
                } else if (input.type === 'radio') {
                    var checkedRadio = group.querySelector('input[type="radio"]:checked');
                    value = checkedRadio ? checkedRadio.value : '-';
                } else if (input.type === 'checkbox') {
                    value = input.checked ? 'بله' : 'خیر';
                } else {
                    value = input.value || '-';
                }
            } else {
                // برای گروه‌های رادیویی که مستقیم input ندارند
                var radioGroup = group.querySelector('.radio-group');
                if (radioGroup) {
                    var checkedRadio = radioGroup.querySelector('input[type="radio"]:checked');
                    value = checkedRadio ? checkedRadio.value : '-';
                }
            }
            specsHtml += '<div class="preview-row"><span class="preview-label">' + label + '</span><span class="preview-value rtl">' + value + '</span></div>';
        });

        // اضافه کردن کلید نخورده به پیش‌نمایش
        var isNotKeyed = document.getElementById('regIsNotKeyed');
        if (isNotKeyed && isNotKeyed.checked) {
            specsHtml += '<div class="preview-row"><span class="preview-label">کلید نخورده</span><span class="preview-value rtl">بله</span></div>';
        }

        // اضافه کردن تاریخ تحویل (پیش‌فروش)
        var deliveryDateJalali = document.getElementById('regDeliveryDateJalali');
        var deliveryDate = document.getElementById('regDeliveryDate');
        var deliveryShown = (deliveryDateJalali && deliveryDateJalali.value) ? deliveryDateJalali.value : (deliveryDate && deliveryDate.value ? deliveryDate.value : '');
        if (deliveryShown) {
            specsHtml += '<div class="preview-row"><span class="preview-label">تاریخ تحویل</span><span class="preview-value rtl">' + deliveryShown + '</span></div>';
        }

        // اضافه کردن تاریخ تخلیه و واحد تجاری تخلیه است (اجاره)
        var vacancyDate = document.getElementById('regVacancyDate');
        var isVacant = document.getElementById('regIsVacant');
        if (vacancyDate && vacancyDate.value) {
            specsHtml += '<div class="preview-row"><span class="preview-label">تاریخ تخلیه</span><span class="preview-value rtl">' + vacancyDate.value + '</span></div>';
        }
        if (isVacant && isVacant.checked) {
            specsHtml += '<div class="preview-row"><span class="preview-label">واحد تجاری تخلیه است</span><span class="preview-value rtl">بله</span></div>';
        }

        var amenitiesHtml = '';
        document.querySelectorAll('#step3 input[type="checkbox"]:checked').forEach(function(cb) {
            amenitiesHtml += '<span style="display:inline-block;background:var(--bg);padding:2px 8px;border-radius:4px;border:1px solid var(--border);margin:2px;">' + cb.value + '</span> ';
        });
        if (!amenitiesHtml) amenitiesHtml = 'هیچکدام';

        var priceHtml = '';
        if (transType === 'فروش') {
            var price = document.querySelector('input[name="price_sell"]') ? document.querySelector('input[name="price_sell"]').value : '-';
            var condition = document.querySelector('input[name="price_condition"]:checked') ? document.querySelector('input[name="price_condition"]:checked').value : 'negotiable';
            var exchangeChk = document.getElementById('exchangeCheckbox');
            var exchangeVal = exchangeChk && exchangeChk.checked ? document.querySelector('textarea[name="exchange_with"]').value : '-';
            priceHtml = '<div class="preview-row"><span class="preview-label">قیمت</span><span class="preview-value">' + price + ' تومان</span></div>';
            priceHtml += '<div class="preview-row"><span class="preview-label">وضعیت</span><span class="preview-value rtl">' + (condition === 'negotiable' ? 'قابل مذاکره' : 'مقطوع') + '</span></div>';
            if (exchangeChk && exchangeChk.checked) {
                priceHtml += '<div class="preview-row"><span class="preview-label">معاوضه با</span><span class="preview-value rtl">' + exchangeVal + '</span></div>';
            }
        } else if (transType === 'اجاره') {
            var deposit = document.querySelector('input[name="deposit"]') ? document.querySelector('input[name="deposit"]').value : '-';
            var rent = document.querySelector('input[name="rent_monthly"]') ? document.querySelector('input[name="rent_monthly"]').value : '-';
            var fullRentChk = document.getElementById('fullRentCheckbox');
            var fullRentVal = fullRentChk.checked ? document.querySelector('input[name="full_rent"]').value : '-';
            priceHtml = '<div class="preview-row"><span class="preview-label">ودیعه</span><span class="preview-value">' + deposit + ' تومان</span></div>';
            priceHtml += '<div class="preview-row"><span class="preview-label">اجاره ماهانه</span><span class="preview-value">' + rent + ' تومان</span></div>';
            if (fullRentChk.checked) {
                priceHtml += '<div class="preview-row"><span class="preview-label">رهن کامل</span><span class="preview-value">' + fullRentVal + ' تومان</span></div>';
            }
        } else if (transType === 'پیش فروش') {
            var total = document.querySelector('input[name="total_price"]') ? document.querySelector('input[name="total_price"]').value : '-';
            var down = document.querySelector('input[name="down_payment"]') ? document.querySelector('input[name="down_payment"]').value : '-';
            var terms = document.querySelector('textarea[name="payment_terms"]') ? document.querySelector('textarea[name="payment_terms"]').value : '-';
            priceHtml = '<div class="preview-row"><span class="preview-label">قیمت کل</span><span class="preview-value">' + total + ' تومان</span></div>';
            priceHtml += '<div class="preview-row"><span class="preview-label">پیش‌پرداخت</span><span class="preview-value">' + down + ' تومان</span></div>';
            priceHtml += '<div class="preview-row"><span class="preview-label">شرایط پرداخت</span><span class="preview-value rtl">' + terms + '</span></div>';
        }

        // اضافه کردن ساعت بازدید به پیش‌نمایش
        var visitHours = document.querySelector('textarea[name="visit_hours"]').value || '-';

                // ===== سند، معاوضه و وام در پیش‌نمایش (راند ۱۴) =====
        var deedCheckedBox = document.querySelector('input[name="deed_type"]:checked');
        var deedTypeVal = deedCheckedBox ? deedCheckedBox.value : '-';
        var deedNotesEl = document.querySelector('textarea[name="deed_notes"]');
        var deedNotesVal = (deedNotesEl && deedNotesEl.value) ? deedNotesEl.value : '-';
        var exTypesChecked = Array.prototype.slice.call(document.querySelectorAll('input[name="exchange_types[]"]:checked')).map(function (c) { return c.value; });
        var exMainChk = document.getElementById('exchangeCheckbox');
        var wantsExchange = !!(exMainChk && exMainChk.checked);
        var exWithEl = document.querySelector('textarea[name="exchange_with"]');
        var exWithVal = (wantsExchange && exWithEl && exWithEl.value) ? exWithEl.value : '';
        var exchangePreviewVal = 'خیر';
        if (wantsExchange) {
            var exParts = [];
            if (exTypesChecked.length) exParts.push(exTypesChecked.join('\u060c '));
            if (exWithVal) exParts.push(exWithVal);
            exchangePreviewVal = 'بله' + (exParts.length ? ' \u2014 ' + exParts.join(' \u2014 ') : '');
        }
        var loanPreviewHtml = '';
        var hasLoanChk = document.getElementById('hasLoanCheckbox');
        if (hasLoanChk && hasLoanChk.checked) {
            var lv = function (n) { var el = document.querySelector('[name="' + n + '"]'); return (el && el.value) ? el.value : '-'; };
            loanPreviewHtml =
                '<div class="preview-row"><span class="preview-label">وام دارد</span><span class="preview-value rtl">بله</span></div>' +
                '<div class="preview-row"><span class="preview-label">مبلغ وام</span><span class="preview-value">' + lv('loan_amount') + ' تومان</span></div>' +
                '<div class="preview-row"><span class="preview-label">نوع وام</span><span class="preview-value rtl">' + lv('loan_type') + '</span></div>' +
                '<div class="preview-row"><span class="preview-label">مدت وام</span><span class="preview-value rtl">' + lv('loan_duration') + '</span></div>' +
                '<div class="preview-row"><span class="preview-label">بانک</span><span class="preview-value rtl">' + lv('loan_bank') + '</span></div>' +
                '<div class="preview-row"><span class="preview-label">مبلغ هر قسط</span><span class="preview-value">' + lv('loan_installment') + ' تومان</span></div>' +
                '<div class="preview-row"><span class="preview-label">تعداد اقساط پرداخت شده</span><span class="preview-value rtl">' + lv('loan_installments_paid') + '</span></div>' +
                '<div class="preview-row"><span class="preview-label">توضیحات وام</span><span class="preview-value rtl" style="font-weight:normal;">' + lv('loan_notes') + '</span></div>';
        }

        document.getElementById('previewContent').innerHTML = 
            '<div class="preview-card-title" style="font-size:14px;margin-top:0;">اطلاعات تماس</div>' +
            '<div class="preview-row"><span class="preview-label">جنسیت</span><span class="preview-value rtl">' + gender + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">نام خانوادگی</span><span class="preview-value rtl">' + lastName + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">شماره تماس</span><span class="preview-value">' + phone + '</span></div>' +
            
            '<div class="preview-card-title" style="font-size:14px;">نوع معامله و ملک</div>' +
            '<div class="preview-row"><span class="preview-label">نوع معامله</span><span class="preview-value rtl">' + transTypeLabel + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">نوع ملک</span><span class="preview-value rtl">' + propertyType + '</span></div>' +

            '<div class="preview-card-title" style="font-size:14px;">اطلاعات پایه</div>' +
            '<div class="preview-row"><span class="preview-label">عنوان</span><span class="preview-value rtl">' + title + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">محله</span><span class="preview-value rtl">' + location + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">آدرس دقیق</span><span class="preview-value rtl">' + address + '</span></div>' +

            '<div class="preview-card-title" style="font-size:14px;">مشخصات ملک تجاری</div>' +
            specsHtml +

            '<div class="preview-card-title" style="font-size:14px;">امکانات</div>' +
            '<div class="preview-row"><span class="preview-label">انتخاب شده</span><span class="preview-value rtl" style="font-weight:normal;">' + amenitiesHtml + '</span></div>' +

            '<div class="preview-card-title" style="font-size:14px;">قیمت</div>' +
            priceHtml + loanPreviewHtml +

            '<div class="preview-card-title" style="font-size:14px;">سند و معاوضه</div>' +
            '<div class="preview-row"><span class="preview-label">نوع سند</span><span class="preview-value rtl">' + deedTypeVal + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">توضیحات سند</span><span class="preview-value rtl" style="font-weight:normal;white-space:pre-wrap;">' + deedNotesVal + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">مایل به معاوضه</span><span class="preview-value rtl" style="font-weight:normal;">' + exchangePreviewVal + '</span></div>' +

            '<div class="preview-card-title" style="font-size:14px;">تصاویر</div>' +
            '<div class="preview-row"><span class="preview-label">وضعیت انتشار</span><span class="preview-value rtl">' + publishStatus + '</span></div>' +
            '<div id="previewImages" class="image-preview-grid"></div>' +

            '<div class="preview-card-title" style="font-size:14px;">توضیحات و زمان بازدید</div>' +
            '<div class="preview-row"><span class="preview-label">توضیحات</span><span class="preview-value rtl" style="font-weight:normal;white-space:pre-wrap;">' + description + '</span></div>' +
            '<div class="preview-row"><span class="preview-label">زمان‌های بازدید</span><span class="preview-value rtl" style="font-weight:normal;">' + visitHours + '</span></div>';
        
        renderPreviewImages();
    }

    function renderPreviewImages() {
        var container = document.getElementById('previewImages');
        container.innerHTML = '';
        uploadedImages.forEach(function(imgPath, index) {
            var div = document.createElement('div');
            div.className = 'image-preview-item';
            div.innerHTML = '<img src="' + imgPath + '" alt="تصویر ' + (index+1) + '">';
            container.appendChild(div);
        });
    }

    // ===================== دکمه‌های مرحله ۵ =====================
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

        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep += direction;
        if (currentStep < 1) currentStep = 1;
        if (currentStep > totalSteps) currentStep = totalSteps;
        document.getElementById('step' + currentStep).classList.add('active');
        document.getElementById('stepText').innerText = 'مرحله ' + currentStep + ' از ' + totalSteps;
        for (var i=1; i<totalSteps; i++) {
            var line = document.getElementById('line' + i);
            if (i < currentStep) line.classList.add('active');
            else line.classList.remove('active');
        }

        document.getElementById('prevBtn').style.display = (currentStep === 1) ? 'none' : 'flex';
        document.getElementById('nextBtn').innerText = (currentStep === totalSteps) ? 'پیش‌نمایش و ثبت' : 'مرحله بعد';
        document.getElementById('mainContent').scrollTop = 0;
    }

    // ===================== تبدیل اعداد و فرمت قیمت =====================
    function toEnglishDigits(str) {
        var persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        var arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        for (var i = 0; i < 10; i++) {
            str = str.replaceAll(persianDigits[i], i);
            str = str.replaceAll(arabicDigits[i], i);
        }
        return str;
    }

    function formatPrice(input) {
        var val = toEnglishDigits(input.value);
        val = val.replace(/,/g, '').replace(/[^0-9]/g, '');
        if (val === '') { input.value = ''; return; }
        var num = parseInt(val, 10);
        input.value = num.toLocaleString('en-US');
    }

    function normalizeNumeric(input) {
        var val = toEnglishDigits(input.value);
        val = val.replace(/[^0-9.]/g, '');
        if (val === '') { input.value = ''; return; }
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

/* ---- V2 CSP-safe bindings (replaces inline onclick/onchange/oninput) ---- */
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
