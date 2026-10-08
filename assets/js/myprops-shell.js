/* Melkino V2 — my-properties shell, extracted VERBATIM (only the PHP telegram bridge adapted). */

(function () {

    'use strict';


    /* =====================================================
       Elements
       ===================================================== */

    const list = document.getElementById('list');
    const notice = document.getElementById('notice');
    const modal = document.getElementById('modal');
    const form = document.getElementById('form');
    const submitBtn = document.getElementById('submitBtn');
    const propertyCount = document.getElementById('propertyCount');

    const telegramId = (function () {
        try {
            var el = document.getElementById('mxMpData');
            return el ? (JSON.parse(el.textContent || '{}').tg || '') : '';
        } catch (e) { return ''; }
    })();


    /* =====================================================
       Helpers
       ===================================================== */

    function esc(value) {

        return String(value ?? '').replace(
            /[&<>"']/g,
            function (char) {

                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];

            }
        );
    }


    function formatMoney(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '';
        }

        let str = String(value);

        str = str.replace(
            /[۰-۹]/g,
            function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
            }
        );

        str = str.replace(/[^\d]/g, '');

        if (!str) {
            return '';
        }

        return Number(str).toLocaleString('en-US');
    }


    function unformatMoney(value) {

        let str = String(value ?? '');

        str = str.replace(
            /[۰-۹]/g,
            function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
            }
        );

        return str.replace(/[^\d.-]/g, '');
    }


    function formatArea(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '—';
        }

        const number = Number(value);

        if (Number.isNaN(number)) {
            return esc(value);
        }

        return number.toLocaleString('en-US') + ' متر';
    }


    function showNotice(message, success = true) {

        notice.className =
            'notice ' +
            (success ? 'ok' : 'err');

        notice.textContent = message;

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

        setTimeout(function () {

            if (notice.textContent === message) {
                notice.style.display = 'none';
            }

        }, 6000);
    }


    function statusInfo(ad) {

        if (ad.review_pending) {

            return {
                className: 'pending',
                icon: '⏳',
                text: 'در انتظار تأیید'
            };
        }

        const status = String(
            ad.status || ''
        ).toLowerCase();


        if (
            status === 'active' ||
            status === 'published' ||
            status === 'approved'
        ) {

            return {
                className: 'active',
                icon: '✓',
                text: 'فعال'
            };
        }


        if (
            status === 'rejected' ||
            status === 'declined'
        ) {

            return {
                className: 'rejected',
                icon: '!',
                text: 'رد شده'
            };
        }


        return {
            className: 'default',
            icon: '•',
            text: ad.status || 'نامشخص'
        };
    }


    /* =====================================================
       API
       ===================================================== */

    async function api(action, options = {}) {

        const params = new URLSearchParams();

        params.set(
            'action',
            action
        );

        if (
            options.method === 'POST' &&
            options.body
        ) {

            return fetch(
                'my-properties.php?' + params.toString(),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: options.body
                }
            );
        }


        return fetch(
            'my-properties.php?' + params.toString(),
            {
                method: 'GET',
                cache: 'no-store'
            }
        );
    }


    /* =====================================================
       Load ads
       ===================================================== */

    async function load() {

        try {

            list.innerHTML = `
                <div class="mp-loading">
                    <div class="mp-skeleton"></div>
                    <div class="mp-skeleton"></div>
                </div>
            `;


            const response = await fetch(
                'my-properties.php?action=list&telegram_id=' +
                encodeURIComponent(telegramId),
                {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );


            /*
             * اگر PHP خطای 500 بدهد
             */
            if (!response.ok) {

                const text = await response.text();

                console.error(
                    'my-properties.php error:',
                    text
                );

                throw new Error(
                    'HTTP ' + response.status
                );
            }


            const data = await response.json();


            if (!data.success) {

                showNotice(
                    data.message ||
                    'خطا در دریافت املاک.',
                    false
                );

                list.innerHTML = '';

                return;
            }


            const ads = Array.isArray(data.ads)
                ? data.ads
                : [];


            propertyCount.textContent =
                ads.length.toLocaleString('fa-IR');


            if (!ads.length) {

                list.className = '';

                list.innerHTML = `
                    <div class="mp-empty">

                        <div class="mp-empty-icon">
                            🏠
                        </div>

                        <div class="mp-empty-title">
                            هنوز آگهی‌ای ثبت نکرده‌اید
                        </div>

                        <div class="mp-empty-text">
                            بعد از ثبت آگهی، تمام املاک شما
                            در این قسمت نمایش داده می‌شوند.
                        </div>

                    </div>
                `;

                return;
            }


            list.className = 'mp-list';


            list.innerHTML = ads.map(
                function (ad) {

                    const status =
                        statusInfo(ad);


                    const pendingHtml =
                        ad.review_pending
                        ? `
                            <div class="mp-pending">
                                ⏳ تغییرات این آگهی برای بررسی
                                و تأیید ادمین ارسال شده است.
                            </div>
                        `
                        : '';


                    return `
                        <article
                            class="mp-card"
                            data-id="${esc(ad.id)}"
                        >

                            <div class="mp-card-top">

                                <div class="mp-card-title-wrap">

                                    <h2 class="mp-card-title">
                                        ${esc(
                                            ad.title ||
                                            'بدون عنوان'
                                        )}
                                    </h2>

                                    <div class="mp-card-id">
                                        کد آگهی:
                                        ${esc(ad.id || '—')}
                                    </div>

                                </div>


                                <span
                                    class="mp-status ${status.className}"
                                >
                                    ${status.icon}
                                    ${esc(status.text)}
                                </span>

                            </div>


                            <div class="mp-info-grid">

                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        نوع ملک
                                    </span>

                                    <strong class="mp-info-value">
                                        ${esc(
                                            ad.property_type ||
                                            '—'
                                        )}
                                    </strong>

                                </div>


                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        نوع معامله
                                    </span>

                                    <strong class="mp-info-value">
                                        ${esc(
                                            ad.transaction_type ||
                                            '—'
                                        )}
                                    </strong>

                                </div>


                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        متراژ
                                    </span>

                                    <strong class="mp-info-value">
                                        ${formatArea(ad.area)}
                                    </strong>

                                </div>


                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        قیمت فروش
                                    </span>

                                    <strong class="mp-info-value money">
                                        ${
                                            formatMoney(
                                                ad.price_sell
                                            ) || '—'
                                        }
                                    </strong>

                                </div>


                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        ودیعه
                                    </span>

                                    <strong class="mp-info-value money">
                                        ${
                                            formatMoney(
                                                ad.deposit
                                            ) || '—'
                                        }
                                    </strong>

                                </div>


                                <div class="mp-info">

                                    <span class="mp-info-label">
                                        اجاره ماهانه
                                    </span>

                                    <strong class="mp-info-value money">
                                        ${
                                            formatMoney(
                                                ad.rent_monthly
                                            ) || '—'
                                        }
                                    </strong>

                                </div>

                            </div>


                            ${pendingHtml}


                            <div class="mp-actions">

                                <button
                                    type="button"
                                    class="mp-btn"
                                    data-edit
                                >
                                    ✏️ ویرایش
                                </button>


                                <button
                                    type="button"
                                    class="mp-btn danger"
                                    data-delete
                                >
                                    🗑 حذف
                                </button>


                                <a
                                    class="mp-btn primary"
                                    href="property-details.php?id=${encodeURIComponent(
                                        ad.id
                                    )}"
                                >
                                    👁 مشاهده
                                </a>

                            </div>

                        </article>
                    `;
                }
            ).join('');


            /*
             * Bind buttons
             */
            list.querySelectorAll('[data-edit]')
                .forEach(function (button, index) {

                    button.addEventListener(
                        'click',
                        function () {

                            editAd(ads[index]);

                        }
                    );
                });


            list.querySelectorAll('[data-delete]')
                .forEach(function (button, index) {

                    button.addEventListener(
                        'click',
                        function () {

                            removeAd(
                                ads[index].id
                            );

                        }
                    );
                });


        } catch (error) {

            console.error(
                'Load properties error:',
                error
            );


            propertyCount.textContent = '—';


            list.className = '';


            list.innerHTML = `
                <div class="mp-empty">

                    <div class="mp-empty-icon">
                        ⚠️
                    </div>

                    <div class="mp-empty-title">
                        دریافت اطلاعات انجام نشد
                    </div>

                    <div class="mp-empty-text">
                        اتصال به سرور یا پردازش اطلاعات با مشکل مواجه شد.
                        لطفاً صفحه را دوباره بارگذاری کنید.
                    </div>

                    <button
                        type="button"
                        class="mp-btn primary"
                        style="margin-top:16px"
                        id="retryLoad"
                    >
                        🔄 تلاش دوباره
                    </button>

                </div>
            `;


            const retry =
                document.getElementById(
                    'retryLoad'
                );


            if (retry) {

                retry.addEventListener(
                    'click',
                    load
                );
            }


            showNotice(
                'ارتباط با سرور برقرار نشد. جزئیات خطا در Console مرورگر قابل مشاهده است.',
                false
            );
        }
    }


    /* =====================================================
       Edit
       ===================================================== */

    function editAd(ad) {

        form.elements.id.value =
            ad.id || '';


        form.elements.title.value =
            ad.title || '';

        if (form.elements.transaction_type) form.elements.transaction_type.value = ad.transaction_type || '';
        if (form.elements.property_type) form.elements.property_type.value = ad.property_type || '';
        if (form.elements.location) form.elements.location.value = ad.location || '';
        if (form.elements.address) form.elements.address.value = ad.address || '';
        if (form.elements.land_area) form.elements.land_area.value = ad.land_area ?? '';
        if (form.elements.built_area) form.elements.built_area.value = ad.built_area ?? '';
        if (form.elements.rooms) form.elements.rooms.value = ad.rooms ?? '';
        if (form.elements.floor) form.elements.floor.value = ad.floor ?? '';
        if (form.elements.building_age) form.elements.building_age.value = ad.building_age ?? '';
        if (form.elements.total_price) form.elements.total_price.value = formatMoney(ad.total_price);
        if (form.elements.full_rent) form.elements.full_rent.value = formatMoney(ad.full_rent);
        if (form.elements.down_payment) form.elements.down_payment.value = formatMoney(ad.down_payment);

        form.elements.area.value =
            ad.area ?? '';


        form.elements.price_sell.value =
            formatMoney(ad.price_sell);


        form.elements.deposit.value =
            formatMoney(ad.deposit);


        form.elements.rent_monthly.value =
            formatMoney(ad.rent_monthly);


        form.elements.description.value =
            ad.description || '';


        modal.classList.add('show');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.style.overflow =
            'hidden';


        setTimeout(
            function () {

                form.elements.title.focus();

            },
            100
        );
    }


    function closeModal() {

        modal.classList.remove('show');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );


        document.body.style.overflow =
            '';
    }


    /* =====================================================
       Delete
       ===================================================== */

    async function removeAd(id) {

        if (!id) {
            return;
        }


        const confirmed = confirm(
            'آیا مطمئن هستید که می‌خواهید این آگهی را حذف کنید؟'
        );


        if (!confirmed) {
            return;
        }


        try {

            const body =
                new URLSearchParams();

            body.set(
                'id',
                id
            );

            body.set(
                'telegram_id',
                telegramId
            );


            const response =
                await fetch(
                    'my-properties.php?action=delete',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded;charset=UTF-8',
                            'Accept':
                                'application/json'
                        },
                        body: body.toString()
                    }
                );


            const data =
                await response.json();


            showNotice(
                data.message ||
                (
                    data.success
                        ? 'عملیات انجام شد.'
                        : 'حذف انجام نشد.'
                ),
                !!data.success
            );


            if (data.success) {
                await load();
            }

        } catch (error) {

            console.error(
                'Delete error:',
                error
            );


            showNotice(
                'حذف آگهی انجام نشد. لطفاً دوباره تلاش کنید.',
                false
            );
        }
    }


    /* =====================================================
       Money inputs
       ===================================================== */

    document.querySelectorAll(
        '.money-input'
    ).forEach(function (input) {

        input.addEventListener(
            'input',
            function () {

                input.value =
                    formatMoney(
                        input.value
                    );
            }
        );

    });


    /* =====================================================
       Submit edit
       ===================================================== */

    form.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();


            const oldText =
                submitBtn.textContent;


            submitBtn.disabled = true;

            submitBtn.textContent =
                '⏳ در حال ارسال...';


            try {

                const formData =
                    new FormData(form);


                formData.set(
                    'price_sell',
                    unformatMoney(
                        formData.get(
                            'price_sell'
                        )
                    )
                );


                formData.set(
                    'deposit',
                    unformatMoney(
                        formData.get(
                            'deposit'
                        )
                    )
                );


                formData.set(
                    'rent_monthly',
                    unformatMoney(
                        formData.get(
                            'rent_monthly'
                        )
                    )
                );
                ['total_price', 'full_rent', 'down_payment'].forEach(function (k) {
                    if (formData.has(k)) formData.set(k, unformatMoney(formData.get(k)));
                });


                const body =
                    new URLSearchParams();


                formData.forEach(
                    function (value, key) {

                        body.set(
                            key,
                            value
                        );

                    }
                );


                const response =
                    await fetch(
                        'my-properties.php?action=update',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded;charset=UTF-8',
                                'Accept':
                                    'application/json'
                            },
                            body: body.toString()
                        }
                    );


                const data =
                    await response.json();


                showNotice(
                    data.message ||
                    (
                        data.success
                            ? 'تغییرات ذخیره شد.'
                            : 'ذخیره تغییرات انجام نشد.'
                    ),
                    !!data.success
                );


                if (data.success) {

                    closeModal();

                    await load();
                }


            } catch (error) {

                console.error(
                    'Update error:',
                    error
                );


                showNotice(
                    'ارسال تغییرات انجام نشد. لطفاً اتصال را بررسی کنید.',
                    false
                );

            } finally {

                submitBtn.disabled =
                    false;

                submitBtn.textContent =
                    oldText;
            }

        }
    );


    /* =====================================================
       Modal controls
       ===================================================== */

    document
        .getElementById('closeModalBtn')
        .addEventListener(
            'click',
            closeModal
        );


    document
        .getElementById('cancelBtn')
        .addEventListener(
            'click',
            closeModal
        );


    modal.addEventListener(
        'click',
        function (event) {

            if (
                event.target === modal
            ) {
                closeModal();
            }

        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal.classList.contains('show')
            ) {

                closeModal();
            }

        }
    );


    /* =====================================================
       Initial load
       ===================================================== */

    load();

})();
