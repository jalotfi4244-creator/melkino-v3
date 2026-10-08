<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — admin TOTP store + V2-shell guard (spec §38–§39).
 * Schema: migrations/002_admin_totp.php (admin_totp table).
 *
 * Semantics (incremental, non-breaking):
 * - Password login (legacy) is unchanged; enrollment is per-admin and opt-in.
 * - Once an admin enrolls, the V2 shell (admin.php dashboard + ?tab pages)
 *   requires a fresh TOTP code every session (guardSatisfied()).
 * - The legacy panel (admin-panel.php) does NOT check TOTP — full cutover
 *   happens when the panel retires; documented in SECURITY.md.
 * - Fail-open only when the DB/table is unreachable (same spirit as the
 *   legacy lockout defaults); enroll/verify never run without a PDO.
 */
final class AdminTotp
{
    public const TABLE = 'admin_totp';

    public static function tableReady(?PDO $pdo): bool
    {
        if (!$pdo) {
            return false;
        }
        try {
            // Self-healing (same SQL as migrations/002): hosts without PHP CLI
            // can never run tools/migrate.php, so a fresh DB would miss this
            // table forever. Idempotent; the migration stays the tracked record.
            $pdo->exec('CREATE TABLE IF NOT EXISTS admin_totp (
                admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
                secret VARCHAR(64) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                verified_at TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            return Database::tableExists(self::TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function enrolled(?PDO $pdo, int $adminId): bool
    {
        if ($adminId <= 0 || !self::tableReady($pdo)) {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT verified_at FROM ' . self::TABLE . ' WHERE admin_id = ? LIMIT 1');
            $st->execute([$adminId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) && $row['verified_at'] !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Verified secret, or null (never leaks unverified rows). */
    public static function secretFor(?PDO $pdo, int $adminId): ?string
    {
        if ($adminId <= 0 || !self::tableReady($pdo)) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT secret FROM ' . self::TABLE . ' WHERE admin_id = ? AND verified_at IS NOT NULL LIMIT 1');
            $st->execute([$adminId]);
            $s = $st->fetchColumn();
            return is_string($s) && $s !== '' ? $s : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Insert-or-replace + mark verified (call only after a code was verified). */
    public static function activate(PDO $pdo, int $adminId, string $secret): void
    {
        $st = $pdo->prepare('INSERT INTO ' . self::TABLE . ' (admin_id, secret, verified_at) VALUES (?, ?, NOW())'
            . ' ON DUPLICATE KEY UPDATE secret = VALUES(secret), verified_at = NOW()');
        $st->execute([$adminId, $secret]);
    }

    public static function revoke(PDO $pdo, int $adminId): void
    {
        $st = $pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE admin_id = ?');
        $st->execute([$adminId]);
    }

    /**
     * True when this session already passed 2FA for the CURRENT admin login.
     * Bound to admin_id + admin_login_at so a re-login must re-verify.
     */
    public static function guardSatisfied(): bool
    {
        $ok = $_SESSION['admin_2fa_ok'] ?? null;
        if (!is_array($ok) || ($ok['v'] ?? 0) !== 1) {
            return false;
        }
        return (int)($ok['admin_id'] ?? 0) === (int)($_SESSION['admin_id'] ?? 0)
            && (int)($ok['login_at'] ?? 0) === (int)($_SESSION['admin_login_at'] ?? 0)
            && (int)($_SESSION['admin_id'] ?? 0) > 0;
    }

    public static function markSatisfied(): void
    {
        Session::regenerate();
        $_SESSION['admin_2fa_ok'] = [
            'v' => 1,
            'admin_id' => (int)($_SESSION['admin_id'] ?? 0),
            'login_at' => (int)($_SESSION['admin_login_at'] ?? 0),
            'at' => time(),
        ];
    }

    /** V2-shell gate: enrolled admin without a fresh 2FA pass must verify. */
    public static function needsCheck(): bool
    {
        if (!Auth::isAdmin() || self::guardSatisfied()) {
            return false;
        }
        return self::enrolled(Database::pdo(), (int)($_SESSION['admin_id'] ?? 0));
    }
}
