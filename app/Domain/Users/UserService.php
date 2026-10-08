<?php
declare(strict_types=1);

namespace Melkino\Domain\Users;

use Melkino\Auth\AuthService;
use Melkino\Core\Auth;
use Melkino\Support\Persian;

/** Melkino V2 — user flows: verified-phone login, messenger login, profile update. */
final class UserService
{
    /** Complete login after OTP verification. */
    public static function loginWithPhone(string $rawPhone): ?array
    {
        $phone = Persian::normalizePhone($rawPhone);
        if (!Persian::isValidPhone($phone)) {
            return null;
        }
        $me = Auth::identity();
        $user = UserRepository::upsert([
            'phone' => $phone,
            'telegram_id' => $me['telegram_id'], 'bale_id' => $me['bale_id'], 'eitaa_id' => $me['eitaa_id'],
        ]);
        if (!$user) {
            $user = UserRepository::findByPhone($phone);
        }
        if (!$user) {
            return null;
        }
        $uid = (int)($user['id'] ?? 0);
        UserRepository::touchLogin($uid, 'otp');
        AuthService::loginUser($uid, $phone, (string)($user['name'] ?? ''), [
            'telegram' => (string)($user['telegram_id'] ?? Auth::telegramId()),
            'bale' => (string)($user['bale_id'] ?? Auth::baleId()),
            'eitaa' => (string)($user['eitaa_id'] ?? Auth::eitaaId()),
        ]);
        return $user;
    }

    /** Complete login after messenger (Telegram/Bale/Eitaa) verification. */
    public static function loginWithMessenger(string $provider, string $providerId, array $profile = []): ?array
    {
        $field = match ($provider) {
            'telegram' => 'telegram_id',
            'bale' => 'bale_id',
            'eitaa' => 'eitaa_id',
            default => null,
        };
        if (!$field || trim($providerId) === '') {
            return null;
        }
        $me = Auth::identity();
        $identity = [
            'telegram_id' => $me['telegram_id'], 'bale_id' => $me['bale_id'], 'eitaa_id' => $me['eitaa_id'],
            'phone' => $me['phone'], $field => trim($providerId),
        ];
        foreach (['phone', 'name', 'username'] as $k) {
            if (!empty($profile[$k])) {
                $identity[$k] = $profile[$k];
            }
        }
        $user = UserRepository::upsert($identity) ?? UserRepository::findByMessenger($field, $providerId);
        if (!$user) {
            return null;
        }
        $uid = (int)($user['id'] ?? 0);
        UserRepository::touchLogin($uid, $provider);
        AuthService::loginUser($uid, (string)($user['phone'] ?? ''), (string)($user['name'] ?? ''), [
            'telegram' => (string)($user['telegram_id'] ?? ''),
            'bale' => (string)($user['bale_id'] ?? ''),
            'eitaa' => (string)($user['eitaa_id'] ?? ''),
        ]);
        return $user;
    }
}
