<?php
declare(strict_types=1);

namespace Melkino\Support;

use Melkino\Config\Config;

/**
 * Melkino V2 — URL helpers (spec §4, §74: legacy URLs keep working, canonical /melk/{id} ready).
 */
final class Urls
{
    public static function base(): string
    {
        return Config::appUrl();
    }

    public static function to(string $path): string
    {
        $base = self::base();
        if ($base === '') {
            return ltrim($path, '/');
        }
        return $base . '/' . ltrim($path, '/');
    }

    /** Legacy public URL (always works). */
    public static function property(int $id): string
    {
        return 'property-details.php?id=' . $id;
    }

    /** Canonical pretty URL (served via .htaccess rewrite when available). */
    public static function propertyCanonical(int $id, string $slug = ''): string
    {
        $slug = trim($slug);
        return $slug !== '' ? 'melk/' . $id . '/' . rawurlencode($slug) : 'melk/' . $id;
    }

    public static function absProperty(int $id): string
    {
        return self::to(self::property($id));
    }

    public static function asset(string $path): string
    {
        return Assets::url($path);
    }
}
