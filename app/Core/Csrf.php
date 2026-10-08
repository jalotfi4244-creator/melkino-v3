<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — canonical CSRF implementation (spec §25, §69, §128).
 * csrf-shim.php stays as the compatibility layer and delegates here.
 * Behavior preserved: 419 JSON on failure + legacy admin same-origin exemption w/ logging.
 */
final class Csrf
{
    public const HEADER = 'X-CSRF-Token';
    public const FIELD = 'csrf_token';

    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['melkino_csrf']) || !is_string($_SESSION['melkino_csrf'])) {
            $_SESSION['melkino_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['melkino_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function meta(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function isMutatingRequest(): bool
    {
        return in_array((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public static function readBody(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }
        if (!empty($_POST) && is_array($_POST)) {
            return $cached = $_POST;
        }
        $raw = (string)@file_get_contents('php://input');
        if ($raw === '') {
            return $cached = [];
        }
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $cached = $json;
        }
        $parsed = [];
        parse_str($raw, $parsed);
        return $cached = is_array($parsed) ? $parsed : [];
    }

    public static function isSameOrigin(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $host = explode(':', $host)[0];
        if ($host === '') {
            return false;
        }
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
            $v = (string)($_SERVER[$h] ?? '');
            if ($v === '') {
                continue;
            }
            $oh = strtolower((string)(parse_url($v, PHP_URL_HOST) ?? ''));
            if ($oh !== '' && $oh === $host) {
                return true;
            }
        }
        return false;
    }

    public static function validate(?string $sent = null): bool
    {
        if ($sent === null) {
            $body = self::readBody();
            $sent = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if ($sent === '') {
                $sent = (string)($body[self::FIELD] ?? $_POST[self::FIELD] ?? $_GET[self::FIELD] ?? '');
            }
        }
        $expected = self::token();
        if ($sent !== '' && hash_equals($expected, $sent)) {
            return true;
        }
        // Legacy exemption: admin endpoints on iOS webviews without token headers.
        if (!empty($_SESSION['is_admin'])) {
            $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
            $adminEp = (strpos($script, 'admin-') === 0)
                || in_array($script, ['admin-panel.php', 'admin-field-display.php', 'admin-card-display.php'], true);
            if ($adminEp) {
                $secFetchSite = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
                if ($secFetchSite === 'cross-site' || $secFetchSite === 'same-site') {
                    return false;
                }
                if ($secFetchSite === 'same-origin' || self::isSameOrigin()) {
                    return true;
                }
                $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
                $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
                if ($secFetchSite === '' && $origin === '' && $referer === '') {
                    Logger::warning('CSRF legacy no-header exemption', ['script' => $script]);
                    return true;
                }
            }
        }
        return false;
    }

    /** Terminates with 419 JSON when the token is invalid. */
    public static function check(): void
    {
        if (!self::validate()) {
            if (!headers_sent()) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'csrf_error' => true,
                'message' => 'درخواست از نظر امنیتی نامعتبر بود (CSRF). صفحه را دوباره بارگذاری کنید.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
