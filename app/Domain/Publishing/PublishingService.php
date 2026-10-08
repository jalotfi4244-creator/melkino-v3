<?php
declare(strict_types=1);

namespace Melkino\Domain\Publishing;

use Melkino\Core\Audit;
use Melkino\Core\Authorization;
use Melkino\Core\Database;
use Melkino\Domain\Properties\PropertyRepository;
use Melkino\Integrations\BaleNotifier;
use Melkino\Integrations\TelegramNotifier;

/**
 * Melkino V2 — channel publishing (spec §104).
 * Keeps legacy publish-to-telegram.php / publish-to-bale.php behavior behind one service.
 */
final class PublishingService
{
    public static function publishAd(int $adId, string $channel = 'telegram'): bool
    {
        Authorization::requireAdmin(Authorization::PROPERTY_PUBLISH);
        $ad = PropertyRepository::find($adId);
        if (!$ad) {
            return false;
        }
        $text = self::adText($ad);
        $target = $channel === 'bale'
            ? (string)(defined('BALE_CHANNEL_ID') ? BALE_CHANNEL_ID : '')
            : (defined('CHANNEL_ID') ? (string)CHANNEL_ID : '');
        if ($target === '') {
            return false;
        }
        $ok = $channel === 'bale'
            ? (new BaleNotifier())->send($target, $text)
            : (new TelegramNotifier())->send($target, $text);
        self::log($adId, $channel, $ok ? 'sent' : 'failed');
        if ($ok) {
            Audit::publish('ad:' . $channel, $adId);
        }
        return $ok;
    }

    private static function adText(array $ad): string
    {
        $title = trim((string)($ad['title'] ?? 'ملک'));
        $loc = trim((string)($ad['location'] ?? ''));
        $lines = ['🏠 ' . $title];
        if ($loc !== '') {
            $lines[] = '📍 ' . $loc;
        }
        if (function_exists('melkinoAdDisplayPrice')) {
            try {
                $lines[] = '💰 ' . (string)@melkinoAdDisplayPrice($ad);
            } catch (\Throwable $ignored) {
            }
        }
        $lines[] = '🔗 property-details.php?id=' . (int)$ad['id'];
        return implode("\n", $lines);
    }

    private static function log(int $adId, string $channel, string $status): void
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $st = $pdo->prepare('INSERT INTO channel_publish_logs (ad_id, channel, status, created_at) VALUES (?,?,?,NOW())');
            $st->execute([$adId, $channel, $status]);
        } catch (\Throwable $ignored) {
        }
    }
}
