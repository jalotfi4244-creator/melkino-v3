<?php
declare(strict_types=1);

namespace Melkino\Support;

/**
 * Melkino V2 — canonical Persian/digit/phone helpers (spec §97, §98, §128).
 * The ONLY digit/phone normalizer in new code; legacy aliases delegate here.
 */
final class Persian
{
    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const AR = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const EN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function toEnglishDigits(string $s): string
    {
        return str_replace(array_merge(self::FA, self::AR), array_merge(self::EN, self::EN), $s);
    }

    public static function toPersianDigits(string $s): string
    {
        return str_replace(self::EN, self::FA, $s);
    }

    /** Canonical phone: 09XXXXXXXXX (accepts +98/0098/98/9…/فارسی). Returns '' when invalid-ish. */
    public static function normalizePhone(string $raw): string
    {
        $p = self::toEnglishDigits(trim($raw));
        $p = preg_replace('/[\s\-\(\)]/', '', $p) ?? '';
        if (str_starts_with($p, '+98')) {
            $p = '0' . substr($p, 3);
        } elseif (str_starts_with($p, '0098')) {
            $p = '0' . substr($p, 4);
        } elseif (str_starts_with($p, '98') && strlen($p) === 12) {
            $p = '0' . substr($p, 2);
        } elseif (preg_match('/^9\d{9}$/', $p)) {
            $p = '0' . $p;
        }
        $p = preg_replace('/\D/', '', $p) ?? '';
        return $p;
    }

    public static function isValidPhone(string $raw): bool
    {
        return (bool)preg_match('/^09\d{9}$/', self::normalizePhone($raw));
    }

    /** Mask for display: 0912****345 */
    public static function maskPhone(string $phone): string
    {
        $p = self::normalizePhone($phone);
        if (strlen($p) !== 11) {
            return $phone;
        }
        return substr($p, 0, 4) . '****' . substr($p, 8);
    }

    /** Extract plain number from money/area strings (میلیارد/تومان/٬/… tolerated). */
    public static function normalizeMoney(string $raw): float
    {
        $s = self::toEnglishDigits($raw);
        $s = str_replace([',', '٬', '٫', ' ', ' ', 'تومان', 'ریال', 'TMN', 'tmn'], '', $s);
        if (preg_match('/(-?\d+(?:\.\d+)?)/', $s, $m)) {
            return (float)$m[1];
        }
        return 0.0;
    }

    public static function normalizeArea(string $raw): float
    {
        $s = self::toEnglishDigits($raw);
        $s = str_replace([',', '٬', ' ', 'متر', 'مربع', 'm²', 'm2'], '', $s);
        if (preg_match('/(-?\d+(?:\.\d+)?)/', $s, $m)) {
            return (float)$m[1];
        }
        return 0.0;
    }

    public static function faNumber(int|float $n, int $decimals = 0): string
    {
        return self::toPersianDigits(number_format($n, $decimals));
    }
}
