<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Core\Request;
use Melkino\Domain\Notifications\NotificationRepository;

/** Melkino V2 — notification center (spec §26): tabs, actionable items, mark-all-read. */
final class NotificationsController
{
    public function render(): string
    {
        Gate::check('notifications.php');
        $identity = Auth::identity();
        if (!Auth::check()) {
            return melkinoView('layouts/public.php', [
                'title' => 'اعلان‌ها | ملکینو',
                'active_nav' => 'profile',
                'scripts' => [],
                'content_view' => 'pages/login-required.php',
                'content_data' => ['from' => 'notifications.php'],
            ]);
        }
        // POST actions (CSRF-checked).
        if (Request::method() === 'POST') {
            \Melkino\Core\Csrf::check();
            $action = Request::string('action');
            if ($action === 'mark_all_read') {
                NotificationRepository::markAllRead($identity);
            } elseif ($action === 'mark_read') {
                NotificationRepository::markRead(Request::int('id', 0, 1), $identity);
            }
            \Melkino\Core\Response::redirect('notifications.php' . (!empty($_GET['tab']) ? '?tab=' . urlencode((string)$_GET['tab']) : ''));
        }
        $tab = Request::oneOf('tab', ['all', 'property', 'request', 'account'], 'all');
        $page = Request::int('page', 1, 1, 1000);
        $result = NotificationRepository::paginate($identity, $tab === 'all' ? null : $tab, $page, 20);
        return melkinoView('layouts/public.php', [
            'title' => 'اعلان‌ها | ملکینو',
            'active_nav' => 'profile',
            'scripts' => ['notifications'],
            'content_view' => 'pages/notifications.php',
            'content_data' => ['result' => $result, 'tab' => $tab, 'page' => $page],
        ]);
    }
}
