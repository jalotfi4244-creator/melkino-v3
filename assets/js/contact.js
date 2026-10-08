/* Melkino V2 — contact page logic, extracted VERBATIM from contact.php
 * (only the PHP data line + directions binding adapted for CSP). */


/* =========================================================
   اطلاعاتِ تماس از سمت سرور (تنظیم‌شده در پنل ادمین)
   این مقدار برای همه‌ی بازدیدکنندگان یکسان است.
   ========================================================= */

/* V2: server contact comes from #mxContactData JSON (CSP-safe, same merge logic below). */
window.MELKINO_SERVER_CONTACT = (function () {
    try {
        var el = document.getElementById('mxContactData');
        return el ? JSON.parse(el.textContent || 'null') : null;
    } catch (e) { return null; }
})();

(function () {

    'use strict';

    /* =========================================================
       HELPERS
       ========================================================= */

    function normalizeDigits(value) {

        if (!value) {
            return '';
        }

        return String(value)
            .replace(/[۰-۹]/g, function (digit) {
                return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
            })
            .replace(/[٠-٩]/g, function (digit) {
                return String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
            });
    }


    function normalizePhone(phone) {

        let value = normalizeDigits(phone)
            .replace(/[^\d+]/g, '');

        if (value.startsWith('+98')) {
            value = '0' + value.substring(3);
        }

        if (
            value.startsWith('98') &&
            value.length >= 11
        ) {
            value = '0' + value.substring(2);
        }

        return value;
    }


    function loadContactInfo() {

        const fallback = {
            agencyName: 'املاک ملکینو شاهرود',
            address: '',
            phone: '',
            email: '',
            whatsapp: '',
            telegram: '',
            instagram: '',
            linkedin: '',
            workingHours: '',
            mapImage: '',
            officeLat: '',
            officeLng: '',
            officeZoom: 15,
            bale: '',
            customCards: []
        };


        let fromStorage = {};

        try {

            const saved =
                localStorage.getItem('melkino_contact_info');

            if (saved) {

                const parsed = JSON.parse(saved);

                if (parsed && typeof parsed === 'object') {
                    fromStorage = parsed;
                }
            }

        } catch (error) {

            console.warn(
                'Melkino contact info parse error:',
                error
            );
        }


        /* اولویت: سرور > localStorage > پیش‌فرض */

        const merged =
            Object.assign({}, fallback, fromStorage);


        if (
            window.MELKINO_SERVER_CONTACT &&
            typeof window.MELKINO_SERVER_CONTACT === 'object'
        ) {

            const server =
                window.MELKINO_SERVER_CONTACT;

            Object.keys(server).forEach(function (key) {

                const value = server[key];

                if (value === null || value === undefined) {
                    return;
                }

                if (
                    typeof value === 'string' &&
                    value.trim() === ''
                ) {
                    return;
                }

                merged[key] = value;
            });
        }


        if (!Array.isArray(merged.customCards)) {
            merged.customCards = [];
        }

        return merged;
    }


    function normalizeCardColor(value, fallback) {
        var hex = String(value || '').trim();
        if (/^#([0-9A-Fa-f]{3})$/.test(hex)) {
            hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
        }
        if (!/^#([0-9A-Fa-f]{6})$/.test(hex)) {
            return fallback;
        }
        return hex.toUpperCase();
    }


    function darkenCardColor(hex, amount) {
        var n = parseInt(hex.slice(1), 16);
        if (!isFinite(n)) return hex;
        var r = Math.max(0, ((n >> 16) & 255) - amount);
        var g = Math.max(0, ((n >> 8) & 255) - amount);
        var b = Math.max(0, (n & 255) - amount);
        return '#' + [r, g, b].map(function (x) {
            var s = x.toString(16);
            return s.length < 2 ? '0' + s : s;
        }).join('').toUpperCase();
    }


    function applySocialCardColor(el, color, fallback) {
        if (!el) return;
        var hex = normalizeCardColor(color, fallback);
        el.style.background = 'linear-gradient(135deg, ' + hex + ', ' + darkenCardColor(hex, 42) + ')';
        el.style.color = '#fff';
        el.style.boxShadow = '0 12px 30px ' + hex + '33';
    }


    function setCardDescription(id, value, fallback) {
        var el = document.getElementById(id);
        if (!el) return;
        var text = String(value || '').trim();
        el.textContent = text !== '' ? text : fallback;
    }


    function createWhatsAppUrl(value) {

        const text = String(value || '').trim();

        if (!text) {
            return '';
        }

        if (
            text.indexOf('http://') === 0 ||
            text.indexOf('https://') === 0
        ) {
            return text;
        }

        let phone = normalizePhone(text);

        if (phone.startsWith('0')) {
            phone = '98' + phone.substring(1);
        }

        return 'https://wa.me/' + phone;
    }


    function showToast(
        title,
        message,
        type
    ) {

        const toast =
            document.getElementById('contactToast');

        const titleElement =
            document.getElementById('toastTitle');

        const messageElement =
            document.getElementById('toastMessage');

        const icon =
            document.getElementById('toastIcon');


        if (!toast) {
            return;
        }


        titleElement.textContent = title;
        messageElement.textContent = message;


        if (type === 'error') {

            icon.innerHTML = `
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 8v4"></path>
                <path d="M12 16h.01"></path>
            `;

        } else {

            icon.innerHTML = `
                <path d="M20 6L9 17l-5-5"></path>
            `;
        }


        toast.classList.add('show');


        clearTimeout(
            window.__melkinoContactToastTimer
        );


        window.__melkinoContactToastTimer =
            setTimeout(function () {

                toast.classList.remove('show');

            }, 3500);
    }


    /* =========================================================
       RENDER CONTACT DATA
       ========================================================= */

    function renderContactPage() {

        const data = loadContactInfo();


        const agencyName =
            String(
                data.agencyName ||
                'املاک ملکینو شاهرود'
            ).trim();


        const address =
            String(
                data.address || ''
            ).trim();


        const phone =
            String(
                data.phone || ''
            ).trim();


        const email =
            String(
                data.email || ''
            ).trim();


        const whatsapp =
            String(
                data.whatsapp || ''
            ).trim();


        const telegram =
            String(
                data.telegram || ''
            ).trim();


        const instagram =
            String(
                data.instagram || ''
            ).trim();


        const bale =
            String(
                data.bale || ''
            ).trim();


        const mapImage =
            String(
                data.mapImage || ''
            ).trim();

        const officeLat = parseFloat(data.officeLat);
        const officeLng = parseFloat(data.officeLng);
        const officeZoom = parseInt(data.officeZoom, 10) || 15;
        const hasOfficeCoords = isFinite(officeLat) && isFinite(officeLng)
            && officeLat >= -90 && officeLat <= 90
            && officeLng >= -180 && officeLng <= 180;

        const workingHours =
            String(
                data.workingHours ||
                data.hours ||
                data.workHours ||
                ''
            ).trim();


        /* =====================================================
           HERO
           ===================================================== */

        const heroAgencyName =
            document.getElementById(
                'heroAgencyName'
            );


        if (heroAgencyName) {
            heroAgencyName.textContent =
                agencyName;
        }


        /* =====================================================
           OFFICE
           ===================================================== */

        const officeAddress =
            document.getElementById(
                'officeAddress'
            );


        if (officeAddress) {

            officeAddress.textContent =
                address ||
                'آدرس دفتر هنوز ثبت نشده است';
        }


        const officePhone =
            document.getElementById(
                'officePhone'
            );


        if (officePhone) {

            if (phone) {

                officePhone.textContent =
                    phone;

                officePhone.href =
                    'tel:' +
                    normalizePhone(phone);

            } else {

                officePhone.textContent =
                    'شماره تماس ثبت نشده';

                officePhone.removeAttribute(
                    'href'
                );
            }
        }


        const officeEmail =
            document.getElementById(
                'officeEmail'
            );


        if (officeEmail) {

            if (email) {

                officeEmail.textContent =
                    email;

                officeEmail.href =
                    'mailto:' +
                    email;

            } else {

                officeEmail.textContent =
                    'ایمیل ثبت نشده';

                officeEmail.removeAttribute(
                    'href'
                );
            }
        }


        const officeHours =
            document.getElementById(
                'officeHours'
            );


        if (officeHours) {

            officeHours.textContent =
                workingHours ||
                'ساعت کاری ثبت نشده است';
        }


        /* =====================================================
           PHONE CARD
           ===================================================== */

        const quickPhone =
            document.getElementById(
                'quickPhone'
            );


        const quickPhoneText =
            document.getElementById(
                'quickPhoneText'
            );


        if (phone) {

            quickPhone.href =
                'tel:' +
                normalizePhone(phone);

            quickPhoneText.textContent =
                phone;

        } else {

            quickPhone.href = '#';

            quickPhoneText.textContent =
                'شماره تماس ثبت نشده';
        }


        /* =====================================================
           WHATSAPP
           ===================================================== */

        const quickWhatsapp =
            document.getElementById(
                'quickWhatsapp'
            );


        if (quickWhatsapp) {

            if (whatsapp) {

                quickWhatsapp.href =
                    createWhatsAppUrl(
                        whatsapp
                    );

                quickWhatsapp.style.display =
                    'flex';

            } else {

                quickWhatsapp.style.display =
                    'none';
            }
        }


        /* =====================================================
           TELEGRAM
           ===================================================== */

        const quickTelegram =
            document.getElementById(
                'quickTelegram'
            );


        if (quickTelegram) {

            if (telegram) {

                quickTelegram.href =
                    telegram;

                quickTelegram.style.display =
                    'flex';

            } else {

                quickTelegram.style.display =
                    'none';
            }
        }


        /* =====================================================
           INSTAGRAM
           ===================================================== */

        const quickInstagram =
            document.getElementById(
                'quickInstagram'
            );


        if (quickInstagram) {

            if (instagram) {

                quickInstagram.href =
                    instagram;

                quickInstagram.style.display =
                    'flex';

            } else {

                quickInstagram.style.display =
                    'none';
            }
        }


        /* =====================================================
           SOCIAL PREMIUM LINKS
           ===================================================== */

        const telegramChannelLink =
            document.getElementById(
                'telegramChannelLink'
            );


        const instagramChannelLink =
            document.getElementById(
                'instagramChannelLink'
            );


        if (telegramChannelLink) {

            if (telegram) {

                telegramChannelLink.href =
                    telegram;

                telegramChannelLink.style.display =
                    'flex';

                applySocialCardColor(
                    telegramChannelLink,
                    data.telegramColor,
                    '#174D46'
                );

                setCardDescription(
                    'telegramChannelDesc',
                    data.telegramDescription,
                    'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو'
                );

            } else {

                telegramChannelLink.href =
                    '#';

                telegramChannelLink.style.display =
                    'none';
            }
        }


        if (instagramChannelLink) {

            if (instagram) {

                instagramChannelLink.href =
                    instagram;

                instagramChannelLink.style.display =
                    'flex';

                applySocialCardColor(
                    instagramChannelLink,
                    data.instagramColor,
                    '#4B3D32'
                );

                setCardDescription(
                    'instagramChannelDesc',
                    data.instagramDescription,
                    'تصاویر، فایل‌ها و محتوای اختصاصی ملکینو'
                );

            } else {

                instagramChannelLink.href =
                    '#';

                instagramChannelLink.style.display =
                    'none';
            }
        }


        /* =====================================================
           BALE CHANNEL
           ===================================================== */

        const baleChannelLink =
            document.getElementById(
                'baleChannelLink'
            );


        if (baleChannelLink) {

            if (bale) {

                baleChannelLink.href =
                    bale;

                baleChannelLink.style.display =
                    'flex';

                applySocialCardColor(
                    baleChannelLink,
                    data.baleColor,
                    '#4AB06A'
                );

                setCardDescription(
                    'baleChannelDesc',
                    data.baleDescription,
                    'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو'
                );

                const baleLogo = String(data.baleLogo || '').trim();
                const baleLogoEl = document.getElementById('baleChannelLogo');
                const baleSvgEl = document.getElementById('baleChannelSvg');
                if (baleLogo && baleLogoEl) {
                    baleLogoEl.src = baleLogo;
                    baleLogoEl.style.display = 'block';
                    if (baleSvgEl) baleSvgEl.style.display = 'none';
                } else {
                    if (baleLogoEl) baleLogoEl.style.display = 'none';
                    if (baleSvgEl) baleSvgEl.style.display = '';
                }

            } else {

                baleChannelLink.href =
                    '#';

                baleChannelLink.style.display =
                    'none';
            }
        }


        /* =====================================================
           کارت‌های سفارشی (۵ عدد — از پنل ادمین تنظیم می‌شوند)
           ===================================================== */

        const customCards =
            Array.isArray(
                data.customCards
            )
                ? data.customCards
                : [];


        for (let i = 0; i < 5; i++) {

            const link =
                document.getElementById(
                    'customChannelLink' + i
                );

            if (!link) {
                continue;
            }

            const card =
                customCards[i] || {};

            const cardLabel =
                String(
                    card.label || ''
                ).trim();

            const cardMessenger =
                String(
                    card.messenger || ''
                ).trim();

            const cardUrl =
                String(
                    card.url || ''
                ).trim();

            const cardIcon =
                String(
                    card.icon || ''
                ).trim();


            /* بدونِ آدرس یا بدونِ متن، کارت مخفی می‌ماند */

            if (!cardUrl || !cardLabel) {

                link.href =
                    '#';

                link.style.display =
                    'none';

                continue;
            }


            link.href =
                cardUrl;

            link.style.display =
                'flex';

            var hay = (cardLabel + ' ' + cardMessenger + ' ' + cardUrl).toLowerCase();
            link.classList.toggle('eitaa-card', /eitaa|ایتا/.test(hay));
            link.classList.toggle('bale-card', /bale|بله/.test(hay) && !/telegram|تلگرام/.test(hay));


            const labelEl =
                document.getElementById(
                    'customChannelLabel' + i
                );

            const nameEl =
                document.getElementById(
                    'customChannelName' + i
                );

            const descEl =
                document.getElementById(
                    'customChannelDesc' + i
                );

            const iconEl =
                document.getElementById(
                    'customChannelIcon' + i
                );

            const emojiEl =
                document.getElementById(
                    'customChannelEmoji' + i
                );


            if (labelEl) {
                labelEl.textContent =
                    cardLabel;
            }

            if (nameEl) {
                nameEl.textContent =
                    cardMessenger ||
                    cardLabel;
            }

            if (descEl) {
                descEl.textContent =
                    String(card.description || '').trim() ||
                    cardMessenger;
            }

            applySocialCardColor(
                link,
                card.color,
                /eitaa|ایتا/i.test(hay) ? '#7B4FC4' : '#2A4A62'
            );


            if (iconEl && cardIcon) {

                iconEl.src =
                    cardIcon;

                iconEl.style.display =
                    'block';

                if (emojiEl) {
                    emojiEl.style.display =
                        'none';
                }

            } else {

                if (iconEl) {
                    iconEl.style.display =
                        'none';
                }

                if (emojiEl) {
                    emojiEl.style.display =
                        'inline-block';
                }
            }
        }


        /* =====================================================
           MAP IMAGE (uploaded by admin)
           ===================================================== */

        const officeMapLink =
            document.getElementById(
                'officeMapLink'
            );


        const officeMapImage =
            document.getElementById(
                'officeMapImage'
            );


        const mapPlaceholder =
            document.getElementById(
                'mapPlaceholder'
            );


        var liveMapEl = document.getElementById('officeLiveMap');

        function ensureLeaflet(cb) {
            if (window.L && window.L.map) { cb(); return; }
            if (window.__mkPubLeafletLoading) { window.__mkPubLeafletLoading.push(cb); return; }
            window.__mkPubLeafletLoading = [cb];
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
            document.head.appendChild(css);
            var s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
            s.onload = function () {
                var q = window.__mkPubLeafletLoading || [];
                window.__mkPubLeafletLoading = null;
                q.forEach(function (fn) { try { fn(); } catch (e) {} });
            };
            document.head.appendChild(s);
        }

        if (hasOfficeCoords && liveMapEl) {
            liveMapEl.style.display = 'block';
            if (mapPlaceholder) mapPlaceholder.style.display = 'none';
            if (officeMapLink) officeMapLink.style.display = 'none';
            var dirBtn = document.getElementById('officeDirectionsBtn');
            if (dirBtn) dirBtn.style.display = 'inline-flex';
            ensureLeaflet(function () {
                if (!window.__mkPubMap) {
                    window.__mkPubMap = L.map(liveMapEl).setView([officeLat, officeLng], officeZoom);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(window.__mkPubMap);
                    window.__mkPubMarker = L.marker([officeLat, officeLng]).addTo(window.__mkPubMap);
                } else {
                    window.__mkPubMap.setView([officeLat, officeLng], officeZoom);
                    if (window.__mkPubMarker) window.__mkPubMarker.setLatLng([officeLat, officeLng]);
                }
                setTimeout(function () { try { window.__mkPubMap.invalidateSize(); } catch (e) {} }, 80);
            });
        } else {
            if (liveMapEl) liveMapEl.style.display = 'none';

            if (officeMapLink) {
                officeMapLink.style.display = 'none';
            }

            var dirBtn2 = document.getElementById('officeDirectionsBtn');
            if (dirBtn2) {
                var addrForDir = String((loadContactInfo() || {}).address || '').trim();
                dirBtn2.style.display = addrForDir ? 'inline-flex' : 'none';
            }

            if (mapPlaceholder) {
                mapPlaceholder.style.display = 'flex';
            }
        }
    }

    /* مسیریابی: باز کردن موقعیت دفتر در برنامهٔ نقشهٔ گوشی کاربر */
    window.mkOpenDirections = function (ev) {
        if (ev) ev.preventDefault();
        try {
            var data = loadContactInfo();
            var lat = parseFloat(data && data.officeLat);
            var lng = parseFloat(data && data.officeLng);
            var hasCoords = isFinite(lat) && isFinite(lng)
                && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
            var addr = String((data && data.address) || '').trim();
            if (!hasCoords && !addr) {
                showToast('موقعیت ثبت نشده', 'موقعیت یا آدرس دفتر در تنظیمات ثبت نشده است.');
                return;
            }
            var isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
            // مقصد وب (نشان): هم در مرورگر و هم در اپ اندروید باز می‌شود
            var webNav = hasCoords
                ? 'https://nshn.ir/maps?destination=' + lat + ',' + lng + '&type=drive'
                : 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(addr);
            /* داخل مینی‌اپ تلگرام/بله (وب‌ویو)Schemeهای geo: غالباً ساکت بلاک
               می‌شوند؛ مسیر درست، API خودِ پلتفرم است: openLink صفحه را در
               مرورگر خارجی گوشی باز می‌کند و آنجا برنامهٔ نقشه می‌گیرد. */
            try {
                var __wapps = [
                    (window.Telegram && window.Telegram.WebApp) || null,
                    (window.Bale && window.Bale.WebApp) || null
                ];
                for (var __wi = 0; __wi < __wapps.length; __wi++) {
                    var __wa = __wapps[__wi];
                    if (__wa && typeof __wa.openLink === 'function') {
                        __wa.openLink(webNav);
                        try { showToast('مسیریاب در مرورگر گوشی باز شد…', '', 'success'); } catch (eT2) {}
                        return;
                    }
                }
            } catch (eW) {}
            if (!isMobile) {
                // دسکتاپ: باز شدن در تب جدید از داخل خود کلیک (مستثنی از بلاکر)
                var a = document.createElement('a');
                a.href = webNav;
                a.target = '_blank';
                a.rel = 'noopener';
                document.body.appendChild(a);
                a.click();
                setTimeout(function () { try { document.body.removeChild(a); } catch (e) {} }, 60);
                return;
            }
            // موبایل: ناوبری مستقیم geo: داخل خودِ کلیک — ناوبری است نه
            // پنجرهٔ جدید، پس بلاکرها جلویش را نمی‌گیرند و انتخابگر برنامهٔ
            // نقشهٔ گوشی (بالد/نشان/گوگل و…) باز می‌شود.
            // طبق مشخصات geo: اندروید، برچسب باید داخل پرانتزِ «واقعی» باشد؛
            // encode شدن پرانتزها باعث می‌شود بعضی برنامه‌های نقشه URL را نپذیرند.
            var geoUrl = hasCoords
                ? 'geo:' + lat + ',' + lng + '?q=' + lat + ',' + lng + '(دفتر ملکینو)'
                : 'geo:0,0?q=' + encodeURIComponent(addr);
            var left = false;
            var onLeft = function () { left = true; };
            window.addEventListener('pagehide', onLeft);
            window.addEventListener('blur', onLeft);
            try { showToast('در حال باز کردن برنامهٔ مسیریاب…', '', 'success'); } catch (eT) {}
            window.location.href = geoUrl;
            setTimeout(function () {
                window.removeEventListener('pagehide', onLeft);
                window.removeEventListener('blur', onLeft);
                // اگر برنامه‌ای باز نشده بود (iOS یا نبود برنامه) → نقشهٔ وب
                if (!left && !document.hidden) {
                    window.location.href = webNav;
                }
            }, 900);
        } catch (e) {}
    };

    /* =========================================================
       PHONE GUARD
       ========================================================= */

    const quickPhone =
        document.getElementById(
            'quickPhone'
        );


    if (quickPhone) {

        quickPhone.addEventListener(
            'click',
            function (event) {

                const data =
                    loadContactInfo();


                if (
                    !data.phone ||
                    !String(
                        data.phone
                    ).trim()
                ) {

                    event.preventDefault();


                    showToast(
                        'شماره تماس ثبت نشده',
                        'شماره تماس از تنظیمات ملکینو ثبت نشده است.',
                        'error'
                    );
                }
            }
        );
    }


    /* =========================================================
       TELEGRAM GUARD
       ========================================================= */

    const telegramLink =
        document.getElementById(
            'telegramChannelLink'
        );


    if (telegramLink) {

        telegramLink.addEventListener(
            'click',
            function (event) {

                const data =
                    loadContactInfo();


                if (
                    !data.telegram ||
                    !String(
                        data.telegram
                    ).trim()
                ) {

                    event.preventDefault();


                    showToast(
                        'لینک تلگرام ثبت نشده',
                        'لینک کانال تلگرام از تنظیمات ملکینو ثبت نشده است.',
                        'error'
                    );
                }
            }
        );
    }


    /* =========================================================
       INSTAGRAM GUARD
       ========================================================= */

    const instagramLink =
        document.getElementById(
            'instagramChannelLink'
        );


    if (instagramLink) {

        instagramLink.addEventListener(
            'click',
            function (event) {

                const data =
                    loadContactInfo();


                if (
                    !data.instagram ||
                    !String(
                        data.instagram
                    ).trim()
                ) {

                    event.preventDefault();


                    showToast(
                        'لینک اینستاگرام ثبت نشده',
                        'لینک اینستاگرام از تنظیمات ملکینو ثبت نشده است.',
                        'error'
                    );
                }
            }
        );
    }


    /* =========================================================
       INIT
       ========================================================= */

    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            renderContactPage
        );

    } else {

        renderContactPage();

    }

})();

/* V2: CSP-safe directions binding (replaces inline onclick). */
(function () {
    function bind() {
        var b = document.getElementById('officeDirectionsBtn');
        if (b && !b.__mxBound && window.mkOpenDirections) {
            b.__mxBound = true;
            b.addEventListener('click', window.mkOpenDirections);
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { setTimeout(bind, 0); });
    else setTimeout(bind, 0);
})();
