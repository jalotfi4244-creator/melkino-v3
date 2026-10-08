<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Requests\RequestRepository;

/**
 * Melkino V2 — request wizard (spec §23, §24).
 * GET  => new 8-step wizard view (posts IDENTICAL field names to the same URL).
 * POST => legacy processor (battle-tested validation/matching/tracking) — unchanged behavior.
 */
final class RequestController
{
    public function handle(): string
    {
        Gate::check('property-request.php');
        if (Request::method() === 'POST') {
            // Delegate to the legacy processor: same validation, same tracking code, same matching.
            require MELKINO_VIEWS . '/legacy/property-request-legacy.php';
            exit;
        }
        $identity = Auth::identity();
        if (!Auth::check()) {
            Response::redirect('login.php?redirect=' . rawurlencode('property-request.php') . (function_exists('melkinoMiniAppForwardQuery') ? melkinoMiniAppForwardQuery() : ''));
        }
        $mine = RequestRepository::mine($identity, 5);
        $amenities = [];
        try {
            $pdo = \Melkino\Core\Database::pdo();
            if ($pdo) {
                $amenities = $pdo->query('SELECT id, name FROM amenities ORDER BY name ASC')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }
        } catch (\Throwable $ignored) {
        }
        if (!$amenities) {
            // Table missing/empty (fresh DB): use the exact legacy 48-name catalog.
            $amenities = \Melkino\Domain\Requests\AmenityCatalog::rows();
        }
        return melkinoView('layouts/public.php', [
            'title' => 'درخواست ملک | ملکینو',
            'description' => 'ثبت درخواست خرید، رهن یا اجاره ملک در ملکینو',
            'active_nav' => 'requests',
            'scripts' => ['forms'],
            'content_view' => 'pages/request.php',
            'content_data' => ['identity' => $identity, 'mine' => $mine, 'amenities' => $amenities],
        ]);
    }
}
