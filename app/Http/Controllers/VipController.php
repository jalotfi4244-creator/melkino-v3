<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Database;
use Melkino\Http\Gate;

/**
 * Melkino V2 — VIP files (same normalized rows + same shared renderer ad-cards.js).
 * NOTE: V2 bootstrap defines MOCK_MODE=false (production), so VIP always uses DB
 * mode here. (Legacy standalone defaulted to a missing ads.json and rendered empty.)
 */
final class VipController
{
    public function render(): string
    {
        Gate::check('vip.php');
        foreach (['db_helpers.php', 'card-display.php', 'ad-cards-bootstrap.php', 'melkino-logo.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        $pdo = Database::pdo();
        $ads = $this->rows($pdo);

        $head = '';
        if (function_exists('melkinoAdCardsHead')) {
            $head = melkinoAdCardsHead('vip');
            // The helper's inline payload script needs our CSP nonce.
            $head = preg_replace('/<script>/', '<script' . \Melkino\Core\Csp::attr() . '>', $head, 1);
        }

        return melkinoView('layouts/public.php', [
            'title' => 'فایل‌های VIP | ملکینو',
            'description' => 'فایل‌های منتخب و ویژه ملکینو',
            'active_nav' => 'search',
            'head_extra' => $head,
            'scripts' => ['vip'],
            'content_view' => 'pages/vip.php',
            'content_data' => ['ads' => $ads],
        ]);
    }

    /** Same JSON-or-DB assembly + row shape as legacy vip.php. */
    private function rows(?\PDO $pdo): array
    {
        $decode = static function ($v): array {
            if (is_array($v)) {
                return $v;
            }
            if (is_string($v) && trim($v) !== '') {
                $d = json_decode($v, true);
                return is_array($d) ? $d : [];
            }
            return [];
        };
        $price = static function (array $ad): string {
            if (function_exists('melkinoAdDisplayPrice')) {
                try {
                    return (string)melkinoAdDisplayPrice($ad);
                } catch (\Throwable $ignored) {
                }
            }
            return (string)($ad['display_price'] ?? '');
        };
        $out = [];

        $mock = defined('MOCK_MODE') ? (bool)MOCK_MODE : true;
        if ($mock) {
            $file = MELKINO_ROOT . '/ads.json';
            $list = [];
            if (is_file($file)) {
                $d = json_decode((string)@file_get_contents($file), true);
                if (is_array($d)) {
                    $list = $d;
                }
            }
            foreach ($list as $ad) {
                if (!is_array($ad) || ($ad['status'] ?? '') !== 'published') {
                    continue;
                }
                $v = $ad['is_vip'] ?? false;
                if (!($v === true || $v === 1 || $v === '1')) {
                    continue;
                }
                $out[] = [
                    'id' => (string)($ad['id'] ?? ''),
                    'title' => $ad['title'] ?? 'ملک بدون عنوان',
                    'transaction_type' => $ad['transaction_type'] ?? $ad['transactionType'] ?? 'فروش',
                    'property_type' => $ad['property_type'] ?? $ad['propertyType'] ?? 'آپارتمان',
                    'location' => $ad['location'] ?? '',
                    'display_price' => $price($ad),
                    'price' => $ad['price'] ?? '',
                    'price_sell' => $ad['price_sell'] ?? '',
                    'total_price' => $ad['total_price'] ?? '',
                    'deposit' => $ad['deposit'] ?? '',
                    'rent_monthly' => $ad['rent_monthly'] ?? null,
                    'price_hidden' => !empty($ad['price_hidden']),
                    'description' => $ad['description'] ?? '',
                    'selected_images' => $decode($ad['selected_images'] ?? $ad['selectedImages'] ?? []),
                    'details' => $decode($ad['property_details'] ?? []),
                    'amenities' => $decode($ad['amenities'] ?? []),
                    'created_at' => $ad['created_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
            return $out;
        }

        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query("SELECT * FROM ads WHERE status = 'published' AND is_vip = 1 ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
        $imgSt = $pdo->prepare('SELECT filename FROM images WHERE ad_id = ? AND is_selected = 1 AND publish_publicly = 1 ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $amenSt = $pdo->prepare('SELECT am.name FROM ad_amenities aa INNER JOIN amenities am ON am.id = aa.amenity_id WHERE aa.ad_id = ? ORDER BY am.sort_order, am.id');
        foreach ($rows as $ad) {
            try {
                $imgSt->execute([$ad['id']]);
                $images = $imgSt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                $amenSt->execute([$ad['id']]);
                $amenities = $amenSt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            } catch (\Throwable $e) {
                $images = [];
                $amenities = [];
            }
            $out[] = [
                'id' => (string)$ad['id'],
                'title' => $ad['title'] ?? 'ملک بدون عنوان',
                'transaction_type' => $ad['transaction_type'] ?? 'فروش',
                'property_type' => $ad['property_type'] ?? 'آپارتمان',
                'location' => $ad['location'] ?? '',
                'display_price' => $price($ad),
                'price' => $ad['price'] ?? '',
                'price_sell' => $ad['price_sell'] ?? '',
                'total_price' => $ad['total_price'] ?? '',
                'deposit' => $ad['deposit'] ?? '',
                'rent_monthly' => $ad['rent_monthly'] ?? '',
                'price_hidden' => !empty($ad['price_hidden']),
                'description' => $ad['description'] ?? '',
                'selected_images' => $images,
                'details' => $decode($ad['property_details'] ?? []),
                'amenities' => $amenities,
                'created_at' => $ad['created_at'] ?? date('Y-m-d H:i:s'),
            ];
        }
        return $out;
    }
}
