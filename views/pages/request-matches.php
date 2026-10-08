<?php
/**
 * Melkino V2 — request matches (server-rendered body VERBATIM).
 * Vars: $trackingCode, $selected, $matches, $combinations, $minDeposit, $maxDeposit.
 */
?>
<main class="request-matches-page">

    <div class="request-matches-title">
        🔎 فایل‌های مطابق درخواست
    </div>

    <div class="request-matches-sub">
        فایل‌ها براساس میزان تطبیق با درخواست شما مرتب شده‌اند.
        فایل‌هایی که مبلغشان خارج از بازه مالی انتخابی شما باشد نمایش داده نمی‌شوند.
    </div>

    <?php if ($selected): ?>

        <div class="request-code">

            <small>
                کد رهگیری درخواست
            </small>

            <strong>
                <?= htmlspecialchars(
                    (string)$selected['tracking_code'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>

        <div class="request-summary">

            <?= htmlspecialchars(
                (
                    ($selected['transaction_type'] ?? '')
                    . ' · ' .
                    ($selected['property_type'] ?? '')
                    . ' · ' .
                    ($selected['location'] ?? '')
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>

            <?php

            $summaryTransaction =
                reqMatchNormalizeTransaction(
                    $selected['transaction_type'] ?? ''
                );

            $summaryParts = [];

            if (
                $summaryTransaction === 'فروش' ||
                $summaryTransaction === 'پیش فروش'
            ) {

                $minPrice =
                    reqMatchNumber(
                        $selected['min_price'] ?? null
                    );

                $maxPrice =
                    reqMatchNumber(
                        $selected['max_price'] ?? null
                    );

                if (
                    $minPrice !== null ||
                    $maxPrice !== null
                ) {

                    if (
                        $minPrice !== null &&
                        $maxPrice !== null
                    ) {

                        $summaryParts[] =
                            'بودجه: ' .
                            number_format(
                                $minPrice
                            ) .
                            ' تا ' .
                            number_format(
                                $maxPrice
                            ) .
                            ' تومان';

                    } elseif (
                        $minPrice !== null
                    ) {

                        $summaryParts[] =
                            'حداقل بودجه: ' .
                            number_format(
                                $minPrice
                            ) .
                            ' تومان';

                    } elseif (
                        $maxPrice !== null
                    ) {

                        $summaryParts[] =
                            'حداکثر بودجه: ' .
                            number_format(
                                $maxPrice
                            ) .
                            ' تومان';
                    }
                }
            }

            if (
                $summaryTransaction === 'اجاره'
            ) {

                $minDeposit =
                    reqMatchNumber(
                        $selected['min_deposit'] ?? null
                    );

                $maxDeposit =
                    reqMatchNumber(
                        $selected['max_deposit'] ?? null
                    );

                $minRent =
                    reqMatchNumber(
                        $selected['min_rent'] ?? null
                    );

                $maxRent =
                    reqMatchNumber(
                        $selected['max_rent'] ?? null
                    );

                if (
                    $minDeposit !== null ||
                    $maxDeposit !== null
                ) {

                    if (
                        $minDeposit !== null &&
                        $maxDeposit !== null
                    ) {

                        $summaryParts[] =
                            'ودیعه: ' .
                            number_format(
                                $minDeposit
                            ) .
                            ' تا ' .
                            number_format(
                                $maxDeposit
                            ) .
                            ' تومان';

                    } elseif (
                        $minDeposit !== null
                    ) {

                        $summaryParts[] =
                            'حداقل ودیعه: ' .
                            number_format(
                                $minDeposit
                            ) .
                            ' تومان';

                    } elseif (
                        $maxDeposit !== null
                    ) {

                        $summaryParts[] =
                            'حداکثر ودیعه: ' .
                            number_format(
                                $maxDeposit
                            ) .
                            ' تومان';
                    }
                }

                if (
                    $minRent !== null ||
                    $maxRent !== null
                ) {

                    if (
                        $minRent !== null &&
                        $maxRent !== null
                    ) {

                        $summaryParts[] =
                            'اجاره: ' .
                            number_format(
                                $minRent
                            ) .
                            ' تا ' .
                            number_format(
                                $maxRent
                            ) .
                            ' تومان';

                    } elseif (
                        $minRent !== null
                    ) {

                        $summaryParts[] =
                            'حداقل اجاره: ' .
                            number_format(
                                $minRent
                            ) .
                            ' تومان';

                    } elseif (
                        $maxRent !== null
                    ) {

                        $summaryParts[] =
                            'حداکثر اجاره: ' .
                            number_format(
                                $maxRent
                            ) .
                            ' تومان';
                    }
                }
            }

            if ($summaryParts):
            ?>

                <div class="amount-rule">
                    <?= htmlspecialchars(
                        implode(
                            ' | ',
                            $summaryParts
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

        </div>

        <?php if (!empty($combinations)): ?>
            <div class="combo-section">
                <div class="combo-title">💰 ترکیب‌های پیشنهادی برای سرمایه‌گذاری</div>
                <div class="combo-grid">
                    <?php foreach ($combinations as $combo): ?>
                        <div class="combo-card">
                            <div class="combo-price">
                                مجموع قیمت: <?= number_format((float)$combo['total_price']) ?> تومان
                            </div>
                            <div class="combo-items">
                                <?php foreach ($combo['items'] as $item): 
                                    $price = isset($item['price']) && is_numeric($item['price']) ? (float)$item['price'] : 0;
                                    $title = isset($item['title']) ? $item['title'] : 'ملک';
                                    $adId = isset($item['ad_id']) ? $item['ad_id'] : '';
                                ?>
                                    <a class="combo-item" href="property-details.php?id=<?= urlencode($adId) ?>">
                                        <?= htmlspecialchars($title) ?>
                                        -
                                        <?= number_format($price) ?> تومان
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($matches): ?>

            <?php foreach ($matches as $m): ?>

                <a
                    class="match-card"
                    href="property-details.php?id=<?= urlencode(
                        (string)$m['ad_id']
                    ) ?>&from=request-matches&code=<?= urlencode(
                        (string)$selected['tracking_code']
                    ) ?>"
                >

                    <div class="match-head">

                        <div class="match-title">

                            <?= htmlspecialchars(
                                (string)(
                                    $m['title']
                                    ?? 'ملک بدون عنوان'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <div class="match-percent">

                            <?= (int)(
                                $m['match_percent']
                                ?? 0
                            ) ?>٪

                        </div>

                    </div>

                    <div class="match-meta">

                        <?= htmlspecialchars(
                            (
                                ($m['property_type'] ?? '')
                                . ' · ' .
                                ($m['transaction_type'] ?? '')
                                . ' · ' .
                                ($m['location'] ?? '')
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                    <div class="match-action">
                        مشاهده جزئیات آگهی ←
                    </div>

                </a>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">

                فایل مناسبی با معیارهای فعلی شما پیدا نشد.

                <br>

                <small>
                    فایل‌هایی که مبلغشان خارج از بازه انتخابی شما باشد
                    حتی در صورت داشتن درصد تطبیق بالا نمایش داده نمی‌شوند.
                </small>

            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="empty">
            درخواست موردنظر پیدا نشد.
        </div>

    <?php endif; ?>

    <a
        class="back-btn"
        href="home.php"
    >
        بازگشت به خانه
    </a>

</main>

<?php
