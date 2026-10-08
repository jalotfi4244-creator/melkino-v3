<?php
declare(strict_types=1);

namespace Melkino\Support;

/** Melkino V2 — number formatting (delegates digits to Persian). */
final class Numbers
{
    public static function format(int|float $n, int $decimals = 0): string
    {
        return number_format($n, $decimals);
    }

    public static function formatFa(int|float $n, int $decimals = 0): string
    {
        return Persian::toPersianDigits(number_format($n, $decimals));
    }

    /** 4800000000 => «۴.۸ میلیارد تومان» */
    public static function wordsFa(float $amount, string $unit = 'تومان'): string
    {
        $a = abs($amount);
        if ($a >= 1_000_000_000) {
            $v = $amount / 1_000_000_000;
            $label = 'میلیارد';
        } elseif ($a >= 1_000_000) {
            $v = $amount / 1_000_000;
            $label = 'میلیون';
        } elseif ($a >= 1_000) {
            $v = $amount / 1_000;
            $label = 'هزار';
        } else {
            return Persian::toPersianDigits((string)(int)$amount) . ' ' . $unit;
        }
        $str = rtrim(rtrim(number_format($v, 2), '0'), '.');
        return Persian::toPersianDigits($str) . ' ' . $label . ' ' . $unit;
    }

    public static function percent(float $ratio): string
    {
        return Persian::toPersianDigits((string)(int)round($ratio * 100)) . '٪';
    }
}
