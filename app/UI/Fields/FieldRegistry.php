<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

/**
 * Melkino V2 — canonical field definitions (spec §47, §128).
 * Home-card + detail field definitions in ONE place (labels separate from data mapping).
 * Legacy field-display.php stays as compat; new views use this registry.
 */
final class FieldRegistry
{
    /** @return array<string,array{label:string,sources:string[],format:string}> */
    public static function home(): array
    {
        return [
            'price' => ['label' => 'قیمت', 'sources' => ['price_sell', 'total_price', 'deposit'], 'format' => 'price'],
            'area' => ['label' => 'متراژ', 'sources' => ['area', 'built_area'], 'format' => 'area'],
            'rooms' => ['label' => 'خواب', 'sources' => ['rooms'], 'format' => 'int'],
            'floor' => ['label' => 'طبقه', 'sources' => ['floor'], 'format' => 'text'],
            'location' => ['label' => 'موقعیت', 'sources' => ['location', 'address'], 'format' => 'text'],
            'year' => ['label' => 'سال ساخت', 'sources' => ['year'], 'format' => 'text'],
        ];
    }

    /** @return array<string,array{label:string,fields:array<string,array{label:string,sources:string[],format:string}>}> */
    public static function detailSections(): array
    {
        $specs = PropertySpecCatalog::definitions('');
        $map = [];
        foreach ($specs as $key => $def) {
            $map[$key] = ['label' => $def['label'], 'sources' => $def['sources'], 'format' => $def['format']];
        }
        return [
            'main' => ['label' => 'اطلاعات اصلی', 'fields' => [
                'transaction' => ['label' => 'نوع معامله', 'sources' => ['transaction_type'], 'format' => 'text'],
                'type' => ['label' => 'نوع ملک', 'sources' => ['property_type'], 'format' => 'text'],
                'location' => ['label' => 'موقعیت', 'sources' => ['location', 'address'], 'format' => 'text'],
            ]],
            'specs' => ['label' => 'مشخصات', 'fields' => $map],
            'price' => ['label' => 'قیمت', 'fields' => [
                'sale' => ['label' => 'قیمت فروش', 'sources' => ['price_sell', 'total_price'], 'format' => 'price'],
                'deposit' => ['label' => 'ودیعه', 'sources' => ['deposit'], 'format' => 'price'],
                'rent' => ['label' => 'اجاره ماهانه', 'sources' => ['rent_monthly', 'rent'], 'format' => 'price'],
            ]],
        ];
    }
}
