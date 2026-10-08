<?php
declare(strict_types=1);

namespace Melkino\UI;

use Melkino\Studio\StudioService;
use Melkino\Support\Assets;

/**
 * Melkino V2 — layout shell helper (spec §27, §28).
 * Renders document head/foot with the design system; keeps mini-app SDK logic in partials.
 */
final class Layout
{
    /** @param array<string,mixed> $meta */
    public static function head(array $meta = []): string
    {
        $title = (string)($meta['title'] ?? 'ملکینو');
        $desc = (string)($meta['description'] ?? 'ملکینو؛ انتخابی فراتر از یک ملک');
        $theme = StudioService::current();
        $h = '<!DOCTYPE html><html lang="fa" dir="rtl" id="melkinoRoot">'
            . '<head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
            . '<title>' . e($title) . '</title>'
            . '<meta name="description" content="' . e($desc) . '">';
        if (!empty($meta['canonical'])) {
            $h .= '<link rel="canonical" href="' . e((string)$meta['canonical']) . '">';
        }
        // OpenGraph / Twitter (spec §73).
        $h .= '<meta property="og:type" content="' . e((string)($meta['og_type'] ?? 'website')) . '">'
            . '<meta property="og:title" content="' . e($title) . '">'
            . '<meta property="og:description" content="' . e($desc) . '">';
        if (!empty($meta['og_image'])) {
            $h .= '<meta property="og:image" content="' . e((string)$meta['og_image']) . '">';
        }
        $h .= '<meta name="theme-color" content="#0b5d59">';
        // Design system (order matters: tokens first).
        foreach (['tokens', 'reset', 'typography', 'layout', 'components', 'cards', 'forms', 'modals', 'responsive', 'utilities'] as $css) {
            $h .= Assets::css('assets/css/' . $css . '.css');
        }
        $h .= StudioService::cssVars();
        $h .= '<body data-mx-style="' . e($theme['style']) . '" data-mx-color="' . e($theme['color']) . '">';
        return $h;
    }

    /** @param string[] $scripts */
    public static function foot(array $scripts = []): string
    {
        $h = '';
        foreach (['core', 'ui'] as $js) {
            $h .= Assets::js('assets/js/' . $js . '.js');
        }
        foreach ($scripts as $js) {
            $h .= Assets::js('assets/js/' . $js . '.js');
        }
        return $h . '</body></html>';
    }
}
