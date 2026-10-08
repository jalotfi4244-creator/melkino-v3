<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

/** Melkino V2 — resolves raw values from an ad row via ordered source columns. */
final class FieldResolver
{
    public static function raw(array $ad, array $def): mixed
    {
        foreach ($def['sources'] ?? [] as $col) {
            if (isset($ad[$col]) && $ad[$col] !== '' && $ad[$col] !== null) {
                return $ad[$col];
            }
        }
        return null;
    }

    public static function formatted(array $ad, array $def): string
    {
        $raw = self::raw($ad, $def);
        if ($raw === null || $raw === '') {
            return '';
        }
        return FieldFormatter::format($raw, (string)($def['format'] ?? 'text'));
    }
}
