<?php
declare(strict_types=1);

/** Melkino V2 — unread badge count (server-authoritative). GET */
require_once __DIR__ . '/../_bootstrap.php';

use Melkino\Core\Auth;
use Melkino\Core\Response;
use Melkino\Domain\Notifications\NotificationRepository;

if (!Auth::check()) {
    Response::ok(['unread' => 0]);
}
Response::ok(['unread' => NotificationRepository::unreadCount(Auth::identity())]);
