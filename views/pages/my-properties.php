<?php
/**
 * Melkino V2 — my-properties shell (mp-* skeleton VERBATIM; ?action served by legacy).
 * Vars: $telegram_id.
 */
?>
<script type="application/json" id="mxMpData"><?= json_encode(['tg' => (string)($telegram_id ?? '')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<main class="mpage">

    <!-- Header -->
    <header class="mp-header">

        <div class="mp-header-content">

            <h1 class="mp-title">
                🏠 املاک من
            </h1>

            <div class="mp-subtitle">
                آگهی‌های ثبت‌شده شما در این بخش نمایش داده می‌شوند.
                برای ویرایش، تغییرات ابتدا برای بررسی و تأیید ادمین ارسال خواهند شد.
            </div>

        </div>

        <div class="mp-summary">

            <span
                id="propertyCount"
                class="mp-summary-number"
            >
                —
            </span>

            <span class="mp-summary-label">
                آگهی ثبت‌شده
            </span>

        </div>

    </header>

    <!-- Notice -->
    <div
        id="notice"
        class="notice"
    ></div>

    <!-- List -->
    <div
        id="list"
        class="mp-list"
    >

        <div class="mp-loading">

            <div class="mp-skeleton"></div>
            <div class="mp-skeleton"></div>

        </div>

    </div>

</main>

<!-- =========================================================
     EDIT MODAL
     ========================================================= -->

<div
    class="mp-modal"
    id="modal"
    aria-hidden="true"
>

    <div class="mp-modal-box">

        <div class="mp-modal-header">

            <div class="mp-modal-title">
                ✏️ ویرایش آگهی
            </div>

            <button
                type="button"
                class="mp-modal-close"
                id="closeModalBtn"
                aria-label="بستن"
            >
                ×
            </button>

        </div>

        <div class="mp-modal-body">

            <form
                id="form"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    name="id"
                >

                <input
                    type="hidden"
                    name="telegram_id"
                    value="<?= e((string)($telegram_id ?? '')) ?>"
                >

                <label class="mp-form-label">

                    عنوان آگهی

                    <input
                        class="mp-form-control"
                        type="text"
                        name="title"
                        maxlength="255"
                        required
                    >

                </label>

                <div class="mp-form-row">
                    <label class="mp-form-label">نوع معامله
                        <input class="mp-form-control" type="text" name="transaction_type" id="mpTx" readonly>
                    </label>
                    <label class="mp-form-label">نوع ملک
                        <input class="mp-form-control" type="text" name="property_type" id="mpPt" readonly>
                    </label>
                </div>
                <p class="mp-subtitle" style="margin:0 0 10px;font-size:11px;">نوع معامله و نوع ملک قابل تغییر نیست.</p>

                <label class="mp-form-label">محله / محدوده
                    <input class="mp-form-control" type="text" name="location">
                </label>
                <label class="mp-form-label">آدرس
                    <input class="mp-form-control" type="text" name="address">
                </label>

                <div class="mp-form-row">

                    <label class="mp-form-label">

                        متراژ

                        <input
                            class="mp-form-control"
                            type="text"
                            name="area"
                            inputmode="decimal"
                        >

                    </label>

                    <label class="mp-form-label">

                        قیمت فروش

                        <input
                            class="mp-form-control money-input"
                            type="text"
                            name="price_sell"
                            inputmode="numeric"
                        >

                    </label>

                </div>

                <div class="mp-form-row">

                    <label class="mp-form-label">

                        ودیعه

                        <input
                            class="mp-form-control money-input"
                            type="text"
                            name="deposit"
                            inputmode="numeric"
                        >

                    </label>

                    <label class="mp-form-label">

                        اجاره ماهانه

                        <input
                            class="mp-form-control money-input"
                            type="text"
                            name="rent_monthly"
                            inputmode="numeric"
                        >

                    </label>

                </div>

                <div class="mp-form-row">
                    <label class="mp-form-label">متراژ زمین
                        <input class="mp-form-control" type="text" name="land_area" inputmode="decimal">
                    </label>
                    <label class="mp-form-label">زیربنا
                        <input class="mp-form-control" type="text" name="built_area" inputmode="decimal">
                    </label>
                </div>
                <div class="mp-form-row">
                    <label class="mp-form-label">خواب
                        <input class="mp-form-control" type="text" name="rooms" inputmode="numeric">
                    </label>
                    <label class="mp-form-label">طبقه
                        <input class="mp-form-control" type="text" name="floor">
                    </label>
                </div>
                <div class="mp-form-row">
                    <label class="mp-form-label">سن بنا
                        <input class="mp-form-control" type="text" name="building_age" inputmode="numeric">
                    </label>
                    <label class="mp-form-label">قیمت کل / پیش‌فروش
                        <input class="mp-form-control money-input" type="text" name="total_price" inputmode="numeric">
                    </label>
                </div>
                <div class="mp-form-row">
                    <label class="mp-form-label">رهن کامل
                        <input class="mp-form-control money-input" type="text" name="full_rent" inputmode="numeric">
                    </label>
                    <label class="mp-form-label">پیش‌پرداخت
                        <input class="mp-form-control money-input" type="text" name="down_payment" inputmode="numeric">
                    </label>
                </div>

                <label class="mp-form-label">

                    توضیحات

                    <textarea
                        class="mp-form-control"
                        name="description"
                        rows="6"
                    ></textarea>

                </label>

                <div class="mp-form-footer">

                    <button
                        type="button"
                        class="mp-btn"
                        id="cancelBtn"
                    >
                        انصراف
                    </button>

                    <button
                        type="submit"
                        class="mp-btn primary"
                        id="submitBtn"
                    >
                        ارسال برای تأیید
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php
require_once dirname(__DIR__, 2) . '/
