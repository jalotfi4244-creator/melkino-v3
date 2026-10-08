<?php
/**
 * Melkino V2 — contact view. Static skeleton extracted VERBATIM from contact.php
 * (same ids/copy/SVGs; only the inline onclick removed — bound in contact.js for CSP).
 * Vars: $contact (server contact.info array|null), $logoPath.
 */
?>
<script type="application/json" id="mxContactData"><?= json_encode($contact ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<div class="main-content" id="mainContent">

    <div class="contact-page">

        <!-- =====================================================
             HERO (لوگوی بزرگ سمت راست، نوشته‌ها سمت چپ)
             ===================================================== -->

        <section class="contact-hero">

            <div class="contact-hero-content">

                <!-- ===== سمت راست: لوگوی بزرگ (اولین آیتم) ===== -->
                <div class="hero-right">
                    <div class="hero-logo-wrapper">
                        <?php if ($logoPath !== ''): ?><img src="<?= htmlspecialchars($logoPath) ?>" alt="ملکینو"><?php else: ?><span style="font-weight:900; font-size:1.2rem; color:var(--primary,#0b5d5b);">ملکینو</span><?php endif; ?>
                    </div>
                </div>

                <!-- ===== سمت چپ: نوشته‌ها (دومین آیتم) ===== -->
                <div class="hero-left">

                    <div class="hero-badge">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2h-3l-4 4-1.5-4H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/>
                            <path d="M8 8h8"/>
                            <path d="M8 12h5"/>
                        </svg>

                        <span id="heroAgencyName">املاک ملکینو شاهرود</span>

                    </div>

                    <h1>
                        ملکینو؛ انتخابی فراتر از یک ملک
                    </h1>

                    <p>
                        برای خرید، فروش، رهن، اجاره یا ثبت درخواست ملک،
                        کارشناسان ملکینو در کنار شما هستند تا بهترین انتخاب را داشته باشید.
                    </p>

                </div>

            </div>

        </section>


        <!-- =====================================================
             QUICK CONTACT
             ===================================================== -->

        <section class="contact-section">

            <div class="section-heading">

                <div class="section-heading-main">

                    <div class="section-heading-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </div>

                    <div>
                        <h2>راه‌های ارتباط سریع</h2>
                        <p>سریع‌ترین راه برای ارتباط با کارشناسان ملکینو</p>
                    </div>

                </div>

            </div>


            <div class="quick-grid">

                <!-- PHONE -->

                <a
                    class="quick-card"
                    id="quickPhone"
                    href="#"
                >

                    <div class="quick-top">

                        <div class="quick-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 11.2 18.9a19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.72c.12.9.34 1.78.66 2.62a2 2 0 0 1-.45 2.11L8.09 9.72a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.84.32 1.72.54 2.62.66A2 2 0 0 1 22 16.92z"/>
                            </svg>

                        </div>

                        <div class="quick-arrow">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>

                        </div>

                    </div>


                    <div>

                        <h3>تماس تلفنی</h3>

                        <p id="quickPhoneText">
                            در حال بارگذاری...
                        </p>

                    </div>

                </a>


                <!-- WHATSAPP -->

                <a
                    class="quick-card"
                    id="quickWhatsapp"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none;"
                >

                    <div class="quick-top">

                        <div class="quick-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 11.5a8 8 0 0 1-11.8 7l-4.2 1 1-4A8 8 0 1 1 20 11.5z"/>
                                <path d="M8.5 8.5c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.6 1.4c.1.2.1.4-.1.6l-.5.6c-.1.2-.1.3 0 .5.3.6.8 1.1 1.4 1.4.2.1.3.1.5 0l.6-.5c.2-.2.4-.2.6-.1l1.4.6c.3.1.4.3.4.5v.5c0 .3 0 .5-.4.7-.4.2-1.2.4-1.6.2-2.1-.9-3.6-2.4-4.5-4.5-.2-.4 0-1.2.2-1.6z"/>
                            </svg>

                        </div>

                        <div class="quick-arrow">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>

                        </div>

                    </div>


                    <div>

                        <h3>واتساپ</h3>

                        <p>
                            شروع گفتگو با کارشناس
                        </p>

                    </div>

                </a>


                <!-- TELEGRAM -->

                <a
                    class="quick-card"
                    id="quickTelegram"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none;"
                >

                    <div class="quick-top">

                        <div class="quick-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M21.5 4.5L18 20c-.2.9-.8 1.1-1.5.7l-4.5-3.5-2.3 2.2c-.3.3-.6.5-1.1.5l.4-4.6 8.2-7.4c.4-.4-.1-.6-.6-.2L6.5 13.9 2.1 12.5c-1-.3-1-1 .2-1.4L20.5 3.9c.9-.3 1.5.2 1 0.6z"/>
                            </svg>

                        </div>

                        <div class="quick-arrow">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>

                        </div>

                    </div>


                    <div>

                        <h3>تلگرام</h3>

                        <p>
                            ارتباط مستقیم در تلگرام
                        </p>

                    </div>

                </a>


                <!-- INSTAGRAM -->

                <a
                    class="quick-card"
                    id="quickInstagram"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none;"
                >

                    <div class="quick-top">

                        <div class="quick-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="3" width="18" height="18" rx="5"/>
                                <circle cx="12" cy="12" r="4"/>
                                <circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/>
                            </svg>

                        </div>

                        <div class="quick-arrow">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>

                        </div>

                    </div>


                    <div>

                        <h3>اینستاگرام</h3>

                        <p>
                            ما را در اینستاگرام دنبال کنید
                        </p>

                    </div>

                </a>

            </div>

        </section>


        <!-- =====================================================
             OFFICE
             ===================================================== -->

        <section class="contact-section">

            <div class="section-heading">

                <div class="section-heading-main">

                    <div class="section-heading-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l7-4 7 4v14"/>
                            <path d="M9 21v-8h6v8"/>
                            <path d="M8 9h.01"/>
                            <path d="M12 9h.01"/>
                            <path d="M16 9h.01"/>
                        </svg>

                    </div>

                    <div>

                        <h2>
                            دفتر املاک ملکینو
                        </h2>

                        <p>
                            مشتاق دیدار شما در دفتر ملکینو هستیم
                        </p>

                    </div>

                </div>

            </div>


            <div class="office-layout">

                <div class="contact-card office-info">

                    <!-- ADDRESS -->

                    <div class="office-row">

                        <div class="office-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z"/>
                                <circle cx="12" cy="9" r="2.5"/>
                            </svg>

                        </div>


                        <div class="office-content">

                            <strong>
                                آدرس دفتر
                            </strong>

                            <span id="officeAddress">
                                ثبت نشده
                            </span>

                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="office-row">

                        <div class="office-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 11.2 18.9a19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.72c.12.9.34 1.78.66 2.62a2 2 0 0 1-.45 2.11L8.09 9.72a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.84.32 1.72.54 2.62.66A2 2 0 0 1 22 16.92z"/>
                            </svg>

                        </div>


                        <div class="office-content">

                            <strong>
                                شماره تماس
                            </strong>

                            <a
                                href="#"
                                id="officePhone"
                            >
                                ثبت نشده
                            </a>

                        </div>

                    </div>


                    <!-- HOURS -->

                    <div class="office-row">

                        <div class="office-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M16 2v4"/>
                                <path d="M8 2v4"/>
                                <path d="M3 9h18"/>
                            </svg>

                        </div>


                        <div class="office-content">

                            <strong>
                                ساعت کاری
                            </strong>

                            <span id="officeHours">
                                ثبت نشده
                            </span>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="office-row">

                        <div class="office-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M3 7l9 6 9-6"/>
                            </svg>

                        </div>


                        <div class="office-content">

                            <strong>
                                ایمیل پشتیبانی
                            </strong>

                            <a
                                href="#"
                                id="officeEmail"
                            >
                                ثبت نشده
                            </a>

                        </div>

                    </div>

                </div>


                <!-- MAP IMAGE (admin uploads a screenshot of the map) -->

                <div class="map-box">

                    <div id="officeLiveMap" role="img" aria-label="نقشه موقعیت دفتر ملکینو"></div>

                    <div
                        class="map-placeholder"
                        id="mapPlaceholder"
                    >

                        <div class="map-placeholder-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z"/>
                                <circle cx="12" cy="9" r="2.5"/>
                            </svg>

                        </div>


                        <strong>
                            موقعیت دفتر ملکینو
                        </strong>


                        <span>
                            تصویر نقشه دفتر هنوز ثبت نشده است.
                        </span>

                    </div>


                    <a
                        id="officeMapLink"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="display:none;"
                        title="برای مشاهده بزرگ‌تر کلیک کنید"
                    >
                        <img
                            id="officeMapImage"
                            src=""
                            alt="موقعیت دفتر ملکینو روی نقشه"
                        >
                    </a>

                    <!-- مسیریابی به دفتر ملکینو با برنامهٔ نقشهٔ گوشی کاربر -->
                    <button
                        type="button"
                        class="map-direction-btn"
                        id="officeDirectionsBtn"
                        style="display:none;"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        مسیریابی تا دفتر ملکینو
                    </button>

                </div>

            </div>

        </section>


        <!-- =====================================================
             SOCIAL MEDIA
             ===================================================== -->

        <section class="contact-section">

            <div class="section-heading">

                <div class="section-heading-main">

                    <div class="section-heading-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>

                    </div>

                    <div>

                        <h2>
                            ملکینو را دنبال کنید
                        </h2>

                        <p>
                            آخرین فایل‌ها، اخبار و فرصت‌های ملکی
                        </p>

                    </div>

                </div>

            </div>


            <div class="social-premium-grid">

                <!-- TELEGRAM -->

                <a
                    href="#"
                    id="telegramChannelLink"
                    class="social-premium-card telegram-card"
                    target="_blank"
                    rel="noopener noreferrer"
                >

                    <div class="social-premium-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21.5 4.5L18 20c-.2.9-.8 1.1-1.5.7l-4.5-3.5-2.3 2.2c-.3.3-.6.5-1.1.5l.4-4.6 8.2-7.4c.4-.4-.1-.6-.6-.2L6.5 13.9 2.1 12.5c-1-.3-1-1 .2-1.4L20.5 3.9c.9-.3 1.5.2 1 0.6z"/>
                        </svg>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label">
                            کانال رسمی ملکینو
                        </span>

                        <strong>
                            تلگرام
                        </strong>

                        <span class="social-premium-description" id="telegramChannelDesc">
                            مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو
                        </span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>


                <!-- INSTAGRAM -->

                <a
                    href="#"
                    id="instagramChannelLink"
                    class="social-premium-card instagram-card"
                    target="_blank"
                    rel="noopener noreferrer"
                >

                    <div class="social-premium-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="18" height="18" rx="5"/>
                            <circle cx="12" cy="12" r="4"/>
                            <circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/>
                        </svg>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label">
                            صفحه رسمی ملکینو
                        </span>

                        <strong>
                            اینستاگرام
                        </strong>

                        <span class="social-premium-description" id="instagramChannelDesc">
                            تصاویر، فایل‌ها و محتوای اختصاصی ملکینو
                        </span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>

                <!-- بله -->

                <a
                    href="#"
                    id="baleChannelLink"
                    class="social-premium-card bale-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon" id="baleChannelIconWrap">

                        <img id="baleChannelLogo" alt="" style="display:none;width:100%;height:100%;object-fit:contain;border-radius:12px;">
                        <svg id="baleChannelSvg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21.3 4.4L2.9 11.1c-.8.3-.8 1.5.1 1.7l4.5 1.1 1.7 5.3c.2.7 1.1.9 1.6.3l2.5-2.8 4.4 3.2c.6.4 1.4.1 1.6-.6l2.8-13.3c.2-.9-.6-1.6-1.3-1.1z"/>
                        </svg>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label">
                            کانال رسمی ملکینو
                        </span>

                        <strong>
                            بله
                        </strong>

                        <span class="social-premium-description" id="baleChannelDesc">
                            مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو
                        </span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>


                <!-- کارت‌های سفارشی — متن، نام پیام‌رسان و آدرس از پنل ادمین تنظیم می‌شود -->

                <a
                    href="#"
                    id="customChannelLink0"
                    class="social-premium-card custom-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon">

                        <img
                            id="customChannelIcon0"
                            src=""
                            alt=""
                            style="width:26px;height:26px;object-fit:contain;display:none;border-radius:6px"
                        >

                        <span
                            id="customChannelEmoji0"
                            style="font-size:24px;line-height:1"
                        >🌐</span>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label" id="customChannelLabel0"></span>

                        <strong id="customChannelName0"></strong>

                        <span class="social-premium-description" id="customChannelDesc0"></span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>

                <a
                    href="#"
                    id="customChannelLink1"
                    class="social-premium-card custom-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon">

                        <img
                            id="customChannelIcon1"
                            src=""
                            alt=""
                            style="width:26px;height:26px;object-fit:contain;display:none;border-radius:6px"
                        >

                        <span
                            id="customChannelEmoji1"
                            style="font-size:24px;line-height:1"
                        >🌐</span>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label" id="customChannelLabel1"></span>

                        <strong id="customChannelName1"></strong>

                        <span class="social-premium-description" id="customChannelDesc1"></span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>

                <a
                    href="#"
                    id="customChannelLink2"
                    class="social-premium-card custom-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon">

                        <img
                            id="customChannelIcon2"
                            src=""
                            alt=""
                            style="width:26px;height:26px;object-fit:contain;display:none;border-radius:6px"
                        >

                        <span
                            id="customChannelEmoji2"
                            style="font-size:24px;line-height:1"
                        >🌐</span>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label" id="customChannelLabel2"></span>

                        <strong id="customChannelName2"></strong>

                        <span class="social-premium-description" id="customChannelDesc2"></span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>

                <a
                    href="#"
                    id="customChannelLink3"
                    class="social-premium-card custom-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon">

                        <img
                            id="customChannelIcon3"
                            src=""
                            alt=""
                            style="width:26px;height:26px;object-fit:contain;display:none;border-radius:6px"
                        >

                        <span
                            id="customChannelEmoji3"
                            style="font-size:24px;line-height:1"
                        >🌐</span>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label" id="customChannelLabel3"></span>

                        <strong id="customChannelName3"></strong>

                        <span class="social-premium-description" id="customChannelDesc3"></span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>

                <a
                    href="#"
                    id="customChannelLink4"
                    class="social-premium-card custom-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:none"
                >

                    <div class="social-premium-icon">

                        <img
                            id="customChannelIcon4"
                            src=""
                            alt=""
                            style="width:26px;height:26px;object-fit:contain;display:none;border-radius:6px"
                        >

                        <span
                            id="customChannelEmoji4"
                            style="font-size:24px;line-height:1"
                        >🌐</span>

                    </div>


                    <div class="social-premium-content">

                        <span class="social-premium-label" id="customChannelLabel4"></span>

                        <strong id="customChannelName4"></strong>

                        <span class="social-premium-description" id="customChannelDesc4"></span>

                    </div>


                    <div class="social-premium-arrow">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>

                    </div>

                </a>


            </div>

        </section>

    </div>

</div>


<!-- =========================================================
     TOAST
     ========================================================= -->

<div
    class="contact-toast"
    id="contactToast"
>

    <div class="toast-icon">

        <svg
            id="toastIcon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path d="M20 6L9 17l-5-5"/>
        </svg>

    </div>


    <div class="toast-content">

        <strong id="toastTitle">
            انجام شد
        </strong>

        <span id="toastMessage">
            عملیات با موفقیت انجام شد.
        </span>

    </div>

</div>


<script>

/* =========================================================
   اطلاعاتِ تماس از سمت سرور (تنظیم‌شده در پنل ادمین)
   این مقدار برای همه‌ی بازدیدکنندگان یکسان است.
   ========================================================= */

window.MELKINO_SERVER_CONTACT = <?= melkinoJsJson($melkinoServerContact) ?>;

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
</script>
