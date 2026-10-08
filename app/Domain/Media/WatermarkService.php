<?php
declare(strict_types=1);

namespace Melkino\Domain\Media;

/**
 * Melkino V2 — watermark service (spec §105).
 * Delegates to legacy photo-watermark.php functions when present (same output, zero drift).
 */
final class WatermarkService
{
    public static function apply(string $srcPath, string $destPath, array $options = []): bool
    {
        if (!is_file($srcPath)) {
            return false;
        }
        if (!function_exists('melkinoWatermarkApply')) {
            @require_once MELKINO_ROOT . '/photo-watermark.php';
        }
        if (function_exists('melkinoWatermarkApply')) {
            try {
                return (bool)@melkinoWatermarkApply($srcPath, $destPath, $options);
            } catch (\Throwable $e) {
                return false;
            }
        }
        // No legacy watermarker: plain copy keeps the flow working.
        return @copy($srcPath, $destPath);
    }
}
