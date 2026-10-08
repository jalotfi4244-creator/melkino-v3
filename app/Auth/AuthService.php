<?php
declare(strict_types=1);

namespace Melkino\Auth;

use Melkino\Core\Auth;
use Melkino\Core\Audit;
use Melkino\Core\Database;
use Melkino\Core\Logger;
use Melkino\Core\Session;
use Melkino\Support\Persian;
use PDO;

/**
 * Melkino V2 — canonical auth service (spec §32, §33, §128).
 * Owns: login tokens (single-use, short TTL), session restore, logout, login events.
 * Root files (auth.php, auth-telegram/bale/eitaa.php, request/verify-otp.php, identity-sync.php)
 * stay as thin adapters and delegate here.
 */
final class AuthService
{
    public static function ensureLoginTokenTable(): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS login_tokens (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    token CHAR(64) NOT NULL,
                    user_id BIGINT UNSIGNED NULL,
                    telegram_id VARCHAR(191) NULL,
                    bale_id VARCHAR(191) NULL,
                    user_agent VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    first_used_at DATETIME NULL,
                    expires_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_login_tokens_token (token),
                    KEY idx_login_tokens_user (user_id),
                    KEY idx_login_tokens_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function mintLoginToken(mixed $userId, ?string $telegramId = null, ?string $baleId = null, int $days = 1): string
    {
        $pdo = Database::pdo();
        if (!$pdo || !self::ensureLoginTokenTable()) {
            return '';
        }
        try {
            $token = bin2hex(random_bytes(32));
            $st = $pdo->prepare(
                'INSERT INTO login_tokens (token, user_id, telegram_id, bale_id, user_agent, expires_at)
                 VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))'
            );
            $st->execute([
                $token,
                $userId ? (int)$userId : null,
                ($telegramId !== null && $telegramId !== '') ? (string)$telegramId : null,
                ($baleId !== null && $baleId !== '') ? (string)$baleId : null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                max(1, min(365, $days)),
            ]);
            $pdo->exec('DELETE FROM login_tokens WHERE expires_at < NOW()');
            return $token;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Single-use consume: a copied ?t= URL cannot be reused. */
    public static function consumeLoginToken(string $token): array
    {
        $pdo = Database::pdo();
        $token = trim($token);
        if ($token === '' || preg_match('/^[a-f0-9]{64}$/', $token) !== 1 || !$pdo) {
            return [];
        }
        if (!self::ensureLoginTokenTable()) {
            return [];
        }
        try {
            $st = $pdo->prepare(
                'SELECT id, user_id, telegram_id, bale_id FROM login_tokens
                  WHERE token = ? AND expires_at > NOW() LIMIT 1'
            );
            $st->execute([$token]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return [];
            }
            try {
                $del = $pdo->prepare('DELETE FROM login_tokens WHERE id = ?');
                $del->execute([$row['id']]);
            } catch (\Throwable $ignored) {
            }
            return [
                'user_id' => $row['user_id'] !== null ? (int)$row['user_id'] : null,
                'telegram_id' => $row['telegram_id'] !== null ? (string)$row['telegram_id'] : '',
                'bale_id' => $row['bale_id'] !== null ? (string)$row['bale_id'] : '',
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function loginUser(?int $userId, string $phone = '', string $name = '', array $ids = []): void
    {
        Auth::loginUser($userId, $phone, $name, $ids);
        self::recordLoginEvent($userId, $phone, 'otp');
        Audit::login($phone !== '' ? $phone : ('user#' . (int)$userId));
    }

    public static function logout(): void
    {
        $who = Auth::phone() !== '' ? Auth::phone() : (Auth::isAdmin() ? 'admin' : 'guest');
        Session::destroy();
        Audit::logout($who);
    }

    public static function recordLoginEvent(?int $userId, string $phone, string $method): void
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS login_events (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NULL,
                    phone VARCHAR(32) NULL,
                    method VARCHAR(32) NOT NULL DEFAULT \'\',
                    ip VARCHAR(64) NULL,
                    user_agent VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_login_events_user (user_id),
                    KEY idx_login_events_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $st = $pdo->prepare('INSERT INTO login_events (user_id, phone, method, ip, user_agent) VALUES (?,?,?,?,?)');
            $st->execute([
                $userId,
                Persian::normalizePhone($phone) !== '' ? Persian::normalizePhone($phone) : null,
                substr($method, 0, 32),
                substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('login event failed', ['error' => $e->getMessage()]);
        }
    }

    /** Active OTP/messenger providers for the login UI (only configured ones shown). */
    public static function activeProviders(): array
    {
        return [
            'otp' => true,
            'telegram' => defined('BOT_TOKEN') && BOT_TOKEN !== '',
            'bale' => defined('BALE_BOT_TOKEN') && BALE_BOT_TOKEN !== '',
            'eitaa' => defined('EITAA_BOT_TOKEN') && EITAA_BOT_TOKEN !== '',
        ];
    }
}
