<?php
declare(strict_types=1);

/**
 * Melkino V2 — canonical k-combinations (exact port of the legacy getCombinations recursion
 * from property-request.php; semantics preserved: size>n → [], size==0 → [[]]).
 */
if (!function_exists('melkinoCombinations')) {
    function melkinoCombinations(array $array, int $size): array
    {
        $result = [];
        $n = count($array);
        if ($size > $n) {
            return $result;
        }
        if ($size === 0) {
            return [[]];
        }
        for ($i = 0; $i < $n - $size + 1; $i++) {
            $first = $array[$i];
            $rest = array_slice($array, $i + 1);
            foreach (melkinoCombinations($rest, $size - 1) as $combo) {
                $result[] = array_merge([$first], $combo);
            }
        }
        return $result;
    }
}
