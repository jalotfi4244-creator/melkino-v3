<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — policies (spec §26, §70, §136).
 * Every mutation checks ownership server-side; hidden inputs are never trusted.
 */
final class Authorization
{
    // Abilities
    public const PROPERTY_VIEW = 'property.view';
    public const PROPERTY_CREATE = 'property.create';
    public const PROPERTY_EDIT = 'property.edit';
    public const PROPERTY_DELETE = 'property.delete';
    public const PROPERTY_APPROVE = 'property.approve';
    public const PROPERTY_PUBLISH = 'property.publish';
    public const REQUEST_VIEW = 'request.view';
    public const REQUEST_UPDATE = 'request.update';
    public const USER_VIEW = 'user.view';
    public const USER_EDIT = 'user.edit';
    public const BACKUP_RUN = 'backup.run';
    public const SETTINGS_EDIT = 'settings.edit';
    public const ADMIN_MANAGE = 'admin.manage';

    /** Role => abilities. Legacy admins (no role) keep full access. */
    private const ROLE_ABILITIES = [
        'super_admin' => ['*'],
        'property_manager' => [
            self::PROPERTY_VIEW, self::PROPERTY_CREATE, self::PROPERTY_EDIT,
            self::PROPERTY_APPROVE, self::PROPERTY_PUBLISH, self::REQUEST_VIEW, self::USER_VIEW,
        ],
        'support' => [self::REQUEST_VIEW, self::REQUEST_UPDATE, self::USER_VIEW, self::PROPERTY_VIEW],
        'marketing' => [self::PROPERTY_VIEW, self::USER_VIEW, self::SETTINGS_EDIT],
        'editor' => [self::PROPERTY_VIEW, self::PROPERTY_CREATE, self::PROPERTY_EDIT, self::REQUEST_VIEW],
    ];

    public static function adminCan(string $ability): bool
    {
        if (!Auth::isAdmin()) {
            return false;
        }
        $role = Auth::adminRole();
        if ($role === '') {
            return true; // Legacy admin: preserve previous behavior.
        }
        $abilities = self::ROLE_ABILITIES[$role] ?? [];
        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public static function requireAdmin(string $ability = self::ADMIN_MANAGE): void
    {
        if (!self::adminCan($ability)) {
            Response::fail('دسترسی غیرمجاز', 403);
        }
    }

    public static function requireAdminPage(string $ability = self::ADMIN_MANAGE): void
    {
        if (!self::adminCan($ability)) {
            http_response_code(403);
            Session::flash('error', 'دسترسی غیرمجاز');
            Response::redirect('admin-login.php');
        }
    }

    /** Server-side ownership check for user-owned rows (ads/requests/etc). */
    public static function ownsRow(array $row, array $ownerKeys = ['user_id', 'owner_phone', 'phone', 'telegram_id', 'bale_id']): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        $me = Auth::identity();
        foreach ($ownerKeys as $k) {
            if (!array_key_exists($k, $row) || $row[$k] === null || $row[$k] === '') {
                continue;
            }
            $rv = (string)$row[$k];
            if ($k === 'user_id' && $me['user_id'] !== null && (int)$rv === $me['user_id']) {
                return true;
            }
            if (in_array($k, ['owner_phone', 'phone'], true) && $me['phone'] !== '' && $rv === $me['phone']) {
                return true;
            }
            if ($k === 'telegram_id' && $me['telegram_id'] !== '' && $rv === $me['telegram_id']) {
                return true;
            }
            if ($k === 'bale_id' && $me['bale_id'] !== '' && $rv === $me['bale_id']) {
                return true;
            }
        }
        return false;
    }
}
