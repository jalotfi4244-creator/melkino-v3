<?php
declare(strict_types=1);

namespace Melkino\Domain\Media;

use Melkino\Core\Database;
use PDO;

/** Melkino V2 — images table persistence. */
final class MediaRepository
{
    /** @return array<int,array> */
    public static function forAd(int $adId): array
    {
        $pdo = Database::pdo();
        if (!$pdo || $adId <= 0) {
            return [];
        }
        $st = $pdo->prepare('SELECT * FROM images WHERE ad_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $st->execute([$adId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function add(int $adId, string $filename, array $flags = []): ?int
    {
        $pdo = Database::pdo();
        if (!$pdo || $adId <= 0 || trim($filename) === '') {
            return null;
        }
        try {
            $st = $pdo->prepare(
                'INSERT INTO images (ad_id, filename, storage_path, sort_order, is_selected, is_primary, publish_publicly, created_at)
                 VALUES (?,?,?,?,?,?,?,NOW())'
            );
            $st->execute([
                $adId, $filename, $flags['storage_path'] ?? $filename,
                (int)($flags['sort_order'] ?? 0), !empty($flags['is_selected']) ? 1 : 0,
                !empty($flags['is_primary']) ? 1 : 0, !empty($flags['publish_publicly']) || !isset($flags['publish_publicly']) ? 1 : 0,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function setPrimary(int $adId, int $imageId): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE images SET is_primary = 0 WHERE ad_id = ?')->execute([$adId]);
            $pdo->prepare('UPDATE images SET is_primary = 1 WHERE id = ? AND ad_id = ?')->execute([$imageId, $adId]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            try {
                $pdo->rollBack();
            } catch (\Throwable $ignored) {
            }
            return false;
        }
    }

    public static function setPublish(int $imageId, int $adId, bool $publish): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $st = $pdo->prepare('UPDATE images SET publish_publicly = ? WHERE id = ? AND ad_id = ?');
            return $st->execute([$publish ? 1 : 0, $imageId, $adId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function delete(int $imageId, int $adId): ?string
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT filename FROM images WHERE id = ? AND ad_id = ? LIMIT 1');
            $st->execute([$imageId, $adId]);
            $file = $st->fetchColumn();
            $pdo->prepare('DELETE FROM images WHERE id = ? AND ad_id = ? LIMIT 1')->execute([$imageId, $adId]);
            return $file ? (string)$file : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
