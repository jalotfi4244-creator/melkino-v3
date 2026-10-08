<?php
declare(strict_types=1);

namespace Melkino\Domain\Matching;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — request_matches + request_match_feedback persistence. */
final class MatchRepository
{
    /** @return array<int,array> matches joined with ad summary */
    public static function forRequest(int $requestId, int $limit = 100): array
    {
        $pdo = Database::pdo();
        if (!$pdo || $requestId <= 0) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        try {
            $st = $pdo->prepare(
                'SELECT m.*, a.title, a.transaction_type, a.property_type, a.location, a.area, a.rooms,
                        a.price_sell, a.deposit, a.rent_monthly, a.total_price, a.price_hidden, a.status AS ad_status
                 FROM request_matches m INNER JOIN ads a ON a.id = m.ad_id
                 WHERE m.request_id = ? ORDER BY m.match_percent DESC LIMIT ' . $limit
            );
            $st->execute([$requestId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array{ok:bool} store «not suitable» feedback with reason (spec §22). */
    public static function saveFeedback(int $matchId, string $feedback, array $identity): array
    {
        $pdo = Database::pdo();
        $allowed = ['price_high', 'bad_area', 'small_area', 'few_rooms', 'found_other', 'other', 'not_suitable'];
        if (!$pdo || $matchId <= 0 || !in_array($feedback, $allowed, true)) {
            return ['ok' => false];
        }
        if (function_exists('m5EnsureFeedbackTable')) {
            @m5EnsureFeedbackTable($pdo);
        }
        try {
            $st = $pdo->prepare(
                'INSERT INTO request_match_feedback (request_match_id, user_id, telegram_id, feedback, created_at, updated_at)
                 VALUES (?,?,?,?,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE feedback = VALUES(feedback), updated_at = NOW()'
            );
            // Table may lack the unique key on legacy installs; fall back to plain insert.
            try {
                $ok = $st->execute([$matchId, $identity['user_id'] ?? null, $identity['telegram_id'] ?? null, $feedback]);
            } catch (\Throwable $e) {
                $st = $pdo->prepare(
                    'INSERT INTO request_match_feedback (request_match_id, user_id, telegram_id, feedback, created_at) VALUES (?,?,?,?,NOW())'
                );
                $ok = $st->execute([$matchId, $identity['user_id'] ?? null, $identity['telegram_id'] ?? null, $feedback]);
            }
            return ['ok' => (bool)$ok];
        } catch (\Throwable $e) {
            return ['ok' => false];
        }
    }
}
