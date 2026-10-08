<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — input accessor with validation (spec §134).
 * Never trust client types: every getter coerces + constrains.
 */
final class Request
{
    public static function method(): string
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        $body = Csrf::readBody();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }
        return $_GET[$key] ?? $default;
    }

    public static function string(string $key, string $default = '', int $maxLen = 2000): string
    {
        $v = self::input($key, $default);
        if (is_array($v)) {
            return $default;
        }
        $s = trim((string)$v);
        if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
            $s = mb_substr($s, 0, $maxLen);
        }
        return $s;
    }

    public static function int(string $key, int $default = 0, ?int $min = null, ?int $max = null): int
    {
        $v = self::input($key, $default);
        if (is_array($v) || (!is_numeric($v) && $v !== $default)) {
            return $default;
        }
        $n = (int)$v;
        if ($min !== null && $n < $min) {
            $n = $min;
        }
        if ($max !== null && $n > $max) {
            $n = $max;
        }
        return $n;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $v = self::input($key, $default);
        if (is_array($v) || !is_numeric($v)) {
            return $default;
        }
        return (float)$v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::input($key, $default);
        if (is_bool($v)) {
            return $v;
        }
        if (is_string($v) || is_numeric($v)) {
            return filter_var($v, FILTER_VALIDATE_BOOLEAN);
        }
        return $default;
    }

    /** @return string[] */
    public static function stringArray(string $key, int $maxItems = 100, int $maxLen = 500): array
    {
        $v = self::input($key, []);
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach (array_slice($v, 0, $maxItems) as $item) {
            if (is_scalar($item)) {
                $s = trim((string)$item);
                if ($s !== '') {
                    $out[] = mb_substr($s, 0, $maxLen);
                }
            }
        }
        return $out;
    }

    /** Allow-listed enum value or default. */
    public static function oneOf(string $key, array $allowed, mixed $default = null): mixed
    {
        $v = self::input($key, $default);
        if (is_array($v)) {
            return $default;
        }
        return in_array((string)$v, array_map('strval', $allowed), true) ? (string)$v : $default;
    }

    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            $v = trim((string)($_SERVER[$h] ?? ''));
            if ($v === '') {
                continue;
            }
            $ip = trim(explode(',', $v)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}
