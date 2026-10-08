<?php
declare(strict_types=1);

namespace Melkino\Integrations;

/** Melkino V2 — Eitaa auth adapter (delegates to proven db_helpers verifier). */
final class EitaaAuth
{
    public static function verifyInitData(string $initData): ?array
    {
        if (!function_exists('melkinoVerifyEitaaInitData')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        if (!function_exists('melkinoVerifyEitaaInitData')) {
            return null;
        }
        try {
            $r = melkinoVerifyEitaaInitData($initData);
            return is_array($r) ? $r : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
