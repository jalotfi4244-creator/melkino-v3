<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — typed settings service (spec §94, §95, §128).
 * Fixes the string-vs-JSON-array reader mismatch: every read declares its type.
 * Backed by the existing `settings` table (db-settings.php schema), with file cache.
 */
final class Settings
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function raw(string $group, string $key): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        if (function_exists('melkinoEnsureSettingsTable')) {
            @melkinoEnsureSettingsTable($pdo);
        }
        try {
            $st = $pdo->prepare('SELECT setting_value, value_type FROM settings WHERE setting_group = ? AND setting_key = ? LIMIT 1');
            $st->execute([$group, $key]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getString(string $group, string $key, string $default = ''): string
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        $v = $row['setting_value'];
        if (($row['value_type'] ?? '') === 'json') {
            $d = json_decode((string)$v, true);
            // Typed contract: a JSON setting read as string returns '' unless it IS a string.
            return is_string($d) ? $d : $default;
        }
        return (string)$v;
    }

    public function getBool(string $group, string $key, bool $default = false): bool
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        return filter_var($row['setting_value'], FILTER_VALIDATE_BOOLEAN);
    }

    public function getInt(string $group, string $key, int $default = 0): int
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        return (int)$row['setting_value'];
    }

    public function getFloat(string $group, string $key, float $default = 0.0): float
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        return (float)$row['setting_value'];
    }

    /** @return array<mixed> */
    public function getArray(string $group, string $key, array $default = []): array
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        if (($row['value_type'] ?? '') === 'json') {
            $d = json_decode((string)$row['setting_value'], true);
            return is_array($d) ? $d : $default;
        }
        // Legacy plain-string that actually holds JSON (the historic mismatch): tolerate it.
        $s = trim((string)$row['setting_value']);
        if ($s !== '' && ($s[0] === '{' || $s[0] === '[')) {
            $d = json_decode($s, true);
            return is_array($d) ? $d : $default;
        }
        return $default;
    }

    public function getJson(string $group, string $key, mixed $default = null): mixed
    {
        $row = $this->raw($group, $key);
        if (!$row) {
            return $default;
        }
        $d = json_decode((string)$row['setting_value'], true);
        return (is_array($d) || is_scalar($d)) ? $d : $default;
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string', ?int $adminId = null): bool
    {
        $pdo = Database::pdo();
        if (!$pdo || !function_exists('dbSettingSet')) {
            return false;
        }
        $ok = @dbSettingSet($pdo, $group, $key, $value, $type, $adminId);
        Cache::forget("settings.{$group}.{$key}");
        return (bool)$ok;
    }

    /** Cached read for hot paths (global settings etc). */
    public function cached(string $group, string $key, string $type, mixed $default = null, int $ttl = 120): mixed
    {
        $ck = "settings.{$group}.{$key}.{$type}";
        if (Cache::has($ck)) {
            return Cache::get($ck, $default);
        }
        $v = match ($type) {
            'bool' => $this->getBool($group, $key, (bool)$default),
            'int' => $this->getInt($group, $key, (int)$default),
            'float' => $this->getFloat($group, $key, (float)$default),
            'array', 'json' => $this->getArray($group, $key, is_array($default) ? $default : []),
            default => $this->getString($group, $key, (string)$default),
        };
        Cache::set($ck, $v, $ttl);
        return $v;
    }
}

/** Global helper: settings()->getString(...) */
if (!function_exists('settings')) {
    function settings(): Settings
    {
        return Settings::instance();
    }
}
