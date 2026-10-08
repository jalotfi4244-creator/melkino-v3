<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Http\Gate;
use Melkino\Support\Assets;

/**
 * Melkino V2 — request matches (server-rendered; preamble logic VERBATIM,
 * login gate via Gate::check, chrome via public layout).
 */
final class RequestMatchesController
{
    public function render(): string
    {
        Gate::check('property-request-matches.php');

        $_dbh = MELKINO_ROOT . '/db_helpers.php';
                if (is_file($_dbh)) {
                    require_once $_dbh;
                }

        global $pdo;

        $trackingCode = trim((string)($_GET['code'] ?? ''));

        $selected = null;
        $matches = [];
        $combinations = [];

        // ==========================================================
        // توابع کمکی
        // ==========================================================

        function reqMatchDigitsToEnglish($value)
        {
            $value = (string)$value;

            $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

            return str_replace(
                array_merge($fa, $ar),
                array_merge(range(0, 9), range(0, 9)),
                $value
            );
        }

        function reqMatchNumber($value)
        {
            if ($value === null) {
                return null;
            }

            $value = reqMatchDigitsToEnglish($value);

            $value = str_replace(
                [',', '٬', ' تومان', 'تومان', ' '],
                '',
                $value
            );

            $value = preg_replace(
                '/[^0-9.]/',
                '',
                $value
            );

            if ($value === '') {
                return null;
            }

            return (float)$value;
        }

        function reqMatchNormalizeTransaction($value)
        {
            $value = trim((string)$value);

            $value = str_replace(
                ['ي', 'ى', 'ئ'],
                'ی',
                $value
            );

            $value = str_replace(
                ['ك'],
                'ک',
                $value
            );

            $value = preg_replace(
                '/\s+/u',
                ' ',
                $value
            );

            if (
                in_array(
                    $value,
                    ['اجاره', 'رهن و اجاره', 'رهن واجاره'],
                    true
                )
            ) {
                return 'اجاره';
            }

            if (
                in_array(
                    $value,
                    ['رهن کامل', 'رهنکامل'],
                    true
                )
            ) {
                return 'رهن کامل';
            }

            if (
                in_array(
                    $value,
                    ['پیش فروش', 'پیشفروش'],
                    true
                )
            ) {
                return 'پیش فروش';
            }

            if ($value === 'فروش') {
                return 'فروش';
            }

            return $value;
        }

        /**
         * بررسی اینکه یک مبلغ داخل بازه انتخابی کاربر باشد.
         *
         * اگر min خالی باشد فقط max بررسی می‌شود.
         * اگر max خالی باشد فقط min بررسی می‌شود.
         * اگر هر دو خالی باشند true است.
         */
        function reqMatchAmountInRange($actual, $min, $max)
        {
            $actual = reqMatchNumber($actual);
            $min    = reqMatchNumber($min);
            $max    = reqMatchNumber($max);

            // اگر قیمت آگهی قابل تشخیص نیست، نمی‌توانیم
            // با اطمینان آن را مناسب بدانیم.
            if ($actual === null) {
                return false;
            }

            // هیچ محدودیتی تعیین نشده
            if ($min === null && $max === null) {
                return true;
            }

            // اگر کاربر اشتباهی حداقل را بیشتر از حداکثر زده
            if (
                $min !== null &&
                $max !== null &&
                $min > $max
            ) {
                [$min, $max] = [$max, $min];
            }

            if (
                $min !== null &&
                $actual < $min
            ) {
                return false;
            }

            if (
                $max !== null &&
                $actual > $max
            ) {
                return false;
            }

            return true;
        }

        /**
         * کنترل سختگیرانه مبلغ آگهی نسبت به درخواست.
         *
         * فروش / پیش فروش:
         *   price_sell یا total_price
         *
         * اجاره:
         *   deposit و rent_monthly
         *
         * رهن کامل:
         *   full_rent یا deposit
         */
        function reqMatchPriceAllowed($request, $ad)
        {
            $transaction = reqMatchNormalizeTransaction(
                $request['transaction_type'] ?? ''
            );

            // ======================================================
            // فروش / پیش فروش
            // ======================================================

            if (
                $transaction === 'فروش' ||
                $transaction === 'پیش فروش'
            ) {
                $actualPrice =
                    $ad['price_sell']
                    ?? $ad['total_price']
                    ?? null;

                return reqMatchAmountInRange(
                    $actualPrice,
                    $request['min_price'] ?? null,
                    $request['max_price'] ?? null
                );
            }

            // ======================================================
            // رهن کامل
            // ======================================================

            if ($transaction === 'رهن کامل') {

                $actualDeposit =
                    $ad['full_rent']
                    ?? $ad['deposit']
                    ?? null;

                $minDeposit =
                    $request['min_deposit'] !== ''
                        ? $request['min_deposit']
                        : ($request['min_price'] ?? null);

                $maxDeposit =
                    $request['max_deposit'] !== ''
                        ? $request['max_deposit']
                        : ($request['max_price'] ?? null);

                return reqMatchAmountInRange(
                    $actualDeposit,
                    $minDeposit,
                    $maxDeposit
                );
            }

            // ======================================================
            // اجاره
            // ======================================================

            if ($transaction === 'اجاره') {

                /*
                 * اگر کاربر گزینه «رهن کامل» را زده باشد،
                 * مبلغ رهن کنترل می‌شود و اجاره ماهانه نباید
                 * به عنوان شرط جداگانه اجباری شود.
                 */
                $rahnKamal =
                    reqMatchDigitsToEnglish(
                        $request['rahn_kamal'] ?? ''
                    );

                $isFullRahne =
                    in_array(
                        $rahnKamal,
                        ['1', 'بله', 'true'],
                        true
                    );

                if ($isFullRahne) {

                    $actualDeposit =
                        $ad['full_rent']
                        ?? $ad['deposit']
                        ?? null;

                    $minDeposit =
                        $request['min_deposit'] ?? null;

                    $maxDeposit =
                        $request['max_deposit'] ?? null;

                    // اگر برای ودیعه بازه‌ای ثبت شده
                    if (
                        reqMatchNumber($minDeposit) !== null ||
                        reqMatchNumber($maxDeposit) !== null
                    ) {
                        return reqMatchAmountInRange(
                            $actualDeposit,
                            $minDeposit,
                            $maxDeposit
                        );
                    }

                    // اگر کاربر از min_price/max_price استفاده کرده
                    return reqMatchAmountInRange(
                        $actualDeposit,
                        $request['min_price'] ?? null,
                        $request['max_price'] ?? null
                    );
                }

                // --------------------------------------------------
                // اجاره عادی
                // --------------------------------------------------

                $hasDepositRange =
                    reqMatchNumber(
                        $request['min_deposit'] ?? null
                    ) !== null
                    ||
                    reqMatchNumber(
                        $request['max_deposit'] ?? null
                    ) !== null;

                $hasRentRange =
                    reqMatchNumber(
                        $request['min_rent'] ?? null
                    ) !== null
                    ||
                    reqMatchNumber(
                        $request['max_rent'] ?? null
                    ) !== null;

                // اگر کاربر ودیعه تعیین کرده،
                // آگهی باید دقیقاً در محدوده ودیعه باشد.
                if ($hasDepositRange) {

                    $depositAllowed =
                        reqMatchAmountInRange(
                            $ad['deposit']
                                ?? $ad['full_rent']
                                ?? null,
                            $request['min_deposit'] ?? null,
                            $request['max_deposit'] ?? null
                        );

                    if (!$depositAllowed) {
                        return false;
                    }
                }

                // اگر کاربر اجاره ماهانه تعیین کرده،
                // آگهی باید دقیقاً در محدوده اجاره باشد.
                if ($hasRentRange) {

                    $rentAllowed =
                        reqMatchAmountInRange(
                            $ad['rent_monthly'] ?? null,
                            $request['min_rent'] ?? null,
                            $request['max_rent'] ?? null
                        );

                    if (!$rentAllowed) {
                        return false;
                    }
                }

                // اگر هیچ محدودیت مالی ثبت نشده باشد
                return true;
            }

            // ======================================================
            // نوع معامله ناشناخته
            // ======================================================

            return true;
        }

        // ==========================================================
        // دریافت درخواست
        // ==========================================================

        if ($pdo instanceof PDO) {

            $identity = melkinoCurrentIdentity(
                $_GET['telegram_id'] ?? null
            );

            // ------------------------------------------------------
            // حالت اول: دریافت با کد رهگیری
            // ------------------------------------------------------

            if ($trackingCode !== '') {

                $stmt = $pdo->prepare(
                    'SELECT *
                     FROM property_requests
                     WHERE tracking_code = ?
                     LIMIT 1'
                );

                $stmt->execute([
                    $trackingCode
                ]);

                $selected =
                    $stmt->fetch(PDO::FETCH_ASSOC)
                    ?: null;
            }

            // ------------------------------------------------------
            // حالت دوم: دریافت آخرین درخواست کاربر
            // ------------------------------------------------------

            elseif (
                $identity['user_id']
                ||
                $identity['telegram_id'] !== ''
            ) {

                $conds = [];
                $params = [];

                if ($identity['user_id']) {

                    $conds[] = 'user_id = ?';

                    $params[] =
                        $identity['user_id'];
                }

                if (
                    $identity['telegram_id'] !== ''
                ) {

                    $conds[] = 'telegram_id = ?';

                    $params[] =
                        $identity['telegram_id'];
                }

                if ($conds) {

                    $stmt = $pdo->prepare(
                        'SELECT *
                         FROM property_requests
                         WHERE ' .
                         implode(' OR ', $conds) .
                         '
                         ORDER BY created_at DESC, id DESC
                         LIMIT 1'
                    );

                    $stmt->execute(
                        $params
                    );

                    $selected =
                        $stmt->fetch(PDO::FETCH_ASSOC)
                        ?: null;
                }
            }

            // ======================================================
            // دریافت مچ‌ها
            // ======================================================

            if ($selected) {

                $stmt = $pdo->prepare(
                    "SELECT
                        rm.*,
                        a.title,
                        a.property_type,
                        a.transaction_type,
                        a.location,
                        a.deposit,
                        a.rent_monthly,
                        a.full_rent,
                        a.price_sell,
                        a.total_price,
                        a.price_condition,
                        a.price_hidden,
                        a.property_details
                     FROM request_matches rm
                     INNER JOIN ads a
                        ON a.id = rm.ad_id
                       AND a.status = 'published'
                     WHERE rm.request_id = ?
                     ORDER BY
                        rm.match_percent DESC,
                        rm.created_at DESC"
                );

                $stmt->execute([
                    (int)$selected['id']
                ]);

                $allMatches =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );

                // ==================================================
                // فیلتر سخت‌گیرانه مبلغ
                // ==================================================

                $matches = [];

                // تشخیص سرمایه‌گذاری
                $isInvestment = (reqMatchNormalizeTransaction($selected['transaction_type'] ?? '') === 'سرمایه‌گذاری');

                foreach ($allMatches as $match) {

                    // ------------------------------------------------
                    // اگر سرمایه‌گذاری است، فیلتر قیمت را نادیده بگیر
                    // ------------------------------------------------
                    if ($isInvestment) {
                        $matches[] = $match;
                        continue;
                    }

                    // ------------------------------------------------
                    // برای سایر نوع معاملات، فیلتر قیمت اعمال شود
                    // ------------------------------------------------
                    if (!reqMatchPriceAllowed($selected, $match)) {
                        continue;
                    }

                    $matches[] = $match;
                }

                // ==================================================
                // مرتب‌سازی نهایی
                // ==================================================

                usort(
                    $matches,
                    static function ($a, $b) {

                        $percentCompare =
                            (
                                (int)(
                                    $b['match_percent']
                                    ?? 0
                                )
                            )
                            <=>
                            (
                                (int)(
                                    $a['match_percent']
                                    ?? 0
                                )
                            );

                        if (
                            $percentCompare !== 0
                        ) {
                            return $percentCompare;
                        }

                        return
                            (
                                strtotime(
                                    $b['created_at']
                                    ?? '1970-01-01'
                                )
                            )
                            <=>
                            (
                                strtotime(
                                    $a['created_at']
                                    ?? '1970-01-01'
                                )
                            );
                    }
                );

                // ==================================================
                // استخراج ترکیب‌های سرمایه‌گذاری
                // ==================================================

                $additional = [];
                if (!empty($selected['additional'])) {
                    $additional = json_decode($selected['additional'], true);
                    if (!is_array($additional)) {
                        $additional = [];
                    }
                }

                if (!empty($additional['combinations'])) {
                    $combinations = $additional['combinations'];
                }
            }
        }

        // ==========================================================
        // Header

        return melkinoView('layouts/public.php', [
            'title' => 'فایل‌های مطابق درخواست | ملکینو',
            'description' => 'فایل‌های مطابق درخواست ثبت‌شده با کد پیگیری',
            'active_nav' => '',
            'head_extra' => Assets::css('assets/css/request-matches-legacy.css'),
            'content_view' => 'pages/request-matches.php',
            'content_data' => [
                'trackingCode' => $trackingCode,
                'selected' => $selected,
                'matches' => $matches,
                'combinations' => $combinations,
                'minDeposit' => $minDeposit,
                'maxDeposit' => $maxDeposit,
            ],
        ]);
    }
}
