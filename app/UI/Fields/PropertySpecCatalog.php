<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

use Melkino\Support\Persian;

/**
 * Melkino V2 — THE property spec catalog (spec §48, §128).
 * One canonical definition per property type: label + unit + source columns.
 * Replaces the scattered per-page spec lists.
 *
 * @return array<string,array{label:string,unit:string,sources:string[],format:string}>
 */
final class PropertySpecCatalog
{
    /** @return array<string,array> */
    public static function definitions(string $propertyType = ''): array
    {
        $common = [
            'area' => ['label' => 'متراژ', 'unit' => 'متر', 'sources' => ['area', 'built_area'], 'format' => 'int'],
            'rooms' => ['label' => 'خواب', 'unit' => '', 'sources' => ['rooms'], 'format' => 'int'],
            'floor' => ['label' => 'طبقه', 'unit' => '', 'sources' => ['floor'], 'format' => 'text'],
            'year' => ['label' => 'سال ساخت', 'unit' => '', 'sources' => ['year'], 'format' => 'year'],
            'deed' => ['label' => 'سند', 'unit' => '', 'sources' => ['deed_type'], 'format' => 'text'],
        ];
        $extra = match ($propertyType) {
            'آپارتمان' => [],
            'خانه', 'ویلایی', 'ویلا' => [
                'land_area' => ['label' => 'متراژ زمین', 'unit' => 'متر', 'sources' => ['land_area'], 'format' => 'int'],
            ],
            'زمین' => [
                'land_area' => ['label' => 'مساحت زمین', 'unit' => 'متر', 'sources' => ['land_area', 'area'], 'format' => 'int'],
                'water_share' => ['label' => 'سهم آب', 'unit' => '', 'sources' => ['water_share'], 'format' => 'text'],
            ],
            'باغ' => [
                'land_area' => ['label' => 'مساحت باغ', 'unit' => 'متر', 'sources' => ['land_area', 'area'], 'format' => 'int'],
                'water_share' => ['label' => 'سهم آب', 'unit' => '', 'sources' => ['water_share'], 'format' => 'text'],
                'well' => ['label' => 'چاه', 'unit' => '', 'sources' => ['well_name'], 'format' => 'text'],
            ],
            'تجاری', 'اداری', 'مغازه', 'دفتر' => [
                'floor' => ['label' => 'طبقه', 'unit' => '', 'sources' => ['floor'], 'format' => 'text'],
            ],
            default => [],
        };
        return array_merge($common, $extra);
    }

    /** Key facts row for detail pages (max 4). @return array<int,array{label:string,value:string}> */
    public static function facts(array $ad): array
    {
        $defs = self::definitions((string)($ad['property_type'] ?? ''));
        $out = [];
        foreach (['area', 'rooms', 'floor', 'year'] as $key) {
            if (!isset($defs[$key])) {
                continue;
            }
            $v = self::value($ad, $defs[$key]);
            if ($v !== '') {
                $out[] = ['label' => $defs[$key]['label'], 'value' => $v];
            }
        }
        return array_slice($out, 0, 4);
    }

    /** Full spec table for detail pages. @return array<int,array{label:string,value:string}> */
    public static function full(array $ad): array
    {
        $defs = self::definitions((string)($ad['property_type'] ?? ''));
        $out = [];
        foreach ($defs as $def) {
            $v = self::value($ad, $def);
            if ($v !== '') {
                $out[] = ['label' => $def['label'], 'value' => $v];
            }
        }
        // Common extras with labels.
        $extras = [
            'deed_notes' => 'توضیح سند', 'is_renovated' => 'بازسازی', 'is_old' => 'کلنگی',
            'exchange_interested' => 'معاوضه', 'has_loan' => 'وام',
        ];
        foreach ($extras as $col => $label) {
            if (!empty($ad[$col])) {
                $out[] = ['label' => $label, 'value' => is_numeric($ad[$col]) ? 'دارد' : (string)$ad[$col]];
            }
        }
        return $out;
    }

    private static function value(array $ad, array $def): string
    {
        $raw = null;
        foreach ($def['sources'] as $col) {
            if (isset($ad[$col]) && $ad[$col] !== '' && $ad[$col] !== null) {
                $raw = $ad[$col];
                break;
            }
        }
        if ($raw === null || $raw === '') {
            return '';
        }
        $unit = $def['unit'] !== '' ? ' ' . $def['unit'] : '';
        return match ($def['format']) {
            'int' => Persian::toPersianDigits((string)(int)(float)$raw) . $unit,
            'year' => Persian::toPersianDigits((string)$raw),
            default => Persian::toPersianDigits((string)$raw) . $unit,
        };
    }
}
