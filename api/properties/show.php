<?php
declare(strict_types=1);

/** Melkino V2 — canonical detail endpoint. GET /api/properties/show.php?id=123 */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyPolicy;
use Melkino\Domain\Properties\PropertyRepository;

$id = Request::int('id', 0, 1);
$ad = $id > 0 ? PropertyRepository::find($id) : null;
if (!$ad || !PropertyPolicy::canView($ad)) {
    Response::fail('ملک پیدا نشد.', 404);
}
Response::ok(PropertyFormatter::detail(
    $ad,
    PropertyRepository::gallery($id, true),
    PropertyRepository::amenities($id)
));
