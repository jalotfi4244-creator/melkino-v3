<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;
use Melkino\Domain\Favorites\FavoriteRepository;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyRepository;

/** Melkino V2 — favorites (spec §20). */
final class FavoritesController
{
    public function render(): string
    {
        Gate::check('favorites.php');
        $identity = Auth::identity();
        if (!Auth::check()) {
            return melkinoView('layouts/public.php', [
                'title' => 'ملک‌های ذخیره‌شده | ملکینو',
                'active_nav' => 'favorites',
                'scripts' => [],
                'content_view' => 'pages/login-required.php',
                'content_data' => ['from' => 'favorites.php'],
            ]);
        }
        $tx = (string)($_GET['tx'] ?? '');
        if (!in_array($tx, ['', 'فروش', 'اجاره', 'رهن'], true)) {
            $tx = '';
        }
        $rows = FavoriteRepository::list($identity, $tx !== '' ? $tx : null, 100);
        if ($rows) {
            $images = PropertyRepository::primaryImages(array_column($rows, 'id'));
            foreach ($rows as &$r) {
                $r['primary_image'] = $images[(int)$r['id']] ?? null;
            }
            unset($r);
        }
        $dtos = array_map(static fn($ad) => PropertyFormatter::card($ad, ['favorite' => true]), $rows);
        return melkinoView('layouts/public.php', [
            'title' => 'ملک‌های ذخیره‌شده | ملکینو',
            'active_nav' => 'favorites',
            'scripts' => ['favorites', 'compare', 'properties'],
            'content_view' => 'pages/favorites.php',
            'content_data' => ['identity' => $identity, 'items' => $dtos, 'tx' => $tx],
        ]);
    }
}
