<?php
declare(strict_types=1);

namespace Melkino\Services;

use Melkino\Core\Logger;
use Melkino\Integrations\BaleNotifier;
use Melkino\Integrations\EitaaNotifier;
use Melkino\Integrations\SmsNotifier;
use Melkino\Integrations\TelegramNotifier;

/**
 * Melkino V2 — delivery fan-out (spec §41, §100).
 * Synchronous today (no broker), but behind an interface so a queue can be added later.
 * Failures never break the request that triggered the notification.
 */
final class NotificationDispatcher
{
    /** @param array<string,mixed> $notification @param array<string,mixed> $payload */
    public static function dispatch(string $event, array $notification, array $payload = []): void
    {
        try {
            $owner = $payload['owner'] ?? $payload['ad'] ?? [];
            $text = ($notification['title'] ?? '') . "\n" . ($notification['message'] ?? '');
            if (!empty($notification['url'])) {
                $text .= "\n" . $notification['url'];
            }
            // Telegram DM to owner (if linked).
            $tg = (string)($owner['telegram_id'] ?? $notification['telegram_id'] ?? '');
            if ($tg !== '' && is_numeric($tg)) {
                (new TelegramNotifier())->send($tg, $text);
            }
            // Bale DM to owner (if linked).
            $bale = (string)($owner['bale_id'] ?? '');
            if ($bale !== '' && is_numeric($bale)) {
                (new BaleNotifier())->send($bale, $text);
            }
            // Eitaa DM to owner (if linked).
            $eitaa = (string)($owner['eitaa_id'] ?? '');
            if ($eitaa !== '' && is_numeric($eitaa)) {
                (new EitaaNotifier())->send($eitaa, $text);
            }
            // Critical events also via SMS (only when user opted in — checked by gateway journal).
            if (in_array($event, ['match.created', 'property.approved'], true) && !empty($owner['phone'])) {
                // Best-effort; SmsNotifier journals every attempt.
                (new SmsNotifier())->send((string)$owner['phone'], $text, ['purpose' => $event]);
            }
        } catch (\Throwable $e) {
            Logger::warning('notification dispatch failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
