<?php
declare(strict_types=1);

namespace Melkino\Integrations;

/** Melkino V2 — Bale auth adapter (delegates to proven db_helpers verifier). */
final class BaleAuth
{
    public static function verifyInitData(string $initData): ?array
    {
        if (!function_exists('melkinoVerifyBaleInitData')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        if (!function_exists('melkinoVerifyBaleInitData')) {
            return null;
        }
        try {
            $r = melkinoVerifyBaleInitData($initData);
            return is_array($r) ? $r : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
