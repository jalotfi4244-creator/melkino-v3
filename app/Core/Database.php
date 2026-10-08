<?php
declare(strict_types=1);

namespace Melkino\Core;

use PDO;
use PDOException;

/**
 * Melkino V2 — single database provider (spec §7, §128).
 * One PDO instance, global $pdo kept in sync for legacy code.
 */
final class Database
{
    private static ?PDO $instance = null;
    private static bool $attempted = false;

    public static function pdo(): ?PDO
    {
        global $pdo;
        if (self::$instance instanceof PDO) {
            $pdo = self::$instance;
            return self::$instance;
        }
        if ($pdo instanceof PDO) {
            self::$instance = $pdo;
            return $pdo;
        }
        if (self::$attempted) {
            return null;
        }
        self::$attempted = true;
        if (defined('MOCK_MODE') && MOCK_MODE) {
            return null;
        }
        if (!defined('DB_HOST') || DB_HOST === '' || DB_NAME === '') {
            return null;
        }
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            self::$instance = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            $pdo = null;
            error_log('[melkino] Database connection failed: ' . $e->getMessage());
            Logger::error('Database connection failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$attempted = false;
    }

    /** Pages that cannot render without DB: show a clean 503 instead of white screen. */
    public static function requirePdo(): PDO
    {
        $pdo = self::pdo();
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        http_response_code(503);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>سایت موقتاً در دسترس نیست</title></head>'
            . '<body style="font-family:Tahoma,sans-serif;text-align:center;padding:60px 20px">'
            . '<h1>ملکینو موقتاً در دسترس نیست</h1>'
            . '<p>اتصال به پایگاه داده برقرار نشد. لطفاً کمی بعد دوباره تلاش کنید.</p></body></html>';
        exit;
    }

    public static function tableExists(string $table): bool
    {
        $pdo = self::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
            $st->execute([$table]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function columnExists(string $table, string $column): bool
    {
        $pdo = self::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
            $st->execute([$table, $column]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
