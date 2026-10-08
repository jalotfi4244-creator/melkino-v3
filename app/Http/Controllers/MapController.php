<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Database;
use Melkino\Http\Gate;

/**
 * Melkino V2 — map page (same data contract as map.php: public settings,
 * studio theme with draft priority, marker shapes; map.js/map-markers.js untouched).
 */
final class MapController
{
    public function render(): string
    {
        Gate::check('map.php');
        foreach (['map-lib.php', 'map-markers.php', 'design-studio-lib.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        $pdo = Database::pdo();
        $settings = ($pdo && function_exists('melkinoMapPublicSettings'))
            ? melkinoMapPublicSettings($pdo)
            : (function_exists('melkinoMapDefaults') ? melkinoMapDefaults() : ['enabled' => true]);

        $theme = function_exists('melkinoStudioDefaultTheme') ? melkinoStudioDefaultTheme() : [];
        try {
            if ($pdo && function_exists('dbSettingGet')) {
                $draft = dbSettingGet($pdo, 'theme', 'studio_draft', null);
                $pub = dbSettingGet($pdo, 'theme', 'studio_published', null);
                $raw = (is_array($draft) && !empty($draft['layout'])) ? $draft : $pub;
                if (is_array($raw) && function_exists('melkinoStudioSanitize')) {
                    $theme = melkinoStudioSanitize($raw);
                }
            }
        } catch (\Throwable $ignored) {
        }
        $mapTheme = [
            'mapCard' => $theme['mapCard'] ?? [],
            'marker' => $theme['marker'] ?? [],
            'colors' => [
                'primary' => $theme['colors']['primary'] ?? '#0e7c6e',
                'gold' => $theme['colors']['gold'] ?? '#D4AF37',
            ],
        ];
        $studioCss = function_exists('melkinoStudioCss') ? (string)melkinoStudioCss($theme) : '';
        $shapes = function_exists('melkinoMarkerShapes') ? melkinoMarkerShapes() : [];

        return melkinoView('layouts/public.php', [
            'title' => 'نقشه املاک | ملکینو',
            'description' => 'مشاهده موقعیت ملک‌ها روی نقشه',
            'active_nav' => 'search',
            'head_extra' => '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">'
                . "\n" . \Melkino\Support\Assets::css('map.css'),
            'scripts' => ['./map-markers.js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', './map.js'],
            'content_view' => 'pages/map.php',
            'content_data' => [
                'settings' => $settings, 'mapTheme' => $mapTheme,
                'studioCss' => $studioCss, 'shapes' => $shapes,
            ],
        ]);
    }
}
