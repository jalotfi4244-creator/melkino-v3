<?php
declare(strict_types=1);

namespace Melkino\Domain\Matching;

use Melkino\Core\Database;
use Melkino\Domain\Notifications\NotificationService;

/**
 * Melkino V2 — matching orchestration (spec §22).
 * Scoring stays in the proven match-engine (m5* functions); this service owns
 * recompute triggers, notifications (deduped) and the product-level API.
 */
final class MatchingService
{
    public static function engineLoaded(): bool
    {
        if (!function_exists('m5EnsureRequestMatches')) {
            @require_once MELKINO_ROOT . '/match-engine.php';
        }
        return function_exists('m5EnsureRequestMatches');
    }

    /** Recompute matches for a request (used after request create/update + new ad publish). */
    public static function recompute(int $requestId): int
    {
        $pdo = Database::pdo();
        if (!$pdo || $requestId <= 0 || !self::engineLoaded()) {
            return 0;
        }
        try {
            return (int)@m5EnsureRequestMatches($pdo, $requestId);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @return array<int,array> top matches with per-card breakdown for the UI */
    public static function topForRequest(int $requestId, int $limit = 20): array
    {
        $rows = MatchRepository::forRequest($requestId, $limit);
        foreach ($rows as &$r) {
            $r['breakdown'] = [
                ['key' => 'budget', 'score' => (float)($r['budget_score'] ?? 0), 'label' => 'بودجه'],
                ['key' => 'location', 'score' => (float)($r['location_score'] ?? 0), 'label' => 'منطقه'],
                ['key' => 'area', 'score' => (float)($r['area_score'] ?? 0), 'label' => 'متراژ'],
                ['key' => 'amenities', 'score' => (float)($r['amenities_score'] ?? 0), 'label' => 'امکانات'],
            ];
        }
        unset($r);
        return $rows;
    }

    /** Notify request owner about a new match (deduped per request+ad). */
    public static function notifyMatch(array $request, array $ad, float $percent): void
    {
        NotificationService::emit('match.created', ['request' => $request, 'ad' => $ad, 'percent' => $percent], [
            'dedupe' => 'match:' . (int)($request['id'] ?? 0) . ':' . (int)($ad['id'] ?? 0),
        ]);
    }
}
