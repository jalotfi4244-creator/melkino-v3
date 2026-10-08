<?php
declare(strict_types=1);

namespace Melkino\Domain\Properties;

use Melkino\Support\Persian;
use Melkino\Support\Price;
use Melkino\UI\Fields\PropertySpecCatalog;

/**
 * Melkino V2 — ad row => normalized DTOs (spec §150, §151).
 * Card renderer and detail view ONLY receive these DTOs, never raw DB rows.
 */
final class PropertyFormatter
{
    /** @return array{id:int,title:string,transaction:string,type:string,location:string,price:array,image:array,features:array,favorite:bool,compare:bool} */
    public static function card(array $ad, array $opts = []): array
    {
        $price = Price::normalize($ad);
        $img = $ad['primary_image'] ?? null;
        if (is_array($img)) {
            $img = $img['filename'] ?? null;
        }
        return [
            'id' => (int)$ad['id'],
            'title' => self::title($ad),
            'transaction' => (string)($ad['transaction_type'] ?? ''),
            'type' => (string)($ad['property_type'] ?? ''),
            'location' => trim((string)(($ad['location'] ?? '') !== '' ? $ad['location'] : ($ad['address'] ?? ''))),
            'price' => $price,
            'image' => [
                'src' => $img ? ('uploads/' . ltrim((string)$img, '/')) : '',
                'alt' => self::title($ad),
            ],
            'features' => self::cardFeatures($ad),
            'favorite' => (bool)($opts['favorite'] ?? false),
            'compare' => (bool)($opts['compare'] ?? false),
            'is_vip' => !empty($ad['is_vip']),
            'views' => (int)($ad['views'] ?? 0),
            'created_at' => (string)($ad['created_at'] ?? ''),
        ];
    }

    /** @return array{property:array,gallery:array,pricing:array,facts:array,amenities:array,location:array,contact:array,availability:array,metadata:array} */
    public static function detail(array $ad, array $gallery = [], array $amenities = []): array
    {
        return [
            'property' => [
                'id' => (int)$ad['id'],
                'code' => (string)($ad['ad_code'] ?? ''),
                'title' => self::title($ad),
                'transaction' => (string)($ad['transaction_type'] ?? ''),
                'type' => (string)($ad['property_type'] ?? ''),
                'description' => (string)($ad['description'] ?? ''),
                'status' => (string)($ad['status'] ?? ''),
            ],
            'gallery' => array_map(static fn($g) => [
                'src' => 'uploads/' . ltrim((string)(is_array($g) ? ($g['filename'] ?? '') : $g), '/'),
                'alt' => self::title($ad),
            ], $gallery),
            'pricing' => Price::normalize($ad),
            'facts' => PropertySpecCatalog::facts($ad),
            'specs' => PropertySpecCatalog::full($ad),
            'amenities' => array_values($amenities),
            'location' => [
                'text' => trim((string)(($ad['location'] ?? '') !== '' ? $ad['location'] : ($ad['address'] ?? ''))),
                'lat' => isset($ad['latitude']) && $ad['latitude'] !== '' ? (float)$ad['latitude'] : null,
                'lng' => isset($ad['longitude']) && $ad['longitude'] !== '' ? (float)$ad['longitude'] : null,
            ],
            'contact' => [
                'advertiser' => trim((string)($ad['last_name'] ?? '')),
            ],
            'availability' => [
                'is_vacant' => !empty($ad['is_vacant']),
                'vacancy_date' => (string)($ad['vacancy_date'] ?? ''),
                'delivery_date' => (string)($ad['delivery_date'] ?? ''),
                'visit_hours' => (string)($ad['visit_hours'] ?? ''),
            ],
            'metadata' => [
                'views' => (int)($ad['views'] ?? 0),
                'created_at' => (string)($ad['created_at'] ?? ''),
                'published_at' => (string)($ad['published_at'] ?? ''),
                'is_vip' => !empty($ad['is_vip']),
            ],
        ];
    }

    public static function title(array $ad): string
    {
        $t = trim((string)($ad['title'] ?? ''));
        if ($t !== '') {
            return $t;
        }
        $type = trim((string)($ad['property_type'] ?? 'ملک'));
        $area = (float)($ad['area'] ?? $ad['built_area'] ?? 0);
        $tx = trim((string)($ad['transaction_type'] ?? ''));
        $title = ($tx !== '' ? $tx . ' ' : '') . $type;
        if ($area > 0) {
            $title .= ' ' . Persian::toPersianDigits((string)(int)$area) . ' متری';
        }
        return $title;
    }

    /** Max 3–4 features for cards (spec §16). */
    private static function cardFeatures(array $ad): array
    {
        $out = [];
        $area = (float)($ad['area'] ?? $ad['built_area'] ?? 0);
        if ($area > 0) {
            $out[] = ['icon' => 'area', 'label' => Persian::toPersianDigits((string)(int)$area) . ' متر'];
        }
        if (!empty($ad['rooms'])) {
            $out[] = ['icon' => 'bed', 'label' => Persian::toPersianDigits((string)$ad['rooms']) . ' خواب'];
        }
        if (!empty($ad['floor'])) {
            $out[] = ['icon' => 'floor', 'label' => 'طبقه ' . Persian::toPersianDigits((string)$ad['floor'])];
        }
        if (empty($out) && !empty($ad['year'])) {
            $out[] = ['icon' => 'calendar', 'label' => 'ساخت ' . Persian::toPersianDigits((string)$ad['year'])];
        }
        return array_slice($out, 0, 4);
    }
}
