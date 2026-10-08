<?php
declare(strict_types=1);

namespace Melkino\UI\Fields;

/**
 * Melkino V2 — field visibility (admin-configured, validated, with safe defaults).
 * Invalid JSON never crashes the app (spec §93).
 */
final class FieldVisibility
{
    /** @return array<string,bool> */
    public static function home(): array
    {
        $defs = array_keys(FieldRegistry::home());
        $vis = array_fill_keys($defs, true);
        try {
            if (function_exists('settings')) {
                $stored = settings()->getArray('field_display', 'home_visibility', []);
                foreach ($stored as $k => $v) {
                    if (array_key_exists($k, $vis)) {
                        $vis[$k] = (bool)$v;
                    }
                }
            }
        } catch (\Throwable $ignored) {
        }
        return $vis;
    }

    /** @return array<string,array<string,bool>> section => field => visible */
    public static function detail(): array
    {
        $out = [];
        foreach (FieldRegistry::detailSections() as $sk => $sec) {
            $out[$sk] = array_fill_keys(array_keys($sec['fields']), true);
        }
        try {
            if (function_exists('settings')) {
                $stored = settings()->getArray('field_display', 'detail_visibility', []);
                foreach ($stored as $sk => $fields) {
                    if (!isset($out[$sk]) || !is_array($fields)) {
                        continue;
                    }
                    foreach ($fields as $fk => $v) {
                        if (array_key_exists($fk, $out[$sk])) {
                            $out[$sk][$fk] = (bool)$v;
                        }
                    }
                }
            }
        } catch (\Throwable $ignored) {
        }
        return $out;
    }
}
