<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Core\Csp;
use Melkino\Core\Response;

/** Melkino V2 — admin leads/SMS follow-up (standalone; same guard + same shell as legacy). */
final class AdminLeadsController
{
    public function render(): string
    {
        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php');
        }

        if (is_file(MELKINO_ROOT . '/config.php')) {
            require_once MELKINO_ROOT . '/config.php';
        }

        Csp::sendHtmlHeaders();

        return melkinoView('pages/admin-leads.php', []);
    }
}
