<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه ربات‌ها و پیامک (مرحله ۳۲)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ admin-bots.php سایت: تنظیمات (با ماسک توکن‌ها)،
 * تست‌های اتصال تلگرام/بله/ایتا/کانال/پیامک، و تنظیمات انتشار آگهی
 * در کانال + پیش‌نمایش متن.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_sms.php';
require_once __DIR__ . '/_publish.php';

if (!function_exists('office_bt_boot')) {
    function office_bt_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
    }
}

if (!function_exists('office_bt_masked')) {
    /** @return array<string,string> عین اکشن get سایت (توکن‌ها ماسک). */
    function office_bt_masked(): array
    {
        $settings = melkinoBotSettings();
        foreach (['telegram_token', 'bale_token', 'eitaa_token', 'sms_api_key', 'sms_password'] as $k) {
            if (!empty($settings[$k])) {
                $settings[$k . '_masked'] = substr($settings[$k], 0, 6) . '••••••' . substr($settings[$k], -4);
            } else {
                $settings[$k . '_masked'] = '';
            }
            unset($settings[$k]);
        }
        return $settings;
    }
}

if (!function_exists('office_bt_save')) {
    /** @return array{0:bool,1:string} */
    function office_bt_save(array $data): array
    {
        try {
            $ok = melkinoSaveBotSettings($data);
        } catch (Throwable $e) {
            $ok = false;
        }
        return $ok ? [true, 'تنظیمات ربات‌ها ذخیره شد.'] : [false, 'ذخیره‌سازی ناموفق بود.'];
    }
}

if (!function_exists('office_bt_test_telegram')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_telegram(): array
    {
        $r = melkinoTestTelegramConnection();
        return [!empty($r['success']), (string)($r['message'] ?? '') . (!empty($r['username']) ? ' (@' . $r['username'] . ')' : '')];
    }
}

if (!function_exists('office_bt_test_bale')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_bale(): array
    {
        $r = melkinoTestBaleConnection();
        return [!empty($r['success']), (string)($r['message'] ?? '') . (!empty($r['username']) ? ' (@' . $r['username'] . ')' : '')];
    }
}

if (!function_exists('office_bt_test_eitaa')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_eitaa(): array
    {
        $r = melkinoTestEitaaConnection();
        return [!empty($r['success']), (string)($r['message'] ?? '')];
    }
}

if (!function_exists('office_bt_test_channel')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_channel(string $channel): array
    {
        $channel = trim($channel);
        if ($channel === '') {
            return [false, 'شناسه کانال وارد نشده است.'];
        }
        $token = melkinoTelegramToken();
        if ($token === '') {
            return [false, 'ابتدا توکن تلگرام را ذخیره کن.'];
        }
        $url = 'https://api.telegram.org/bot' . $token . '/getChat?chat_id=' . urlencode($channel);
        $response = function_exists('melkinoHttpPost') ? melkinoHttpPost($url, '') : @file_get_contents($url);
        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            $desc = is_array($decoded) ? ($decoded['description'] ?? 'پاسخ نامعتبر') : 'ارتباط برقرار نشد';
            return [false, 'کانال: ' . $desc];
        }
        $title = (string)(($decoded['result']['title'] ?? '') !== '' ? $decoded['result']['title'] : $channel);
        return [true, 'دسترسی به کانال «' . $title . '» برقرار است.'];
    }
}

if (!function_exists('office_bt_test_bale_channel')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_bale_channel(string $channel): array
    {
        $channel = trim($channel);
        $placeholders = ['', '@آیدی_کانال', 'آیدی_کانال', '@', '-', '0'];
        if (in_array($channel, $placeholders, true)) {
            return [false, 'شناسه کانال بله وارد نشده است. شناسه را وارد و ذخیره کن.'];
        }
        $token = function_exists('melkinoBaleToken') ? (string)melkinoBaleToken() : '';
        if ($token === '' || in_array($token, ['توکن_ربات_بله'], true)) {
            return [false, 'ابتدا توکن ربات بله را وارد و ذخیره کن.'];
        }
        $url = 'https://tapi.bale.ai/bot' . $token . '/getChat?chat_id=' . urlencode($channel);
        $response = function_exists('melkinoHttpPost') ? melkinoHttpPost($url, '') : @file_get_contents($url);
        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            $desc = is_array($decoded) ? ($decoded['description'] ?? 'پاسخ نامعتبر') : 'ارتباط برقرار نشد';
            return [false, 'کانال بله: ' . $desc];
        }
        $title = (string)(($decoded['result']['title'] ?? '') !== '' ? $decoded['result']['title'] : $channel);
        return [true, 'دسترسی به کانال بله «' . $title . '» برقرار است.'];
    }
}

if (!function_exists('office_bt_test_sms')) {
    /** @return array{0:bool,1:string} */
    function office_bt_test_sms(string $phone): array
    {
        $phone = strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $phone = preg_replace('/\D/', '', $phone);
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return [false, 'شماره موبایل معتبر نیست (فرمت درست: 09123456789).'];
        }
        $result = smsSendText($phone, 'تست پنل پیامک ملکینو ✅');
        return [!empty($result['success']), (string)($result['message'] ?? '')];
    }
}

if (!function_exists('office_bt_publish_save')) {
    /** @return array{0:bool,1:string} */
    function office_bt_publish_save(string $platform, array $data): array
    {
        $platform = melkinoPublishPlatform($platform);
        try {
            $ok = melkinoSavePublishSettings($platform, $data);
        } catch (Throwable $e) {
            $ok = false;
        }
        return $ok ? [true, 'تنظیمات انتشار ذخیره شد.'] : [false, 'ذخیره تنظیمات انتشار ناموفق بود.'];
    }
}

if (!function_exists('office_bt_sample_ad')) {
    function office_bt_sample_ad(string $pType, string $pTrans): array
    {
        $ad = [
            'id' => 'AD-0000-0000',
            'title' => 'آپارتمان ۱۲۰ متری در مرکز شهر',
            'transaction_type' => $pTrans !== '' ? $pTrans : 'فروش',
            'property_type' => $pType !== '' ? $pType : 'آپارتمان',
            'location' => 'خیابان امام',
            'address' => 'خیابان امام، کوچه ۵',
            'area' => '120',
            'rooms' => '3',
            'floor' => '2',
            'year' => '1398',
            'price_sell' => '2800000000',
            'description' => 'آپارتمانی نورگیر با دسترسی عالی.',
            'last_name' => 'نام نمونه',
            'phone' => '09123456789',
        ];
        $ad['deed_type'] = 'طلق';
        $ad['deed_notes'] = '';
        $ad['exchange_interested'] = 1;
        $ad['exchange_types'] = 'آپارتمان,خودرو';
        $ad['exchange_with'] = '';
        $__pdSamples = [
            'آپارتمان' => ['area' => '120', 'floor' => '2', 'rooms' => '3', 'year' => '1398', 'flooring' => 'سرامیک', 'cabinet' => 'MDF', 'cooling' => 'اسپیلیت', 'heating' => 'پکیج', 'total_units' => '5'],
            'ویلا' => ['land_area' => '250', 'area' => '180', 'rooms' => '3', 'year' => '1399', 'flooring' => 'سرامیک', 'cabinet' => 'MDF', 'cooling' => 'اسپیلیت', 'heating' => 'پکیج'],
            'زمین' => ['land_area' => '212', 'land_type' => 'مسکونی', 'land_width' => '11.5', 'land_length' => '18.5', 'land_front_width' => '11.5', 'land_blocks' => '1', 'land_direction' => 'شمالی', 'land_shape' => 'مستطیل', 'land_deed_status' => 'دارد', 'land_deed_type' => 'تک‌برگ', 'land_setback_status' => 'ندارد', 'land_ownership' => 'شش‌دانگ'],
            'باغ' => ['garden_area' => '1000', 'tree_types' => 'گردو، بادام', 'tree_age' => '۸ سال', 'irrigation_type' => 'قطره‌ای', 'has_well' => '1', 'has_pond' => '0', 'has_building' => '1', 'building_area' => '60', 'document_type' => 'قولنامه'],
            'اداری' => ['office_area' => '90', 'office_floor' => '3', 'office_units_per_floor' => '4', 'office_rooms' => '2', 'office_year' => '1395', 'office_condition' => 'بازسازی‌شده', 'office_orientation' => 'جنوبی', 'office_usage' => 'دفتر کار'],
            'تجاری' => ['area' => '45', 'front' => '6', 'flooring' => 'سرامیک', 'wall' => 'رنگ روغن', 'cabinet' => 'ندارد', 'cooling' => 'اسپیلیت', 'heating' => 'برقی', 'location_type' => 'دوبر', 'location_features' => 'بر خیابان اصلی', 'jobs' => 'رستوران، کافه'],
        ];
        $__pdKey = (string)($ad['property_type'] ?? '');
        if (isset($__pdSamples[$__pdKey])) {
            $ad['property_details'] = json_encode($__pdSamples[$__pdKey], JSON_UNESCAPED_UNICODE);
        }
        $__sampleBase = [
            'زمین' => ['title' => 'زمین ۲۱۲ متری مسکونی', 'area' => null, 'rooms' => null, 'floor' => null, 'year' => null],
            'باغ' => ['title' => 'باغ ۱۰۰۰ متری با خانه باغ', 'area' => null, 'rooms' => null, 'floor' => null, 'year' => null],
            'اداری' => ['title' => 'واحد اداری ۹۰ متری', 'area' => null, 'rooms' => null, 'floor' => null, 'year' => null],
            'تجاری' => ['title' => 'مغازه ۴۵ متری دوبر', 'area' => '45', 'rooms' => null, 'floor' => null, 'year' => null],
            'ویلا' => ['title' => 'ویلای ۲۵۰ متری', 'area' => '180', 'rooms' => '3', 'floor' => null, 'year' => '1399'],
        ];
        if (isset($__sampleBase[$__pdKey])) {
            $ad = array_merge($ad, $__sampleBase[$__pdKey]);
        }
        if (in_array($ad['transaction_type'], ['رهن کامل'], true)) {
            $ad['price_sell'] = null;
            $ad['full_rent_enabled'] = 1;
            $ad['full_rent'] = '300000000';
        } elseif (in_array($ad['transaction_type'], ['اجاره'], true)) {
            $ad['price_sell'] = null;
            $ad['deposit'] = '150000000';
            $ad['rent_monthly'] = '12000000';
        } elseif (in_array($ad['transaction_type'], ['رهن و اجاره'], true)) {
            $ad['price_sell'] = null;
            $ad['deposit'] = '100000000';
            $ad['rent_monthly'] = '20000000';
        }
        if (in_array($ad['transaction_type'], ['فروش', 'پیش فروش', ''], true)) {
            $ad['total_price'] = '2800000000';
            $ad['has_loan'] = 1;
            $ad['loan_amount'] = '300000000';
            $ad['loan_type'] = 'وام مسکن';
            $ad['loan_duration'] = '۱۲ سال';
            $ad['loan_bank'] = 'بانک مسکن';
            $ad['loan_installment'] = '5000000';
            $ad['loan_installments_paid'] = '۲۴';
            $ad['loan_notes'] = 'وام قابل انتقال به خریدار است.';
        }
        return $ad;
    }
}

if (!function_exists('office_bt_preview')) {
    /** @return array{text:string,is_sample:bool,ad_title:string} */
    function office_bt_preview(PDO $pdo, string $platform, string $pType, string $pTrans): array
    {
        $platform = melkinoPublishPlatform($platform);
        $ad = null;
        try {
            if ($pType !== '' || $pTrans !== '') {
                $where = ["status = 'published'"];
                $params = [];
                if ($pType !== '') {
                    $where[] = 'property_type = ?';
                    $params[] = $pType;
                }
                if ($pTrans !== '') {
                    $where[] = 'transaction_type = ?';
                    $params[] = $pTrans;
                }
                $st = $pdo->prepare('SELECT * FROM ads WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC LIMIT 1');
                $st->execute($params);
                $ad = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            if (!$ad && $pType === '') {
                $st = $pdo->query("SELECT * FROM ads WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 1");
                $ad = $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: null) : null;
            }
        } catch (Throwable $e) {
            $ad = null;
        }
        $isSample = false;
        if (!$ad) {
            $isSample = true;
            $ad = office_bt_sample_ad($pType, $pTrans);
        } elseif ($pType !== '' || $pTrans !== '') {
            if ($pType !== '') {
                $ad['property_type'] = $pType;
            }
            if ($pTrans !== '') {
                $ad['transaction_type'] = $pTrans;
            }
        }
        $text = office_pub_ad_message($ad, $platform !== 'bale', $platform);
        return ['text' => $text, 'is_sample' => $isSample, 'ad_title' => (string)($ad['title'] ?? '')];
    }
}
