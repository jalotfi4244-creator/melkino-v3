<?php
declare(strict_types=1);

namespace Melkino\Support;

/**
 * Melkino V2 — canonical price normalization (spec §96, §128).
 * Inputs: price/price_sell/total_price/deposit/rent_monthly/full_rent/price_hidden/...
 * Output: sale|deposit|rent|combined|hidden (+ display strings). Never renders «0 | 0».
 */
final class Price
{
    public const SALE = 'sale';
    public const DEPOSIT = 'deposit';
    public const RENT = 'rent';
    public const COMBINED = 'combined';
    public const HIDDEN = 'hidden';
    public const UNKNOWN = 'unknown';

    /** @return array{kind:string,sale:float,deposit:float,rent:float,hidden:bool,label:string,short:string} */
    public static function normalize(array $ad): array
    {
        $num = static function (mixed $v): float {
            if ($v === null || $v === '') {
                return 0.0;
            }
            if (is_numeric($v)) {
                return (float)$v;
            }
            return Persian::normalizeMoney((string)$v);
        };

        $sale = $num($ad['price_sell'] ?? $ad['total_price'] ?? $ad['price'] ?? 0);
        $deposit = $num($ad['deposit'] ?? $ad['rahn'] ?? 0);
        $rent = $num($ad['rent_monthly'] ?? $ad['rent'] ?? $ad['ejareh'] ?? $ad['full_rent'] ?? 0);
        $hidden = !empty($ad['price_hidden']) || !empty($ad['hide_price']);

        // Global kill-switch (cached).
        try {
            $hideAll = (bool)\Melkino\Core\Cache::remember('price.hide_all', 120, static function () {
                if (function_exists('settings')) {
                    return (bool)settings()->getBool('global', 'hide_all_prices', false)
                        || !(bool)settings()->getBool('global', 'show_prices', true);
                }
                return false;
            });
            if ($hideAll) {
                $hidden = true;
            }
        } catch (\Throwable $ignored) {
        }

        if ($hidden) {
            return ['kind' => self::HIDDEN, 'sale' => 0, 'deposit' => 0, 'rent' => 0, 'hidden' => true, 'label' => 'تماس بگیرید', 'short' => 'تماس بگیرید'];
        }

        $hasSale = $sale > 0;
        $hasDeposit = $deposit > 0;
        $hasRent = $rent > 0;

        if ($hasSale && ($hasDeposit || $hasRent)) {
            $kind = self::COMBINED;
        } elseif ($hasSale) {
            $kind = self::SALE;
        } elseif ($hasDeposit && $hasRent) {
            $kind = self::COMBINED;
        } elseif ($hasDeposit) {
            $kind = self::DEPOSIT;
        } elseif ($hasRent) {
            $kind = self::RENT;
        } else {
            return ['kind' => self::UNKNOWN, 'sale' => 0, 'deposit' => 0, 'rent' => 0, 'hidden' => false, 'label' => 'قیمت توافقی', 'short' => 'توافقی'];
        }

        $parts = [];
        if ($hasSale) {
            $parts[] = Numbers::wordsFa($sale);
        }
        if ($hasDeposit) {
            $parts[] = 'ودیعه ' . Numbers::wordsFa($deposit);
        }
        if ($hasRent) {
            $parts[] = 'اجاره ' . Numbers::wordsFa($rent);
        }
        $label = implode(' · ', $parts);
        $short = $hasSale ? Numbers::wordsFa($sale) : ($hasDeposit ? Numbers::wordsFa($deposit) : Numbers::wordsFa($rent));

        return ['kind' => $kind, 'sale' => $sale, 'deposit' => $deposit, 'rent' => $rent, 'hidden' => false, 'label' => $label, 'short' => $short];
    }

    public static function label(array $ad): string
    {
        return self::normalize($ad)['label'];
    }

    public static function short(array $ad): string
    {
        return self::normalize($ad)['short'];
    }
}
