<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — موتور generic فرم‌های ثبت (ویلا/زمین/تجاری/اداری/باغ)
 *--------------------------------------------------------------------------
 * همان قرارداد _forms.php: نام فیلدها و مقادیر عین فرم سایت، گزینه‌ها از
 * همان کاتالوگ، اعتبارسنجی و نگاشت عین register-*-legacy، ذخیره با همان
 * savePropertyToDatabase. تفاوت‌های دفتر فقط: هویت مالک از فرم،
 * consultant_id، وضعیت انتشار، مختصات دستی.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms.php';

if (!function_exists('office_amenities_villa')) {
    function office_amenities_villa(): array
    {
        return ['آسانسور', 'پارکینگ', 'انباری', 'مطبخ', 'بالکن / تراس', 'حیاط اختصاصی', 'روف گاردن', 'لاندری روم', 'کلوزت', 'اتاق مستر', 'نگهبانی', 'استخر', 'سونا', 'جکوزی', 'زیرزمین', 'گلخانه', 'حیاط خلوت', 'دوبلکس'];
    }
}

if (!function_exists('office_amenities_land')) {
    function office_amenities_land(): array
    {
        return ['آب', 'برق', 'گاز', 'تلفن', 'فاضلاب', 'آب شهری', 'چاه', 'دیوارکشی', 'درب ورودی', 'آسفالت بودن مسیر', 'دسترسی به خیابان اصلی', 'دسترسی به کوچه'];
    }
}

if (!function_exists('office_amenities_comm')) {
    function office_amenities_comm(): array
    {
        return ['شیشه سکوریت', 'درب اتوماتیک', 'درب فلزی', 'کرکره برقی', 'کرکره معمولی', 'سرویس بهداشتی', 'آسانسور', 'بالابر', 'ویترین', 'نورپردازی', 'اسپیلت', 'کولر آبی', 'آبگرمکن', 'پکیج', 'بخاری'];
    }
}

if (!function_exists('office_amenities_office')) {
    function office_amenities_office(): array
    {
        return ['آسانسور', 'پارکینگ', 'انباری', 'لابی', 'نگهبانی', 'دوربین مداربسته', 'سیستم اعلام حریق', 'اطفای حریق', 'سیستم سرمایش', 'سیستم گرمایش', 'برق اختصاصی', 'سه‌فاز', 'آب', 'گاز', 'اینترنت', 'تلفن', 'آبدارخانه', 'سرویس بهداشتی', 'اتاق مدیریت', 'اتاق جلسات', 'پارتیشن‌بندی', 'سیستم هوشمند', 'درب ضدسرقت', 'تابلوخور مناسب', 'دسترسی به حمل‌ونقل عمومی'];
    }
}

if (!function_exists('office_amenities_garden')) {
    function office_amenities_garden(): array
    {
        return ['سرویس بهداشتی', 'پارکینگ', 'انباری', 'آلاچیق', 'استخر', 'سونا', 'باربیکیو', 'برق', 'گاز', 'آب شهری', 'دیوارکشی', 'نگهبانی', 'درب ورودی خودرو'];
    }
}

if (!function_exists('office_type_spec')) {
    /** @return array<string,mixed>|null */
    function office_type_spec(string $type): ?array
    {
        static $specs = null;
        if ($specs === null) {
            $specs = [
                'villa' => [
                    'property_type' => 'ویلایی',
                    'legacy' => 'register-villa-legacy.php',
                    'spec_title' => 'مشخصات ویلا',
                    'keyed' => true, 'old_new' => true, 'vacant' => true, 'visit' => true,
                    'amen_field' => 'amenities_villa', 'amen_fn' => 'office_amenities_villa',
                    'exchange_ph' => 'مثلاً: معاوضه با ویلا بزرگ‌تر یا معاوضه با آپارتمان یا ... ب',
                    'desc_ph' => 'شرح کامل امکانات و شرایط ملک...',
                    'required' => [
                        'land_villa' => 'لطفاً متراژ زمین را وارد کنید.',
                        'built_villa' => 'لطفاً زیربنا را وارد کنید.',
                        'rooms_villa' => 'لطفاً تعداد اتاق را انتخاب کنید.',
                        'year_villa' => 'لطفاً سال ساخت ملک را وارد کنید.',
                    ],
                    'extra_validate' => null,
                    'map_spec' => 'office_map_villa',
                    'spec' => [
                        ['land_villa', 'متراژ زمین (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۵۰۰']],
                        ['built_villa', 'زیربنا (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۳۰۰']],
                        ['rooms_villa', 'تعداد اتاق', 'select', ['combo' => 'rooms', 'req' => true]],
                        ['year_villa', 'سال ساخت', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۴۰۲']],
                        ['building_age', 'سن بنا', 'text', ['readonly' => true, 'placeholder' => 'خودکار']],
                        ['villa_type', 'نوع ویلا', 'radio', ['options' => ['فلت', 'دوبلکس', 'تریبلکس'], 'default' => 'فلت']],
                        ['flooring_villa', 'نوع پوشش کف', 'select', ['combo' => 'flooring']],
                        ['cabinet_villa', 'نوع کابینت', 'select', ['combo' => 'cabinet']],
                        ['cooling_villa', 'سیستم سرمایش', 'select', ['combo' => 'cooling']],
                        ['heating_villa', 'سیستم گرمایش', 'select', ['combo' => 'heating']],
                        ['amenities_villa', 'امکانات', 'checks', ['options_fn' => 'office_amenities_villa']],
                    ],
                ],
                'land' => [
                    'property_type' => 'زمین',
                    'legacy' => 'register-land-legacy.php',
                    'spec_title' => 'مشخصات زمین',
                    'keyed' => false, 'old_new' => false, 'vacant' => false, 'visit' => false,
                    'amen_field' => 'land_amenities', 'amen_fn' => 'office_amenities_land',
                    'exchange_ph' => 'مثلاً: معاوضه با زمین بزرگ‌تر یا معاوضه با ملک دیگر یا ... ب',
                    'desc_ph' => 'شرح کامل مشخصات و وضعیت زمین...',
                    'required' => [
                        'land_area' => 'لطفاً مساحت زمین را وارد کنید.',
                        'land_type' => 'لطفاً کاربری زمین را انتخاب کنید.',
                        'land_width' => 'لطفاً عرض زمین را وارد کنید.',
                        'land_length' => 'لطفاً طول زمین را وارد کنید.',
                        'land_ownership' => 'لطفاً وضعیت مالکیت را انتخاب کنید.',
                    ],
                    'extra_validate' => null,
                    'map_spec' => 'office_map_land',
                    'spec' => [
                        ['land_area', 'مساحت زمین (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۵۰۰']],
                        ['land_type', 'کاربری زمین', 'radio', ['options' => ['مسکونی', 'تجاری', 'کشاورزی', 'باغی'], 'req' => true]],
                        ['land_width', 'عرض زمین (متر)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۲']],
                        ['land_length', 'طول زمین (متر)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۴۲']],
                        ['land_front_width', 'عرض بر (متر)', 'text', ['ltr' => true, 'placeholder' => '۱۰']],
                        ['land_blocks', 'تعداد بر', 'text', ['ltr' => true, 'placeholder' => '۲']],
                        ['land_direction', 'جهت ملک', 'select', ['combo' => 'land_direction']],
                        ['land_shape', 'شکل زمین', 'select', ['combo' => 'land_shape']],
                        ['land_deed_status', 'وضعیت سند', 'select', ['combo' => 'land_deed_status']],
                        ['land_deed_type', 'نوع سند', 'select', ['combo' => 'land_deed_type']],
                        ['land_setback_status', 'وضعیت عقب‌نشینی', 'select', ['combo' => 'land_setback']],
                        ['land_ownership', 'وضعیت مالکیت', 'radio', ['options' => ['شش‌دانگ', 'مشاع'], 'req' => true]],
                        ['land_amenities', 'امکانات و دسترسی‌ها', 'checks', ['options_fn' => 'office_amenities_land']],
                    ],
                ],
                'commercial' => [
                    'property_type' => 'تجاری',
                    'legacy' => 'register-commercial-legacy.php',
                    'spec_title' => 'مشخصات ملک تجاری',
                    'keyed' => true, 'old_new' => false, 'vacant' => true, 'visit' => true,
                    'amen_field' => 'amenities_comm', 'amen_fn' => 'office_amenities_comm',
                    'exchange_ph' => 'مثلاً: معاوضه با ملک تجاری بزرگ‌تر یا معاوضه با ملک دیگر یا ',
                    'desc_ph' => 'شرح کامل امکانات و شرایط ملک تجاری...',
                    'required' => ['area_comm' => 'لطفاً متراژ ملک را وارد کنید.'],
                    'extra_validate' => null,
                    'map_spec' => 'office_map_commercial',
                    'spec' => [
                        ['area_comm', 'متراژ (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۷۰']],
                        ['front_comm', 'بر مغازه (متر)', 'text', ['ltr' => true, 'placeholder' => '۶']],
                        ['location_type_1', 'موقعیت ملک', 'radio', ['options' => ['دونبش', 'دوبر', 'یک بر']]],
                        ['location_type_2', 'ویژگی موقعیت', 'radio', ['options' => ['main_street' => 'بر خیابان اصلی', 'side_street' => 'بر خیابان فرعی', 'in_passage' => 'در پاساژ', 'in_garage' => 'در گاراژ']]],
                        ['floor_comm', 'پوشش کف', 'select', ['combo' => 'flooring']],
                        ['wall_comm', 'پوشش دیوارها', 'select', ['combo' => 'wall']],
                        ['cabinet_comm', 'نوع کابینت', 'select', ['combo' => 'cabinet']],
                        ['cooling_comm', 'سیستم سرمایش', 'select', ['combo' => 'cooling']],
                        ['heating_comm', 'سیستم گرمایش', 'select', ['combo' => 'heating']],
                        ['jobs_comm', 'مناسب برای مشاغل', 'text', ['placeholder' => 'مثلاً: آرایشگاه، رستوران، دفتر کار، فروشگاه']],
                        ['amenities_comm', 'امکانات', 'checks', ['options_fn' => 'office_amenities_comm']],
                    ],
                ],
                'office' => [
                    'property_type' => 'اداری',
                    'legacy' => 'register-office-legacy.php',
                    'spec_title' => 'مشخصات واحد اداری',
                    'keyed' => true, 'old_new' => false, 'vacant' => true, 'visit' => true,
                    'amen_field' => 'office_amenities', 'amen_fn' => 'office_amenities_office',
                    'exchange_ph' => 'مثلاً: معاوضه با واحد بزرگ‌تر یا معاوضه با ملک دیگر یا ... ب',
                    'desc_ph' => 'شرح کامل امکانات و شرایط واحد اداری...',
                    'required' => [
                        'office_area' => 'لطفاً متراژ واحد را وارد کنید.',
                        'office_floor' => 'لطفاً طبقه را وارد کنید.',
                        'office_units_per_floor' => 'لطفاً تعداد واحد در طبقه را وارد کنید.',
                        'office_rooms' => 'لطفاً تعداد اتاق را انتخاب کنید.',
                        'office_year' => 'لطفاً سال ساخت را وارد کنید.',
                        'office_condition' => 'لطفاً وضعیت واحد را انتخاب کنید.',
                        'office_orientation' => 'لطفاً موقعیت واحد را انتخاب کنید.',
                        'office_usage' => 'لطفاً کاربری واحد را انتخاب کنید.',
                    ],
                    'extra_validate' => null,
                    'map_spec' => 'office_map_office',
                    'spec' => [
                        ['office_area', 'متراژ واحد (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۲۰']],
                        ['office_floor', 'طبقه', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۳']],
                        ['office_units_per_floor', 'تعداد واحد در طبقه', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۴']],
                        ['office_rooms', 'تعداد اتاق', 'select', ['combo' => 'rooms', 'req' => true]],
                        ['office_year', 'سال ساخت', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۴۰۲']],
                        ['building_age', 'سن بنا', 'text', ['readonly' => true, 'placeholder' => 'خودکار']],
                        ['office_condition', 'وضعیت واحد', 'radio', ['options' => ['نوساز', 'بازسازی‌شده', 'قدیمی'], 'req' => true]],
                        ['office_orientation', 'موقعیت واحد', 'radio', ['options' => ['شمالی', 'جنوبی', 'شرقی', 'غربی'], 'req' => true]],
                        ['office_usage', 'کاربری واحد', 'radio', ['options' => ['اداری', 'دفتر کار', 'تجاری-اداری'], 'req' => true]],
                        ['office_amenities', 'امکانات', 'checks', ['options_fn' => 'office_amenities_office']],
                    ],
                ],
                'garden' => [
                    'property_type' => 'باغ',
                    'legacy' => 'register-garden-legacy.php',
                    'spec_title' => 'مشخصات باغ',
                    'keyed' => false, 'old_new' => false, 'vacant' => false, 'visit' => false,
                    'amen_field' => 'garden_amenities', 'amen_fn' => 'office_amenities_garden',
                    'exchange_ph' => 'مثلاً: معاوضه با باغ بزرگ‌تر یا معاوضه با ملک دیگر یا ... بر',
                    'desc_ph' => 'شرح کامل امکانات و شرایط باغ...',
                    'required' => [
                        'garden_area' => 'لطفاً مساحت باغ را وارد کنید.',
                        'document_type' => 'لطفاً نوع سند را انتخاب کنید.',
                    ],
                    'extra_validate' => 'office_validate_garden',
                    'map_spec' => 'office_map_garden',
                    'spec' => [
                        ['garden_area', 'مساحت باغ (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۱۰۰۰']],
                        ['tree_types', 'نوع درختان', 'text', ['placeholder' => 'گردو، سیب، گیلاس، ...']],
                        ['tree_age', 'سن درختان', 'text', ['placeholder' => 'بین ۵ تا ۱۵ سال']],
                        ['irrigation_type', 'نوع آبیاری', 'select', ['combo' => 'irrigation']],
                        ['has_well', 'آب ملکی', 'radio', ['options' => ['1' => 'دارد', '0' => 'ندارد'], 'default' => '0']],
                        ['water_share', 'مقدار ساعت آب', 'text', ['placeholder' => 'مثلاً ۶ ساعت در هفته']],
                        ['well_name', 'نام چاه آب', 'text', ['placeholder' => 'مثلاً چاه اصلی یا چاه شماره ...']],
                        ['has_pond', 'استخر ذخیره آب', 'radio', ['options' => ['1' => 'دارد', '0' => 'ندارد'], 'default' => '0']],
                        ['has_building', 'بنا / خانه باغ', 'radio', ['options' => ['1' => 'دارد', '0' => 'ندارد'], 'default' => '0']],
                        ['building_area', 'متراژ بنا', 'text', ['ltr' => true, 'placeholder' => '۱۵۰']],
                        ['document_type', 'نوع سند', 'radio', ['options' => ['سند', 'قولنامه'], 'req' => true]],
                        ['garden_amenities', 'امکانات', 'checks', ['options_fn' => 'office_amenities_garden']],
                    ],
                ],
            ];
        }
        return $specs[$type] ?? null;
    }
}

if (!function_exists('office_validate_garden')) {
    /** اعتبارسنجی شرطی باغ — عین register-garden-legacy. */
    function office_validate_garden(array $post, string $tx, array &$errors): void
    {
        if (isset($post['has_well']) && (string)$post['has_well'] === '1') {
            if (empty($post['water_share'])) {
                $errors[] = 'لطفاً مقدار ساعت آب را وارد کنید.';
            }
            if (empty($post['well_name'])) {
                $errors[] = 'لطفاً نام چاه آب را وارد کنید.';
            }
        }
    }
}

if (!function_exists('office_req_missing')) {
    /** معادل empty()‎ فرم سایت روی ورودی POST (شامل رد '0'). */
    function office_req_missing(array $post, string $key): bool
    {
        if (!isset($post[$key]) || is_array($post[$key])) {
            return !isset($post[$key]);
        }
        $t = trim((string)$post[$key]);
        return $t === '' || $t === '0';
    }
}

if (!function_exists('office_map_villa')) {
    /** @return array<string,mixed> */
    function office_map_villa(array $post, callable $g): array
    {
        $vt = $g('villa_type');
        if (!in_array($vt, ['فلت', 'دوبلکس', 'تریبلکس'], true)) {
            $vt = 'فلت';
        }
        $age = null;
        if (function_exists('melkinoBuildingAge')) {
            try {
                $age = melkinoBuildingAge($g('year_villa'));
            } catch (Throwable $e) {
            }
        }
        return [
            'land_area' => $post['land_villa'] ?? '0',
            'built_area' => $post['built_villa'] ?? '0',
            'land_villa' => $post['land_villa'] ?? '0',
            'built_villa' => $post['built_villa'] ?? '0',
            'area' => $post['land_villa'] ?? '0',
            'rooms' => $post['rooms_villa'] ?? '0',
            'year' => $post['year_villa'] ?? '1403',
            'building_age' => $age,
            'flooring' => $g('flooring_villa'),
            'cabinet' => $g('cabinet_villa'),
            'cooling' => $g('cooling_villa'),
            'heating' => $g('heating_villa'),
            'villa_type' => $vt,
            'amenities' => isset($post['amenities_villa']) && is_array($post['amenities_villa']) ? array_values($post['amenities_villa']) : [],
            'is_not_keyed' => isset($post['is_not_keyed']) ? '1' : '0',
            'is_old' => isset($post['is_old']) ? '1' : '0',
            'is_renovated' => isset($post['is_renovated']) ? '1' : '0',
            'is_vacant' => isset($post['is_vacant']) ? '1' : '0',
        ];
    }
}

if (!function_exists('office_map_land')) {
    /** @return array<string,mixed> */
    function office_map_land(array $post, callable $g): array
    {
        return [
            'land_area' => $post['land_area'] ?? '0',
            'land_type' => $g('land_type'),
            'land_width' => $post['land_width'] ?? '0',
            'land_length' => $post['land_length'] ?? '0',
            'land_front_width' => $post['land_front_width'] ?? '0',
            'land_blocks' => $post['land_blocks'] ?? '0',
            'land_direction' => $g('land_direction'),
            'land_shape' => $g('land_shape'),
            'land_deed_status' => $g('land_deed_status'),
            'land_deed_type' => $g('land_deed_type'),
            'land_setback_status' => $g('land_setback_status'),
            'land_ownership' => $g('land_ownership'),
            'amenities' => isset($post['land_amenities']) && is_array($post['land_amenities']) ? array_values($post['land_amenities']) : [],
        ];
    }
}

if (!function_exists('office_map_commercial')) {
    /** @return array<string,mixed> */
    function office_map_commercial(array $post, callable $g): array
    {
        // عین نگاشت راند ۱۷ فرم سایت (شامل فارسی‌سازی location_type_2)
        $faMap = ['main_street' => 'بر خیابان اصلی', 'side_street' => 'بر خیابان فرعی', 'in_passage' => 'در پاساژ', 'in_garage' => 'در گاراژ'];
        $lt2 = $g('location_type_2');
        return [
            'area' => $post['area_comm'] ?? '0',
            'area_comm' => $post['area_comm'] ?? '0',
            'front' => $post['front_comm'] ?? '0',
            'front_comm' => $post['front_comm'] ?? '0',
            'flooring' => $g('floor_comm'),
            'floor_comm' => $g('floor_comm'),
            'wall' => $g('wall_comm'),
            'wall_comm' => $g('wall_comm'),
            'cabinet' => $g('cabinet_comm'),
            'cabinet_comm' => $g('cabinet_comm'),
            'cooling' => $g('cooling_comm'),
            'cooling_comm' => $g('cooling_comm'),
            'heating' => $g('heating_comm'),
            'heating_comm' => $g('heating_comm'),
            'location_type' => $g('location_type_1'),
            'location_type_1' => $g('location_type_1'),
            'location_features' => $faMap[$lt2] ?? $lt2,
            'location_type_2' => $lt2,
            'jobs' => $g('jobs_comm'),
            'jobs_comm' => $g('jobs_comm'),
            'amenities' => isset($post['amenities_comm']) && is_array($post['amenities_comm']) ? array_values($post['amenities_comm']) : [],
            'is_not_keyed' => isset($post['is_not_keyed']) ? '1' : '0',
            'is_vacant' => isset($post['is_vacant']) ? '1' : '0',
        ];
    }
}

if (!function_exists('office_map_office')) {
    /** @return array<string,mixed> */
    function office_map_office(array $post, callable $g): array
    {
        $age = null;
        if (function_exists('melkinoBuildingAge')) {
            try {
                $age = melkinoBuildingAge($g('office_year'));
            } catch (Throwable $e) {
            }
        }
        return [
            'office_area' => $post['office_area'] ?? '0',
            'office_floor' => $post['office_floor'] ?? '0',
            'office_units_per_floor' => $post['office_units_per_floor'] ?? '0',
            'office_rooms' => $post['office_rooms'] ?? '0',
            'office_year' => $post['office_year'] ?? '1403',
            'year' => $post['office_year'] ?? '1403',
            'building_age' => $age,
            'office_condition' => $g('office_condition'),
            'office_orientation' => $g('office_orientation'),
            'office_usage' => $g('office_usage'),
            'amenities' => isset($post['office_amenities']) && is_array($post['office_amenities']) ? array_values($post['office_amenities']) : [],
            'is_not_keyed' => isset($post['is_not_keyed']) ? '1' : '0',
            'is_vacant' => isset($post['is_vacant']) ? '1' : '0',
        ];
    }
}

if (!function_exists('office_map_garden')) {
    /** @return array<string,mixed> */
    function office_map_garden(array $post, callable $g): array
    {
        return [
            'garden_area' => $post['garden_area'] ?? '0',
            'tree_types' => $g('tree_types'),
            'tree_age' => $g('tree_age'),
            'irrigation_type' => $g('irrigation_type'),
            'has_well' => isset($post['has_well']) && (string)$post['has_well'] === '1' ? '1' : '0',
            'water_share' => $g('water_share'),
            'well_name' => $g('well_name'),
            'has_pond' => isset($post['has_pond']) && (string)$post['has_pond'] === '1' ? '1' : '0',
            'has_building' => isset($post['has_building']) && (string)$post['has_building'] === '1' ? '1' : '0',
            'building_area' => $post['building_area'] ?? '0',
            'document_type' => $g('document_type'),
            'amenities' => isset($post['garden_amenities']) && is_array($post['garden_amenities']) ? array_values($post['garden_amenities']) : [],
        ];
    }
}

if (!function_exists('office_sections_for')) {
    /** @return array<string,array<int,array>> */
    function office_sections_for(string $type, bool $forEdit = false): array
    {
        if ($type === 'apartment') {
            return office_apartment_sections($forEdit);
        }
        $spec = office_type_spec($type);
        if ($spec === null) {
            return [];
        }
        $statusOpts = $forEdit
            ? ['published' => 'منتشرشده', 'pending' => 'در انتظار', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی']
            : ['published' => 'منتشر شود', 'pending' => 'پیش‌نویس / در انتظار'];
        $tx = [
            ['transaction_type', 'نوع معامله', 'radio', ['options' => ['فروش', 'اجاره', 'پیش فروش'], 'req' => true]],
            ['status', 'وضعیت انتشار', 'select', ['options' => $statusOpts, 'default' => 'published']],
        ];
        if (!empty($spec['keyed'])) {
            $tx[] = ['is_not_keyed', 'کلید نخورده', 'check', ['value' => '1']];
        }
        if (!empty($spec['old_new'])) {
            $tx[] = ['is_old', 'کلنگی', 'check', ['value' => '1']];
            $tx[] = ['is_renovated', 'بازسازی‌شده', 'check', ['value' => '1']];
        }
        if (!empty($spec['vacant'])) {
            $tx[] = ['is_vacant', 'تخلیه', 'check', ['value' => '1']];
        }
        $sections = [
            'معامله و وضعیت' => $tx,
            'مشخصات مالک' => [
                ['gender', 'جنسیت مالک', 'radio', ['options' => ['آقا', 'خانم'], 'req' => true]],
                ['last_name', 'نام مالک', 'text', ['req' => true, 'placeholder' => 'نام و نام خانوادگی مالک']],
                ['phone', 'موبایل مالک', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۰۹...']],
                ['telegram_id', 'آی‌دی تلگرام مالک (اختیاری)', 'text', ['ltr' => true, 'placeholder' => '@...']],
            ],
            'عنوان و موقعیت' => [
                ['title', 'عنوان آگهی', 'text', ['placeholder' => '']],
                ['location', 'محله / خیابان اصلی', 'text', ['placeholder' => 'خیابان بهار']],
                ['address', 'آدرس دقیق', 'text', ['placeholder' => 'خ بهار کوچه بیستم...']],
                ['map_lat', 'عرض جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۳۵.۷...']],
                ['map_lng', 'طول جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۵۱.۴...']],
            ],
            (string)$spec['spec_title'] => $spec['spec'],
            'قیمت' => [
                ['price_sell', 'قیمت (تومان)', 'text', ['ltr' => true, 'placeholder' => '۲,۸۰۰,۰۰۰,۰۰۰', 'tx' => 'فروش']],
                ['price_condition', 'شرایط قیمت', 'pricecond', ['tx' => 'فروش']],
                ['deposit', 'ودیعه', 'text', ['ltr' => true, 'placeholder' => '۵۰۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['rent_monthly', 'اجاره ماهانه', 'text', ['ltr' => true, 'placeholder' => '۳۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['full_rent_enabled', 'رهن کامل', 'check', ['value' => '1', 'tx' => 'اجاره']],
                ['full_rent', 'مبلغ رهن کامل', 'text', ['ltr' => true, 'placeholder' => '۱,۰۰۰,۰۰۰,۰۰۰', 'tx' => 'اجاره']],
                ['total_price', 'قیمت کل', 'text', ['ltr' => true, 'placeholder' => '۳,۰۰۰,۰۰۰,۰۰۰', 'tx' => 'پیش فروش']],
                ['down_payment', 'پیش‌پرداخت', 'text', ['ltr' => true, 'placeholder' => '۹۰۰,۰۰۰,۰۰۰', 'tx' => 'پیش فروش']],
                ['payment_terms', 'شرایط پرداخت', 'textarea', ['placeholder' => 'مثال: ۳۰٪ قرارداد، ۲۰٪ اسکلت...', 'tx' => 'پیش فروش']],
            ],
            'وام' => [
                ['has_loan', 'وام دارد', 'check', ['value' => '1']],
                ['loan_amount', 'مبلغ وام (تومان)', 'text', ['ltr' => true, 'placeholder' => '۳۰۰,۰۰۰,۰۰۰']],
                ['loan_type', 'نوع وام', 'text', ['placeholder' => 'مثلاً: وام مسکن / اوراق']],
                ['loan_duration', 'مدت وام', 'text', ['placeholder' => 'مثلاً: ۱۲ سال']],
                ['loan_bank', 'بانک', 'text', ['placeholder' => 'مثلاً: بانک مسکن']],
                ['loan_installment', 'مبلغ هر قسط (تومان)', 'text', ['ltr' => true, 'placeholder' => '۵,۰۰۰,۰۰۰']],
                ['loan_installments_paid', 'تعداد اقساط پرداخت شده', 'text', ['ltr' => true, 'placeholder' => 'مثلاً: ۲۴']],
                ['loan_notes', 'توضیحات تکمیلی وام', 'textarea', ['placeholder' => 'توضیحات بیشتر دربارهٔ وام (اختیاری)']],
            ],
            'سند و معاوضه' => [
                ['deed_type', 'نوع سند ملک', 'radio', ['combo' => 'deed_type']],
                ['deed_notes', 'توضیحات', 'textarea', ['placeholder' => 'اگر سند نیازمند توضیح است در این کادر بنویسید']],
                ['exchange_interested', 'مایل به معاوضه', 'check', ['value' => '1']],
                ['exchange_types', 'معاوضه با', 'checks', ['options_fn' => 'office_exchange_types']],
                ['exchange_with', 'معاوضه با:', 'textarea', ['placeholder' => (string)$spec['exchange_ph']]],
            ],
        ];
        if (!empty($spec['visit'])) {
            $sections['بازدید و تحویل'] = [
                ['visit_hours', 'چه زمان‌هایی میشه بازدید کرد؟', 'textarea', ['placeholder' => 'مثال: فقط عصرها میشه بازدید کرد یا همیشه میشه بازدید کرد با ...']],
                ['delivery_date', 'تاریخ تحویل', 'text', ['placeholder' => '۱۴۰۵/۰۲/۱۵']],
                ['vacancy_date', 'تاریخ تخلیه', 'date', []],
            ];
        }
        $sections['عکس‌ها'] = [
            ['images', 'انتخاب عکس‌ها', 'file', ['multiple' => true, 'note' => 'حداکثر ۵ مگابایت برای هر عکس — JPG/PNG/WebP/GIF']],
            ['publish_photos', 'انتشار عکس‌ها در سایت', 'radio', ['options' => ['yes' => 'بله', 'no' => 'خیر'], 'default' => 'yes']],
        ];
        $sections['توضیحات'] = [
            ['full_description', 'توضیحات تکمیلی (حداکثر ۱۰۰۰ کاراکتر)', 'textarea', ['placeholder' => (string)$spec['desc_ph']]],
        ];
        return $sections;
    }
}

if (!function_exists('office_collect_for')) {
    /**
     * @return array{0: array, 1: string[], 2: string[]}
     */
    function office_collect_for(string $type, array $post, mixed $filesEntry, int $adminId): array
    {
        if ($type === 'apartment') {
            return office_collect_apartment($post, $filesEntry, $adminId);
        }
        return office_collect_property($type, $post, $filesEntry, $adminId);
    }
}

if (!function_exists('office_collect_property')) {
    /**
     * کالکتور generic پنج نوع ملک — عین منطق مشترک register-*-legacy.
     * @return array{0: array, 1: string[], 2: string[]}
     */
    function office_collect_property(string $type, array $post, mixed $filesEntry, int $adminId): array
    {
        $spec = office_type_spec($type);
        $errors = [];
        if ($spec === null) {
            return [[], ['نوع ملک نامعتبر است.'], []];
        }
        // قانون مبالغ: جداکننده‌های نمایشی حذف تا عین سایتِ بدون‌کاما ذخیره شود
        foreach (office_money_fields() as $mk) {
            if (isset($post[$mk]) && is_string($post[$mk])) {
                $post[$mk] = str_replace([',', '٬', '،', ' '], '', $post[$mk]);
            }
        }
        $g = static fn(string $k, string $d = ''): string => trim((string)($post[$k] ?? $d));

        $gender = $g('gender');
        $last_name = $g('last_name');
        $phone = $g('phone');
        $telegram_id = $g('telegram_id');

        $transaction_type = $g('transaction_type', 'فروش');
        if (!in_array($transaction_type, ['فروش', 'اجاره', 'پیش فروش'], true)) {
            $transaction_type = 'فروش';
        }
        $property_type = (string)$spec['property_type'];
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

        if ($gender === '' || !in_array($gender, ['آقا', 'خانم'], true)) {
            $errors[] = 'لطفاً جنسیت خود را انتخاب کنید.';
        }
        if ($last_name === '') {
            $errors[] = 'لطفاً نام خانوادگی خود را وارد کنید.';
        }
        if ($phone === '') {
            $errors[] = 'لطفاً شماره تماس خود را وارد کنید.';
        }
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

        foreach ((array)$spec['required'] as $rk => $msg) {
            if (office_req_missing($post, (string)$rk)) {
                $errors[] = (string)$msg;
            }
        }
        if (!empty($spec['extra_validate']) && function_exists((string)$spec['extra_validate'])) {
            ((string)$spec['extra_validate'])($post, $transaction_type, $errors);
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

        $adId = function_exists('melkino_ad_id') && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO
            ? (string)melkino_ad_id($GLOBALS['pdo'])
            : ('AD-' . date('Ymd') . '-' . random_int(1000, 9999));
        [$imgPaths, $uploadErrors] = office_store_images($filesEntry, $adId);

        $mapFn = (string)$spec['map_spec'];
        $specKeys = function_exists($mapFn) ? (array)$mapFn($post, $g) : [];

        $newAd = array_merge([
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
            'owner_user_id' => 0,
            'consultant_id' => $adminId,
            'propertyType' => $property_type,
            'transactionType' => $transaction_type,
        ], $specKeys, [
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
            'exchange_interested' => $exchange_interested,
            'exchange_with' => $exchange_with,
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
        ]);

        // کلیدهای بازدید فقط در نوع‌هایی که فرم سایت دارد (ویلا/تجاری/اداری + آپارتمانِ موتور قدیم)
        if (!empty($spec['visit'])) {
            $newAd['visit_hours'] = $visit_hours;
            $newAd['delivery_date'] = $delivery_date;
            $newAd['vacancy_date'] = $vacancy_date;
        }

        return [$newAd, $errors, $uploadErrors];
    }
}
