<?php
declare(strict_types=1);

/**
 * Melkino V2 — canonical filesystem paths.
 * Single source of truth for directory locations (spec §3, §128).
 */

if (!defined('MELKINO_ROOT')) {
    define('MELKINO_ROOT', dirname(__DIR__, 2));
}
if (!defined('MELKINO_APP')) {
    define('MELKINO_APP', MELKINO_ROOT . '/app');
}
if (!defined('MELKINO_VIEWS')) {
    define('MELKINO_VIEWS', MELKINO_ROOT . '/views');
}
if (!defined('MELKINO_ASSETS')) {
    define('MELKINO_ASSETS', MELKINO_ROOT . '/assets');
}
if (!defined('MELKINO_STORAGE')) {
    define('MELKINO_STORAGE', MELKINO_ROOT . '/storage');
}
if (!defined('MELKINO_UPLOADS')) {
    define('MELKINO_UPLOADS', MELKINO_ROOT . '/uploads');
}
if (!defined('MELKINO_MIGRATIONS')) {
    define('MELKINO_MIGRATIONS', MELKINO_ROOT . '/migrations');
}
if (!defined('SETTINGS_DIR')) {
    define('SETTINGS_DIR', MELKINO_ROOT . '/settings');
}

// Ensure runtime directories exist (shared-hosting friendly, no CLI needed).
foreach ([MELKINO_STORAGE . '/logs', MELKINO_STORAGE . '/cache', MELKINO_STORAGE . '/temp'] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
