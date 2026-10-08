<?php
declare(strict_types=1);

namespace Melkino\Domain\Matching;

/**
 * Melkino V2 — CANONICAL k-combinations (spec §6).
 * Fixes «Cannot redeclare getCombinations» (property-request.php + my-request-matches.php).
 * Both legacy files now delegate to melkinoCombinations().
 */
final class Combinations
{
    /** @return array<int,array> */
    public static function of(array $items, int $k): array
    {
        $items = array_values($items);
        $n = count($items);
        if ($k <= 0 || $k > $n) {
            return $k === 0 ? [[]] : [];
        }
        $out = [];
        $idx = range(0, $k - 1);
        while (true) {
            $combo = [];
            foreach ($idx as $i) {
                $combo[] = $items[$i];
            }
            $out[] = $combo;
            $p = $k - 1;
            while ($p >= 0 && $idx[$p] === $n - $k + $p) {
                $p--;
            }
            if ($p < 0) {
                break;
            }
            $idx[$p]++;
            for ($i = $p + 1; $i < $k; $i++) {
                $idx[$i] = $idx[$i - 1] + 1;
            }
        }
        return $out;
    }
}

if (!function_exists('melkinoCombinations')) {
    function melkinoCombinations(array $items, int $k): array
    {
        return Combinations::of($items, $k);
    }
}

// Backward-compatible global (guarded — the historic fatal is gone).
if (!function_exists('getCombinations')) {
    function getCombinations(array $array, int $size): array
    {
        return Combinations::of($array, $size);
    }
}
