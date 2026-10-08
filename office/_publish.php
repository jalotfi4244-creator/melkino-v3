<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه رندر متن انتشار آگهی (مرحله ۳۲)
 *--------------------------------------------------------------------------
 * استخراج دقیق رندر melkinoAdMessageText سایت از db_helpers.php (که به‌خاطر
 * نگهبان ورود سایت قابل require نیست) + توابع کمکی‌اش. برای پیش‌نمایش
 * متن انتشار کانال در صفحه ربات‌ها.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofPubJ = dirname(__DIR__) . '/jalali-lib.php';
if (is_file($__ofPubJ)) {
    require_once $__ofPubJ;
}
unset($__ofPubJ);
if (!function_exists('melkinoGregorianToJalali')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/jalali-lib.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}
$__ofPubB = dirname(__DIR__) . '/bot-settings.php';
if (is_file($__ofPubB)) {
    require_once $__ofPubB;
}
unset($__ofPubB);
if (!function_exists('melkinoBotSettings')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/bot-settings.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_pub_price_to_num')) {
    function office_pub_price_to_num($value): float
    {
        $s = str_replace(
            ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
            ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],
            trim((string)$value)
        );
        // راند ۲۷: جداکننده‌های فارسی/عربی — ٬ و ، و «تومان» و فاصله حذف می‌شوند
        // و ممیز عربی ٫ (U+066B) به نقطه تبدیل می‌شود.
        $s = str_replace(['٬', '،', ' ', 'تومان'], '', $s);
        $s = str_replace('٫', '.', $s);
        // راند ۲۷: کامای اعشاری (مثل 1234,50) فقط وقتی نقطه وجود نداشته باشد
        // و دقیقاً یک کاما با ۱-۲ رقم بعدش باشد؛ وگرنه کاما جداکنندهٔ هزارگان است.
        if (strpos($s, '.') === false && preg_match('/^([+-]?\d+),(\d{1,2})$/', $s)) {
            $s = str_replace(',', '.', $s);
        }
        $s = str_replace(',', '', $s);
        return is_numeric($s) ? (float)$s : 0.0;
    }
}

if (!function_exists('office_pub_ad_message')) {
    function office_pub_ad_message(array $ad, bool $html = true, string $platform = 'telegram', ?array $fieldsOverride = null): string
    {
        $esc = function ($text) use ($html) {
            $text = (string)$text;
            return $html ? str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text) : $text;
        };

        $money = function ($value) {
            $n = office_pub_price_to_num($value);
            return $n > 0 ? number_format($n, 0, '.', ',') . ' تومان' : '';
        };

        // تنظیمات انتشار این پلتفرم (با fallback به رفتار قبلی)
        // اگر برای «نوع ملک × نوع معاملهٔ» این آگهی تنظیم اختصاصی ذخیره شده
        // باشد، همان اعمال می‌شود؛ وگرنه تنظیمات عمومی.
        $enabledFields = null; // null یعنی همه‌ی فیلدها روشن
        $pubHeader = '';
        $pubFooter = '';
        if (function_exists('melkinoPublishSettings')) {
            try {
                $pub = melkinoPublishSettings(
                    $platform,
                    (string)($ad['property_type'] ?? ''),
                    (string)($ad['transaction_type'] ?? '')
                );
                $enabledFields = is_array($pub['fields'] ?? null) ? array_map('strval', $pub['fields']) : null;
                $pubHeader = trim((string)($pub['header'] ?? ''));
                $pubFooter = trim((string)($pub['footer'] ?? ''));
            } catch (Throwable $e) {
                $enabledFields = null;
            }
        }
        if (is_array($fieldsOverride)) {
            $enabledFields = array_values(array_map('strval', $fieldsOverride));
        }
        $on = function (string $key) use ($enabledFields) {
            return $enabledFields === null || in_array($key, $enabledFields, true);
        };

        $lines = [];

        if ($pubHeader !== '') {
            $lines[] = $esc($pubHeader);
            $lines[] = '';
        }

        if ($on('title')) {
            $lines[] = ($html ? '🏠 <b>' : '🏠 ') . $esc($ad['title'] ?: 'آگهی ملک') . ($html ? '</b>' : '');
            $lines[] = '';
        }
        if ($on('transaction')) {
            $lines[] = '📌 نوع معامله: ' . $esc($ad['transaction_type'] ?: '-');
        }
        if ($on('property_type')) {
            $lines[] = '🏷️ نوع ملک: ' . $esc($ad['property_type'] ?: '-');
        }
        if ($on('location') && !empty($ad['location'])) {
            $lines[] = '📍 موقعیت: ' . $esc($ad['location']);
        }
        if ($on('address') && !empty($ad['address'])) {
            $lines[] = '🗺️ آدرس: ' . $esc($ad['address']);
        }
        if ($on('area') && !empty($ad['area'])) {
            $lines[] = '📐 متراژ: ' . $esc($ad['area']) . ' متر';
        }
        if ($on('rooms') && !empty($ad['rooms'])) {
            $lines[] = '🛏️ تعداد اتاق: ' . $esc($ad['rooms']);
        }
        if ($on('floor') && !empty($ad['floor'])) {
            $lines[] = '🏢 طبقه: ' . $esc($ad['floor']);
        }
        if ($on('year') && !empty($ad['year'])) {
            $lines[] = '📅 سال ساخت: ' . $esc($ad['year']);
            if (function_exists('melkinoBuildingAgeDisplay')) {
                $__age = melkinoBuildingAgeDisplay($ad['year']);
                if ($__age !== '') {
                    $lines[] = '⏳ سن بنا: ' . $__age;
                }
            }
        }

        // فیلدهای اختصاصیِ نوع ملک از property_details (راند ۱۷)
        // کلیدها با پیشوند pd. در melkinoPublishFieldDefs تعریف شده‌اند.
        $__pdDefs = [];
        if (function_exists('melkinoPublishFieldDefs')) {
            foreach (melkinoPublishFieldDefs((string)($ad['property_type'] ?? '')) as $__k => $__v) {
                if (strpos($__k, 'pd.') === 0) {
                    $__pdDefs[$__k] = $__v;
                }
            }
        }
        if ($__pdDefs) {
            $__details = [];
            $__rawDetails = $ad['property_details'] ?? null;
            if (is_string($__rawDetails) && trim($__rawDetails) !== '') {
                $__decoded = json_decode($__rawDetails, true);
                if (is_array($__decoded)) {
                    $__details = $__decoded;
                }
            } elseif (is_array($__rawDetails)) {
                $__details = $__rawDetails;
            }
            foreach ($__pdDefs as $__k => $__def) {
                if (!$on($__k)) {
                    continue;
                }
                $__dk = substr($__k, 3);
                $__val = $__details[$__dk] ?? '';
                if (is_array($__val)) {
                    $__val = implode('، ', array_filter(array_map('strval', $__val), static fn($x) => trim($x) !== ''));
                }
                $__val = trim((string)$__val);
                if (strpos($__dk, 'has_') === 0) {
                    if ($__val !== '1') {
                        continue;
                    }
                    $__val = 'دارد';
                }
                if ($__val === '' || $__val === '0' || $__val === '۰') {
                    continue;
                }
                $lines[] = ($__def['emoji'] ?? '🔹') . ' ' . ($__def['label'] ?? $__dk) . ': ' . $esc($__val) . (string)($__def['suffix'] ?? '');
            }
        }

        // اطلاعات وام (فقط فروش/پیش‌فروش)
        $loan = function_exists('office_pub_loan_info') ? office_pub_loan_info($ad) : ['has' => false];

        if ($on('price')) {
            if (empty($ad['price_hidden'])) {
                // راند ۲۲: همهٔ مبالغ با office_pub_price_to_num نرمال می‌شوند تا
                // مقدارهای قالب‌بندی‌شده (رقم فارسی/جداکننده) یا صفرگونه
                // («0»، «0,000»، «۰») باعث انتشار «۰ تومان» یا قیمت خالی نشوند.
                $sellNum     = office_pub_price_to_num($ad['price_sell'] ?? '');
                $totalNum    = office_pub_price_to_num($ad['total_price'] ?? '');
                $displayNum  = office_pub_price_to_num($ad['display_price'] ?? '');
                $depositNum  = office_pub_price_to_num($ad['deposit'] ?? '');
                $rentNum     = office_pub_price_to_num($ad['rent_monthly'] ?? '');
                $fullRentNum = !empty($ad['full_rent_enabled']) ? office_pub_price_to_num($ad['full_rent'] ?? '') : 0.0;
                $isPreSell   = mb_strpos(trim((string)($ad['transaction_type'] ?? '')), 'پیش') === 0;
                $priceLine   = '';
                $basePrice   = 0.0; // مبنای «قیمت هر متر» (فروش/پیش‌فروش)
                if ($sellNum > 0) {
                    $priceLine = '💰 قیمت فروش: ' . $money($sellNum);
                    $basePrice = $sellNum;
                } elseif ($totalNum > 0) {
                    $priceLine = ($isPreSell ? '💰 قیمت کل (پیش‌فروش): ' : '💰 قیمت کل: ') . $money($totalNum);
                    $basePrice = $totalNum;
                } elseif ($fullRentNum > 0) {
                    // راند ۱۸: اگر کلید اختصاصی «رهن کامل» روشن است، خطِ جداگانه
                    // پایین چاپ می‌شود و اینجا تکرار نمی‌کنیم
                    if (!$on('full_rent')) {
                        $priceLine = '💰 اجاره کامل: ' . $money($fullRentNum);
                    }
                } elseif ($depositNum > 0 || $rentNum > 0) {
                    if (!$on('deposit') && !$on('rent_monthly')) {
                        $priceLine = '💰 ودیعه: ' . $money($depositNum) . ' | اجاره: ' . $money($rentNum);
                    }
                } elseif ($displayNum > 0) {
                    // راند ۲۲: چارهٔ آخر — اگر قیمت فقط در display_price ذخیره شده
                    $priceLine = '💰 قیمت: ' . $money($displayNum);
                    $basePrice = $displayNum;
                }
                if ($priceLine !== '') {
                    $lines[] = $priceLine;
                }
                // قیمت هر متر مربع (راند ۲۲ — فقط فروش/پیش‌فروش با متراژ معتبر)
                $areaNum = office_pub_price_to_num($ad['area'] ?? '');
                if ($on('price_per_meter') && $basePrice > 0 && $areaNum > 0) {
                    $lines[] = '💹 قیمت هر متر: ' . $money($basePrice / $areaNum);
                }
                // پیش‌پرداخت و شرایط پرداختِ پیش‌فروش (راند ۲۲)
                $downNum = office_pub_price_to_num($ad['down_payment'] ?? '');
                if ($on('down_payment') && $downNum > 0) {
                    $lines[] = '💳 پیش‌پرداخت: ' . $money($downNum);
                }
                if ($on('down_payment') && !empty($ad['payment_terms'])) {
                    $lines[] = '📋 شرایط پرداخت: ' . $esc($ad['payment_terms']);
                }
                // ملک وام‌دار: قیمت دوم = (قیمت منهای مبلغ وام) + وام
                if (!empty($loan['has']) && $priceLine !== '' && !empty($loan['net'])) {
                    $lines[] = '💵 نقد + وام: ' . $money($loan['net']) . ' + ' . $money($loan['amount']) . ' وام';
                } elseif (!empty($loan['has']) && $priceLine !== '') {
                    $lines[] = '🏦 این ملک ' . $money($loan['amount']) . ' وام دارد (از قیمت کسر می‌شود)';
                }
            } else {
                $lines[] = '💰 قیمت: توافقی (تماس بگیرید)';
            }
        }

        // خطوط جداگانهٔ رهن و اجاره (راند ۱۸)
        if ($on('full_rent') && !empty($ad['full_rent_enabled']) && !empty($ad['full_rent']) && (float)$ad['full_rent'] > 0) {
            $lines[] = '🔑 رهن کامل: ' . $money($ad['full_rent']);
        }
        if ($on('deposit') && !empty($ad['deposit']) && (float)$ad['deposit'] > 0) {
            $lines[] = '💵 رهن (ودیعه): ' . $money($ad['deposit']);
        }
        if ($on('rent_monthly') && !empty($ad['rent_monthly']) && (float)$ad['rent_monthly'] > 0) {
            $lines[] = '🗓️ اجارهٔ ماهانه: ' . $money($ad['rent_monthly']);
        }

        // مشخصات کامل وام (نوع/بانک/مدت/قسط/اقساط پرداخت‌شده/توضیحات)
        if ($on('loan') && !empty($loan['has'])) {
            $loanParts = [];
            if (!empty($loan['type']))     { $loanParts[] = 'نوع: ' . $esc($loan['type']); }
            if (!empty($loan['bank']))     { $loanParts[] = 'بانک: ' . $esc($loan['bank']); }
            if (!empty($loan['duration'])) { $loanParts[] = 'مدت: ' . $esc($loan['duration']); }
            $installmentMoney = $money($loan['installment']);
            if ($installmentMoney !== '')  { $loanParts[] = 'قسط: ' . $installmentMoney; }
            if (!empty($loan['paid']))     { $loanParts[] = 'اقساط پرداخت‌شده: ' . $esc($loan['paid']); }
            $lines[] = '🏦 وام: ' . $money($loan['amount']) . ($loanParts ? ' | ' . implode(' | ', $loanParts) : '');
            if (!empty($loan['notes'])) {
                $lines[] = '📄 توضیحات وام: ' . $esc($loan['notes']);
            }
        }

        // نوع سند و توضیحات سند (راند ۱۴)
        if ($on('deed') && !empty($ad['deed_type'])) {
            $lines[] = '📜 سند: ' . $esc($ad['deed_type']);
            if (!empty($ad['deed_notes'])) {
                $lines[] = '📄 توضیحات سند: ' . $esc($ad['deed_notes']);
            }
        }

        // تمایل به معاوضه + گزینه‌های انتخابی (راند ۱۴)
        if ($on('exchange') && !empty($ad['exchange_interested'])) {
            $exParts = [];
            if (!empty($ad['exchange_types'])) {
                $exArr = array_values(array_filter(array_map('trim', explode(',', (string)$ad['exchange_types'])), static fn($x) => $x !== ''));
                if ($exArr) {
                    $exParts[] = $esc(implode('، ', $exArr));
                }
            }
            if (!empty($ad['exchange_with'])) {
                $exParts[] = $esc($ad['exchange_with']);
            }
            $lines[] = '🔄 مایل به معاوضه' . ($exParts ? ': ' . implode(' — ', $exParts) : '');
        }

        if ($on('description') && !empty($ad['description'])) {
            $lines[] = '';
            $lines[] = '📝 ' . $esc($ad['description']);
        }

        if ($on('contact') || ($on('phone') && !empty($ad['phone']))) {
            $lines[] = '';
        }
        if ($on('contact')) {
            $lines[] = '👤 تماس: ' . $esc($ad['last_name'] ?: '-');
        }
        if ($on('phone') && !empty($ad['phone'])) {
            $lines[] = '📞 شماره تماس: ' . $esc($ad['phone']);
        }
        if ($on('consultant')) {
            $cName = '';
            $cPhone = '';
            if (!function_exists('findConsultantForAd') && is_file(__DIR__ . '/consultant_helper.php')) {
                require_once __DIR__ . '/consultant_helper.php';
            }
            if (function_exists('findConsultantForAd')) {
                $cRow = findConsultantForAd((string) ($ad['property_type'] ?? ''), (string) ($ad['transaction_type'] ?? ''));
                $cDisp = function_exists('getConsultantDisplayData') ? getConsultantDisplayData($cRow) : null;
                if (is_array($cDisp)) {
                    $cName = trim((string) ($cDisp['name'] ?? ''));
                    $cPhone = trim((string) ($cDisp['phone'] ?? ''));
                }
            }
            if ($cPhone === '' && function_exists('getConsultantPhone')) {
                $cPhone = trim((string) getConsultantPhone());
            }
            if ($cName === '' && function_exists('getConsultantName')) {
                $cName = trim((string) getConsultantName());
            }
            if ($cName !== '' || $cPhone !== '') {
                $consultLine = $cName;
                if ($cName !== '' && $cPhone !== '') {
                    $consultLine .= ' - ';
                }
                $consultLine .= $cPhone;
                $lines[] = '☎️ مشاور ملکینو: ' . $esc($consultLine);
            }
        }

        if ($on('ad_id')) {
            $lines[] = '';
            $lines[] = '🔗 کد آگهی: ' . $esc($ad['id']);
        }

        // لینک مستقیم همان آگهی روی سایت (ادمین از تب «ربات و کانال»
        // روشن/خاموشش می‌کند). اگر HOST در دسترس نباشد (مثلاً اجرای CLI)
        // خط لینک ساده حذف می‌شود تا پیام خراب نشود.
        if ($on('ad_link')) {
            $adUrl = function_exists('office_pub_ad_url') ? office_pub_ad_url($ad) : '';
            if ($adUrl !== '') {
                $lines[] = '🌐 لینک آگهی: ' . $adUrl;
            }
        }

        if ($pubFooter !== '') {
            $lines[] = '';
            $lines[] = $esc($pubFooter);
        }

        // خط‌های خالیِ اضافه (ناشی از خاموش‌بودن فیلدها) جمع می‌شوند
        $text = implode("\n", $lines);
        $text = (string)preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}

if (!function_exists('office_pub_ad_url')) {
if (!function_exists('office_pub_ad_url')) {
    function office_pub_ad_url(array $ad): string
    {
        if (empty($ad['id'])) {
            return '';
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        $scheme = $isHttps ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');

        if ($host === '') {
            return '';
        }

        return $scheme . '://' . $host . '/property-details.php?id=' . rawurlencode((string)$ad['id']);
    }
}
}

if (!function_exists('office_pub_loan_info')) {
    function office_pub_loan_info(array $ad): array
    {
        $none = [
            'has' => false, 'amount' => 0.0, 'price' => 0.0, 'net' => 0.0,
            'type' => '', 'duration' => '', 'bank' => '', 'installment' => '',
            'paid' => '', 'notes' => '',
        ];

        // تبدیل رقم‌های فارسی/عربی و حذف جداکننده‌ها
        $toNum = static function ($value): float {
            $s = str_replace(
                ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٬','،',',',' ','تومان'],
                ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','','','','','','',''],
                trim((string)$value)
            );
            return is_numeric($s) ? (float)$s : 0.0;
        };

        if (empty($ad['has_loan'])) {
            return $none;
        }

        $amount = $toNum($ad['loan_amount'] ?? '');
        if ($amount <= 0) {
            return $none;
        }

        $tx = trim((string)($ad['transaction_type'] ?? ''));
        $price = 0.0;
        if ($tx === 'پیش فروش') {
            $price = $toNum($ad['total_price'] ?? '');
        } else {
            // وام فقط برای فروش/پیش‌فروش معنا دارد؛ بقیه معامله‌ها نادیده گرفته می‌شوند
            if ($tx !== '' && $tx !== 'فروش') {
                return $none;
            }
            $price = $toNum($ad['price_sell'] ?? '');
        }

        $net = $price - $amount;
        if ($price <= 0 || $net <= 0) {
            // قیمت معتبر نیست یا وام از قیمت بیشتر است؛ فقط مبلغ وام را نشان بده
            $net = 0.0;
        }

        return [
            'has'         => true,
            'amount'      => $amount,
            'price'       => $price,
            'net'         => $net,
            'type'        => trim((string)($ad['loan_type'] ?? '')),
            'duration'    => trim((string)($ad['loan_duration'] ?? '')),
            'bank'        => trim((string)($ad['loan_bank'] ?? '')),
            'installment' => trim((string)($ad['loan_installment'] ?? '')),
            'paid'        => trim((string)($ad['loan_installments_paid'] ?? '')),
            'notes'       => trim((string)($ad['loan_notes'] ?? '')),
        ];
    }
}
