<?php
declare(strict_types=1);

namespace Melkino\Services;

use Melkino\Core\Database;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyRepository;

/**
 * Melkino V2 — canonical search (spec §13, §77).
 * Canonical params: tx, property_type, district, min_price, max_price, min_area, max_area, rooms, q, sort.
 * Legacy aliases (type, location, ...) mapped in the compatibility layer.
 */
final class SearchService
{
    public const LEGACY_ALIASES = [
        'type' => 'tx',
        'transaction' => 'tx',
        'location' => 'district',
        'city' => 'district',
        'area_min' => 'min_area',
        'area_max' => 'max_area',
        'price_min' => 'min_price',
        'price_max' => 'max_price',
        'bedrooms' => 'rooms',
        'keyword' => 'q',
        'search' => 'q',
    ];

    /** @param array<string,mixed> $input @return array<string,mixed> canonical filters */
    public static function canonicalize(array $input): array
    {
        foreach (self::LEGACY_ALIASES as $old => $new) {
            if (!isset($input[$new]) && isset($input[$old]) && $input[$old] !== '') {
                $input[$new] = $input[$old];
            }
        }
        $out = [];
        foreach (['tx', 'property_type', 'district', 'q', 'sort'] as $k) {
            if (isset($input[$k]) && !is_array($input[$k]) && trim((string)$input[$k]) !== '') {
                $out[$k] = mb_substr(trim((string)$input[$k]), 0, 120);
            }
        }
        foreach (['min_price', 'max_price', 'min_area', 'max_area', 'rooms'] as $k) {
            if (isset($input[$k]) && is_numeric($input[$k]) && (float)$input[$k] > 0) {
                $out[$k] = $k === 'rooms' ? (int)$input[$k] : (float)$input[$k];
            }
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $input raw GET
     * @return array{items:array,total:int,page:int,per_page:int,filters:array}
     */
    public static function search(array $input, int $page = 1, int $perPage = 20, array $identity = []): array
    {
        $filters = self::canonicalize($input);
        $res = PropertyRepository::paginate($filters, $page, $perPage);
        $favStates = [];
        $cmpStates = [];
        if ($identity) {
            $ids = array_column($res['items'], 'id');
            $favStates = \Melkino\Domain\Favorites\FavoriteRepository::states($ids, $identity);
            $cmpStates = self::compareStates($ids);
        }
        $res['items'] = array_map(
            static fn($ad) => PropertyFormatter::card($ad, [
                'favorite' => !empty($favStates[(int)$ad['id']]),
                'compare' => !empty($cmpStates[(int)$ad['id']]),
            ]),
            $res['items']
        );
        $res['filters'] = $filters;
        return $res;
    }

    /** @return array<int,bool> */
    private static function compareStates(array $adIds): array
    {
        try {
            $mine = \Melkino\Domain\Compare\CompareService::allIds();
            $set = array_flip($mine);
            $out = [];
            foreach ($adIds as $id) {
                if (isset($set[(int)$id])) {
                    $out[(int)$id] = true;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Shareable canonical query string for current filters. */
    public static function shareQuery(array $filters): string
    {
        return http_build_query(array_filter(self::canonicalize($filters), static fn($v) => $v !== '' && $v !== null));
    }
}
