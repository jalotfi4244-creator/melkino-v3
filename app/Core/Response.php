<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — unified output (spec §79: {success,data,message,errors} + correct HTTP status).
 */
final class Response
{
    public static function json(mixed $data = [], string $message = '', bool $success = true, int $status = 200, array $errors = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        $payload = ['success' => $success, 'data' => $data, 'message' => $message];
        if (!$success && $errors) {
            $payload['errors'] = $errors;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(mixed $data = [], string $message = '', int $status = 200): void
    {
        self::json($data, $message, true, $status);
    }

    public static function fail(string $message, int $status = 400, array $errors = [], mixed $data = []): void
    {
        self::json($data, $message, false, $status, $errors);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $url);
        }
        exit;
    }

    /** Open-redirect-safe redirect: only same-origin relative paths or allow-listed hosts. */
    public static function safeRedirect(?string $target, string $fallback = 'home.php'): void
    {
        $target = trim((string)$target);
        if ($target === '' || preg_match('#^https?://#i', $target)) {
            // Absolute URLs: allow only same host.
            if ($target !== '' && preg_match('#^https?://#i', $target)) {
                $host = strtolower((string)(parse_url($target, PHP_URL_HOST) ?? ''));
                $mine = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0]);
                if ($host === '' || $host !== $mine) {
                    $target = '';
                }
            }
        } elseif (!str_starts_with($target, '/') && !preg_match('/^[a-zA-Z0-9_\-\.\/\?=&%]+$/', $target)) {
            $target = '';
        }
        if ($target === '' || str_contains($target, '..') || str_contains($target, '\\')) {
            $target = $fallback;
        }
        self::redirect($target);
    }
}
