<?php
declare(strict_types=1);

/** Melkino V2 — canonical search endpoint (spec §77). GET /api/properties/search.php?tx=...&page=... */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Services\SearchService;

$page = Request::int('page', 1, 1, 1000);
$perPage = Request::int('per_page', 20, 1, 60);
$result = SearchService::search($_GET, $page, $perPage, melkinoApiIdentity());
Response::ok($result);
