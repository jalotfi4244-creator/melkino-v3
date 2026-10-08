<?php
declare(strict_types=1);

namespace Melkino\Config;

/**
 * Melkino V2 — environment reader (spec §5, §80).
 * Priority: real server ENV > .env file > default.
 * APP_ENV=production + MOCK_MODE=0 is the only production behavior.
 */
final class Environment
{
    /** @var array<string,bool> */
    private static array $dotenvLoaded = [];

    public static function loadDotEnv(string $path): void
    {
        if (isset(self::$dotenvLoaded[$path]) || !is_file($path) || !is_readable($path)) {
            return;
        }
        self::$dotenvLoaded[$path] = true;
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) {
                continue;
            }
            if (strlen($v) > 1 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            if (getenv($k) !== false && getenv($k) !== '') {
                continue; // Real server ENV always wins.
            }
            $_ENV[$k] = $v;
            @putenv($k . '=' . $v);
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        return ($value === null || $value === '') ? $default : (string)$value;
    }

    public static function appEnv(): string
    {
        $env = strtolower(trim(self::get('APP_ENV', 'production')));
        return in_array($env, ['production', 'staging', 'development', 'testing'], true) ? $env : 'production';
    }

    public static function isProduction(): bool
    {
        return self::appEnv() === 'production';
    }

    /** Mock data is ONLY allowed with explicit development + MOCK_MODE=1. Never accidental. */
    public static function mockEnabled(): bool
    {
        if (self::isProduction()) {
            return false;
        }
        return self::get('MOCK_MODE', '0') === '1';
    }

    public static function debugAllowed(): bool
    {
        if (defined('MELKINO_ALLOW_DEBUG')) {
            return (string)MELKINO_ALLOW_DEBUG === '1';
        }
        return self::get('MELKINO_ALLOW_DEBUG', '0') === '1';
    }
}
