<?php
declare(strict_types=1);

namespace Melkino\Config;

/**
 * Melkino V2 — central configuration (spec §5, §128).
 * Legacy constants (DB_*, BOT_TOKEN, ...) are defined here exactly once
 * for backward compatibility with the existing codebase.
 */
final class Config
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        Environment::loadDotEnv(MELKINO_ROOT . '/.env');
        Secrets::loadFile();

        self::defineOnce('MELKINO_ENV', Environment::appEnv());
        self::defineOnce('MELKINO_MOCK', Environment::mockEnabled());
        // Legacy MOCK_MODE constant: production is ALWAYS false (spec §9, §80).
        self::defineOnce('MOCK_MODE', false);

        self::defineOnce('DB_HOST', Secrets::get('DB_HOST', 'DB_HOST', ''));
        self::defineOnce('DB_NAME', Secrets::get('DB_NAME', 'DB_NAME', ''));
        self::defineOnce('DB_USER', Secrets::get('DB_USER', 'DB_USER', ''));
        self::defineOnce('DB_PASS', Secrets::get('DB_PASS', 'DB_PASS', ''));

        self::defineOnce('BOT_TOKEN', Secrets::get('BOT_TOKEN', 'MELKINO_BOT_TOKEN', ''));
        self::defineOnce('CHANNEL_ID', Secrets::get('CHANNEL_ID', 'MELKINO_CHANNEL_ID', '@melkino_shahrood'));
        self::defineOnce('BALE_BOT_TOKEN', Secrets::get('BALE_BOT_TOKEN', 'MELKINO_BALE_BOT_TOKEN', ''));
        self::defineOnce('EITAA_BOT_TOKEN', Secrets::get('EITAA_BOT_TOKEN', 'MELKINO_EITAA_BOT_TOKEN', ''));

        self::defineOnce('SMS_API_KEY', Secrets::get('SMS_API_KEY', 'MELKINO_SMS_API_KEY', ''));
        self::defineOnce('SMS_API_URL', Secrets::get('SMS_API_URL', 'MELKINO_SMS_API_URL', ''));
        self::defineOnce('SMS_SENDER_LINE', Secrets::get('SMS_SENDER_LINE', 'MELKINO_SMS_SENDER_LINE', ''));

        self::defineOnce('MELKINO_VERSION', '2.0.0');
    }

    private static function defineOnce(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    public static function appUrl(): string
    {
        $url = Environment::get('APP_URL', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
            return '';
        }
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
}
