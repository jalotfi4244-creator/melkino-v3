<?php
declare(strict_types=1);

namespace Melkino\Domain\Media;

use Melkino\Core\Logger;

/** Melkino V2 — hardened upload flow (spec §67): validate -> random name -> move -> sanitize. */
final class UploadService
{
    /** @return array{ok:bool,filename:string,error:string} */
    public static function store(array $file, string $subdir = ''): array
    {
        $v = ImageService::validate($file);
        if (!$v['ok']) {
            return ['ok' => false, 'filename' => '', 'error' => $v['error']];
        }
        $dir = MELKINO_UPLOADS . ($subdir !== '' ? '/' . trim($subdir, '/') : '');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $name = ImageService::randomName((string)($file['name'] ?? 'image.jpg'));
        $dest = $dir . '/' . $name;
        $moved = @move_uploaded_file((string)$file['tmp_name'], $dest);
        if (!$moved) {
            $moved = @copy((string)$file['tmp_name'], $dest);
        }
        if (!$moved) {
            Logger::warning('upload move failed', ['name' => $file['name'] ?? '']);
            return ['ok' => false, 'filename' => '', 'error' => 'ذخیره فایل ناموفق بود.'];
        }
        @chmod($dest, 0644);
        ImageService::sanitize($dest);
        return ['ok' => true, 'filename' => ($subdir !== '' ? trim($subdir, '/') . '/' : '') . $name, 'error' => ''];
    }
}
