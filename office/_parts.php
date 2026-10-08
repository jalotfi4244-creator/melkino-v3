<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — مدیریت مشارکت در ساخت (آینه کنسول ادمین سایت، مرحله ۷)
 *--------------------------------------------------------------------------
 * قرارداد عین سایت (views/legacy/admin-partnership-legacy.php):
 * - لیست: همان ستون‌ها/فیلتر وضعیت/جست‌وجو/شمارش + صفحه‌بندی دفتر.
 * - جزئیات: همان ردیف کامل partnership_requests.
 * - وضعیت: همان ۷ حالت melkinoPartStatuses + updated_at.
 * - یادداشت: همان admin_note (‏strip_tags‏، حداکثر ۱۰۰۰ نویسه).
 * - حذف: همان DELETE (آینهٔ PRT عین سایت دست‌نخورده می‌ماند).
 * - انتشار: همان منطق site_publish (آینهٔ ads با id برابر PRT-{id}).
 * - ویرایش: همان کالکتور ثبت (melkinoPartSanitize/Validate) + UPDATE؛
 *   ستون‌های خارج از فرم (city/notes/مراحل حذف‌شده/‎code/user_id/admin_note‎)
 *   عین رفتار partial-safe ادمین سایت دست‌نخورده می‌مانند.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_forms3.php';

if (!function_exists('office_part_statuses')) {
    /** @return array<string,string> کد => برچسب فارسی (تک‌منبع: سایت) */
    function office_part_statuses(): array
    {
        return function_exists('melkinoPartStatuses') ? (array)melkinoPartStatuses() : [];
    }
}

if (!function_exists('office_part_ensure')) {
    function office_part_ensure(PDO $pdo): void
    {
        if (function_exists('melkinoEnsurePartnershipSchema')) {
            try {
                melkinoEnsurePartnershipSchema($pdo);
            } catch (Throwable $e) {
            }
        }
    }
}

if (!function_exists('office_part_get')) {
    /** @return array<string,mixed>|null */
    function office_part_get(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        office_part_ensure($pdo);
        try {
            $st = $pdo->prepare('SELECT * FROM partnership_requests WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_part_counts')) {
    /** @return array<string,int> شمارش هر وضعیت (عین کوئری سایت) */
    function office_part_counts(PDO $pdo): array
    {
        $out = [];
        office_part_ensure($pdo);
        try {
            foreach ($pdo->query('SELECT status, COUNT(*) c FROM partnership_requests GROUP BY status') as $r) {
                $out[(string)($r['status'] ?? '')] = (int)$r['c'];
            }
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('office_part_list')) {
    /**
     * لیست صفحه‌بندی‌شده با همان فیلتر/جست‌وجوی سایت.
     * @return array{0: array<int,array<string,mixed>>, 1: int} [rows, total]
     */
    function office_part_list(PDO $pdo, string $status, string $q, int $page, int $perPage = 25): array
    {
        office_part_ensure($pdo);
        $statuses = office_part_statuses();
        if ($status !== '' && !array_key_exists($status, $statuses)) {
            $status = '';
        }
        $cols = 'id, code, status, title, property_type, area, current_status, city, neighborhood,
                 owner_share, builder_share, balaghz, balaghz_amount, value_from, value_to,
                 owner_name, phone, completeness, created_at';
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(code LIKE ? OR title LIKE ? OR city LIKE ? OR neighborhood LIKE ? OR phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM partnership_requests $whereSql");
            $st->execute($params);
            $total = (int)$st->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min(max(1, $page), $pages);
            $off = ($page - 1) * $perPage;
            $st = $pdo->prepare(
                "SELECT $cols FROM partnership_requests $whereSql ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $off"
            );
            $st->execute($params);
            return [$st->fetchAll(PDO::FETCH_ASSOC) ?: [], $total];
        } catch (Throwable $e) {
            return [[], 0];
        }
    }
}

if (!function_exists('office_part_set_status')) {
    /** تغییر وضعیت با whitelist سایت؛ نامعتبر ← false و بدون تغییر. */
    function office_part_set_status(PDO $pdo, int $id, string $status): bool
    {
        if ($id <= 0 || !array_key_exists($status, office_part_statuses())) {
            return false;
        }
        office_part_ensure($pdo);
        try {
            $st = $pdo->prepare('UPDATE partnership_requests SET status = ?, updated_at = NOW() WHERE id = ?');
            return $st->execute([$status, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_part_save_note')) {
    /** یادداشت ادمین — عین اکشن note سایت. */
    function office_part_save_note(PDO $pdo, int $id, string $note): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_part_ensure($pdo);
        $note = function_exists('mb_substr')
            ? mb_substr(trim(strip_tags($note)), 0, 1000)
            : substr(trim(strip_tags($note)), 0, 1000);
        try {
            $st = $pdo->prepare('UPDATE partnership_requests SET admin_note = ?, updated_at = NOW() WHERE id = ?');
            return $st->execute([$note, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_part_delete')) {
    /** حذف درخواست — عین اکشن delete سایت (آینهٔ PRT دست‌نخورده). */
    function office_part_delete(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        office_part_ensure($pdo);
        try {
            $st = $pdo->prepare('DELETE FROM partnership_requests WHERE id = ?');
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_part_mirror_id')) {
    function office_part_mirror_id(int $pid): string
    {
        return 'PRT-' . $pid;
    }
}

if (!function_exists('office_part_mirror_status')) {
    /** وضعیت آینهٔ ads؛ رشته خالی یعنی آینه‌ای وجود ندارد. */
    function office_part_mirror_status(PDO $pdo, int $pid): string
    {
        if ($pid <= 0) {
            return '';
        }
        try {
            $st = $pdo->prepare('SELECT status FROM ads WHERE id = ? LIMIT 1');
            $st->execute([office_part_mirror_id($pid)]);
            return (string)($st->fetchColumn() ?: '');
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('office_part_publish')) {
    /**
     * انتشار/قطع انتشار در سایت — همان منطق site_publish ادمین سایت.
     * @return array{published:bool, message:string}
     */
    function office_part_publish(PDO $pdo, array $pr, bool $on): array
    {
        $pid = (int)($pr['id'] ?? 0);
        if ($pid <= 0) {
            return ['published' => false, 'message' => 'شناسه نامعتبر است.'];
        }
        $mirrorId = office_part_mirror_id($pid);
        $mirrorStatus = office_part_mirror_status($pdo, $pid);
        if (!$on) {
            try {
                $pdo->prepare('UPDATE ads SET status = \'archived\', updated_at = NOW() WHERE id = ?')->execute([$mirrorId]);
            } catch (Throwable $e) {
                return ['published' => $mirrorStatus === 'published', 'message' => 'قطع انتشار ناموفق بود.'];
            }
            if (function_exists('melkinoLogAdHistory')) {
                try {
                    melkinoLogAdHistory($pdo, $mirrorId, 'قطع انتشار مشارکت', 'درخواست مشارکت #' . $pid);
                } catch (Throwable $e) {
                }
            }
            return ['published' => false, 'message' => 'انتشار مشارکت از سایت قطع شد.'];
        }
        $loc = trim(($pr['city'] ?? '') . (($pr['neighborhood'] ?? '') !== '' ? '، ' . $pr['neighborhood'] : ''));
        $descParts = [];
        if (!empty($pr['current_status'])) $descParts[] = 'وضعیت فعلی: ' . $pr['current_status'];
        if (!empty($pr['br_count'])) $descParts[] = 'بر: ' . $pr['br_count'];
        if (!empty($pr['direction'])) $descParts[] = 'جهت: ' . $pr['direction'];
        if (!empty($pr['buildable_floors'])) $descParts[] = 'تراکم قابل ساخت: ' . $pr['buildable_floors'] . ' طبقه';
        if (!empty($pr['buildable_units'])) $descParts[] = 'تعداد واحد قابل ساخت: ' . $pr['buildable_units'];
        if (!empty($pr['owner_share'])) $descParts[] = 'سهم مالک: ' . $pr['owner_share'];
        if (!empty($pr['duration'])) $descParts[] = 'مدت: ' . $pr['duration'];
        $desc = 'مشارکت در ساخت — ' . implode(' · ', array_filter($descParts));
        if (!empty($pr['notes'])) $desc .= "\n" . mb_substr((string)$pr['notes'], 0, 800);
        $fields = [
            ':id' => $mirrorId,
            ':title' => mb_substr((string)($pr['title'] ?? 'مشارکت در ساخت'), 0, 180),
            ':pt' => (string)($pr['property_type'] ?? ''),
            ':area' => (string)($pr['area'] ?? ''),
            ':loc' => mb_substr($loc !== '' ? $loc : 'مشارکت در ساخت', 0, 250),
            ':addr' => mb_substr((string)($pr['address'] ?? ''), 0, 500),
            ':phone' => (string)($pr['phone'] ?? ''),
            ':owner' => mb_substr((string)($pr['owner_name'] ?? ''), 0, 120),
            ':desc' => $desc,
            ':lat' => ($pr['latitude'] ?? null) !== null && $pr['latitude'] !== '' ? (float)$pr['latitude'] : null,
            ':lng' => ($pr['longitude'] ?? null) !== null && $pr['longitude'] !== '' ? (float)$pr['longitude'] : null,
        ];
        try {
            if ($mirrorStatus !== '') {
                $pdo->prepare("UPDATE ads SET title=:title, property_type=:pt, area=:area, location=:loc, address=:addr, phone=:phone, last_name=:owner, description=:desc, latitude=:lat, longitude=:lng, status='published', updated_at=NOW() WHERE id=:id")
                    ->execute($fields);
            } else {
                $pdo->prepare("INSERT INTO ads (id, title, status, transaction_type, property_type, area, location, address, phone, last_name, description, latitude, longitude, created_at, updated_at)
                               VALUES (:id, :title, 'published', 'مشارکت در ساخت', :pt, :area, :loc, :addr, :phone, :owner, :desc, :lat, :lng, NOW(), NOW())")
                    ->execute($fields);
            }
        } catch (Throwable $e) {
            return ['published' => false, 'message' => 'انتشار ناموفق بود؛ لطفاً دوباره تلاش کنید.'];
        }
        if (function_exists('melkinoLogAdHistory')) {
            try {
                melkinoLogAdHistory($pdo, $mirrorId, 'انتشار مشارکت در سایت', 'درخواست مشارکت #' . $pid);
            } catch (Throwable $e) {
            }
        }
        return ['published' => true, 'message' => 'مشارکت در سایت منتشر شد.'];
    }
}

if (!function_exists('office_part_json_list')) {
    /** @return string[] */
    function office_part_json_list(mixed $v): array
    {
        if (is_array($v)) {
            return array_values(array_filter(array_map('strval', $v), static fn($s) => $s !== ''));
        }
        $a = json_decode((string)$v, true);
        if (!is_array($a)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $a), static fn($s) => $s !== ''));
    }
}

if (!function_exists('office_update_partnership')) {
    /**
     * ویرایش درخواست مشارکت — همان کالکتور ثبت + UPDATE.
     * عکس/مدرک جدید به قبلی‌ها اضافه می‌شود؛ وضعیت نامعتبر/ارسال‌نشده ← حفظ قبلی.
     * ستون‌های خارج از فرم (city/notes/مراحل حذف‌شده/code/user_id/admin_note/created_at)
     * عین رفتار partial-safe ادمین سایت دست‌نخورده می‌مانند.
     * @return array{0: array|null, 1: string[], 2: string[]} [row, errors, uploadErrors]
     */
    function office_update_partnership(PDO $pdo, int $id, array $post, array $files): array
    {
        $errors = [];
        $uploadErrors = [];
        $old = office_part_get($pdo, $id);
        if (!$old) {
            return [null, ['درخواست یافت نشد.'], []];
        }
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

        // همان شکل payload ثبت؛ فایلِ ارسال‌نشده ← حفظ قبلی (الحاق برای چندتایی‌ها)
        $in = [
            'property_type' => $g('property_type'),
            'area' => $g('area'),
            'current_status' => $g('current_status'),
            'photos' => array_merge(office_part_json_list($old['photos'] ?? '[]'), $photoPaths),
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
            'doc_deed' => $deedPaths[0] ?? (string)($old['doc_deed'] ?? ''),
            'doc_permit' => $permitPaths[0] ?? (string)($old['doc_permit'] ?? ''),
            'doc_endjob' => $endjobPaths[0] ?? (string)($old['doc_endjob'] ?? ''),
            'doc_other' => array_merge(office_part_json_list($old['doc_other'] ?? '[]'), $otherPaths),
        ];

        if (!function_exists('melkinoPartSanitize') || !function_exists('melkinoPartValidate')) {
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

        $statuses = office_part_statuses();
        $newStatus = array_key_exists('status', $post) ? trim((string)$post['status']) : (string)($old['status'] ?? 'pending');
        if (!array_key_exists($newStatus, $statuses)) {
            $newStatus = (string)($old['status'] ?? 'pending');
        }

        $set = [
            'owner_name' => $ownerName,
            'phone' => $phone,
            'property_type' => $data['property_type'],
            'title' => $data['title'],
            'area' => $data['area'],
            'current_status' => $data['current_status'],
            'photos' => $data['photos'],
            'neighborhood' => $data['neighborhood'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'location_source' => $data['location_source'],
            'passage_width' => $data['passage_width'],
            'land_width' => $data['land_width'],
            'br_count' => $data['br_count'],
            'direction' => $data['direction'],
            'capacity_known' => $data['capacity_known'],
            'density' => $data['density'],
            'occupancy_rate' => $data['occupancy_rate'],
            'buildable_floors' => $data['buildable_floors'],
            'buildable_area' => $data['buildable_area'],
            'permit_status' => $data['permit_status'],
            'deed_status' => $data['deed_status'],
            'deed_kind' => $data['deed_kind'],
            'owners_count' => $data['owners_count'],
            'occupancy' => $data['occupancy'],
            'legal_status' => $data['legal_status'],
            'doc_deed' => $data['doc_deed'],
            'doc_permit' => $data['doc_permit'],
            'doc_endjob' => $data['doc_endjob'],
            'doc_other' => $data['doc_other'],
            'completeness' => $data['completeness'],
            'status' => $newStatus,
        ];
        $assign = implode(', ', array_map(static fn($k) => "`$k` = ?", array_keys($set)));
        try {
            $st = $pdo->prepare("UPDATE partnership_requests SET $assign, updated_at = NOW() WHERE id = ?");
            $st->execute(array_merge(array_values($set), [$id]));
        } catch (Throwable $e) {
            return [null, ['ذخیره تغییرات ناموفق بود؛ لطفاً دوباره تلاش کنید.'], $uploadErrors];
        }
        return [office_part_get($pdo, $id) ?? ['id' => $id], [], $uploadErrors];
    }
}

if (!function_exists('office_part_load_for_edit')) {
    /**
     * نگاشت ردیف دیتابیس به مقادیر فرم ویرایش (همان نام‌های فرم ثبت).
     * @return array<string,mixed>
     */
    function office_part_load_for_edit(array $row): array
    {
        $lat = $row['latitude'] ?? null;
        $lng = $row['longitude'] ?? null;
        return [
            'owner_name' => (string)($row['owner_name'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'property_type' => (string)($row['property_type'] ?? ''),
            'area' => (string)($row['area'] ?? ''),
            'current_status' => (string)($row['current_status'] ?? ''),
            'neighborhood' => (string)($row['neighborhood'] ?? ''),
            'address' => (string)($row['address'] ?? ''),
            'map_lat' => ($lat === null || $lat === '') ? '' : (string)$lat,
            'map_lng' => ($lng === null || $lng === '') ? '' : (string)$lng,
            'passage_width' => (string)($row['passage_width'] ?? ''),
            'land_width' => (string)($row['land_width'] ?? ''),
            'br_count' => (string)($row['br_count'] ?? ''),
            'direction' => (string)($row['direction'] ?? ''),
            'permit_status' => (string)($row['permit_status'] ?? ''),
            'density' => (string)($row['density'] ?? ''),
            'occupancy_rate' => (string)($row['occupancy_rate'] ?? ''),
            'buildable_floors' => (string)($row['buildable_floors'] ?? ''),
            'buildable_area' => (string)($row['buildable_area'] ?? ''),
            'deed_status' => (string)($row['deed_status'] ?? ''),
            'deed_kind' => (string)($row['deed_kind'] ?? ''),
            'owners_count' => (string)($row['owners_count'] ?? ''),
            'occupancy' => (string)($row['occupancy'] ?? ''),
            'legal_status' => office_part_json_list($row['legal_status'] ?? '[]'),
            'status' => (string)($row['status'] ?? 'pending'),
        ];
    }
}
