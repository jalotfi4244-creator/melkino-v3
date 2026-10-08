<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Csp;

/**
 * Melkino V2 — admin login (API-shell: POST is served byte-identically by the
 * legacy include — session fixation guard, lockout counting, audit insert;
 * GET renders the V2 shell with the same lockout pre-check as legacy).
 */
final class AdminLoginController
{
    public function handle(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }

        foreach (['config.php', 'db_helpers.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }

        if (function_exists('melkinoRequireDb')) {
            melkinoRequireDb();
        }

        global $pdo;

        $security = [];
        foreach (['lockout', 'admin_login_log'] as $k) {
            $security[$k] = function_exists('dbSettingGet')
                ? (bool) dbSettingGet($pdo, 'security', $k, true)
                : true;
        }

        $state = function_exists('dbSettingGet')
            ? dbSettingGet($pdo, 'security', 'admin_attempt_state', ['count' => 0, 'locked_until' => 0])
            : ['count' => 0, 'locked_until' => 0];
        if (!is_array($state)) {
            $state = ['count' => 0, 'locked_until' => 0];
        }

        $error = false;
        $errorMessage = '';

        if ($security['lockout'] && (int) ($state['locked_until'] ?? 0) > time()) {
            $remaining = max(1, (int) ceil(((int) $state['locked_until'] - time()) / 60));
            $error = true;
            $errorMessage = 'به‌دلیل چند ورود ناموفق، ورود موقتاً قفل شده است. حدود ' . $remaining . ' دقیقه دیگر دوباره تلاش کنید.';
        } elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            require MELKINO_VIEWS . '/legacy/admin-login-legacy.php';
            exit;
        }

        Csp::sendHtmlHeaders();

        return melkinoView('pages/admin-login.php', [
            'error' => $error,
            'errorMessage' => $errorMessage,
        ]);
    }
}
