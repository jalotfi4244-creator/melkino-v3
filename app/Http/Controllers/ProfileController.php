<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Domain\Favorites\FavoriteRepository;
use Melkino\Domain\Notifications\NotificationRepository;
use Melkino\Domain\Requests\RequestRepository;

/** Melkino V2 — «My Real Estate» profile hub (spec §30). */
final class ProfileController
{
    public function render(): string
    {
        Gate::check('profile.php');
        $identity = Auth::identity();
        if (!Auth::check()) {
            return melkinoView('layouts/public.php', [
                'title' => 'پروفایل | ملکینو',
                'active_nav' => 'profile',
                'scripts' => [],
                'content_view' => 'pages/login-required.php',
                'content_data' => ['from' => 'profile.php'],
            ]);
        }
        $requests = RequestRepository::mine($identity, 20);
        $matchCount = 0;
        if ($requests) {
            foreach (array_slice($requests, 0, 3) as $r) {
                $matchCount += count(\Melkino\Domain\Matching\MatchRepository::forRequest((int)$r['id'], 50));
            }
        }
        return melkinoView('layouts/public.php', [
            'title' => 'پروفایل من | ملکینو',
            'active_nav' => 'profile',
            'scripts' => ['notifications'],
            'content_view' => 'pages/profile.php',
            'content_data' => [
                'identity' => $identity,
                'stats' => [
                    'favorites' => FavoriteRepository::count($identity),
                    'matches' => $matchCount,
                    'requests' => count($requests),
                    'unread' => NotificationRepository::unreadCount($identity),
                ],
                'requests' => array_slice($requests, 0, 5),
            ],
        ]);
    }
}
