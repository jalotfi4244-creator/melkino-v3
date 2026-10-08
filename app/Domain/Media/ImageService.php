<?php
declare(strict_types=1);

namespace Melkino\Domain\Media;

/**
 * Melkino V2 — image pipeline (spec §19, §67).
 * Validation/re-encode delegate to the hardened security-lib.php helpers.
 */
final class ImageService
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /** @return array{ok:bool,error:string} */
    public static function validate(array $file): array
    {
        if (!function_exists('melkinoValidateUploadedImage')) {
            @require_once MELKINO_ROOT . '/security-lib.php';
        }
        if (function_exists('melkinoValidateUploadedImage')) {
            // Legacy contract: (string $tmpPath, int $maxBytes) => ['ok','message','ext','type',...]
            $tmp = (string)($file['tmp_name'] ?? '');
            $r = melkinoValidateUploadedImage($tmp, self::MAX_BYTES);
            if (is_array($r)) {
                $ok = (bool)($r['ok'] ?? false);
                if ($ok && !empty($r['ext']) && !in_array(strtolower((string)$r['ext']), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return ['ok' => false, 'error' => 'فقط JPG و PNG و WebP مجاز است.'];
                }
                return ['ok' => $ok, 'error' => $ok ? '' : (string)($r['message'] ?? 'فایل معتبر نیست.')];
            }
            return ['ok' => (bool)$r, 'error' => $r ? '' : 'فایل معتبر نیست.'];
        }
        // Fallback validation (no security-lib).
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'خطا در آپلود فایل.'];
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'حجم فایل بیشتر از حد مجاز است.'];
        }
        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $fi ? (string)finfo_file($fi, (string)$file['tmp_name']) : '';
            if ($fi) {
                finfo_close($fi);
            }
        }
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'فقط JPG و PNG و WebP مجاز است.'];
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['php', 'phtml', 'phar', 'svg', 'html', 'js'], true)) {
            return ['ok' => false, 'error' => 'نوع فایل مجاز نیست.'];
        }
        return ['ok' => true, 'error' => ''];
    }

    public static function randomName(string $original): string
    {
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
        if (!function_exists('melkinoRandomFileName')) {
            @require_once MELKINO_ROOT . '/security-lib.php';
        }
        if (function_exists('melkinoRandomFileName')) {
            // Legacy contract: (string $prefix, string $ext)
            return (string)melkinoRandomFileName('mk' . date('Ymd'), $ext);
        }
        return date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    }

    /** Re-encode to strip metadata/scripts; returns true on success. */
    public static function sanitize(string $path): bool
    {
        if (!function_exists('melkinoReencodeImage')) {
            @require_once MELKINO_ROOT . '/security-lib.php';
        }
        if (function_exists('melkinoReencodeImage') && function_exists('melkinoImageTypeOf')) {
            try {
                $probe = melkinoImageTypeOf($path);
                $type = (int)($probe['type'] ?? 0);
                if ($type <= 0) {
                    return false;
                }
                $tmp = $path . '.clean';
                // Legacy contract: ($srcPath, $destPath, $type)
                if (@melkinoReencodeImage($path, $tmp, $type) && is_file($tmp)) {
                    @rename($tmp, $path);
                    return true;
                }
                @unlink($tmp);
                return false;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
        return true;
    }

    /** Public URL for a stored filename (fallback to type default when empty). */
    public static function url(?string $filename, string $propertyType = ''): string
    {
        if ($filename && trim($filename) !== '') {
            $f = ltrim(trim($filename), '/');
            if (str_starts_with($f, 'uploads/') || str_starts_with($f, 'http')) {
                return $f;
            }
            return 'uploads/' . $f;
        }
        if (function_exists('melkinoDefaultImageForAd')) {
            try {
                $d = (string)@melkinoDefaultImageForAd(['property_type' => $propertyType]);
                if ($d !== '') {
                    return $d;
                }
            } catch (\Throwable $ignored) {
            }
        }
        return 'assets/defaults/property-placeholder.svg';
    }
}
