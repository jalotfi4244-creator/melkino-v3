<?php
declare(strict_types=1);

/**
 * Melkino V2 — application bootstrap (single entry for all new code).
 * Legacy entry: config.php requires this file; new entry: require app/Bootstrap/app.php.
 *
 * Order: paths -> error handling -> autoloader -> helpers -> legacy compat -> services.
 */

if (defined('MELKINO_BOOTSTRAPPED')) {
    return;
}
define('MELKINO_BOOTSTRAPPED', true);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/error-handling.php';

// --- PSR-4 autoloader for Melkino\ (no composer needed on shared hosting) ---
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'Melkino\\') !== 0) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen('Melkino\\'))) . '.php';
    $file = MELKINO_APP . '/' . $rel;
    if (is_file($file)) {
        require_once $file;
    }
});

// --- Global helpers (settings(), asset(), e(), fa(), melkinoView(), ...) ---
require_once MELKINO_APP . '/Support/helpers.php';
require_once MELKINO_APP . '/Support/Assets.php';   // defines asset()
require_once MELKINO_APP . '/Core/Settings.php';    // defines settings()

// --- Legacy compatibility shims (delegates legacy function names to V2 classes) ---
if (is_file(MELKINO_ROOT . '/db-settings.php')) {
    require_once MELKINO_ROOT . '/db-settings.php'; // dbSettingGet/dbSettingSet (kept, guarded)
}
require_once MELKINO_APP . '/Support/legacy-aliases.php';

// --- Services: config, session hardening, headers, DB, maintenance ---
require_once __DIR__ . '/services.php';

// --- Token-based session restore (?t=...) — same rules as legacy config.php ---
if (($GLOBALS['pdo'] ?? null) instanceof PDO && PHP_SAPI !== 'cli') {
    require_once MELKINO_APP . '/Auth/TokenSessionRestore.php';
}

// --- Page-visit gate (legacy behavior preserved) ---
if (PHP_SAPI !== 'cli' && is_file(MELKINO_ROOT . '/melkino-require-login.php')) {
    require_once MELKINO_ROOT . '/melkino-require-login.php';
}
