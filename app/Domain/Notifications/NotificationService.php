<?php
declare(strict_types=1);

namespace Melkino\Domain\Notifications;

use Melkino\Core\Database;
use Melkino\Services\NotificationDispatcher;

/**
 * Melkino V2 — event => notification (spec §41, §99).
 * Events: property.created/approved/rejected/published/updated, request.created/updated,
 * match.created, user.logged_in, support.ticket_created/message_created.
 */
final class NotificationService
{
    /** @param array<string,mixed> $payload @param array{dedupe?:string} $opts */
    public static function emit(string $event, array $payload = [], array $opts = []): ?int
    {
        if (function_exists('melkinoNotificationEventEnabled') && !@melkinoNotificationEventEnabled($event)) {
            return null; // Admin-disabled event (notification-events.php).
        }
        $built = self::build($event, $payload);
        if (!$built) {
            return null;
        }
        if (!empty($opts['dedupe'])) {
            $built['dedupe_key'] = substr((string)$opts['dedupe'], 0, 191);
        }
        $id = NotificationRepository::create($built);
        if ($id) {
            NotificationDispatcher::dispatch($event, $built, $payload);
        }
        return $id;
    }

    /** @return array<string,mixed>|null */
    private static function build(string $event, array $p): ?array
    {
        $ad = $p['ad'] ?? $p;
        $req = $p['request'] ?? [];
        $adId = (int)($ad['id'] ?? 0);
        return match ($event) {
            'property.approved', 'property.published' => [
                'user_id' => $ad['owner_user_id'] ?? $ad['user_id'] ?? null,
                'telegram_id' => $ad['telegram_id'] ?? null,
                'ad_id' => $adId ?: null,
                'title' => 'آگهی شما منتشر شد',
                'message' => 'آگهی «' . mb_substr((string)($ad['title'] ?? 'ملک'), 0, 60) . '» تأیید و منتشر شد.',
                'type' => 'property',
                'url' => $adId ? 'property-details.php?id=' . $adId : '',
            ],
            'property.rejected' => [
                'user_id' => $ad['owner_user_id'] ?? $ad['user_id'] ?? null,
                'telegram_id' => $ad['telegram_id'] ?? null,
                'ad_id' => $adId ?: null,
                'title' => 'آگهی شما نیاز به اصلاح دارد',
                'message' => 'آگهی شما تأیید نشد.' . (!empty($ad['_reason']) ? ' دلیل: ' . $ad['_reason'] : ''),
                'type' => 'property',
                'url' => 'my-properties.php',
            ],
            'request.created' => [
                'user_id' => $req['user_id'] ?? null,
                'telegram_id' => $req['telegram_id'] ?? null,
                'request_id' => (int)($req['id'] ?? 0) ?: null,
                'title' => 'درخواست شما ثبت شد',
                'message' => 'کد پیگیری: ' . ($req['tracking_code'] ?? '') . ' — وضعیت: در انتظار بررسی',
                'type' => 'request',
                'url' => 'my-request-matches.php',
            ],
            'match.created' => [
                'user_id' => $req['user_id'] ?? null,
                'telegram_id' => $req['telegram_id'] ?? null,
                'request_id' => (int)($req['id'] ?? 0) ?: null,
                'ad_id' => $adId ?: null,
                'title' => 'ملک جدید مطابق نیاز شما',
                'message' => 'یک ملک با ' . (int)($p['percent'] ?? 0) . '٪ تطابق پیدا شد.',
                'type' => 'request',
                'url' => $adId ? 'property-details.php?id=' . $adId : 'my-request-matches.php',
                'match_percent' => (float)($p['percent'] ?? 0),
            ],
            'saved_search.matched' => [
                'user_id' => $p['user_id'] ?? null,
                'telegram_id' => $p['telegram_id'] ?? null,
                'ad_id' => $adId ?: null,
                'title' => 'ملک جدید مطابق جستجوی ذخیره‌شده',
                'message' => '«' . mb_substr((string)($p['search_title'] ?? 'جستجوی شما'), 0, 50) . '»: یک ملک جدید پیدا شد.',
                'type' => 'property',
                'url' => $adId ? 'property-details.php?id=' . $adId : 'properties.php',
            ],
            default => null,
        };
    }

    /** Resolve owner identity for an ad row (for direct notify calls). */
    public static function ownerOf(array $ad): array
    {
        return [
            'user_id' => $ad['owner_user_id'] ?? $ad['user_id'] ?? null,
            'telegram_id' => $ad['telegram_id'] ?? null,
            'phone' => $ad['phone'] ?? '',
        ];
    }
}
