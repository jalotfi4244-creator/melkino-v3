<?php
declare(strict_types=1);

namespace Melkino\Support;

/**
 * Melkino V2 — central asset versioning (spec §55, §142).
 * asset('admin/navigation.js') => 'admin/navigation.js?v=<filemtime>' (immutable cache friendly).
 */
final class Assets
{
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        $file = MELKINO_ROOT . '/' . $path;
        if (is_file($file)) {
            $v = (int)@filemtime($file);
            return $path . '?v=' . $v;
        }
        return $path;
    }

    public static function css(string $path, string $extra = ''): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars(self::url($path), ENT_QUOTES, 'UTF-8') . '"' . $extra . '>';
    }

    public static function js(string $path, bool $defer = true): string
    {
        return '<script src="' . htmlspecialchars(self::url($path), ENT_QUOTES, 'UTF-8') . '"' . ($defer ? ' defer' : '') . '></script>';
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Assets::url($path);
    }
}
