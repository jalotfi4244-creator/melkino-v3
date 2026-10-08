<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

/** Melkino V2 — responsive field rules (which fields collapse on small screens). */
final class ResponsiveVisibility
{
    /** @return array<string,string> field => breakpoint class ('' = always visible) */
    public static function classes(): array
    {
        return [
            'price' => '',
            'area' => '',
            'rooms' => '',
            'floor' => 'mx-hide-sm',
            'location' => '',
            'year' => 'mx-hide-md',
        ];
    }

    public static function classFor(string $field): string
    {
        return self::classes()[$field] ?? '';
    }
}
