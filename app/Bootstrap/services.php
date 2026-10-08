<?php
declare(strict_types=1);

/**
 * Melkino V2 — service wiring + security runtime (spec §33, §68).
 * - Hardened session cookie params (before any session_start).
 * - Security headers (inventory-aware: no script-src CSP that would break inline legacy JS).
 * - Optional HTTPS-force + maintenance mode (same rules as legacy config.php).
 */

use Melkino\Config\Config;
use Melkino\Core\Csrf;
use Melkino\Core\Database;
use Melkino\Core\Session;

Config::boot();

if (PHP_SAPI !== 'cli') {
    // --- Session cookie hardening (must precede session_start) ---
    if (session_status() === PHP_SESSION_NONE) {
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_secure', Session::isHttps() ? '1' : '0');
        @ini_set('session.use_strict_mode', '1');
    }

    // --- Security headers (careful: no script-src, legacy inline JS must keep working) ---
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=(), usb=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        // Bale/Telegram/Eitaa webviews load the site in iframes: keep frame-ancestors allow-listed.
        header("Content-Security-Policy: frame-ancestors 'self' https://*.bale.ai https://bale.ai https://web.bale.ai https://ble.ir https://*.telegram.org https://web.telegram.org https://*.eitaa.com https://web.eitaa.com https://eitaa.com");
        if (Session::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// --- Uploads dir self-healing protection (Apache) ---
if (!function_exists('melkinoProtectUploadsDir')) {
    require_once MELKINO_APP . '/Support/UploadProtection.php';
}

// --- DB init (global $pdo stays in sync for legacy code) ---
$pdo = Database::pdo();

// --- HTTPS force + maintenance mode (same allow-list semantics as legacy) ---
if ($pdo instanceof PDO && PHP_SAPI !== 'cli' && function_exists('dbSettingGet')) {
    try {
        if (!Session::isHttps() && (bool)dbSettingGet($pdo, 'security', 'force_https', false)) {
            $host = (string)($_SERVER['HTTP_HOST'] ?? '');
            if ($host !== '') {
                header('Location: https://' . $host . (string)($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
                exit;
            }
        }
    } catch (Throwable $ignored) {
    }

    try {
        $maintenanceAllow = [
            'admin-login.php', 'admin-panel.php', 'admin-ads.php', 'admin-ads-modals.php',
            'admin-requests.php', 'admin-property-revisions.php', 'admin-support.php',
            'save_admin_password.php', 'save_config.php', 'save_consultant_settings.php',
            'save_consultants.php', 'save_global_settings.php', 'save_security_settings.php',
            'save_theme.php', 'support-api.php', 'upload_onboarding_logo.php',
            'upload_onboarding_first_logo.php', 'save_onboarding_first_page.php',
            'melkino-logo-file.php', 'identity-sync.php', 'page-visits.php',
            'db-settings.php', 'publish-to-telegram.php', 'admin-upload-image.php',
            'tools-migrate.php',
        ];
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
        $on = (bool)dbSettingGet($pdo, 'global', 'maintenance_mode', false);
        $since = (int)dbSettingGet($pdo, 'global', 'maintenance_started_at', 0);
        if ($on && $since > 0) {
            $started = (int)($_SESSION['melkino_session_started_at'] ?? 0);
            if ($started <= 0 || $started < $since) {
                unset($_SESSION['is_admin'], $_SESSION['user_role'], $_SESSION['admin_id'],
                    $_SESSION['admin_username'], $_SESSION['admin_display_name'], $_SESSION['admin_login_at'],
                    $_SESSION['reg_telegram_id'], $_SESSION['reg_bale_id'], $_SESSION['user_phone'], $_SESSION['user_name']);
                if (isset($_COOKIE['melkino_access_token']) && !headers_sent()) {
                    setcookie('melkino_access_token', '', time() - 3600, '/');
                    unset($_COOKIE['melkino_access_token']);
                }
            }
        }
        if ($on && !in_array($script, $maintenanceAllow, true) && empty($_SESSION['is_admin'])) {
            http_response_code(503);
            if (!headers_sent()) {
                header('Retry-After: 3600');
            }
            echo melkinoView('errors/maintenance.php');
            exit;
        }
    } catch (Throwable $ignored) {
    }
}

// --- Admin-only ?debug=1 gate (requires server-side MELKINO_ALLOW_DEBUG=1) ---
if (PHP_SAPI !== 'cli'
    && (($_GET['debug'] ?? '') === '1')
    && !empty($_SESSION['is_admin'])
    && \Melkino\Config\Environment::debugAllowed()
) {
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');
    @ini_set('error_reporting', (string)E_ALL);
}
