<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه دستیار (مرحله ۳۰)
 *--------------------------------------------------------------------------
 * آینهٔ سروررندرِ دستیار پنل سایت (admin-assistant-api.php): برد تحلیل،
 * تازه‌سازی، تاریخچه و گفت‌وگو. موتور (admin-assistant-engine.php) خالص
 * است و مستقیم استفاده می‌شود؛ همان جدول‌های assistant_*.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofAstF = dirname(__DIR__) . '/admin-assistant-engine.php';
if (is_file($__ofAstF)) {
    require_once $__ofAstF;
}
unset($__ofAstF);
if (!function_exists('astLoadBoard')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/admin-assistant-engine.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_ast_ready')) {
    function office_ast_ready(): bool
    {
        return function_exists('astLoadBoard') && function_exists('astRefresh')
            && function_exists('astChatAnswer') && function_exists('astChatHistory');
    }
}

if (!function_exists('office_ast_board')) {
    /** @return array{run:bool,board:array,message:string} */
    function office_ast_board(PDO $pdo): array
    {
        if (!office_ast_ready()) {
            return ['run' => false, 'board' => ['run' => null, 'insights' => [], 'kpis' => []], 'message' => 'موتور دستیار روی هاست نیست.'];
        }
        try {
            $board = astLoadBoard($pdo);
            if (empty($board['run'])) {
                $board = astRefresh($pdo);
            }
            return ['run' => true, 'board' => $board, 'message' => ''];
        } catch (Throwable $e) {
            return ['run' => false, 'board' => ['run' => null, 'insights' => [], 'kpis' => []], 'message' => 'ساخت تحلیل انجام نشد.'];
        }
    }
}

if (!function_exists('office_ast_refresh')) {
    /** @return array{0:bool,1:string} */
    function office_ast_refresh(PDO $pdo): array
    {
        if (!office_ast_ready()) {
            return [false, 'موتور دستیار روی هاست نیست.'];
        }
        try {
            astRefresh($pdo);
            return [true, 'تحلیل از دیتابیس ملکینو تازه شد.'];
        } catch (Throwable $e) {
            return [false, 'تحلیل ذخیره نشد.'];
        }
    }
}

if (!function_exists('office_ast_chat')) {
    /** @return array{reply:string,insights:array,filter:string} */
    function office_ast_chat(PDO $pdo, string $message): array
    {
        $fallback = ['reply' => 'موتور دستیار روی هاست نیست.', 'insights' => [], 'filter' => 'all'];
        if (!office_ast_ready()) {
            return $fallback;
        }
        $board = astLoadBoard($pdo);
        if (empty($board['run'])) {
            $board = astRefresh($pdo);
        }
        $message = trim($message);
        if ($message !== '') {
            astSaveChat($pdo, 'admin', $message);
        }
        $ans = astChatAnswer($board, $message);
        astSaveChat($pdo, 'assistant', (string)$ans['reply']);
        return ['reply' => (string)$ans['reply'], 'insights' => $ans['insights'], 'filter' => (string)$ans['filter']];
    }
}

if (!function_exists('office_ast_history')) {
    /** @return array<int,array> */
    function office_ast_history(PDO $pdo, int $limit = 50): array
    {
        if (!office_ast_ready()) {
            return [];
        }
        try {
            return astChatHistory($pdo, $limit);
        } catch (Throwable $e) {
            return [];
        }
    }
}
