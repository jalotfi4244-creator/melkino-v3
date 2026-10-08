<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Core\Request;
use Melkino\Domain\Compare\CompareService;
use Melkino\Domain\Favorites\FavoriteRepository;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyPolicy;
use Melkino\Domain\Properties\PropertyRepository;

/** Melkino V2 — detail (spec §17, §18): server-rendered, SEO'd, one primary CTA. */
final class PropertyDetailController
{
    public function render(): string
    {
        Gate::check('property-details.php');
        $id = Request::int('id', 0, 1);
        if ($id <= 0) {
            return $this->notFound();
        }
        $ad = PropertyRepository::find($id);
        if (!$ad || !PropertyPolicy::canView($ad)) {
            return $this->notFound();
        }
        PropertyRepository::incrementViews($id);
        try {
            if (!function_exists('melkinoRecordAdView')) {
                @require_once MELKINO_ROOT . '/db_helpers.php';
            }
            if (function_exists('melkinoRecordAdView')) {
                // Legacy contract: ($userId, $adId, $title='', $telegramId='', $baleId='')
                $me = Auth::identity();
                @melkinoRecordAdView($me['user_id'] ?? 0, $id, (string)($ad['title'] ?? ''), $me['telegram_id'], $me['bale_id']);
            }
        } catch (\Throwable $ignored) {
        }
        $identity = Auth::identity();
        $gallery = PropertyRepository::gallery($id, true);
        $amenities = PropertyRepository::amenities($id);
        $dto = PropertyFormatter::detail($ad, $gallery, $amenities);
        $similar = array_map(
            static fn($s) => PropertyFormatter::card($s),
            PropertyRepository::similar($ad, 6)
        );
        $title = $dto['property']['title'] . ' در ' . ($dto['location']['text'] !== '' ? $dto['location']['text'] : 'شاهرود') . ' | ملکینو';
        $ogImage = $dto['gallery'][0]['src'] ?? '';
        $jsonLd = $this->jsonLd($dto);
        return melkinoView('layouts/public.php', [
            'title' => $title,
            'description' => mb_substr($dto['property']['description'] !== '' ? $dto['property']['description'] : $title, 0, 160),
            'canonical' => 'property-details.php?id=' . $id,
            'og_image' => $ogImage,
            'og_type' => 'article',
            'active_nav' => 'search',
            'scripts' => ['property-detail', 'favorites', 'compare', 'forms'],
            'head_extra' => '<script type="application/ld+json">' . $jsonLd . '</script>',
            'content_view' => 'pages/property-details.php',
            'content_data' => [
                'identity' => $identity,
                'detail' => $dto,
                'similar' => $similar,
                'is_favorite' => FavoriteRepository::isFavorite($id, $identity),
                'in_compare' => in_array($id, CompareService::allIds(), true),
            ],
        ]);
    }

    private function notFound(): string
    {
        http_response_code(404);
        return melkinoView('layouts/public.php', [
            'title' => 'ملک پیدا نشد | ملکینو',
            'active_nav' => 'search',
            'scripts' => [],
            'content_view' => 'pages/404-property.php',
            'content_data' => [],
        ]);
    }

    private function jsonLd(array $dto): string
    {
        $p = $dto['property'];
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $p['title'],
            'description' => mb_substr($p['description'], 0, 300),
            'url' => 'property-details.php?id=' . $p['id'],
        ];
        if ($dto['gallery']) {
            $data['image'] = array_column($dto['gallery'], 'src');
        }
        if ($dto['location']['text'] !== '') {
            $data['address'] = ['@type' => 'PostalAddress', 'addressLocality' => $dto['location']['text']];
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
