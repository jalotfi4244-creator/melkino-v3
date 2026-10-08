<?php
declare(strict_types=1);

namespace Melkino\Studio;

use Melkino\Core\Database;

/**
 * Melkino V2 — Design Studio service (spec §42, §92).
 * Same settings-based draft/publish/history/import behavior as legacy design-studio-lib.php,
 * with the business logic separated from the HTTP layer. Simplified preset UX:
 * STYLE (minimal/modern/luxury) x COLORS (teal/light/dark) x CARD (standard/premium/compact)
 * x RADIUS (tight/soft/rounded) x SHADOW (none/soft/strong).
 */
final class StudioService
{
    public const STYLES = ['minimal', 'modern', 'luxury'];
    public const COLORS = ['teal', 'light', 'dark'];
    public const CARDS = ['standard', 'premium', 'compact'];
    public const RADII = ['tight', 'soft', 'rounded'];
    public const SHADOWS = ['none', 'soft', 'strong'];

    public static function defaults(): array
    {
        return ['style' => 'modern', 'color' => 'teal', 'card' => 'standard', 'radius' => 'soft', 'shadow' => 'soft'];
    }

    /** @return array<string,string> validated theme (invalid JSON never crashes — falls back). */
    public static function current(): array
    {
        $d = self::defaults();
        try {
            if (function_exists('settings')) {
                $stored = settings()->getArray('studio', 'theme', []);
                return self::sanitize(is_array($stored) ? $stored : []);
            }
        } catch (\Throwable $ignored) {
        }
        return $d;
    }

    /** @param array<string,mixed> $input */
    public static function sanitize(array $input): array
    {
        $d = self::defaults();
        foreach ($d as $k => $v) {
            $const = 'self::' . strtoupper($k) . 'S';
            $allowed = match ($k) {
                'style' => self::STYLES, 'color' => self::COLORS, 'card' => self::CARDS,
                'radius' => self::RADII, 'shadow' => self::SHADOWS, default => [],
            };
            $val = (string)($input[$k] ?? $v);
            $d[$k] = in_array($val, $allowed, true) ? $val : $v;
        }
        return $d;
    }

    /** @return array{ok:bool,theme:array} */
    public static function saveDraft(array $input, ?int $adminId = null): array
    {
        $theme = self::sanitize($input);
        if (!function_exists('settings')) {
            return ['ok' => false, 'theme' => $theme];
        }
        $ok = settings()->set('studio', 'theme_draft', $theme, 'json', $adminId);
        return ['ok' => $ok, 'theme' => $theme];
    }

    /** @return array{ok:bool,theme:array} */
    public static function publish(?int $adminId = null): array
    {
        $theme = self::current();
        if (function_exists('settings')) {
            $draft = settings()->getArray('studio', 'theme_draft', []);
            if ($draft) {
                $theme = self::sanitize($draft);
            }
            self::pushHistory($theme);
            $ok = settings()->set('studio', 'theme', $theme, 'json', $adminId);
            return ['ok' => $ok, 'theme' => $theme];
        }
        return ['ok' => false, 'theme' => $theme];
    }

    /** @return array<int,array> */
    public static function history(int $limit = 20): array
    {
        if (!function_exists('settings')) {
            return [];
        }
        $h = settings()->getArray('studio', 'theme_history', []);
        return array_slice(is_array($h) ? $h : [], 0, max(1, min(50, $limit)));
    }

    private static function pushHistory(array $theme): void
    {
        if (!function_exists('settings')) {
            return;
        }
        $h = settings()->getArray('studio', 'theme_history', []);
        if (!is_array($h)) {
            $h = [];
        }
        array_unshift($h, ['theme' => $theme, 'at' => date('Y-m-d H:i:s')]);
        settings()->set('studio', 'theme_history', array_slice($h, 0, 20), 'json');
    }

    /** Extra CSS variables for the active theme (consumed by the layout). */
    public static function cssVars(): string
    {
        $t = self::current();
        $radius = match ($t['radius']) {
            'tight' => '8px', 'rounded' => '24px', default => '16px',
        };
        $shadow = match ($t['shadow']) {
            'none' => 'none', 'strong' => '0 18px 50px rgba(0,0,0,.16)', default => '0 10px 30px rgba(0,0,0,.08)',
        };
        return '<style>:root{--mx-radius-override:' . $radius . ';--mx-shadow-override:' . $shadow . '}'
            . 'body[data-mx-style="' . e($t['style']) . '"][data-mx-color="' . e($t['color']) . '"]{--mx-card-variant:' . e($t['card']) . '}</style>';
    }
}
