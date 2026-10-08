<?php
declare(strict_types=1);

/**
 * Migration 001 — baseline.
 * The V1 codebase creates its schema lazily via idempotent `melkinoEnsure*()` helpers
 * scattered across lib files. This baseline documents that contract and runs every
 * known ensure-helper once, so a fresh database reaches the exact same schema that
 * production converges to at runtime — without duplicating any CREATE TABLE.
 *
 * New schema changes MUST be added as new versioned files (002_*.php, …) with
 * explicit idempotent SQL, never by editing this file.
 */
return [
    'version' => '001_baseline',
    'description' => 'Run all legacy idempotent ensure-helpers (ads/users/compare/visits/settings/…).',
    'up' => static function (PDO $pdo): void {
        $root = MELKINO_ROOT;
        // Load libs that own ensure-helpers (order: settings/db first).
        foreach ([
            '/db-settings.php', '/security-lib.php', '/db_helpers.php', '/compare-lib.php',
            '/visit-request-lib.php', '/support-lib.php', '/promotions.php', '/broadcast-lib.php',
            '/partnership-lib.php', '/map-lib.php', '/ratings-lib.php', '/admin-audit.php',
            '/rate-limit.php', '/login-events.php',
        ] as $lib) {
            $f = $root . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        // (function, needs-pdo-arg?) — probed from each lib's real signature.
        $helpers = [
            'melkinoEnsureSettingsTable' => true, 'melkinoEnsureAuditTable' => true,
            'melkinoEnsureRateLimitTable' => true, 'melkinoEnsureLoginEventsSchema' => false,
            'melkinoEnsureLoginTokenTable' => false, 'melkinoEnsureUserProfileColumns' => true,
            'melkinoEnsureRequestSchema' => false, 'melkinoEnsureAdsHistory' => true,
            'melkinoEnsureAdsBuildingAgeColumn' => true, 'melkinoEnsureAdsDefaultImageColumn' => true,
            'melkinoEnsureAdViewsSchema' => true, 'melkinoEnsureCompareTables' => true,
            'melkinoEnsureVisitRequestSchema' => false, 'melkinoEnsureSupportTables' => true,
            'melkinoEnsurePromotionsTable' => true, 'melkinoEnsureBroadcastTables' => true,
            'melkinoEnsurePartnershipSchema' => true, 'melkinoMapEnsureSchema' => false,
            'melkinoEnsureRatingColumns' => true,
        ];
        foreach ($helpers as $fn => $takesPdo) {
            if (!function_exists($fn)) {
                continue;
            }
            if ($takesPdo) {
                $fn($pdo);
            } else {
                try {
                    (new ReflectionFunction($fn))->getNumberOfRequiredParameters() > 0 ? $fn($pdo) : $fn();
                } catch (\Throwable $e) {
                    $fn();
                }
            }
        }
    },
];
