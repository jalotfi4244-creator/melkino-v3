<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — central logger (spec §130). File-based, levels, no secret logging.
 * Secrets/tokens are redacted from context automatically.
 */
final class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    private static function path(): string
    {
        $dir = MELKINO_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/melkino-' . date('Y-m-d') . '.log';
    }

    private static function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $lk = strtolower((string)$k);
                if (preg_match('/(token|secret|password|passwd|pwd|api[_-]?key|otp|code|authorization)/', $lk)) {
                    $out[$k] = '[redacted]';
                } else {
                    $out[$k] = self::redact($v);
                }
            }
            return $out;
        }
        return $value;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (!isset(self::LEVELS[$level])) {
            $level = 'info';
        }
        try {
            $line = sprintf(
                "[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                $context ? json_encode(self::redact($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
            );
            @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $ignored) {
        }
    }

    public static function debug(string $m, array $c = []): void { self::log('debug', $m, $c); }
    public static function info(string $m, array $c = []): void { self::log('info', $m, $c); }
    public static function warning(string $m, array $c = []): void { self::log('warning', $m, $c); }
    public static function error(string $m, array $c = []): void { self::log('error', $m, $c); }
    public static function critical(string $m, array $c = []): void { self::log('critical', $m, $c); }
}
