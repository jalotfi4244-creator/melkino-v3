<?php
declare(strict_types=1);

/**
 * Melkino V2 — visit card view-model functions, extracted VERBATIM from visits.php
 * (guarded; the legacy copy keeps working standalone via ?legacy=1).
 */

if (!function_exists('melkinoVisitsPageDigits')) {
    function melkinoVisitsPageDigits(string $v): string
    {
        return strtr(trim($v), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}

if (!function_exists('melkinoVisitsPageFa')) {
    /** ارقام فارسی با strtr آرایه‌ای — strtr سه‌آرگومانی UTF-8 را خراب می‌کند. */
    function melkinoVisitsPageFa(string $v): string
    {
        return strtr($v, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}

if (!function_exists('melkinoVisitsPageDate')) {
    /** تاریخ کارت — مستقل از باگ‌های قدیمی WhenParts */
    function melkinoVisitsPageDate(array $row): array
    {
        $slots = ['morning' => 'صبح', 'evening' => 'عصر'];
        $slotKey = trim((string) ($row['time_slot'] ?? ''));
        $slot = $slots[$slotKey] ?? ($slotKey !== '' ? $slotKey : 'صبح');
        $weekday = trim((string) ($row['weekday'] ?? ''));
        $rawG = $row['preferred_date'] ?? '';
        if ($rawG instanceof DateTimeInterface) {
            $rawG = $rawG->format('Y-m-d');
        }
        $g = melkinoVisitsPageDigits((string) $rawG);
        $fa = melkinoVisitsPageDigits((string) ($row['preferred_date_fa'] ?? ''));
        $months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        $jy = 0;
        $jm = 0;
        $jd = 0;
        $apply = static function (string $s) use (&$jy, &$jm, &$jd): void {
            if ($jy > 0 || !preg_match('/(\d{4})[\/\.\-](\d{1,2})[\/\.\-](\d{1,2})/', $s, $m)) {
                return;
            }
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            if ($mo < 1 || $mo > 12 || $d < 1 || $d > 31) {
                return;
            }
            if ($y >= 1700 && $y <= 2500 && function_exists('melkinoGregorianToJalali')) {
                [$jy, $jm, $jd] = melkinoGregorianToJalali($y, $mo, $d);
                return;
            }
            if ($y >= 1300 && $y <= 1599) {
                $jy = $y;
                $jm = $mo;
                $jd = $d;
            }
        };
        $apply($g);
        $apply($fa);
        if ($jy <= 0 && $g !== '' && strpos($g, '0000') !== 0) {
            try {
                $dt = new DateTimeImmutable($g);
                $yy = (int) $dt->format('Y');
                if ($yy >= 1700 && function_exists('melkinoGregorianToJalali')) {
                    [$jy, $jm, $jd] = melkinoGregorianToJalali($yy, (int) $dt->format('n'), (int) $dt->format('j'));
                }
            } catch (Throwable $e) {
            }
        }
        if ($jy <= 0 && $fa !== '') {
            foreach ($months as $i => $name) {
                if (preg_match('/(\d{1,2})\s*' . preg_quote($name, '/') . '(?:\s+(\d{4}))?/u', $fa, $mm)) {
                    $jd = (int) $mm[1];
                    $jm = $i + 1;
                    $jy = isset($mm[2]) && $mm[2] !== '' ? (int) $mm[2] : (function_exists('melkinoJalaliCurrentYear') ? (int) melkinoJalaliCurrentYear() : 1405);
                    break;
                }
            }
        }
        if ($jy <= 0 && $weekday !== '') {
            try {
                $tz = new DateTimeZone('Asia/Tehran');
                $base = new DateTimeImmutable('now', $tz);
                $rawC = trim((string) ($row['created_at'] ?? ''));
                if ($rawC !== '' && strpos($rawC, '0000') !== 0) {
                    try {
                        $base = new DateTimeImmutable($rawC, $tz);
                    } catch (Throwable $e) {
                    }
                }
                $names = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
                $want = str_replace([' ', '‌', 'ي', 'ك'], ['', '', 'ی', 'ک'], $weekday);
                for ($i = 0; $i <= 14; $i++) {
                    $cand = $base->modify('+' . $i . ' day');
                    $have = str_replace([' ', '‌', 'ي', 'ك'], ['', '', 'ی', 'ک'], $names[(int) $cand->format('w')] ?? '');
                    if ($have === $want && function_exists('melkinoGregorianToJalali')) {
                        [$jy, $jm, $jd] = melkinoGregorianToJalali(
                            (int) $cand->format('Y'),
                            (int) $cand->format('n'),
                            (int) $cand->format('j')
                        );
                        break;
                    }
                }
            } catch (Throwable $e) {
            }
        }
        $dateLabel = '';
        if ($jd >= 1 && $jm >= 1 && $jm <= 12) {
            $dayFa = melkinoVisitsPageFa((string) $jd);
            $yearFa = $jy > 0 ? melkinoVisitsPageFa((string) $jy) : '';
            $dateLabel = trim($dayFa . ' ' . ($months[$jm - 1] ?? '') . ' ' . $yearFa);
        }
        if ($dateLabel === '' && $fa !== '') {
            $dateLabel = melkinoVisitsPageFa($fa);
        }
        if ($dateLabel === '') {
            $dateLabel = '—';
        }
        return [
            'weekday' => $weekday !== '' ? $weekday : '—',
            'date' => $dateLabel,
            'slot' => $slot,
        ];
    }
}

if (!function_exists('melkinoVisitsPageFacts')) {
    /** مشخصات کارت — خالی‌ها حذف؛ متراژ از عنوان «۷۰ متری»؛ کد از ad_id */
    function melkinoVisitsPageFacts(array $it, array $ad, string $adTitle, string $adId): array
    {
        $clean = static function ($v): string {
            $v = trim((string) $v);
            if ($v !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($v, 'UTF-8')) {
                return '';
            }
            $v = str_replace(["\xC2\xA0", "\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xEF\xBB\xBF"], '', $v);
            $v = trim($v);
            if ($v === '' || $v === '—' || $v === '-' || preg_match('/^(متر|تومان|اتاق|سال)$/u', $v)) {
                return '';
            }
            return $v;
        };
        $faN = static function ($v): string {
            $v = trim((string) $v);
            return function_exists('melkinoVisitsPageFa') ? melkinoVisitsPageFa($v) : $v;
        };
        $raw = [];
        $out = [];
        $have = [];
        foreach ($raw as $f) {
            $lab = trim((string) ($f[0] ?? ''));
            $val = $clean($f[1] ?? '');
            if ($lab === '' || $val === '') {
                continue;
            }
            $out[] = [$lab, $val];
            $have[$lab] = true;
        }
        $live = is_array($it['_ad'] ?? null) ? $it['_ad'] : [];
        $snap = [];
        if (!empty($it['ad_snapshot'])) {
            $d = json_decode((string) $it['ad_snapshot'], true);
            if (is_array($d)) {
                $snap = $d;
            }
        }
        $details = [];
        $pd = $live['property_details'] ?? ($ad['property_details'] ?? null);
        if (is_string($pd) && $pd !== '') {
            $dec = json_decode($pd, true);
            $details = is_array($dec) ? $dec : [];
        } elseif (is_array($pd)) {
            $details = $pd;
        }
        $pick = static function (array $keys) use ($live, $ad, $snap, $details): string {
            foreach ($keys as $k) {
                foreach ([$details, $live, $ad, $snap] as $src) {
                    if (!is_array($src) || !isset($src[$k])) {
                        continue;
                    }
                    $v = $src[$k];
                    if (is_array($v)) {
                        $v = $v['value'] ?? $v['val'] ?? $v['text'] ?? '';
                    }
                    $v = trim((string) $v);
                    if ($v !== '' && $v !== '0' && $v !== '۰' && $v !== '0.0') {
                        return $v;
                    }
                }
            }
            return '';
        };
        $titleN = melkinoVisitsPageDigits($adTitle);
        if (empty($have['متراژ'])) {
            $area = $pick(['area', 'area_apt', 'built_area', 'land_area', 'office_area']);
            if ($area === '' && preg_match('/(\d+(?:\.\d+)?)\s*متر/u', $titleN, $tm)) {
                $area = $tm[1];
            }
            if ($area !== '') {
                $out[] = ['متراژ', $faN($area) . (preg_match('/متر/u', $area) ? '' : ' متر')];
                $have['متراژ'] = true;
            }
        }
        if (empty($have['خواب'])) {
            $rooms = $pick(['rooms', 'rooms_apt', 'rooms_villa', 'office_rooms']);
            if ($rooms === '' && preg_match('/(\d+)\s*(?:خواب|اتاق)/u', $titleN, $tr)) {
                $rooms = $tr[1];
            }
            if ($rooms !== '') {
                $out[] = ['خواب', $faN($rooms)];
                $have['خواب'] = true;
            }
        }
        if (empty($have['طبقه'])) {
            $floor = $pick(['floor', 'floor_apt', 'office_floor']);
            if ($floor !== '') {
                $out[] = ['طبقه', $faN($floor)];
                $have['طبقه'] = true;
            }
        }
        if (empty($have['سال ساخت'])) {
            $year = $pick(['year', 'year_apt', 'year_villa', 'office_year', 'build_year']);
            if ($year !== '') {
                $out[] = ['سال ساخت', $faN($year)];
                $have['سال ساخت'] = true;
                if (empty($have['سن بنا']) && function_exists('melkinoBuildingAge')) {
                    $ageN = melkinoBuildingAge($year);
                    if ($ageN !== null) {
                        $out[] = ['سن بنا', $faN((string) $ageN)];
                        $have['سن بنا'] = true;
                    }
                }
            }
        }
        $money = static function ($raw) use ($faN): string {
            $s = melkinoVisitsPageDigits(trim((string) $raw));
            $s = str_replace([',', '٬', '،', ' ', 'تومان', 'ریال'], '', $s);
            if ($s === '' || !is_numeric($s)) {
                return '';
            }
            $n = (float) $s;
            if ($n <= 0) {
                return '';
            }
            return $faN(number_format($n, 0, '.', ',')) . ' تومان';
        };
        $tx = $pick(['transaction_type', 'transactionType']);
        $txN = str_replace(['‌', ' ', 'ي', 'ك'], ['', '', 'ی', 'ک'], $tx);
        $isRent = $txN !== '' && mb_strpos($txN, 'فروش') === false
            && (mb_strpos($txN, 'رهن') !== false || mb_strpos($txN, 'اجاره') !== false);
        if ($isRent) {
            if (empty($have['رهن'])) {
                $dep = $money($pick(['deposit']));
                if ($dep !== '') {
                    $out[] = ['رهن', $dep];
                    $have['رهن'] = true;
                }
            }
            if (empty($have['اجاره'])) {
                $rent = $money($pick(['rent_monthly', 'rent']));
                if ($rent !== '') {
                    $out[] = ['اجاره', $rent];
                    $have['اجاره'] = true;
                }
            }
        } elseif (empty($have['قیمت'])) {
            $price = $money($pick(['price_sell', 'total_price', 'display_price', 'price']));
            if ($price !== '') {
                $out[] = ['قیمت', $price];
                $have['قیمت'] = true;
            }
        }
        if (empty($have['کد آگهی']) && $adId !== '' && $adId !== '0') {
            $out[] = ['کد آگهی', $faN($adId)];
        }
        return $out;
    }
}
