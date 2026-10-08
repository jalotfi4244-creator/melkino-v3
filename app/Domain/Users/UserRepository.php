<?php
declare(strict_types=1);

namespace Melkino\Domain\Users;

use Melkino\Core\Database;
use Melkino\Support\Persian;
use PDO;

/** Melkino V2 — user persistence (delegates upsert/merge to battle-tested legacy helpers). */
final class UserRepository
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByPhone(string $phone): ?array
    {
        $pdo = Database::pdo();
        $phone = Persian::normalizePhone($phone);
        if (!$pdo || $phone === '') {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByMessenger(string $field, string $id): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo || !in_array($field, ['telegram_id', 'bale_id', 'eitaa_id'], true) || trim($id) === '') {
            return null;
        }
        $st = $pdo->prepare("SELECT * FROM users WHERE `{$field}` = ? LIMIT 1");
        $st->execute([trim($id)]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Canonical upsert — delegates to legacy melkinoUpsertUser with its EXACT positional
     * contract ($telegramId, $phone, $name, $username, $providedToken, $baleId, $eitaaId).
     * Legacy returns ['id','token','trusted','status']; this returns the user row (or null).
     * @param array<string,mixed> $identity keys: telegram_id, bale_id, eitaa_id, phone, name, username
     */
    public static function upsert(array $identity): ?array
    {
        if (!function_exists('melkinoUpsertUser')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        if (!function_exists('melkinoUpsertUser')) {
            return null;
        }
        try {
            $r = melkinoUpsertUser(
                (string)($identity['telegram_id'] ?? ''),
                (string)($identity['phone'] ?? ''),
                isset($identity['name']) ? (string)$identity['name'] : null,
                isset($identity['username']) ? (string)$identity['username'] : null,
                null,
                (string)($identity['bale_id'] ?? ''),
                (string)($identity['eitaa_id'] ?? '')
            );
            $id = (int)($r['id'] ?? 0);
            if ($id > 0) {
                return self::find($id);
            }
            // Fall back to lookup by any provided identifier.
            foreach ([['phone', (string)($identity['phone'] ?? '')]] as [$kind, $val]) {
                if ($val !== '' && $kind === 'phone') {
                    $u = self::findByPhone($val);
                    if ($u) {
                        return $u;
                    }
                }
            }
            foreach (['telegram_id', 'bale_id', 'eitaa_id'] as $field) {
                if (!empty($identity[$field])) {
                    $u = self::findByMessenger($field, (string)$identity[$field]);
                    if ($u) {
                        return $u;
                    }
                }
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function updateProfile(int $userId, array $fields): bool
    {
        $pdo = Database::pdo();
        if (!$pdo || $userId <= 0) {
            return false;
        }
        $allowed = ['name', 'first_name', 'last_name', 'username', 'photo_url'];
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (in_array($k, $allowed, true) && is_scalar($v)) {
                $sets[] = "`{$k}` = ?";
                $params[] = mb_substr(trim((string)$v), 0, 191);
            }
        }
        if (!$sets) {
            return false;
        }
        $params[] = $userId;
        try {
            $st = $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?');
            return $st->execute($params);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function touchLogin(int $userId, string $platform = ''): void
    {
        $pdo = Database::pdo();
        if (!$pdo || $userId <= 0) {
            return;
        }
        try {
            $st = $pdo->prepare(
                'UPDATE users SET last_login = NOW(), login_count = login_count + 1,
                 last_ip = ?, last_platform = ?, user_agent = ? WHERE id = ?'
            );
            $st->execute([
                substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
                substr($platform, 0, 32),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                $userId,
            ]);
        } catch (\Throwable $ignored) {
        }
    }
}
