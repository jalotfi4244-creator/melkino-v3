<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Services\SearchService;
use Melkino\Domain\Promotions\PromotionRepository;
use Melkino\Domain\Favorites\FavoriteRepository;

/**
 * Melkino V2 — Home (spec §9, §10): search-first, calm, server-rendered.
 * Production ALWAYS requires DB (no accidental mock fallback).
 */
final class HomeController
{
    public function render(): string
    {
        Gate::check('home.php');
        $identity = Auth::identity();
        $latest = SearchService::search(['sort' => 'newest'], 1, 8, $identity);
        $forYou = $this->forYou($identity);
        $promos = PromotionRepository::active('home', 4);
        return melkinoView('layouts/public.php', [
            'title' => 'ملکینو | خرید، فروش و اجاره ملک',
            'description' => 'ملکینو؛ انتخابی فراتر از یک ملک — جستجوی خرید، فروش و اجاره آپارتمان، خانه، ویلا، زمین و ملک تجاری',
            'active_nav' => 'home',
            'scripts' => ['search', 'properties', 'favorites', 'compare'],
            'content_view' => 'pages/home.php',
            'content_data' => [
                'identity' => $identity,
                'latest' => $latest['items'],
                'for_you' => $forYou,
                'promos' => $promos,
            ],
        ]);
    }

    /** @return array<int,array> */
    private function forYou(array $identity): array
    {
        try {
            if (!empty($identity['phone']) || !empty($identity['user_id'])) {
                $mine = \Melkino\Domain\Requests\RequestRepository::mine($identity, 1);
                if ($mine) {
                    $matches = \Melkino\Domain\Matching\MatchingService::topForRequest((int)$mine[0]['id'], 4);
                    $out = [];
                    foreach ($matches as $m) {
                        $ad = \Melkino\Domain\Properties\PropertyRepository::findPublished((int)$m['ad_id']);
                        if ($ad) {
                            $ad['primary_image'] = null;
                            $imgs = \Melkino\Domain\Properties\PropertyRepository::primaryImages([(int)$ad['id']]);
                            $ad['primary_image'] = $imgs[(int)$ad['id']] ?? null;
                            $dto = PropertyFormatter::card($ad);
                            $dto['match_percent'] = (float)$m['match_percent'];
                            $out[] = $dto;
                        }
                    }
                    if ($out) {
                        return $out;
                    }
                }
            }
        } catch (\Throwable $ignored) {
        }
        // Fallback: VIP / most-viewed.
        $res = SearchService::search(['sort' => 'views'], 1, 4, $identity);
        return $res['items'];
    }
}
