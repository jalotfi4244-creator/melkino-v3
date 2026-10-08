<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — موتور فرم‌های ثبت فایل (مرحله ۲)
 *--------------------------------------------------------------------------
 * قرارداد «عین ملکینو»:
 * - نام فیلدها (name=...) عین فرم‌های ثبت سایت است.
 * - گزینه‌های کمبو از همان کاتالوگ form-options.php خوانده می‌شود
 *   (یعنی اگر ادمین گزینه‌ها را عوض کند، دفتر هم همان را نشان می‌دهد).
 * - اعتبارسنجی و نگاشت POST→$newAd عین منطق register-*-legacy است.
 * - ذخیره با همان تابع savePropertyToDatabase انجام می‌شود.
 *
 * تفاوت‌های عمدی دفتر (فقط این‌ها):
 * - هویت مالک از فرم خوانده می‌شود (مشاور به نیابت از مالک ثبت می‌کند)
 *   و شناسه مشاور در consultant_id ذخیره می‌شود؛ به‌جای قفل پروفایل کاربر.
 * - وضعیت انتشار (published/pending) قابل انتخاب است.
 * - مختصات به‌صورت دستی وارد می‌شود (نقشه تصویری در مرحله نقشه می‌آید).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofRoot = dirname(__DIR__);
foreach (['form-options.php', 'map-lib.php', 'jalali-lib.php', 'property-db-helper.php'] as $__ofLib) {
    $__ofF = $__ofRoot . '/' . $__ofLib;
    if (is_file($__ofF)) {
        require_once $__ofF;
    }
}
unset($__ofRoot, $__ofLib, $__ofF);

if (!function_exists('office_register_types')) {
    /** @return array<string, array{label:string,ready:bool}> */
    function office_register_types(): array
    {
        return [
            'apartment' => ['label' => 'آپارتمان', 'ready' => true],
            'villa' => ['label' => 'ویلا', 'ready' => true],
            'land' => ['label' => 'زمین', 'ready' => true],
            'commercial' => ['label' => 'تجاری', 'ready' => true],
            'office' => ['label' => 'اداری', 'ready' => true],
            'garden' => ['label' => 'باغ', 'ready' => true],
            'partnership' => ['label' => 'مشارکت', 'ready' => true],
        ];
    }
}

if (!function_exists('office_combo_items')) {
    /** گزینه‌های یک کمبو از همان کاتالوگ سایت (با فالبک به پیش‌فرض‌ها). */
    function office_combo_items(string $key): array
    {
        if (function_exists('melkinoFormComboItems')) {
            try {
                $items = melkinoFormComboItems($key);
                if (is_array($items) && $items) {
                    return array_values($items);
                }
            } catch (Throwable $e) {
            }
        }
        if (function_exists('melkinoFormComboDefaults')) {
            $d = melkinoFormComboDefaults();
            if (isset($d[$key]['items']) && is_array($d[$key]['items'])) {
                return array_values($d[$key]['items']);
            }
        }
        return [];
    }
}

if (!function_exists('office_amenities_apt')) {
    /** امکانات آپارتمان — عین فهرست فرم سایت (تست parity آن را کنترل می‌کند). */
    function office_amenities_apt(): array
    {
        return ['آسانسور', 'پارکینگ', 'انباری', 'لابی', 'مطبخ', 'بالکن / تراس', 'حیاط اختصاصی', 'روف گاردن', 'لاندری روم', 'کلوزت', 'اتاق مستر', 'نگهبانی', 'استخر', 'سونا', 'جکوزی'];
    }
}

if (!function_exists('office_exchange_types')) {
    function office_exchange_types(): array
    {
        return ['آپارتمان', 'باغ', 'ویلایی', 'اداری', 'مغازه', 'زمین', 'خودرو'];
    }
}

if (!function_exists('office_apartment_sections')) {
    /**
     * تعریف فیلدهای آپارتمان، گروه‌بندی‌شده در سکشن‌ها.
     * هر فیلد: [name, label, kind, extra...]
     * kind: text|number|date|textarea|select|radio|check|checks|file
     * - select: ['combo' => key کاتالوگ] یا ['options' => [...]]
     * - radio: ['options' => [...]]
     * - checks: ['options' => [...]] (چندتایی با name[])
     */
    function office_apartment_sections(bool $forEdit = false): array
    {
        $statusOpts = $forEdit
            ? ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی']
            : ['published' => 'منتشر شود', 'pending' => 'پیش‌نویس / در انتظار'];
        return [
            'معامله و وضعیت' => [
                ['transaction_type', 'نوع معامله', 'radio', ['options' => ['فروش', 'اجاره', 'پیش فروش'], 'req' => true]],
                ['status', 'وضعیت انتشار', 'select', ['options' => $statusOpts, 'default' => 'published']],
                ['is_not_keyed', 'کلید نخورده', 'check', ['value' => '1']],
                ['is_vacant', 'تخلیه', 'check', ['value' => '1']],
            ],
            'مشخصات مالک' => [
                ['gender', 'جنسیت مالک', 'radio', ['options' => ['آقا', 'خانم'], 'req' => true]],
                ['last_name', 'نام مالک', 'text', ['req' => true, 'placeholder' => 'نام و نام خانوادگی مالک']],
                ['phone', 'موبایل مالک', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۰۹...']],
                ['telegram_id', 'آی‌دی تلگرام مالک (اختیاری)', 'text', ['ltr' => true, 'placeholder' => '@...']],
            ],
            'عنوان و موقعیت' => [
                ['title', 'عنوان آگهی', 'text', ['placeholder' => '۱۲۰ متری نوساز خ فردوسی']],
                ['location', 'موقعیت', 'text', ['placeholder' => 'خیابان بهار']],
                ['address', 'آدرس دقیق', 'text', ['placeholder' => 'خ بهار کوچه بیستم...']],
                ['map_lat', 'عرض جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۳۵.۷...']],
                ['map_lng', 'طول جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۵۱.۴...']],
            ],
            'مشخصات آپارتمان' => [
                ['area_apt', 'متراژ (متر)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۹۰']],
                ['floor', 'طبقه', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۳']],
                ['rooms_apt', 'تعداد اتاق', 'select', ['combo' => 'rooms', 'req' => true]],
                ['year_apt', 'سال ساخت', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۴۰۲']],
                ['building_age', 'سن بنا', 'text', ['readonly' => true, 'placeholder' => 'خودکار']],
                ['apartment_type', 'نوع آپارتمان', 'radio', ['options' => ['فلت', 'دوبلکس'], 'default' => 'فلت']],
                ['units_per_floor', 'تعداد واحد در طبقه', 'radio', ['combo' => 'units_per_floor']],
                ['total_units', 'تعداد کل واحدها', 'text', ['ltr' => true, 'placeholder' => '۲۰']],
                ['flooring_apt', 'پوشش کف', 'select', ['combo' => 'flooring']],
                ['cabinet_apt', 'نوع کابینت', 'select', ['combo' => 'cabinet']],
                ['cooling_apt', 'سیستم سرمایش', 'select', ['combo' => 'cooling']],
                ['heating_apt', 'سیستم گرمایش', 'select', ['combo' => 'heating']],
                ['amenities_apt', 'امکانات', 'checks', ['options_fn' => 'office_amenities_apt']],
            ],
            'قیمت' => [
                ['price_sell', 'قیمت فروش (تومان)', 'text', ['ltr' => true, 'placeholder' => '۲,۸۰۰,۰۰۰,۰۰۰', 'tx' => 'فروش']],
                ['price_condition', 'شرایط قیمت', 'pricecond', ['tx' => 'فروش']],
                ['deposit', 'ودیعه / رهن (تومان)', 'text', ['ltr' => true, 'placeholder' => '۵۰۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['rent_monthly', 'اجاره ماهانه (تومان)', 'text', ['ltr' => true, 'placeholder' => '۳۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['full_rent_enabled', 'رهن کامل', 'check', ['value' => '1', 'tx' => 'اجاره']],
                ['full_rent', 'مبلغ رهن کامل (تومان)', 'text', ['ltr' => true, 'placeholder' => '۱,۰۰۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['total_price', 'قیمت کل پیش‌فروش (تومان)', 'text', ['ltr' => true, 'placeholder' => '۳,۰۰۰,۰۰۰,۰۰۰', 'tx' => 'پیش فروش']],
                ['down_payment', 'پیش‌پرداخت (تومان)', 'text', ['ltr' => true, 'placeholder' => '۹۰۰,۰۰۰,۰۰۰', 'tx' => 'پیش فروش']],
                ['payment_terms', 'شرایط پرداخت', 'textarea', ['placeholder' => 'مثال: ۳۰٪ قرارداد، ۲۰٪ اسکلت...', 'tx' => 'پیش فروش']],
            ],
            'وام' => [
                ['has_loan', 'وام دارد', 'check', ['value' => '1']],
                ['loan_amount', 'مبلغ وام (تومان)', 'text', ['ltr' => true, 'placeholder' => '۳۰۰,۰۰۰,۰۰۰']],
                ['loan_type', 'نوع وام', 'text', ['placeholder' => 'مثلاً: وام مسکن / اوراق']],
                ['loan_duration', 'مدت وام', 'text', ['placeholder' => 'مثلاً: ۱۲ سال']],
                ['loan_bank', 'بانک', 'text', ['placeholder' => 'مثلاً: بانک مسکن']],
                ['loan_installment', 'مبلغ قسط (تومان)', 'text', ['ltr' => true, 'placeholder' => '۵,۰۰۰,۰۰۰']],
                ['loan_installments_paid', 'اقساط پرداخت‌شده', 'text', ['ltr' => true, 'placeholder' => 'مثلاً: ۲۴']],
                ['loan_notes', 'توضیحات وام', 'textarea', ['placeholder' => 'توضیحات بیشتر دربارهٔ وام (اختیاری)']],
            ],
            'سند و معاوضه' => [
                ['deed_type', 'نوع سند', 'radio', ['combo' => 'deed_type']],
                ['deed_notes', 'توضیحات سند', 'textarea', ['placeholder' => 'اگر سند نیازمند توضیح است در اینجا بنویسید']],
                ['exchange_interested', 'مایل به معاوضه', 'check', ['value' => '1']],
                ['exchange_types', 'معاوضه با', 'checks', ['options_fn' => 'office_exchange_types']],
                ['exchange_with', 'شرایط معاوضه', 'textarea', ['placeholder' => 'مثلاً: معاوضه با آپارتمان بزرگتر']],
            ],
            'بازدید و تحویل' => [
                ['visit_hours', 'ساعات بازدید', 'textarea', ['placeholder' => 'مثال: فقط عصرها میشه بازدید کرد']],
                ['delivery_date', 'تاریخ تحویل', 'text', ['placeholder' => '۱۴۰۵/۰۲/۱۵']],
                ['vacancy_date', 'تاریخ تخلیه', 'date', []],
            ],
            'عکس‌ها' => [
                ['images', 'انتخاب عکس‌ها', 'file', ['multiple' => true, 'note' => 'حداکثر ۵ مگابایت برای هر عکس — JPG/PNG/WebP/GIF']],
                ['publish_photos', 'انتشار عکس‌ها در سایت', 'radio', ['options' => ['yes' => 'بله', 'no' => 'خیر'], 'default' => 'yes']],
            ],
            'توضیحات' => [
                ['full_description', 'شرح کامل', 'textarea', ['placeholder' => 'شرح کامل امکانات و شرایط ملک...']],
            ],
        ];
    }
}

if (!function_exists('office_render_field')) {
    function office_render_field(array $f, array $values): string
    {
        [$name, $label, $kind] = [$f[0], $f[1], $f[2]];
        $o = $f[3] ?? [];
        $rawVal = $values[$name] ?? ($o['default'] ?? '');
        $val = is_array($rawVal) ? '' : (string)$rawVal;
        $isMoney = ($kind === 'text' && in_array($name, office_money_fields(), true));
        if ($isMoney && $val !== '') {
            $val = office_group_digits($val);
        }
        $req = !empty($o['req']) ? ' <b style="color:var(--danger)">*</b>' : '';
        $ltr = !empty($o['ltr']) ? ' dir="ltr" style="text-align:left"' : '';
        $ro = !empty($o['readonly']) ? ' readonly tabindex="-1"' : '';
        $ph = isset($o['placeholder']) ? ' placeholder="' . office_h((string)$o['placeholder']) . '"' : '';
        $tx = isset($o['tx']) ? ' data-tx="' . office_h((string)$o['tx']) . '"' : '';
        $h = '<div class="of-field"' . $tx . '><label>' . office_h($label) . $req . '</label>';

        $opts = [];
        if (isset($o['options']) && is_array($o['options'])) {
            $opts = $o['options'];
        } elseif (isset($o['combo'])) {
            $opts = office_combo_items((string)$o['combo']);
        } elseif (isset($o['options_fn']) && function_exists((string)$o['options_fn'])) {
            $opts = (array)((string)$o['options_fn'])();
        }
        // لیستِ ساده یا نگاشت مقدار=>برچسب؟ (کلید عددیِ رشته‌ای مثل '0' در PHP به int تبدیل می‌شود؛ is_int کافی نیست)
        $optList = function_exists('array_is_list') ? array_is_list($opts) : array_keys($opts) === range(0, count($opts) - 1);

        switch ($kind) {
            case 'textarea':
                $h .= '<textarea name="' . $name . '"' . $ph . ' rows="3">' . office_h($val) . '</textarea>';
                break;
            case 'select':
                $h .= '<select name="' . $name . '"><option value="">انتخاب کنید</option>';
                foreach ($opts as $k => $v) {
                    $vv = $optList ? (string)$v : (string)$k;
                    $ll = (string)$v;
                    $sel = ($val !== '' && $val === $vv) ? ' selected' : '';
                    $h .= '<option value="' . office_h($vv) . '"' . $sel . '>' . office_h($ll) . '</option>';
                }
                $h .= '</select>';
                break;
            case 'radio':
                foreach ($opts as $k => $v) {
                    $vv = $optList ? (string)$v : (string)$k;
                    $ll = (string)$v;
                    $chk = ($val !== '' && $val === $vv) ? ' checked' : '';
                    $h .= '<label class="of-radio"><input type="radio" name="' . $name . '" value="' . office_h($vv) . '"' . $chk . '> ' . office_h($ll) . '</label> ';
                }
                break;
            case 'pricecond':
                // عین سایت: دو چک‌باکس mutually-exclusive با همان نام و مقادیر
                $h .= '<label class="of-radio"><input type="checkbox" name="price_condition" value="negotiable"' . ($val !== 'fixed' ? ' checked' : '') . ' onchange="ofPriceCond(this,\'fixed\')"> قابل مذاکره</label> ';
                $h .= '<label class="of-radio"><input type="checkbox" name="price_condition" value="fixed"' . ($val === 'fixed' ? ' checked' : '') . ' onchange="ofPriceCond(this,\'negotiable\')"> مقطوع</label>';
                break;
            case 'check':
                $vv = (string)($o['value'] ?? '1');
                $chk = ($val === $vv || $val === '1' && $vv === '1') ? ' checked' : '';
                $h .= '<label class="of-radio"><input type="checkbox" name="' . $name . '" value="' . office_h($vv) . '"' . $chk . '> ' . office_h($label) . '</label>';
                break;
            case 'checks':
                $sel = $values[$name] ?? [];
                if (!is_array($sel)) {
                    $sel = [];
                }
                $h .= '<div class="of-checks">';
                foreach ($opts as $v) {
                    $v = (string)$v;
                    $chk = in_array($v, $sel, true) ? ' checked' : '';
                    $h .= '<label class="of-radio"><input type="checkbox" name="' . $name . '[]" value="' . office_h($v) . '"' . $chk . '> ' . office_h($v) . '</label> ';
                }
                $h .= '</div>';
                break;
            case 'file':
                $mult = !empty($o['multiple']) ? ' multiple' : '';
                $fname = !empty($o['single']) ? $name : $name . '[]';
                $accept = !empty($o['accept']) ? (string)$o['accept'] : '.jpg,.jpeg,.png,.webp,.gif';
                $h .= '<input type="file" name="' . $fname . '"' . $mult . ' accept="' . $accept . '">';
                if (!empty($o['note'])) {
                    $h .= '<p class="of-muted">' . office_h((string)$o['note']) . '</p>';
                }
                break;
            case 'date':
                $h .= '<input type="date" name="' . $name . '" value="' . office_h($val) . '">';
                break;
            default:
                $h .= '<input type="text" name="' . $name . '" value="' . office_h($val) . '"' . $ph . $ltr . $ro . ($isMoney ? ' data-money="1" inputmode="numeric"' : '') . '>';
        }
        return $h . '</div>';
    }
}

if (!function_exists('office_to_num')) {
    /** همان نرمالایزر عددی فرم سایت (ارقام فارسی/عربی + جداکننده‌ها). */
    function office_to_num(mixed $value): float
    {
        $s = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٬', '،', ',', ' '],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', '', '', ''],
            trim((string)$value)
        );
        return is_numeric($s) ? (float)$s : 0.0;
    }
}

if (!function_exists('office_money_fields')) {
    /** فیلدهای مبلغی فرم‌های ملک — مشمول قانون جداکنندهٔ سه‌رقمی. */
    function office_money_fields(): array
    {
        return ['price_sell', 'deposit', 'rent_monthly', 'full_rent', 'total_price', 'down_payment', 'loan_amount', 'loan_installment'];
    }
}

if (!function_exists('office_store_images')) {
    /**
     * ذخیره عکس‌های فرم با همان قرارداد فرم سایت.
     * @return array{0: string[], 1: string[]} [paths, errors]
     */
    function office_store_images(mixed $filesEntry, string $adId): array
    {
        $paths = [];
        $errors = [];
        $targetDir = defined('OFFICE_TEST_UPLOAD_DIR')
            ? rtrim((string)OFFICE_TEST_UPLOAD_DIR, '/') . '/'
            : (dirname(__DIR__) . '/uploads/');
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        if (!is_array($filesEntry) || !isset($filesEntry['name']) || !is_array($filesEntry['name'])) {
            return [[], []];
        }
        $count = count($filesEntry['name']);
        $anyFile = false;
        for ($i = 0; $i < $count; $i++) {
            if (!empty($filesEntry['name'][$i])) {
                $anyFile = true;
                break;
            }
        }
        if (!$anyFile) {
            return [[], []];
        }
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        if (function_exists('melkinoProtectUploadDir') && is_dir($targetDir)) {
            try {
                melkinoProtectUploadDir($targetDir);
            } catch (Throwable $e) {
            }
        }
        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            return [[], ['پوشه آپلود در دسترس نیست.']];
        }
        for ($i = 0; $i < $count; $i++) {
            $orig = (string)($filesEntry['name'][$i] ?? '');
            if ($orig === '') {
                continue;
            }
            if (($filesEntry['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = $orig . ': کد خطای آپلود ' . (int)($filesEntry['error'][$i] ?? -1);
                continue;
            }
            if ((int)($filesEntry['size'][$i] ?? 0) > $maxSize) {
                $errors[] = $orig . ': حجم فایل بیش از ۵ مگابایت است.';
                continue;
            }
            $tmp = (string)($filesEntry['tmp_name'][$i] ?? '');
            if ($tmp === '' || !is_file($tmp)) {
                $errors[] = $orig . ': فایل موقت یافت نشد.';
                continue;
            }
            $mime = '';
            if (function_exists('finfo_open')) {
                try {
                    $fi = finfo_open(FILEINFO_MIME_TYPE);
                    if ($fi) {
                        $mime = (string)finfo_file($fi, $tmp);
                        finfo_close($fi);
                    }
                } catch (Throwable $e) {
                }
            }
            $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($mime, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
                $errors[] = $orig . ': فرمت تصویر مجاز نیست.';
                continue;
            }
            $type = 0;
            if (function_exists('melkinoImageTypeOf')) {
                try {
                    $probe = melkinoImageTypeOf($tmp);
                    $type = (int)($probe['type'] ?? 0);
                } catch (Throwable $e) {
                }
            }
            if ($type === 0 && function_exists('getimagesize')) {
                $gi = @getimagesize($tmp);
                $type = $gi ? (int)($gi[2] ?? 0) : 0;
            }
            if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                $errors[] = $orig . ': فایل یک تصویر معتبر نیست.';
                continue;
            }
            $fileName = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $adId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $ext);
            $dest = $targetDir . $fileName;
            $storedOk = false;
            if (function_exists('melkinoReencodeImage')) {
                try {
                    $storedOk = (bool)melkinoReencodeImage($tmp, $dest, $type);
                } catch (Throwable $e) {
                }
            }
            if (!$storedOk && is_uploaded_file($tmp)) {
                $storedOk = move_uploaded_file($tmp, $dest);
            }
            if (!$storedOk) {
                // تست CLI (فایل واقعی ولی نه HTTP-upload) + نبود GD
                try {
                    $storedOk = @copy($tmp, $dest);
                } catch (Throwable $e) {
                }
            }
            if ($storedOk) {
                $paths[] = defined('OFFICE_TEST_UPLOAD_DIR') ? $dest : ('uploads/' . $fileName);
            } else {
                $errors[] = $orig . ': ذخیره فایل روی سرور ناموفق بود.';
            }
        }
        return [$paths, $errors];
    }
}

if (!function_exists('office_collect_apartment')) {
    /**
     * نگاشت POST→$newAd عین register-apartment-legacy (به‌جز هویت/وضعیت دفتر).
     * @return array{0: array, 1: string[], 2: string[]} [$newAd, $errors, $uploadErrors]
     */
    function office_collect_apartment(array $post, mixed $filesEntry, int $adminId): array
    {
        $errors = [];
        // قانون مبالغ: جداکننده‌های نمایشی حذف تا عین سایتِ بدون‌کاما ذخیره شود
        foreach (office_money_fields() as $mk) {
            if (isset($post[$mk]) && is_string($post[$mk])) {
                $post[$mk] = str_replace([',', '٬', '،', ' '], '', $post[$mk]);
            }
        }
        $g = static fn(string $k, string $d = ''): string => trim((string)($post[$k] ?? $d));

        // --- هویت دفتر: مالک از فرم، مشاور از سشن ---
        $gender = $g('gender');
        $last_name = $g('last_name');
        $phone = $g('phone');
        $telegram_id = $g('telegram_id');
        $ownerUserId = 0;

        $transaction_type = $g('transaction_type', 'فروش');
        if (!in_array($transaction_type, ['فروش', 'اجاره', 'پیش فروش'], true)) {
            $transaction_type = 'فروش';
        }
        $property_type = 'آپارتمان';
        $status = $g('status', 'published');
        if (!in_array($status, ['published', 'pending'], true)) {
            $status = 'published';
        }

        $title = $g('title');
        $location = $g('location');
        $address = $g('address');
        $description = $g('full_description');
        $publish_photos = $g('publish_photos', 'yes');
        if (!in_array($publish_photos, ['yes', 'no'], true)) {
            $publish_photos = 'yes';
        }

        // --- قیمت بر اساس نوع معامله (عین سایت) ---
        $price_sell = '0';
        $price_condition = '';
        $deposit = '0';
        $rent_monthly = '0';
        $full_rent_enabled = '0';
        $full_rent = '0';
        $total_price = '0';
        $down_payment = '0';
        $payment_terms = '';
        $exchange_interested = isset($post['exchange_interested']) ? '1' : '0';
        $exchange_with = $g('exchange_with');
        $visit_hours = $g('visit_hours');
        $delivery_date = $g('delivery_date');
        $vacancy_date = $g('vacancy_date');
        $is_vacant = isset($post['is_vacant']) ? '1' : '0';

        if ($transaction_type === 'فروش') {
            $price_sell = $g('price_sell', '0');
            $price_condition = isset($post['price_condition']) ? (string)$post['price_condition'] : 'negotiable';
        } elseif ($transaction_type === 'اجاره') {
            $deposit = $g('deposit', '0');
            $rent_monthly = $g('rent_monthly', '0');
            $full_rent_enabled = isset($post['full_rent_enabled']) ? '1' : '0';
            $full_rent = $g('full_rent', '0');
        } elseif ($transaction_type === 'پیش فروش') {
            $total_price = $g('total_price', '0');
            $down_payment = $g('down_payment', '0');
            $payment_terms = $g('payment_terms');
        }

        // --- وام (عین سایت) ---
        $has_loan = '0';
        $loan_amount = '0';
        $loan_type = '';
        $loan_duration = '';
        $loan_bank = '';
        $loan_installment = '0';
        $loan_installments_paid = '';
        $loan_notes = '';
        if (($transaction_type === 'فروش' || $transaction_type === 'پیش فروش') && isset($post['has_loan'])) {
            $has_loan = '1';
            $loan_amount = $g('loan_amount', '0');
            $loan_type = $g('loan_type');
            $loan_duration = $g('loan_duration');
            $loan_bank = $g('loan_bank');
            $loan_installment = $g('loan_installment', '0');
            $loan_installments_paid = $g('loan_installments_paid');
            $loan_notes = $g('loan_notes');
        }

        // --- سند و معاوضه (عین سایت) ---
        $deed_type = $g('deed_type');
        $deed_notes = $g('deed_notes');
        $exchange_types = '';
        if (!empty($post['exchange_types']) && is_array($post['exchange_types'])) {
            $picked = [];
            foreach ($post['exchange_types'] as $x) {
                $x = trim((string)$x);
                if (in_array($x, office_exchange_types(), true) && !in_array($x, $picked, true)) {
                    $picked[] = $x;
                }
            }
            $exchange_types = implode(',', $picked);
        }
        if ($exchange_interested !== '1') {
            $exchange_types = '';
        }

        // --- اعتبارسنجی (عین پیام‌های سایت) ---
        if ($gender === '' || !in_array($gender, ['آقا', 'خانم'], true)) {
            $errors[] = 'لطفاً جنسیت خود را انتخاب کنید.';
        }
        if ($last_name === '') {
            $errors[] = 'لطفاً نام خانوادگی خود را وارد کنید.';
        }
        if ($phone === '') {
            $errors[] = 'لطفاً شماره تماس خود را وارد کنید.';
        }
        // مختصات با همان پارسر سایت (map_lat/map_lng از همین فرم)
        $_postBak = $_POST;
        $_POST = array_merge($_POST, ['map_lat' => $g('map_lat'), 'map_lng' => $g('map_lng'), 'map_source' => 'manual']);
        $mapLoc = ['latitude' => null, 'longitude' => null, 'location_source' => null, 'location_accuracy' => null, 'location_received' => '0'];
        if (function_exists('melkinoMapPostedLocation')) {
            try {
                $mapSet = ['require_location' => true];
                if (function_exists('melkinoMapSettings') && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                    $mapSet = melkinoMapSettings($GLOBALS['pdo']);
                }
                $mapLoc = melkinoMapPostedLocation($errors, is_array($mapSet) ? $mapSet : ['require_location' => true]);
            } catch (Throwable $e) {
                $mapLoc = ['latitude' => null, 'longitude' => null, 'location_source' => null, 'location_accuracy' => null, 'location_received' => '0'];
            }
        }
        $_POST = $_postBak;

        if ($g('area_apt') === '') {
            $errors[] = 'لطفاً متراژ ملک را وارد کنید.';
        }
        if ($g('floor') === '') {
            $errors[] = 'لطفاً طبقه ملک را وارد کنید.';
        }
        if (!isset($post['rooms_apt']) || (string)$post['rooms_apt'] === '') {
            $errors[] = 'لطفاً تعداد اتاق را انتخاب کنید.';
        }
        if ($g('year_apt') === '') {
            $errors[] = 'لطفاً سال ساخت ملک را وارد کنید.';
        }

        $isSell = ($transaction_type === 'فروش');
        $isPreSell = ($transaction_type === 'پیش فروش');
        $isRent = ($transaction_type === 'اجاره');
        if ($isSell && $g('price_sell') === '') {
            $errors[] = 'لطفاً قیمت فروش را وارد کنید.';
        }
        if ($isPreSell && $g('total_price') === '') {
            $errors[] = 'لطفاً قیمت کل (پیش فروش) را وارد کنید.';
        }
        if ($has_loan === '1') {
            $loanNum = office_to_num($loan_amount);
            $baseNum = $isSell ? office_to_num($price_sell) : office_to_num($total_price);
            if ($loanNum <= 0) {
                $errors[] = 'مبلغ وام را وارد کنید (یا تیک «وام دارد» را بردارید).';
            } elseif ($baseNum > 0 && $loanNum >= $baseNum) {
                $errors[] = 'مبلغ وام باید از قیمت ملک کمتر باشد (قیمت منهای وام باید مثبت بماند).';
            }
        }
        $deedAllowed = office_combo_items('deed_type');
        if ($deedAllowed === []) {
            $deedAllowed = ['طلق', 'وقفی', 'مشاعی', 'عرصه', 'اعیان', 'رهنی', 'قولنامه عادی', 'قولنامه شورایی', 'برگه واگذاری'];
        }
        if ($deed_type !== '' && !in_array($deed_type, $deedAllowed, true)) {
            $errors[] = 'نوع سند انتخاب‌شده معتبر نیست.';
        }
        if ($isRent) {
            if ($full_rent_enabled === '1') {
                if ($g('full_rent') === '') {
                    $errors[] = 'لطفاً مبلغ رهن کامل را وارد کنید.';
                }
            } else {
                if ($g('deposit') === '') {
                    $errors[] = 'لطفاً مبلغ ودیعه را وارد کنید.';
                }
                if ($g('rent_monthly') === '') {
                    $errors[] = 'لطفاً مبلغ اجاره ماهانه را وارد کنید.';
                }
            }
        }

        // --- عکس‌ها (همان قرارداد سایت؛ اختیاری) ---
        $adId = function_exists('melkino_ad_id') && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO
            ? (string)melkino_ad_id($GLOBALS['pdo'])
            : ('AD-' . date('Ymd') . '-' . random_int(1000, 9999));
        [$imgPaths, $uploadErrors] = office_store_images($filesEntry, $adId);

        $ageVal = null;
        if (function_exists('melkinoBuildingAge')) {
            try {
                $ageVal = melkinoBuildingAge($g('year_apt'));
            } catch (Throwable $e) {
            }
        }
        $aptType = $g('apartment_type');
        if (!in_array($aptType, ['فلت', 'دوبلکس'], true)) {
            $aptType = 'فلت';
        }

        $newAd = [
            'id' => $adId,
            'title' => $title,
            'location' => $location,
            'address' => $address,
            'latitude' => $mapLoc['latitude'] ?? null,
            'longitude' => $mapLoc['longitude'] ?? null,
            'location_source' => $mapLoc['location_source'] ?? null,
            'location_accuracy' => $mapLoc['location_accuracy'] ?? null,
            'location_received' => $mapLoc['location_received'] ?? '0',
            'status' => $status,
            'tags' => [],
            'gender' => $gender,
            'last_name' => $last_name,
            'phone' => $phone,
            'telegram_id' => $telegram_id,
            'owner_user_id' => $ownerUserId,
            'consultant_id' => $adminId,
            'propertyType' => $property_type,
            'transactionType' => $transaction_type,
            'area' => $post['area_apt'] ?? '0',
            'floor' => $post['floor'] ?? '0',
            'rooms' => $post['rooms_apt'] ?? '0',
            'year' => $post['year_apt'] ?? '1403',
            'building_age' => $ageVal,
            'flooring' => $g('flooring_apt'),
            'cabinet' => $g('cabinet_apt'),
            'cooling' => $g('cooling_apt'),
            'heating' => $g('heating_apt'),
            'total_units' => $g('total_units'),
            'units_per_floor' => $g('units_per_floor'),
            'apartment_type' => $aptType,
            'amenities' => isset($post['amenities_apt']) && is_array($post['amenities_apt']) ? array_values($post['amenities_apt']) : [],
            'description' => $description,
            'images' => $imgPaths,
            'selectedImages' => $imgPaths,
            'publish_photos' => $publish_photos,
            'created_at' => date('Y-m-d H:i:s'),
            'price_sell' => $price_sell,
            'price_condition' => $price_condition,
            'deposit' => $deposit,
            'rent_monthly' => $rent_monthly,
            'full_rent_enabled' => $full_rent_enabled,
            'full_rent' => $full_rent,
            'total_price' => $total_price,
            'down_payment' => $down_payment,
            'payment_terms' => $payment_terms,
            'display_price' => $price_sell ?: ($total_price ?: ($deposit ?: $rent_monthly)),
            'is_not_keyed' => isset($post['is_not_keyed']) ? '1' : '0',
            'exchange_interested' => $exchange_interested,
            'exchange_with' => $exchange_with,
            'visit_hours' => $visit_hours,
            'delivery_date' => $delivery_date,
            'vacancy_date' => $vacancy_date,
            'is_vacant' => $is_vacant,
            'has_loan' => $has_loan,
            'loan_amount' => $loan_amount,
            'loan_type' => $loan_type,
            'loan_duration' => $loan_duration,
            'loan_bank' => $loan_bank,
            'loan_installment' => $loan_installment,
            'loan_installments_paid' => $loan_installments_paid,
            'loan_notes' => $loan_notes,
            'deed_type' => $deed_type,
            'deed_notes' => $deed_notes,
            'exchange_types' => $exchange_types,
        ];

        return [$newAd, $errors, $uploadErrors];
    }
}
