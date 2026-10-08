<?php
declare(strict_types=1);

/**
 * Melkino V2 — centralized error handling (spec §129, §130).
 * - Production: generic user message, full context in server log.
 * - Admin sessions: detailed error box (same behavior as legacy config.php).
 */

error_reporting(E_ALL);
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Asia/Tehran');
}

if (!function_exists('melkinoShouldShowErrorDetails')) {
    function melkinoShouldShowErrorDetails(): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
            @session_start();
        }
        return !empty($_SESSION['is_admin']);
    }
}

if (!function_exists('melkinoRenderError')) {
    function melkinoRenderError(string $message, string $file, int $line): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (!melkinoShouldShowErrorDetails()) {
            echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<title>خطا</title></head><body style="font-family:Tahoma,sans-serif;'
                . 'text-align:center;padding:60px 20px;">'
                . '<h2>مشکلی پیش آمد</h2>'
                . '<p>لطفاً کمی بعد دوباره تلاش کنید.</p></body></html>';
            return;
        }
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
            . '<title>خطای PHP</title></head><body style="font-family:Tahoma,sans-serif;'
            . 'direction:rtl;padding:30px;background:#1a1a1a;color:#f5f5f5;">'
            . '<h2 style="color:#ff6b6b;">یک خطا رخ داد</h2>'
            . '<p style="font-size:16px;background:#2a2a2a;padding:15px;border-radius:8px;'
            . 'white-space:pre-wrap;word-break:break-word;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="color:#aaa;">فایل: ' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '<br>خط: ' . $line . '</p>'
            . '</body></html>';
    }
}

set_exception_handler(function (Throwable $e) {
    error_log('[melkino] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (class_exists('Melkino\\Core\\Logger')) {
        try {
            Melkino\Core\Logger::error('Uncaught exception', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
        } catch (Throwable $ignored) {
        }
    }
    if (PHP_SAPI !== 'cli') {
        melkinoRenderError($e->getMessage(), $e->getFile(), $e->getLine());
    }
    exit(1);
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array((int)$err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[melkino] Fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            melkinoRenderError((string)$err['message'], (string)$err['file'], (int)$err['line']);
        }
    }
});
