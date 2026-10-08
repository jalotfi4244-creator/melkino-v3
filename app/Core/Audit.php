<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — audit log for sensitive operations (spec §72, §137).
 * Uses melkino_audit_log (security-lib.php) when available.
 */
final class Audit
{
    public static function record(string $action, array $context = [], ?int $adminId = null): void
    {
        try {
            $pdo = Database::pdo();
            if (!function_exists('melkinoAudit')) {
                @require_once MELKINO_ROOT . '/security-lib.php';
            }
            if ($pdo && function_exists('melkinoAudit')) {
                // Legacy contract: ($action, $entity='', $entityId=null, $details=[])
                $entity = (string)($context['entity'] ?? '');
                $entityId = $context['id'] ?? $context['entity_id'] ?? null;
                $details = $context + ['admin_id' => $adminId ?? Auth::adminId()];
                @melkinoAudit($action, $entity, $entityId, $details);
                return;
            }
        } catch (\Throwable $ignored) {
        }
        Logger::info('audit: ' . $action, $context + ['admin_id' => $adminId ?? Auth::adminId()]);
    }

    public static function login(string $who): void { self::record('login', ['who' => $who]); }
    public static function logout(string $who): void { self::record('logout', ['who' => $who]); }
    public static function approve(string $entity, int $id): void { self::record('approve', ['entity' => $entity, 'id' => $id]); }
    public static function reject(string $entity, int $id): void { self::record('reject', ['entity' => $entity, 'id' => $id]); }
    public static function publish(string $entity, int $id): void { self::record('publish', ['entity' => $entity, 'id' => $id]); }
    public static function delete(string $entity, int $id): void { self::record('delete', ['entity' => $entity, 'id' => $id]); }
    public static function settings(string $group, string $key): void { self::record('settings.change', ['group' => $group, 'key' => $key]); }
    public static function backup(string $file): void { self::record('backup.run', ['file' => basename($file)]); }
}
