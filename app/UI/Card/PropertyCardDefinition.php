<?php
declare(strict_types=1);

namespace Melkino\UI\Card;

/**
 * Melkino V2 — card registry (spec §15, §128).
 * Card variants (standard/premium/compact) + visibility rules in ONE place.
 * Legacy card-display.php registry values are honored via the Studio/card settings bridge.
 */
final class PropertyCardDefinition
{
    public const VARIANTS = ['standard', 'premium', 'compact'];

    public static function variant(): string
    {
        try {
            if (function_exists('settings')) {
                $v = settings()->getString('card_display', 'variant', 'standard');
                if (in_array($v, self::VARIANTS, true)) {
                    return $v;
                }
            }
            if (function_exists('melkinoCardMode')) {
                $legacy = (string)@melkinoCardMode();
                if (in_array($legacy, self::VARIANTS, true)) {
                    return $legacy;
                }
            }
        } catch (\Throwable $ignored) {
        }
        return 'standard';
    }

    /** @return array<string,bool> field visibility */
    public static function visibility(): array
    {
        $defaults = [
            'image' => true, 'transaction_badge' => true, 'favorite' => true,
            'title' => true, 'location' => true, 'features' => true,
            'price' => true, 'cta' => true, 'compare' => true, 'vip_badge' => true,
        ];
        try {
            if (function_exists('settings')) {
                $stored = settings()->getArray('card_display', 'visibility', []);
                foreach ($stored as $k => $v) {
                    if (array_key_exists($k, $defaults)) {
                        $defaults[$k] = (bool)$v;
                    }
                }
            }
        } catch (\Throwable $ignored) {
        }
        return $defaults;
    }
}
