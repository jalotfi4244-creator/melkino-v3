<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — Content-Security-Policy for V2-rendered HTML (spec §security).
 * Nonce-based for scripts; styles stay 'unsafe-inline' (shared tokens + view tweaks).
 * Sent ONLY from the V2 layouts — legacy pages are untouched (their inline
 * handlers would break under a strict policy).
 */
final class Csp
{
    public static function nonce(): string
    {
        if (!defined('MELKINO_CSP_NONCE')) {
            define('MELKINO_CSP_NONCE', rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='));
        }
        return MELKINO_CSP_NONCE;
    }

    public static function attr(): string
    {
        return ' nonce="' . self::nonce() . '"';
    }

    /** Send the policy. Safe to call during buffered rendering (before first flush). */
    public static function sendHtmlHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        $n = self::nonce();
        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            // مینی‌اپ وبِ پیام‌رسان‌ها صفحه را داخل iframe باز می‌کند؛
            // عین فهرست مجاز config.php (وگرنه «This content is blocked»).
            "frame-ancestors 'self' https://*.bale.ai https://bale.ai https://web.bale.ai https://ble.ir https://*.telegram.org https://web.telegram.org https://*.eitaa.com https://web.eitaa.com https://eitaa.com",
            "form-action 'self'",
            "script-src 'self' 'nonce-{$n}' https://tapi.bale.ai https://developer.eitaa.com https://unpkg.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            "media-src 'self' data: blob: https:",
            "connect-src 'self' https://tapi.bale.ai",
        ]);
        header('Content-Security-Policy: ' . $policy, true);
    }
}
