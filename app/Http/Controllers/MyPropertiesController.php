<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Http\Gate;
use Melkino\Support\Assets;

/**
 * Melkino V2 — "آگهی‌های من" shell (my-properties V2 API-shell contract).
 *
 * The legacy file owns every ?action=* API branch (same-file fetch contract).
 * The V2 controller passes ?action through to the legacy include and renders
 * only the mp-* skeleton for the page chrome.
 */
final class MyPropertiesController
{
    public function handle(): string
    {
        Gate::check('my-properties.php');

        $f = MELKINO_ROOT . '/db_helpers.php';
        if (is_file($f)) {
            require_once $f;
        }

        if (isset($_GET['action']) || isset($_POST['action'])) {
            require MELKINO_VIEWS . '/legacy/my-properties-legacy.php';
            exit;
        }

        $identity = function_exists('melkinoCurrentIdentity')
            ? melkinoCurrentIdentity($_GET['telegram_id'] ?? $_POST['telegram_id'] ?? null)
            : ['telegram_id' => ''];

        return melkinoView('layouts/public.php', [
            'title'        => 'آگهی‌های من | ملکینو',
            'active_nav'   => '',
            'head_extra'   => Assets::css('assets/css/myprops-legacy.css'),
            'scripts'      => ['myprops-shell'],
            'content_view' => 'pages/my-properties.php',
            'content_data' => ['telegram_id' => (string) ($identity['telegram_id'] ?? '')],
        ]);
    }
}
