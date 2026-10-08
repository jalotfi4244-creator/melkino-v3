<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — بارگذاری و به‌روزرسانی فایل (ویرایش)
 *--------------------------------------------------------------------------
 * - خواندن: سطر ads + property_details + لینک‌های ad_amenities به همان
 *   نام‌فیلدهای فرم برمی‌گردند تا فرم ویرایش عین فرم ثبت پر شود.
 * - نوشتن: همان نگاشت savePropertyToDatabase (با همان توابع
 *   melkino_extract_normalized_fields و melkino_numeric) ولی UPDATE.
 * - دست‌نخورده می‌ماند: id، created_at، owner_user_id، consultant_id،
 *   custom_fields، price_hidden، created_by_telegram_id.
 * - عکس‌های قبلی نگه داشته می‌شوند؛ آپلودهای جدید ته صف اضافه می‌شوند.
 * - امکانات جایگزین می‌شوند (حذف لینک‌ها + لینک مجدد با همان
 *   melkino_amenity_id). updated_at به‌روز می‌شود.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms.php';

if (!function_exists('office_ad_type_key')) {
    function office_ad_type_key(mixed $propertyType): ?string
    {
        $pt = trim((string)$propertyType);
        if ($pt === 'آپارتمان') {
            return 'apartment';
        }
        if ($pt === 'زمین') {
            return 'land';
        }
        if ($pt === 'تجاری') {
            return 'commercial';
        }
        if ($pt === 'اداری') {
            return 'office';
        }
        if ($pt === 'باغ') {
            return 'garden';
        }
        if ($pt === 'ویلا' || $pt === 'ویلایی' || (function_exists('mb_strpos') ? mb_strpos($pt, 'ویلا') !== false : strpos($pt, 'ویلا') !== false)) {
            return 'villa';
        }
        return null;
    }
}

if (!function_exists('office_load_ad_for_edit')) {
    /**
     * @return array{type:string,row:array,values:array}|null
     */
    function office_load_ad_for_edit(PDO $pdo, string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return null;
        }
        if (!is_array($row)) {
            return null;
        }
        $type = office_ad_type_key($row['property_type'] ?? '');
        if ($type === null) {
            return null;
        }
        $det = [];
        try {
            $d = json_decode((string)($row['property_details'] ?? ''), true);
            if (is_array($d)) {
                $det = $d;
            }
        } catch (Throwable $e) {
        }
        $amen = [];
        try {
            $st = $pdo->prepare('SELECT a.name FROM ad_amenities l JOIN amenities a ON a.id = l.amenity_id WHERE l.ad_id = ?');
            $st->execute([$id]);
            $amen = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
        }

        $col = static fn(string $k, string $d = ''): string => trim((string)($row[$k] ?? $d));
        $chk = static fn(string $k): string => !empty($row[$k]) ? '1' : '';
        // نگاشت معکوس: کلید details (مقادیر خامِ ثبت‌شده) ← نام فیلد فرم
        $rev = [
            'apartment' => ['area_apt' => 'area', 'floor' => 'floor', 'rooms_apt' => 'rooms', 'year_apt' => 'year', 'flooring_apt' => 'flooring', 'cabinet_apt' => 'cabinet', 'cooling_apt' => 'cooling', 'heating_apt' => 'heating', 'total_units' => 'total_units', 'units_per_floor' => 'units_per_floor', 'apartment_type' => 'apartment_type'],
            'villa' => ['land_villa' => 'land_villa', 'built_villa' => 'built_villa', 'rooms_villa' => 'rooms', 'year_villa' => 'year', 'flooring_villa' => 'flooring', 'cabinet_villa' => 'cabinet', 'cooling_villa' => 'cooling', 'heating_villa' => 'heating', 'villa_type' => 'villa_type'],
            'land' => ['land_area' => 'land_area', 'land_type' => 'land_type', 'land_width' => 'land_width', 'land_length' => 'land_length', 'land_front_width' => 'land_front_width', 'land_blocks' => 'land_blocks', 'land_direction' => 'land_direction', 'land_shape' => 'land_shape', 'land_deed_status' => 'land_deed_status', 'land_deed_type' => 'land_deed_type', 'land_setback_status' => 'land_setback_status', 'land_ownership' => 'land_ownership'],
            'commercial' => ['area_comm' => 'area_comm', 'front_comm' => 'front_comm', 'floor_comm' => 'floor_comm', 'wall_comm' => 'wall_comm', 'cabinet_comm' => 'cabinet_comm', 'cooling_comm' => 'cooling_comm', 'heating_comm' => 'heating_comm', 'location_type_1' => 'location_type_1', 'location_type_2' => 'location_type_2', 'jobs_comm' => 'jobs_comm'],
            'office' => ['office_area' => 'office_area', 'office_floor' => 'office_floor', 'office_units_per_floor' => 'office_units_per_floor', 'office_rooms' => 'office_rooms', 'office_year' => 'office_year', 'office_condition' => 'office_condition', 'office_orientation' => 'office_orientation', 'office_usage' => 'office_usage'],
            'garden' => ['garden_area' => 'garden_area', 'tree_types' => 'tree_types', 'tree_age' => 'tree_age', 'irrigation_type' => 'irrigation_type', 'has_well' => 'has_well', 'water_share' => 'water_share', 'well_name' => 'well_name', 'has_pond' => 'has_pond', 'has_building' => 'has_building', 'building_area' => 'building_area', 'document_type' => 'document_type'],
        ];
        $amenField = ['apartment' => 'amenities_apt', 'villa' => 'amenities_villa', 'land' => 'land_amenities', 'commercial' => 'amenities_comm', 'office' => 'office_amenities', 'garden' => 'garden_amenities'][$type];

        $tx = $col('transaction_type');
        if ($tx === 'رهن و اجاره') {
            // برچسب قدیمی seed/لگاسی؛ معادل همان «اجاره» فرم است تا رادیو پر شود
            $tx = 'اجاره';
        }
        $values = [
            'transaction_type' => $tx,
            'status' => $col('status', 'published'),
            'is_not_keyed' => $chk('is_not_keyed'),
            'is_old' => $chk('is_old'),
            'is_renovated' => $chk('is_renovated'),
            'is_vacant' => $chk('is_vacant'),
            'gender' => $col('gender'),
            'last_name' => $col('last_name'),
            'phone' => $col('phone'),
            'telegram_id' => $col('telegram_id'),
            'title' => $col('title'),
            'location' => $col('location'),
            'address' => $col('address'),
            'map_lat' => $col('latitude'),
            'map_lng' => $col('longitude'),
            'price_sell' => $col('price_sell'),
            'price_condition' => $col('price_condition'),
            'deposit' => $col('deposit'),
            'rent_monthly' => $col('rent_monthly'),
            'full_rent_enabled' => $chk('full_rent_enabled'),
            'full_rent' => $col('full_rent'),
            'total_price' => $col('total_price'),
            'down_payment' => $col('down_payment'),
            'payment_terms' => $col('payment_terms'),
            'has_loan' => $chk('has_loan'),
            'loan_amount' => $col('loan_amount'),
            'loan_type' => $col('loan_type'),
            'loan_duration' => $col('loan_duration'),
            'loan_bank' => $col('loan_bank'),
            'loan_installment' => $col('loan_installment'),
            'loan_installments_paid' => $col('loan_installments_paid'),
            'loan_notes' => $col('loan_notes'),
            'deed_type' => $col('deed_type'),
            'deed_notes' => $col('deed_notes'),
            'exchange_interested' => $chk('exchange_interested'),
            'exchange_with' => $col('exchange_with'),
            'visit_hours' => $col('visit_hours'),
            'delivery_date' => $col('delivery_date'),
            'vacancy_date' => $col('vacancy_date'),
            'publish_photos' => $col('publish_photos', 'yes'),
            'full_description' => $col('description'),
            'building_age' => $col('building_age'),
        ];
        $exTypes = $col('exchange_types');
        if ($exTypes !== '') {
            $values['exchange_types'] = array_values(array_filter(array_map('trim', explode(',', $exTypes))));
        }
        foreach ((array)($rev[$type] ?? []) as $formKey => $detKey) {
            if (isset($det[$detKey]) && !is_array($det[$detKey]) && trim((string)$det[$detKey]) !== '') {
                $values[$formKey] = trim((string)$det[$detKey]);
            }
        }
        // فالبک ستون‌ها وقتی details خام ندارد (ردیف‌های قدیمی/دستی)
        $colFb = [
            'apartment' => ['area_apt' => 'area', 'floor' => 'floor', 'year_apt' => 'year'],
            'villa' => ['land_villa' => 'land_area', 'built_villa' => 'built_area', 'year_villa' => 'year'],
            'land' => ['land_area' => 'land_area', 'land_width' => 'land_width', 'land_length' => 'land_length'],
            'commercial' => ['area_comm' => 'area'],
            'office' => ['office_area' => 'area', 'office_floor' => 'floor', 'office_year' => 'year'],
            'garden' => ['garden_area' => 'land_area'],
        ];
        foreach ((array)($colFb[$type] ?? []) as $formKey => $colKey) {
            if (!isset($values[$formKey]) || $values[$formKey] === '') {
                $v = $col($colKey);
                if ($v !== '') {
                    $values[$formKey] = $v;
                }
            }
        }
        // اتاق: ستون عدد لاتین است ولی آپشن فارسی؛ details خام اولویت دارد
        $roomsFb = ['apartment' => 'rooms_apt', 'villa' => 'rooms_villa', 'office' => 'office_rooms'];
        if (isset($roomsFb[$type]) && (!isset($values[$roomsFb[$type]]) || $values[$roomsFb[$type]] === '')) {
            $rv = $col('rooms');
            if ($rv !== '') {
                $values[$roomsFb[$type]] = office_fa($rv);
            }
        }
        if ($amen) {
            $values[$amenField] = array_values($amen);
        }
        return ['type' => $type, 'row' => $row, 'values' => $values];
    }
}

if (!function_exists('office_update_ad')) {
    /**
     * به‌روزرسانی فایل با همان نگاشت savePropertyToDatabase (به‌صورت UPDATE).
     * @throws Throwable
     */
    function office_update_ad(PDO $pdo, string $id, array $newAd): bool
    {
        if (!function_exists('melkino_extract_normalized_fields') || !function_exists('melkino_numeric')) {
            throw new RuntimeException('property-db-helper در دسترس نیست.');
        }
        $norm = melkino_extract_normalized_fields($newAd);

        $priceSell = melkino_numeric($newAd['price_sell'] ?? null, 0);
        $deposit = melkino_numeric($newAd['deposit'] ?? null, 0);
        $rentMonthly = melkino_numeric($newAd['rent_monthly'] ?? null, 0);
        $fullRent = melkino_numeric($newAd['full_rent'] ?? null, 0);
        $fullRentEnabled = !empty($newAd['full_rent_enabled']) ? 1 : 0;
        $totalPrice = melkino_numeric($newAd['total_price'] ?? null, 0);
        $downPayment = melkino_numeric($newAd['down_payment'] ?? null, 0);
        $displayPrice = melkino_numeric($newAd['display_price'] ?? null, 0);

        $hasLoan = !empty($newAd['has_loan']) ? 1 : 0;
        $loanAmount = melkino_numeric($newAd['loan_amount'] ?? null, 0) ?: 0;
        $loanInstallment = melkino_numeric($newAd['loan_installment'] ?? null, 0) ?: 0;
        if ($hasLoan && $loanAmount <= 0) {
            $hasLoan = 0;
        }

        if (function_exists('melkinoEnsureAdsBuildingAgeColumn')) {
            try {
                melkinoEnsureAdsBuildingAgeColumn($pdo);
            } catch (Throwable $e) {
            }
        }
        $ageVal = null;
        if (function_exists('melkinoBuildingAge')) {
            try {
                $ageVal = melkinoBuildingAge($norm['year'] ?? $newAd['year'] ?? $newAd['office_year'] ?? '');
            } catch (Throwable $e) {
            }
            if ($ageVal !== null) {
                $newAd['building_age'] = $ageVal;
            }
        }

        // همان فهرست حذف هلپر برای ساخت property_details (کپی عین به عین)
        $details = $newAd;
        foreach ([
            'id', 'title', 'location', 'address', 'location_received', 'status', 'tags', 'gender', 'last_name', 'phone',
            'propertyType', 'property_type', 'transactionType', 'transaction_type', 'description', 'images', 'selectedImages',
            'publish_photos', 'created_at', 'price_sell', 'price_condition', 'deposit', 'rent_monthly', 'full_rent_enabled',
            'full_rent', 'total_price', 'down_payment', 'payment_terms', 'display_price', 'amenities',
            'is_not_keyed', 'delivery_date', 'vacancy_date', 'is_vacant', 'exchange_interested', 'exchange_with', 'visit_hours',
            'is_old', 'is_renovated', 'water_share', 'well_name',
            'has_loan', 'loan_amount', 'loan_type', 'loan_duration', 'loan_bank', 'loan_installment',
            'loan_installments_paid', 'loan_notes', 'deed_type', 'deed_notes', 'exchange_types',
            'telegram_id', 'owner_user_id',
            'latitude', 'longitude', 'location_source', 'location_accuracy', 'map_lat', 'map_lng', 'map_source', 'map_accuracy',
        ] as $remove) {
            unset($details[$remove]);
        }
        $tags = $newAd['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'UPDATE ads SET title = ?, status = ?, transaction_type = ?, property_type = ?,
                    gender = ?, last_name = ?, phone = ?, location = ?, address = ?, location_received = ?,
                    area = ?, land_area = ?, built_area = ?, rooms = ?, floor = ?, year = ?,
                    price_sell = ?, price_condition = ?, deposit = ?, rent_monthly = ?, full_rent_enabled = ?, full_rent = ?,
                    total_price = ?, down_payment = ?, payment_terms = ?, display_price = ?, description = ?, publish_photos = ?,
                    tags = ?, property_details = ?,
                    is_not_keyed = ?, delivery_date = ?, vacancy_date = ?, is_vacant = ?, exchange_interested = ?, exchange_with = ?, visit_hours = ?,
                    is_old = ?, is_renovated = ?, water_share = ?, well_name = ?,
                    has_loan = ?, loan_amount = ?, loan_type = ?, loan_duration = ?, loan_bank = ?,
                    loan_installment = ?, loan_installments_paid = ?, loan_notes = ?,
                    deed_type = ?, deed_notes = ?, exchange_types = ?,
                    building_age = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $st->execute([
                trim((string)($newAd['title'] ?? '')),
                trim((string)($newAd['status'] ?? 'pending')) ?: 'pending',
                $norm['transaction_type'],
                $norm['property_type'],
                trim((string)($newAd['gender'] ?? '')) ?: null,
                trim((string)($newAd['last_name'] ?? '')) ?: null,
                trim((string)($newAd['phone'] ?? '')) ?: null,
                trim((string)($newAd['location'] ?? '')) ?: null,
                trim((string)($newAd['address'] ?? '')) ?: null,
                trim((string)($newAd['location_received'] ?? '0')),
                $norm['area'],
                $norm['land_area'],
                $norm['built_area'],
                $norm['rooms'],
                $norm['floor'],
                $norm['year'],
                $priceSell,
                trim((string)($newAd['price_condition'] ?? '')) ?: null,
                $deposit,
                $rentMonthly,
                $fullRentEnabled,
                $fullRent,
                $totalPrice,
                $downPayment,
                trim((string)($newAd['payment_terms'] ?? '')) ?: null,
                $displayPrice,
                trim((string)($newAd['description'] ?? '')) ?: null,
                trim((string)($newAd['publish_photos'] ?? 'yes')),
                json_encode(array_values($tags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                !empty($newAd['is_not_keyed']) ? 1 : 0,
                !empty($newAd['delivery_date']) ? $newAd['delivery_date'] : null,
                !empty($newAd['vacancy_date']) ? $newAd['vacancy_date'] : null,
                !empty($newAd['is_vacant']) ? 1 : 0,
                !empty($newAd['exchange_interested']) ? 1 : 0,
                !empty($newAd['exchange_with']) ? (string)$newAd['exchange_with'] : null,
                !empty($newAd['visit_hours']) ? (string)$newAd['visit_hours'] : null,
                !empty($newAd['is_old']) ? 1 : 0,
                !empty($newAd['is_renovated']) ? 1 : 0,
                !empty($newAd['water_share']) ? (string)$newAd['water_share'] : null,
                !empty($newAd['well_name']) ? (string)$newAd['well_name'] : null,
                $hasLoan,
                $hasLoan ? (string)$loanAmount : null,
                $hasLoan ? (trim((string)($newAd['loan_type'] ?? '')) ?: null) : null,
                $hasLoan ? (trim((string)($newAd['loan_duration'] ?? '')) ?: null) : null,
                $hasLoan ? (trim((string)($newAd['loan_bank'] ?? '')) ?: null) : null,
                $hasLoan ? (string)$loanInstallment : null,
                $hasLoan ? (trim((string)($newAd['loan_installments_paid'] ?? '')) ?: null) : null,
                $hasLoan ? (trim((string)($newAd['loan_notes'] ?? '')) ?: null) : null,
                trim((string)($newAd['deed_type'] ?? '')) ?: null,
                trim((string)($newAd['deed_notes'] ?? '')) ?: null,
                trim((string)($newAd['exchange_types'] ?? '')) ?: null,
                $ageVal,
                $id,
            ]);

            // تلگرام: فقط وقتی فرم مقدار دارد (عین شاخه round-30 هلپر، بدون relink کاربر)
            $tg = trim((string)($newAd['telegram_id'] ?? ''));
            if ($tg !== '') {
                try {
                    $pdo->prepare('UPDATE ads SET telegram_id = ? WHERE id = ?')->execute([$tg, $id]);
                } catch (Throwable $e) {
                }
            }

            // عکس‌های جدید ته صف اضافه می‌شوند؛ قبلی‌ها دست‌نخورده
            $images = $newAd['selectedImages'] ?? $newAd['images'] ?? [];
            if (is_string($images)) {
                $images = array_values(array_filter(array_map('trim', explode(',', $images))));
            }
            if (!is_array($images)) {
                $images = [];
            }
            $images = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $images)));
            if ($images) {
                $maxSort = 0;
                try {
                    $maxSort = (int)($pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM images WHERE ad_id = ' . $pdo->quote($id))->fetchColumn());
                } catch (Throwable $e) {
                }
                $publicImages = strtolower(trim((string)($newAd['publish_photos'] ?? 'yes'))) === 'yes' ? 1 : 0;
                $ins = $pdo->prepare('INSERT INTO images (ad_id, filename, storage_path, sort_order, is_selected, is_primary, publish_publicly) VALUES (?, ?, ?, ?, 1, 0, ?)');
                foreach ($images as $k => $filename) {
                    $ins->execute([$id, $filename, $filename, $maxSort + 1 + $k, $publicImages]);
                }
            }

            // امکانات: جایگزینی کامل با همان melkino_amenity_id
            $amenities = $newAd['amenities'] ?? [];
            if (!is_array($amenities)) {
                $amenities = [$amenities];
            }
            $pdo->prepare('DELETE FROM ad_amenities WHERE ad_id = ?')->execute([$id]);
            if (function_exists('melkino_amenity_id')) {
                $propertyCategory = $norm['property_type'] ?? 'ملک';
                $link = $pdo->prepare('INSERT IGNORE INTO ad_amenities (ad_id, amenity_id) VALUES (?, ?)');
                foreach ($amenities as $am) {
                    if (is_array($am)) {
                        continue;
                    }
                    $amId = melkino_amenity_id($pdo, (string)$am, (string)$propertyCategory);
                    if ($amId !== null) {
                        $link->execute([$id, $amId]);
                    }
                }
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
