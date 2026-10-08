<?php
declare(strict_types=1);

namespace Melkino\Domain\Requests;

/**
 * Melkino V2 — canonical amenity names (exact copy of the 48 legacy form values;
 * the matcher compares by NAME against the `amenities` table, so names must stay identical).
 * Used as fallback when the amenities table is missing/empty.
 */
final class AmenityCatalog
{
    /** @return string[] */
    public static function names(): array
    {
        return [
            'آسانسور', 'پارکینگ', 'انباری', 'استخر', 'سونا', 'جکوزی', 'حیاط اختصاصی',
            'روف گاردن', 'نگهبانی', 'زیرزمین', 'گلخانه', 'آب', 'برق', 'گاز', 'تلفن',
            'فاضلاب', 'آب شهری', 'چاه', 'دیوارکشی', 'درب ورودی', 'دسترسی به خیابان اصلی',
            'سرویس بهداشتی', 'آلاچیق', 'باربیکیو', 'لابی', 'دوربین مداربسته',
            'سیستم اعلام حریق', 'اطفای حریق', 'سیستم سرمایش', 'سیستم گرمایش', 'اینترنت',
            'آبدارخانه', 'اتاق جلسات', 'شیشه سکوریت', 'درب اتوماتیک', 'درب فلزی',
            'کرکره برقی', 'کرکره معمولی', 'بالابر', 'ویترین', 'نورپردازی', 'اسپیلت',
            'کولر آبی', 'پکیج', 'بخاری', 'مطبخ', 'بالکن', 'حیاط',
        ];
    }

    /** @return array<int,array{id:int,name:string}> */
    public static function rows(): array
    {
        $out = [];
        foreach (self::names() as $i => $n) {
            $out[] = ['id' => $i + 1, 'name' => $n];
        }
        return $out;
    }
}
