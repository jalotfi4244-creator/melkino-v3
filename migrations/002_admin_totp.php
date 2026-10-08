<?php
declare(strict_types=1);

/**
 * Migration 002 — admin TOTP enrollment table (spec §38–§39).
 * Idempotent: CREATE TABLE IF NOT EXISTS; tracked in schema_migrations.
 * One row per admin; verified_at NULL = never store unverified secrets
 * (setup keeps the pending secret in session until the first code verifies,
 * so this column is normally always set — it exists to make that invariant
 * queryable).
 */
return [
    'version' => '002_admin_totp',
    'description' => 'Create admin_totp table (admin 2FA enrollment).',
    'up' => static function (PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS admin_totp (
            admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
            secret VARCHAR(64) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    },
];
