<?php
declare(strict_types=1);

/**
 * Melkino V2 — global view helpers (spec §135: output escaping).
 * Loaded by the bootstrap; all guarded for legacy coexistence.
 */

use Melkino\Support\Persian;

if (!function_exists('e')) {
    /** HTML-escape (UTF-8, quotes). */
    function e(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc')) {
    /** Legacy alias (several files define esc(); first-loaded wins, all identical). */
    function esc(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fa')) {
    /** English digits => Persian digits for display. */
    function fa(mixed $v): string
    {
        return Persian::toPersianDigits((string)$v);
    }
}

if (!function_exists('en_digits')) {
    function en_digits(mixed $v): string
    {
        return Persian::toEnglishDigits((string)$v);
    }
}

if (!function_exists('old')) {
    /** Repopulate form value after validation error. */
    function old(string $key, mixed $default = ''): string
    {
        $v = $_POST[$key] ?? $default;
        if (is_array($v)) {
            return '';
        }
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('melkinoView')) {
    /**
     * Render a view file with isolated scope.
     * @param array<string,mixed> $data
     */
    function melkinoView(string $view, array $data = []): string
    {
        $file = MELKINO_VIEWS . '/' . ltrim($view, '/');
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}

if (!function_exists('melkinoPartial')) {
    /** @param array<string,mixed> $data */
    function melkinoPartial(string $partial, array $data = []): string
    {
        return melkinoView('partials/' . ltrim($partial, '/'), $data);
    }
}

if (!function_exists('csp_nonce')) {
    /** Per-request CSP nonce (see Melkino\Core\Csp). */
    function csp_nonce(): string
    {
        return \Melkino\Core\Csp::nonce();
    }
}

if (!function_exists('csp_nonce_attr')) {
    /** Ready-to-paste nonce attribute for inline <script> tags. */
    function csp_nonce_attr(): string
    {
        return \Melkino\Core\Csp::attr();
    }
}

if (!function_exists('melkinoPublicV2')) {
    /**
     * Public-side kill-switch: legacy design is the DEFAULT (operator's choice),
     * V2 renders only with explicit ?v2=1 (preview). Flip the default back to
     * true to restore V2 everywhere; ?legacy=1 still forces legacy.
     */
    function melkinoPublicV2(): bool
    {
        if (($_GET['v2'] ?? '') === '1') {
            return true;
        }
        if (($_GET['legacy'] ?? '') === '1') {
            return false;
        }
        return defined('MELKINO_PUBLIC_V2_DEFAULT') ? (bool)MELKINO_PUBLIC_V2_DEFAULT : false;
    }
}
