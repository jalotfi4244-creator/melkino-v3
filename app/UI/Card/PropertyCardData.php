<?php
declare(strict_types=1);

namespace Melkino\UI\Card;

/**
 * Melkino V2 — card data contract (spec §150).
 * The renderer accepts ONLY this normalized shape, never raw DB rows.
 */
final class PropertyCardData
{
    /** @param array<string,mixed> $dto @return array<string,mixed> normalized */
    public static function normalize(array $dto): array
    {
        return [
            'id' => (int)($dto['id'] ?? 0),
            'title' => (string)($dto['title'] ?? 'ملک'),
            'transaction' => (string)($dto['transaction'] ?? ''),
            'type' => (string)($dto['type'] ?? ''),
            'location' => (string)($dto['location'] ?? ''),
            'price' => is_array($dto['price'] ?? null) ? $dto['price'] : ['label' => 'توافقی', 'short' => 'توافقی', 'kind' => 'unknown'],
            'image' => [
                'src' => (string)(is_array($dto['image'] ?? null) ? ($dto['image']['src'] ?? '') : ($dto['image'] ?? '')),
                'alt' => (string)(is_array($dto['image'] ?? null) ? ($dto['image']['alt'] ?? '') : ($dto['title'] ?? 'ملک')),
            ],
            'features' => array_slice(is_array($dto['features'] ?? null) ? $dto['features'] : [], 0, 4),
            'favorite' => (bool)($dto['favorite'] ?? false),
            'compare' => (bool)($dto['compare'] ?? false),
            'is_vip' => (bool)($dto['is_vip'] ?? false),
        ];
    }
}
