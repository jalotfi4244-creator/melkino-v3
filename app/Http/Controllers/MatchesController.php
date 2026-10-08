<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Core\Csrf;
use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Domain\Matching\MatchingService;
use Melkino\Domain\Matching\MatchRepository;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyRepository;
use Melkino\Domain\Requests\RequestRepository;

/** Melkino V2 — «فایل‌های مناسب من» (spec §22, §25). */
final class MatchesController
{
    public function render(): string
    {
        Gate::check('my-request-matches.php');
        $identity = Auth::identity();
        if (!Auth::check()) {
            return melkinoView('layouts/public.php', [
                'title' => 'فایل‌های مناسب من | ملکینو',
                'active_nav' => 'requests',
                'scripts' => [],
                'content_view' => 'pages/login-required.php',
                'content_data' => ['from' => 'my-request-matches.php'],
            ]);
        }
        // Feedback POST (CSRF-checked, server-side ownership via request lookup).
        if (Request::method() === 'POST' && Request::string('action') === 'feedback') {
            Csrf::check();
            $matchId = Request::int('match_id', 0, 1);
            $feedback = Request::string('feedback');
            MatchRepository::saveFeedback($matchId, $feedback, $identity);
            Response::redirect('my-request-matches.php');
        }
        $requests = RequestRepository::mine($identity, 20);
        $active = $requests[0] ?? null;
        $rid = Request::int('request_id', 0, 1);
        if ($rid > 0) {
            foreach ($requests as $r) {
                if ((int)$r['id'] === $rid) {
                    $active = $r;
                    break;
                }
            }
        }
        $matches = [];
        if ($active) {
            foreach (MatchingService::topForRequest((int)$active['id'], 30) as $m) {
                $ad = PropertyRepository::findPublished((int)$m['ad_id']);
                if (!$ad) {
                    continue;
                }
                $imgs = PropertyRepository::primaryImages([(int)$ad['id']]);
                $ad['primary_image'] = $imgs[(int)$ad['id']] ?? null;
                $dto = PropertyFormatter::card($ad);
                $dto['match_percent'] = (float)$m['match_percent'];
                $dto['match_id'] = (int)$m['id'];
                $dto['breakdown'] = $m['breakdown'];
                $matches[] = $dto;
            }
        }
        return melkinoView('layouts/public.php', [
            'title' => 'فایل‌های مناسب من | ملکینو',
            'active_nav' => 'requests',
            'scripts' => ['favorites', 'compare', 'forms'],
            'content_view' => 'pages/matches.php',
            'content_data' => ['requests' => $requests, 'active' => $active, 'matches' => $matches],
        ]);
    }
}
