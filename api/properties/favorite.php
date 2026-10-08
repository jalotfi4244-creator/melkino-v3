<?php
declare(strict_types=1);

/** Melkino V2 — canonical favorite toggle. POST {ad_id} */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Favorites\FavoriteRepository;

$identity = melkinoApiRequireLogin();
$adId = Request::int('ad_id', 0, 1);
if ($adId <= 0) {
    Response::fail('شناسه ملک معتبر نیست.', 422);
}
$r = FavoriteRepository::toggle($adId, $identity);
if (!$r['ok']) {
    Response::fail('ذخیره انجام نشد.', 500);
}
Response::ok(['saved' => $r['saved'], 'ad_id' => $adId], $r['saved'] ? 'ملک ذخیره شد.' : 'از ذخیره‌ها حذف شد.');
