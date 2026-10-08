<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

use Melkino\Support\Numbers;
use Melkino\Support\Persian;

/** Melkino V2 — typed field formatting (spec §47). */
final class FieldFormatter
{
    public static function format(mixed $raw, string $format): string
    {
        return match ($format) {
            'int' => Persian::toPersianDigits((string)(int)(float)$raw),
            'price' => is_numeric($raw) && (float)$raw > 0 ? Numbers::wordsFa((float)$raw) : '',
            'area' => is_numeric($raw) && (float)$raw > 0 ? Persian::toPersianDigits((string)(int)(float)$raw) . ' متر' : '',
            'year' => Persian::toPersianDigits((string)$raw),
            'bool' => !empty($raw) ? 'دارد' : 'ندارد',
            default => Persian::toPersianDigits((string)$raw),
        };
    }
}
