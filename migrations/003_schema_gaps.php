<?php
declare(strict_types=1);

/**
 * Migration 003 — schema gaps missed by the 001 baseline.
 *
 * The baseline (001) runs ensure-helpers from 14 libs, but four require-safe
 * libs were never loaded, so a FRESH database migrated via tools/migrate.php
 * missed these tables (production converges at runtime because every page
 * calls its own helper — the gap only bites fresh installs, CI and db-doctor):
 *   astEnsureSchema          assistant_runs/insights/evidence/chat (4 tables)
 *   commEnsureSchema         comm_* (16 tables)
 *   m5EnsureFeedbackTable    request_match_feedback (1 table)
 *   smsProgramEnsureSchema   sms_outbox/optouts + saved_searches (3 tables)
 *
 * Intentionally NOT included (all self-ensure at runtime on first use):
 *   broadcast tables   helper lives in admin-notifications.php, which requires
 *                       admin-guard.php (exits outside an admin session) and
 *                       self-runs on first tab visit.
 *   lead_sms_log        helper lives in admin-leads-api.php, whose tail always
 *                       emits JSON + exit; self-runs at the top of the API.
 *   channel_publish_logs + ads_history  inline CREATEs in admin-publish-logs.php
 *                       (unsafe to require: defaults to the list branch); run
 *                       on every require of that endpoint.
 *   user profile columns helper lives in auth.php (page entrypoint, unsafe);
 *                       self-runs on login.
 *   login_tokens        helper lives in config.php; self-runs inside config.
 *   admin_phone_audit / promotions  inline CREATEs in their endpoints.
 */
return [
    'version' => '003_schema_gaps',
    'description' => 'Run missed ensure-helpers (assistant/comm/match-feedback/sms-program).',
    'up' => static function (PDO $pdo): void {
        $root = MELKINO_ROOT;
        foreach ([
            '/db_helpers.php',
            '/admin-assistant-engine.php',
            '/admin-comm-lib.php',
            '/match-engine.php',
            '/sms-program.php',
        ] as $lib) {
            $f = $root . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        // m5EnsureFeedbackTable() reads global $pdo (legacy style); bridge it.
        $hadGlobal = array_key_exists('pdo', $GLOBALS) && $GLOBALS['pdo'] instanceof PDO;
        if (!$hadGlobal) {
            $GLOBALS['pdo'] = $pdo;
        }
        try {
            if (function_exists('astEnsureSchema')) {
                astEnsureSchema($pdo);
            }
            if (function_exists('commEnsureSchema')) {
                commEnsureSchema($pdo);
            }
            if (function_exists('m5EnsureFeedbackTable')) {
                m5EnsureFeedbackTable();
            }
            if (function_exists('smsProgramEnsureSchema')) {
                smsProgramEnsureSchema($pdo);
            }
        } finally {
            if (!$hadGlobal) {
                unset($GLOBALS['pdo']);
            }
        }
    },
];
