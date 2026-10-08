<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Services\SearchService;

/** Melkino V2 — listing (spec §11, §12): server-side pagination, shareable filters, bottom-sheet filters on mobile. */
final class PropertiesController
{
    public function render(): string
    {
        Gate::check('properties.php');
        $identity = Auth::identity();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $result = SearchService::search($_GET, $page, $perPage, $identity);
        $totalPages = max(1, (int)ceil($result['total'] / $perPage));
        return melkinoView('layouts/public.php', [
            'title' => 'جستجوی ملک | ملکینو',
            'description' => 'جستجو و فیلتر آگهی‌های خرید، فروش، رهن و اجاره ملک',
            'active_nav' => 'search',
            'scripts' => ['search', 'properties', 'favorites', 'compare', 'forms'],
            'content_view' => 'pages/properties.php',
            'content_data' => [
                'identity' => $identity,
                'result' => $result,
                'page' => $page,
                'total_pages' => $totalPages,
                'share_query' => SearchService::shareQuery($_GET),
            ],
        ]);
    }
}
