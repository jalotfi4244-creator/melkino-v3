<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — hardened session handling (spec §33).
 * HttpOnly + SameSite=Lax + Secure-on-HTTPS, regeneration on login, full invalidation on logout.
 */
final class Session
{
    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI === 'cli') {
                @session_start();
            }
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            @ini_set('session.cookie_httponly', '1');
            @ini_set('session.cookie_samesite', 'Lax');
            @ini_set('session.cookie_secure', self::isHttps() ? '1' : '0');
            @ini_set('session.use_strict_mode', '1');
            @session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['melkino_session_started_at'])) {
            $_SESSION['melkino_session_started_at'] = time();
        }
    }

    /** Call immediately after any successful login (user or admin). */
    public static function regenerate(): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    /** Full logout: clears auth keys, destroys session + access-token cookie. */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (!headers_sent()) {
            if (isset($_COOKIE['melkino_access_token'])) {
                setcookie('melkino_access_token', '', time() - 3600, '/');
                unset($_COOKIE['melkino_access_token']);
            }
            $params = session_get_cookie_params();
            setcookie(
                (string)session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if (func_num_args() === 1) {
            $v = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $v;
        }
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
}
