<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — tiny file cache (spec §75, §100: settings/config caching, no Redis dependency).
 */
final class Cache
{
    private static function dir(): string
    {
        $dir = MELKINO_STORAGE . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $f = self::file($key);
        if (!is_file($f)) {
            return $default;
        }
        $raw = @file_get_contents($f);
        if ($raw === false) {
            return $default;
        }
        $row = @json_decode($raw, true);
        if (!is_array($row) || !isset($row['exp'], $row['val'])) {
            return $default;
        }
        if ((int)$row['exp'] > 0 && (int)$row['exp'] < time()) {
            @unlink($f);
            return $default;
        }
        return $row['val'];
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        try {
            @file_put_contents(
                self::file($key),
                json_encode(['exp' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0, 'val' => $value], JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        } catch (\Throwable $ignored) {
        }
    }

    public static function forget(string $key): void
    {
        @unlink(self::file($key));
    }

    public static function remember(string $key, int $ttl, callable $producer): mixed
    {
        $v = self::get($key, null);
        if ($v !== null || self::has($key)) {
            return $v;
        }
        $v = $producer();
        self::set($key, $v, $ttl);
        return $v;
    }

    public static function has(string $key): bool
    {
        return is_file(self::file($key));
    }
}
