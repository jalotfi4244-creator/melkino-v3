<?php
declare(strict_types=1);

namespace Melkino\Http;

use Melkino\Core\Auth;
use Melkino\Core\Response;

/**
 * Melkino V2 — login gate (behavior preserved from the legacy $_mkPage blocks).
 * The V1 site requires login for ALL pages except the allow-list below; V2 keeps that
 * default (REQUIRE_LOGIN setting, default ON) so the operator can open the site later
 * without a code change. Admins and allow-listed scripts always pass.
 */
final class Gate
{
    public const ALLOW = [
        'login.php', 'logout.php', 'auth.php', 'auth-telegram.php', 'auth-bale.php',
        'auth-eitaa.php', 'request-otp.php', 'verify-otp.php', 'admin-login.php',
        'admin-logout.php', 'telegram.php', 'bale.php', 'eitaa.php', 'telegram-relay.php',
        'identity-sync.php', 'bale-ok.php', 'r.php',
    ];

    public static function required(): bool
    {
        try {
            if (function_exists('settings')) {
                return (bool)settings()->getBool('security', 'require_login', true);
            }
        } catch (\Throwable $ignored) {
        }
        return true;
    }

    /** Redirect guests to login (same semantics + same redirect param as legacy). */
    public static function check(?string $script = null): void
    {
        if (!self::required()) {
            return;
        }
        $script ??= strtolower(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));
        if ($script === '' || in_array($script, self::ALLOW, true) || strncmp($script, 'admin-', 6) === 0) {
            return;
        }
        if (Auth::isAdmin() || Auth::check()) {
            return;
        }
        $here = (string)($_SERVER['REQUEST_URI'] ?? $script);
        $here = ltrim($here, '/');
        if ($here === '' || str_starts_with($here, 'login.php')) {
            $here = 'home.php';
        }
        // راند ۶۴: فوروارد پارامترهای tgWebApp* تا دادهٔ هویت مینی‌اپ در پرتاب گم نشود.
        $fwd = function_exists('melkinoMiniAppForwardQuery') ? melkinoMiniAppForwardQuery() : '';
        if ($fwd !== '' && function_exists('melkinoMiniAppCleanHere')) {
            $here = melkinoMiniAppCleanHere($here);
        }
        if (!headers_sent()) {
            header('Location: login.php?redirect=' . rawurlencode($here) . $fwd, true, 302);
        }
        exit;
    }

    /** API variant: 401 JSON instead of redirect. */
    public static function checkApi(): void
    {
        if (!self::required()) {
            return;
        }
        if (Auth::isAdmin() || Auth::check()) {
            return;
        }
        Response::fail('ابتدا وارد شوید.', 401);
    }
}
