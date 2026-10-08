<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — فرم ثبت مشارکت در ساخت (عین register-partnership)
 *--------------------------------------------------------------------------
 * قرارداد عین سایت:
 * - گزینه‌ها از همان melkinoPartOptions() (تک‌منبع سایت).
 * - پاک‌سازی/اعتبارسنجی با همان melkinoPartSanitize/Validate و همان
 *   melkinoPartCode و همان ستون‌های INSERT در partnership_requests.
 * - مدارک با همان قوانین upload_doc (‏۸ مگ، تصویر/PDF،
 *   uploads/partnership/doc_...‎) ذخیره می‌شوند.
 *
 * تفاوت‌های دفتر (فقط این‌ها):
 * - نام/موبایل مالک از فرم خوانده می‌شود (به‌جای پروفایل قفل‌شده)؛
 *   user_id ‏null‏ ثبت می‌شود چون ثبت‌کننده مشاور است نه کاربر سایت.
 * - مختصات دستی + آپلود مستقیم در همان درخواست (به‌جای آژاکس‌ی مرحله‌ای)؛
 *   شکل نهایی payload عین همان JSON سایت است.
 * - وضعیت مثل سایت 'pending' سخت‌گذاری می‌شود (گردش‌کارش با مدیریت
 *   مشارکت در پنل است، نه فرم ثبت).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms.php';
$__p3f = dirname(__DIR__) . '/partnership-lib.php';
if (is_file($__p3f)) {
    require_once $__p3f;
}
unset($__p3f);
if (!function_exists('melkinoPartOptions')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/partnership-lib.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_partnership_sections')) {
    /** @return array<string,array<int,array>> */
    function office_partnership_sections(): array
    {
        $opt = function_exists('melkinoPartOptions') ? melkinoPartOptions() : [];
        $o = static fn(string $k) => array_values((array)($opt[$k] ?? []));
        return [
            'مشخصات مالک' => [
                ['owner_name', 'نام مالک', 'text', ['req' => true, 'placeholder' => 'نام و نام خانوادگی مالک']],
                ['phone', 'موبایل مالک', 'text', ['req' => true, 'ltr' => true, 'placeholder' => '۰۹...']],
            ],
            'معرفی ملک' => [
                ['property_type', 'نوع ملک', 'radio', ['options' => $o('property_types'), 'req' => true]],
                ['area', 'مساحت ملک (متر مربع)', 'text', ['req' => true, 'ltr' => true, 'placeholder' => 'مثلاً ۳۰۰']],
                ['current_status', 'وضعیت فعلی ملک', 'radio', ['options' => $o('current_statuses'), 'req' => true]],
            ],
            'موقعیت' => [
                ['neighborhood', 'محله', 'text', ['req' => true, 'placeholder' => 'مثلاً خ فردوسی']],
                ['address', 'آدرس دقیق', 'textarea', ['placeholder' => 'نام خیابان، کوچه، پلاک و...']],
                ['map_lat', 'عرض جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۳۵.۷...']],
                ['map_lng', 'طول جغرافیایی', 'text', ['ltr' => true, 'placeholder' => '۵۱.۴...']],
                ['passage_width', 'عرض کوچه یا گذر (متر)', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۸']],
                ['land_width', 'عرض زمین (متر)', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۱۰']],
                ['br_count', 'تعداد بر', 'radio', ['options' => $o('br_counts')]],
                ['direction', 'جهت', 'radio', ['options' => $o('directions')]],
            ],
            'ظرفیت ساخت و پروانه' => [
                ['permit_status', 'وضعیت پروانه', 'radio', ['options' => $o('permit_statuses')]],
                ['density', 'تراکم مجاز (٪)', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۱۸۰']],
                ['occupancy_rate', 'سطح اشغال مجاز (٪)', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۶۰']],
                ['buildable_floors', 'تعداد طبقات قابل ساخت', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۵']],
                ['buildable_area', 'زیربنای قابل ساخت (متر مربع)', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۹۰۰']],
            ],
            'سند و مالکیت' => [
                ['deed_status', 'وضعیت سند', 'radio', ['options' => $o('deed_statuses'), 'req' => true]],
                ['deed_kind', 'نوع سند', 'radio', ['options' => $o('deed_kinds')]],
                ['owners_count', 'تعداد مالکین', 'text', ['ltr' => true, 'placeholder' => 'مثلاً ۲']],
                ['occupancy', 'وضعیت سکونت/بهره‌برداری', 'select', ['options' => $o('occupancies')]],
                ['legal_status', 'وضعیت حقوقی', 'checks', ['options_fn' => 'office_part_legal_flags']],
            ],
            'عکس و مدارک' => [
                ['photos', 'عکس‌های ملک (حداکثر ۱۰)', 'file', ['multiple' => true, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf', 'note' => 'حداکثر ۸ مگابایت برای هر فایل — تصویر یا PDF']],
                ['doc_deed', 'تصویر سند', 'file', ['single' => true, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf']],
                ['doc_permit', 'تصویر پروانه', 'file', ['single' => true, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf']],
                ['doc_endjob', 'پایان‌کار', 'file', ['single' => true, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf']],
                ['doc_other', 'مدارک دیگر (حداکثر ۵)', 'file', ['multiple' => true, 'max' => 5, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf']],
            ],
        ];
    }
}

if (!function_exists('office_part_legal_flags')) {
    function office_part_legal_flags(): array
    {
        $opt = function_exists('melkinoPartOptions') ? melkinoPartOptions() : [];
        return array_values((array)($opt['legal_flags'] ?? []));
    }
}

if (!function_exists('office_store_part_docs')) {
    /**
     * ذخیره عکس/مدرک مشارکت با همان قوانین upload_doc سایت.
     * مسیر برگشتی همیشه نسبی uploads/partnership/... است (قرارداد دیتابیس)؛
     * در تست، فایل فیزیکی زیر OFFICE_TEST_UPLOAD_DIR/partnership می‌نشیند.
     * @return array{0: string[], 1: string[]}
     */
    function office_store_part_docs(mixed $entry, bool $multi, int $cap = 10): array
    {
        $paths = [];
        $errors = [];
        $targetDir = defined('OFFICE_TEST_UPLOAD_DIR')
            ? rtrim((string)OFFICE_TEST_UPLOAD_DIR, '/') . '/partnership/'
            : (dirname(__DIR__) . '/uploads/partnership/');
        $maxSize = 8 * 1024 * 1024;
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        $items = [];
        if ($multi && is_array($entry) && isset($entry['name']) && is_array($entry['name'])) {
            $n = min(count($entry['name']), $cap);
            for ($i = 0; $i < $n; $i++) {
                $items[] = [
                    'name' => (string)($entry['name'][$i] ?? ''),
                    'tmp_name' => (string)($entry['tmp_name'][$i] ?? ''),
                    'error' => $entry['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => (int)($entry['size'][$i] ?? 0),
                ];
            }
        } elseif (!$multi && is_array($entry) && isset($entry['name']) && is_string($entry['name']) && $entry['name'] !== '') {
            $items[] = [
                'name' => (string)$entry['name'],
                'tmp_name' => (string)($entry['tmp_name'] ?? ''),
                'error' => $entry['error'] ?? UPLOAD_ERR_NO_FILE,
                'size' => (int)($entry['size'] ?? 0),
            ];
        }
        $items = array_values(array_filter($items, static fn($it) => ($it['name'] ?? '') !== ''));
        if (!$items) {
            return [[], []];
        }
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            return [[], ['پوشه uploads/partnership قابل نوشتن نیست.']];
        }
        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
        foreach ($items as $it) {
            $orig = $it['name'];
            if ($it['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $orig . ': کد خطای آپلود ' . (int)$it['error'];
                continue;
            }
            if ($it['size'] > $maxSize) {
                $errors[] = $orig . ': حجم بیش از ۸ مگابایت';
                continue;
            }
            $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = $orig . ': فرمت مجاز نیست (تصویر یا PDF)';
                continue;
            }
            if ($finfo && is_file($it['tmp_name'])) {
                $mime = (string)@finfo_file($finfo, $it['tmp_name']);
                if (strpos($mime, 'image/') !== 0 && $mime !== 'application/pdf') {
                    $errors[] = $orig . ': نوع فایل مجاز نیست';
                    continue;
                }
            }
            if (!is_file($it['tmp_name'])) {
                $errors[] = $orig . ': فایل موقت یافت نشد.';
                continue;
            }
            try {
                $fileName = 'doc_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            } catch (Throwable $e) {
                $fileName = 'doc_' . date('Ymd') . '_' . substr(md5($orig . microtime(true)), 0, 16) . '.' . $ext;
            }
            $dest = $targetDir . $fileName;
            $ok = is_uploaded_file($it['tmp_name']) ? @move_uploaded_file($it['tmp_name'], $dest) : @copy($it['tmp_name'], $dest);
            if ($ok) {
                $paths[] = 'uploads/partnership/' . $fileName;
            } else {
                $errors[] = $orig . ': ذخیره ناموفق';
            }
        }
        if ($finfo) {
            @finfo_close($finfo);
        }
        return [$paths, $errors];
    }
}

if (!function_exists('office_save_partnership')) {
    /**
     * ثبت درخواست مشارکت از دفتر — همان sanitize/validate/INSERT سایت.
     * @return array{0: array|null, 1: string[], 2: string[]} [row(code,title...), errors, uploadErrors]
     */
    function office_save_partnership(PDO $pdo, array $post, array $files, int $adminId): array
    {
        $errors = [];
        $uploadErrors = [];
        $g = static fn(string $k, string $d = ''): string => trim((string)($post[$k] ?? $d));

        $ownerName = $g('owner_name');
        $phone = $g('phone');
        if ($ownerName === '') {
            $errors[] = 'لطفاً نام مالک را وارد کنید.';
        }
        if ($phone === '') {
            $errors[] = 'لطفاً موبایل مالک را وارد کنید.';
        }

        [$photoPaths, $e1] = office_store_part_docs($files['photos'] ?? null, true, 10);
        [$deedPaths, $e2] = office_store_part_docs($files['doc_deed'] ?? null, false);
        [$permitPaths, $e3] = office_store_part_docs($files['doc_permit'] ?? null, false);
        [$endjobPaths, $e4] = office_store_part_docs($files['doc_endjob'] ?? null, false);
        [$otherPaths, $e5] = office_store_part_docs($files['doc_other'] ?? null, true, 5);
        $uploadErrors = array_merge($e1, $e2, $e3, $e4, $e5);

        // همان شکل payload سایت (کلیدها عین JSON ساخته‌شده در JS)
        $in = [
            'property_type' => $g('property_type'),
            'area' => $g('area'),
            'current_status' => $g('current_status'),
            'photos' => $photoPaths,
            'neighborhood' => $g('neighborhood'),
            'address' => $g('address'),
            'latitude' => $g('map_lat'),
            'longitude' => $g('map_lng'),
            'location_source' => 'manual',
            'passage_width' => $g('passage_width'),
            'land_width' => $g('land_width'),
            'br_count' => $g('br_count'),
            'direction' => $g('direction'),
            'permit_status' => $g('permit_status'),
            'density' => $g('density'),
            'occupancy_rate' => $g('occupancy_rate'),
            'buildable_floors' => $g('buildable_floors'),
            'buildable_area' => $g('buildable_area'),
            'deed_status' => $g('deed_status'),
            'deed_kind' => $g('deed_kind'),
            'owners_count' => $g('owners_count'),
            'occupancy' => $g('occupancy'),
            'legal_status' => $post['legal_status'] ?? [],
            'doc_deed' => $deedPaths[0] ?? '',
            'doc_permit' => $permitPaths[0] ?? '',
            'doc_endjob' => $endjobPaths[0] ?? '',
            'doc_other' => $otherPaths,
        ];

        if (!function_exists('melkinoPartSanitize') || !function_exists('melkinoPartValidate') || !function_exists('melkinoPartCode')) {
            $errors[] = 'کتابخانه مشارکت در دسترس نیست.';
            return [null, $errors, $uploadErrors];
        }
        $mapRequire = false;
        if (function_exists('melkinoMapPublicSettings')) {
            try {
                $ps = melkinoMapPublicSettings($pdo);
                $mapRequire = !empty($ps['require_location']);
            } catch (Throwable $e) {
            }
        }
        $data = melkinoPartSanitize($in);
        foreach (melkinoPartValidate($data, $mapRequire) as $ve) {
            $errors[] = $ve;
        }
        if ($errors) {
            return [null, $errors, $uploadErrors];
        }

        if (function_exists('melkinoEnsurePartnershipSchema')) {
            try {
                melkinoEnsurePartnershipSchema($pdo);
            } catch (Throwable $e) {
            }
        }
        $code = melkinoPartCode();
        try {
            $st = $pdo->prepare("INSERT INTO partnership_requests (
                code, user_id, owner_name, phone, status,
                property_type, title, area, current_status, photos,
                city, neighborhood, address, latitude, longitude, location_source,
                passage_width, land_width, br_count, direction,
                building_age, current_floors, current_units, current_parkings, capacity_known,
                density, occupancy_rate, buildable_floors, buildable_area, buildable_units,
                permit_status, permit_number, permit_date, permit_floors, permit_area,
                owner_share, builder_share, balaghz, balaghz_amount, division_method, unit_shares,
                partner_parkings, partner_storage, duration, funding, value_from, value_to, notes,
                deed_status, deed_kind, owners_count, occupancy, legal_status,
                doc_deed, doc_permit, doc_endjob, doc_other, completeness, created_at
            ) VALUES (
                ?, ?, ?, ?, 'pending',
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, NOW()
            )");
            $st->execute([
                $code, null, $ownerName, $phone,
                $data['property_type'], $data['title'], $data['area'], $data['current_status'], $data['photos'],
                $data['city'], $data['neighborhood'], $data['address'], $data['latitude'], $data['longitude'], $data['location_source'],
                $data['passage_width'], $data['land_width'], $data['br_count'], $data['direction'],
                $data['building_age'], $data['current_floors'], $data['current_units'], $data['current_parkings'], $data['capacity_known'],
                $data['density'], $data['occupancy_rate'], $data['buildable_floors'], $data['buildable_area'], $data['buildable_units'],
                $data['permit_status'], $data['permit_number'], $data['permit_date'], $data['permit_floors'], $data['permit_area'],
                $data['owner_share'], $data['builder_share'], $data['balaghz'], $data['balaghz_amount'], $data['division_method'], $data['unit_shares'],
                $data['partner_parkings'], $data['partner_storage'], $data['duration'], $data['funding'], $data['value_from'], $data['value_to'], $data['notes'],
                $data['deed_status'], $data['deed_kind'], $data['owners_count'], $data['occupancy'], $data['legal_status'],
                $data['doc_deed'], $data['doc_permit'], $data['doc_endjob'], $data['doc_other'], $data['completeness'],
            ]);
        } catch (Throwable $e) {
            return [null, ['ثبت درخواست ناموفق بود؛ لطفاً دوباره تلاش کنید.'], $uploadErrors];
        }
        return [['code' => $code, 'title' => (string)$data['title'], 'phone' => $phone], [], $uploadErrors];
    }
}
