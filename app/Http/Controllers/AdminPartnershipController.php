<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Core\Csp;
use Melkino\Core\Response;

/**
 * Melkino V2 — admin partnership console (API-shell: ?action=list|get|status|
 * delete|note|update is served byte-identically by the legacy include; plain
 * GET renders the V2 shell with the same guard + statuses + csrf bootstrap).
 */
final class AdminPartnershipController
{
    public function handle(): string
    {
        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php?redirect=' . rawurlencode('admin-partnership.php'));
        }

        foreach (['config.php', 'db_helpers.php', 'partnership-lib.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        if (function_exists('melkinoEnsurePartnershipSchema')) {
            melkinoEnsurePartnershipSchema();
        }

        if (isset($_GET['action']) || isset($_POST['action'])) {
            require MELKINO_VIEWS . '/legacy/admin-partnership-legacy.php';
            exit;
        }

        Csp::sendHtmlHeaders();

        return melkinoView('pages/admin-partnership.php', [
            'statuses' => function_exists('melkinoPartStatuses') ? (array) melkinoPartStatuses() : [],
            'open' => (int) ($_GET['open'] ?? 0),
            'csrf' => function_exists('melkinoCsrfToken') ? (string) melkinoCsrfToken() : '',
        ]);
    }
}
