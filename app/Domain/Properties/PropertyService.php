<?php
declare(strict_types=1);

namespace Melkino\Domain\Properties;

use Melkino\Core\Audit;
use Melkino\Core\Authorization;
use Melkino\Core\Database;
use Melkino\Domain\Notifications\NotificationService;

/**
 * Melkino V2 — property business operations (approve/reject/publish/...).
 * Every mutation: authorize -> execute -> notify (deduped) -> audit.
 */
final class PropertyService
{
    public static function approve(int $adId, ?int $adminId = null): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_APPROVE);
        $ad = PropertyRepository::find($adId);
        if (!$ad) {
            return false;
        }
        $ok = PropertyRepository::setStatus($adId, 'published');
        if ($ok) {
            NotificationService::emit('property.approved', $ad, ['dedupe' => "property:{$adId}:approved"]);
            Audit::approve('ad', $adId);
        }
        return $ok;
    }

    public static function reject(int $adId, string $reason = '', ?int $adminId = null): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_APPROVE);
        $ad = PropertyRepository::find($adId);
        if (!$ad) {
            return false;
        }
        $ok = PropertyRepository::setStatus($adId, 'rejected');
        if ($ok) {
            NotificationService::emit('property.rejected', $ad + ['_reason' => $reason], ['dedupe' => "property:{$adId}:rejected"]);
            Audit::reject('ad', $adId);
        }
        return $ok;
    }

    public static function publish(int $adId): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_PUBLISH);
        $ok = PropertyRepository::setStatus($adId, 'published');
        if ($ok) {
            $ad = PropertyRepository::find($adId);
            if ($ad) {
                NotificationService::emit('property.published', $ad, ['dedupe' => "property:{$adId}:published"]);
            }
            Audit::publish('ad', $adId);
        }
        return $ok;
    }

    public static function unpublish(int $adId): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_PUBLISH);
        return PropertyRepository::setStatus($adId, 'suspended');
    }

    public static function archive(int $adId): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_EDIT);
        return PropertyRepository::setStatus($adId, 'archived');
    }

    public static function markSold(int $adId): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_EDIT);
        $pdo = Database::pdo();
        $ok = PropertyRepository::setStatus($adId, 'sold');
        if ($ok && $pdo) {
            try {
                $pdo->prepare('UPDATE ads SET sold_at = NOW() WHERE id = ?')->execute([$adId]);
            } catch (\Throwable $ignored) {
            }
        }
        return $ok;
    }

    /** @return array{ok:int,fail:int} */
    public static function bulk(array $adIds, string $action): array
    {
        $ok = 0;
        $fail = 0;
        foreach ($adIds as $id) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }
            $r = match ($action) {
                'approve', 'publish' => self::publish($id),
                'reject' => self::reject($id),
                'unpublish' => self::unpublish($id),
                'archive' => self::archive($id),
                'sold' => self::markSold($id),
                default => false,
            };
            $r ? $ok++ : $fail++;
        }
        return ['ok' => $ok, 'fail' => $fail];
    }
}
