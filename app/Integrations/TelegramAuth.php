<?php
declare(strict_types=1);

namespace Melkino\Integrations;

/**
 * Melkino V2 — Telegram auth adapter (spec §32).
 * Verification delegates to the proven db_helpers verifiers; login via UserService.
 */
final class TelegramAuth
{
    public static function verifyInitData(string $initData): ?array
    {
        if (!function_exists('melkinoVerifyTelegramInitData')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        if (!function_exists('melkinoVerifyTelegramInitData')) {
            return null;
        }
        try {
            $r = melkinoVerifyTelegramInitData($initData);
            return is_array($r) ? $r : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function verifyLogin(array $data): ?array
    {
        if (!function_exists('melkinoVerifyMiniAppInitData')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        // Telegram Login Widget check (hash of sorted fields with bot token).
        if (!defined('BOT_TOKEN') || BOT_TOKEN === '') {
            return null;
        }
        $hash = (string)($data['hash'] ?? '');
        if ($hash === '') {
            return null;
        }
        $pairs = [];
        foreach ($data as $k => $v) {
            if ($k !== 'hash' && is_scalar($v)) {
                $pairs[] = $k . '=' . $v;
            }
        }
        sort($pairs);
        $secret = hash('sha256', (string)BOT_TOKEN, true);
        $calc = hash_hmac('sha256', implode("\n", $pairs), $secret);
        if (!hash_equals($calc, $hash)) {
            return null;
        }
        if (!empty($data['auth_date']) && (time() - (int)$data['auth_date']) > 86400) {
            return null;
        }
        return $data;
    }
}
