<?php
declare(strict_types=1);

/** Melkino V2 — notification list. GET ?tab=&page= */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Notifications\NotificationRepository;

$identity = melkinoApiRequireLogin();
$tab = Request::oneOf('tab', ['all', 'property', 'request', 'account'], 'all');
$page = Request::int('page', 1, 1, 1000);
Response::ok(NotificationRepository::paginate($identity, $tab === 'all' ? null : $tab, $page, 20));
