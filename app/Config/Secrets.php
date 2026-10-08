<?php
declare(strict_types=1);

namespace Melkino\Config;

/**
 * Melkino V2 — secrets loader (spec §5).
 * Priority: config.secrets.php (git-ignored, legacy hosts) > ENV/.env > ''.
 * This class NEVER contains real values and NEVER logs values.
 */
final class Secrets
{
    private static bool $fileLoaded = false;

    public static function loadFile(): void
    {
        if (self::$fileLoaded) {
            return;
        }
        self::$fileLoaded = true;
        $file = MELKINO_ROOT . '/config.secrets.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    public static function get(string $constant, string $envKey, string $default = ''): string
    {
        self::loadFile();
        if (defined($constant)) {
            $v = constant($constant);
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }
        return Environment::get($envKey, $default);
    }

    /** @return array<string,string> Non-sensitive availability map for diagnostics (never values). */
    public static function availability(): array
    {
        $check = static function (string $const, string $env): string {
            $v = self::get($const, $env, '');
            return $v === '' ? 'missing' : 'set';
        };
        return [
            'db'    => ($check('DB_HOST', 'DB_HOST') === 'set' && self::get('DB_NAME', 'DB_NAME', '') !== '') ? 'set' : 'missing',
            'bot'   => $check('BOT_TOKEN', 'MELKINO_BOT_TOKEN'),
            'bale'  => $check('BALE_BOT_TOKEN', 'MELKINO_BALE_BOT_TOKEN'),
            'eitaa' => $check('EITAA_BOT_TOKEN', 'MELKINO_EITAA_BOT_TOKEN'),
            'sms'   => $check('SMS_API_KEY', 'MELKINO_SMS_API_KEY'),
        ];
    }
}
