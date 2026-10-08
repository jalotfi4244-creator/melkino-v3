<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه تاریخچه فایل (مرحله ۲۲)
 *--------------------------------------------------------------------------
 * آینهٔ فقط-خواندنیِ admin-publish-logs.php برای یک فایل:
 * تلاش‌های انتشار در کانال‌ها + تاریخچه عملیات مدیریتی.
 * آماده‌سازی جدول‌ها عین DDL سایت (افزایشی، IF NOT EXISTS).
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_hist_ensure')) {
    function office_hist_ensure(PDO $pdo): void
    {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS channel_publish_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ad_id VARCHAR(40) NOT NULL,
                    platform VARCHAR(10) NOT NULL,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    message_id VARCHAR(60) NULL,
                    note VARCHAR(255) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_cpl_ad (ad_id),
                    INDEX idx_cpl_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS ads_history (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    ad_id VARCHAR(64) NOT NULL,
                    action VARCHAR(60) NOT NULL,
                    detail VARCHAR(500) NULL,
                    actor VARCHAR(120) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_ah_ad (ad_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_hist_ad')) {
    function office_hist_ad(PDO $pdo, string $adId): ?array
    {
        if ($adId === '' || strlen($adId) > 64) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT id, title, status, phone FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$adId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_hist_logs')) {
    /** @return array<int,array> */
    function office_hist_logs(PDO $pdo, string $adId): array
    {
        try {
            $st = $pdo->prepare(
                'SELECT id, platform, success, message_id, note, created_at
                 FROM channel_publish_logs
                 WHERE ad_id = ?
                 ORDER BY id DESC
                 LIMIT 100'
            );
            $st->execute([$adId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('office_hist_history')) {
    /** @return array<int,array> */
    function office_hist_history(PDO $pdo, string $adId): array
    {
        try {
            $st = $pdo->prepare(
                'SELECT id, action, detail, actor, created_at
                 FROM ads_history
                 WHERE ad_id = ?
                 ORDER BY id DESC
                 LIMIT 150'
            );
            $st->execute([$adId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
