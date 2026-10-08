<?php
declare(strict_types=1);

/** Melkino V2 — canonical compare toggle. POST {ad_id} (same round-24 rules as compare.php). */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Compare\CompareService;

$adId = Request::int('ad_id', 0, 1);
if ($adId <= 0) {
    Response::fail('شناسه آگهی الزامی است.', 422);
}
$r = CompareService::toggle($adId);
Response::json(
    ['added' => $r['added'], 'removed' => $r['removed'], 'count' => $r['count']],
    $r['message'],
    $r['ok'],
    $r['code']
);
