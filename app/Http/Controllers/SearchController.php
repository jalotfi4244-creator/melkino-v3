<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Http\Gate;
/** Melkino V2 — search.php / search-results.php canonicalize to the listing (spec §13). */
final class SearchController
{
    public function render(): string
    {
        Gate::check('search.php');
        $q = trim((string)($_GET['q'] ?? $_GET['keyword'] ?? $_GET['search'] ?? ''));
        $params = $_GET;
        if ($q !== '') {
            $params['q'] = $q;
        }
        unset($params['keyword'], $params['search']);
        // Server-render the canonical listing in place (URL preserved, no redirect loop risk).
        $_GET = $params;
        return (new PropertiesController())->render();
    }
}
