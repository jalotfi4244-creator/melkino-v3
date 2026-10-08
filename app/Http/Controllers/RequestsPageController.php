<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Http\Gate;

/**
 * Melkino V2 — my requests shell.
 * ?action=list|update|delete => legacy endpoint (byte-identical JSON, same CSRF);
 * plain GET => V2 shell (same ids/contract, CSP-safe delegation).
 */
final class RequestsPageController
{
    public function handle(): string
    {
        Gate::check('requests.php');
        if (isset($_GET['action'])) {
            require MELKINO_VIEWS . '/legacy/requests-legacy.php';
            exit;
        }
        $f = MELKINO_ROOT . '/db_helpers.php';
        if (is_file($f)) {
            require_once $f;
        }
        $identity = [];
        if (function_exists('melkinoCurrentIdentity')) {
            try {
                $identity = (array)melkinoCurrentIdentity($_GET['telegram_id'] ?? null);
            } catch (\Throwable $ignored) {
            }
        }
        return melkinoView('layouts/public.php', [
            'title' => 'درخواست‌های من | ملکینو',
            'description' => 'درخواست‌های ثبت‌شده، ویرایش و فایل‌های مناسب شما',
            'active_nav' => 'requests',
            'scripts' => ['forms', 'requests-shell'],
            'content_view' => 'pages/requests.php',
            'content_data' => ['telegram_id' => (string)($identity['telegram_id'] ?? '')],
        ]);
    }
}
