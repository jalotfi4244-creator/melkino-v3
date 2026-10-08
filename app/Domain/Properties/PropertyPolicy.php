<?php
declare(strict_types=1);

namespace Melkino\Domain\Properties;

use Melkino\Core\Auth;
use Melkino\Core\Authorization;

/** Melkino V2 — property authorization policy (spec §136). */
final class PropertyPolicy
{
    public static function canView(array $ad): bool
    {
        if (($ad['status'] ?? '') === 'published') {
            return true;
        }
        if (Auth::isAdmin()) {
            return true;
        }
        return self::canEdit($ad); // Owners can preview their own non-published ads.
    }

    public static function canEdit(array $ad): bool
    {
        if (Auth::isAdmin() && Authorization::adminCan(Authorization::PROPERTY_EDIT)) {
            return true;
        }
        return Authorization::ownsRow($ad, ['owner_user_id', 'user_id', 'phone', 'telegram_id', 'bale_id']);
    }

    public static function canDelete(array $ad): bool
    {
        return self::canEdit($ad);
    }

    public static function canApprove(): bool
    {
        return Authorization::adminCan(Authorization::PROPERTY_APPROVE);
    }

    public static function canPublish(): bool
    {
        return Authorization::adminCan(Authorization::PROPERTY_PUBLISH);
    }
}
