<?php
declare(strict_types=1);

namespace Melkino\Core;

use Melkino\Support\Persian;

/**
 * Melkino V2 — server-side validation helpers (spec §57, §134).
 */
final class Validation
{
    public static function required(string $v): bool
    {
        return trim($v) !== '';
    }

    public static function maxLen(string $v, int $max): bool
    {
        return mb_strlen($v) <= $max;
    }

    public static function phone(string $raw): ?string
    {
        $p = Persian::normalizePhone($raw);
        return preg_match('/^09\d{9}$/', $p) ? $p : null;
    }

    public static function otp(string $raw): ?string
    {
        $c = Persian::toEnglishDigits(trim($raw));
        return preg_match('/^\d{4,8}$/', $c) ? $c : null;
    }

    public static function id(mixed $v): ?int
    {
        if (is_numeric($v) && (int)$v > 0) {
            return (int)$v;
        }
        return null;
    }

    public static function url(?string $v): ?string
    {
        if ($v === null || trim($v) === '') {
            return null;
        }
        $v = trim($v);
        if (!preg_match('#^https?://#i', $v)) {
            return null;
        }
        return filter_var($v, FILTER_VALIDATE_URL) ? $v : null;
    }

    public static function telegramLink(?string $v): ?string
    {
        if ($v === null || trim($v) === '') {
            return '';
        }
        $v = trim($v);
        return preg_match('#^https?://t\.me/#i', $v) ? $v : null;
    }

    public static function inList(string $v, array $allowed): bool
    {
        return in_array($v, $allowed, true);
    }
}
