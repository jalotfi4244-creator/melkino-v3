<?php
declare(strict_types=1);

namespace Melkino\UI\Card;

use Melkino\Domain\Media\ImageService;
use Melkino\UI\Icons\IconRegistry;

/**
 * Melkino V2 — THE card renderer (spec §16, §128).
 * One hierarchy, max 4 features, favorite independent, compare secondary, no emoji icons.
 */
final class PropertyCardRenderer
{
    /** @param array<string,mixed> $dto Use PropertyFormatter::card() to build it. */
    public static function render(array $dto, array $opts = []): string
    {
        $c = PropertyCardData::normalize($dto);
        if ($c['id'] <= 0) {
            return '';
        }
        $vis = PropertyCardDefinition::visibility();
        $variant = $opts['variant'] ?? PropertyCardDefinition::variant();
        $url = 'property-details.php?id=' . $c['id'];
        $img = $c['image']['src'] !== '' ? $c['image']['src'] : ImageService::url(null, $c['type']);
        $fav = $c['favorite'];

        $h = '<article class="mx-card mx-card--' . e($variant) . '" data-ad-id="' . $c['id'] . '">';
        if ($vis['image']) {
            $h .= '<a class="mx-card__media" href="' . e($url) . '" aria-label="' . e($c['title']) . '">'
                . '<img class="mx-card__img" src="' . e($img) . '" alt="' . e($c['image']['alt']) . '" loading="lazy" width="600" height="400">'
                . '<span class="mx-card__badges">';
            if ($vis['transaction_badge'] && $c['transaction'] !== '') {
                $h .= '<span class="mx-badge">' . e($c['transaction']) . '</span>';
            }
            if ($vis['vip_badge'] && $c['is_vip']) {
                $h .= '<span class="mx-badge mx-badge--vip">ویژه</span>';
            }
            $h .= '</span></a>';
            if ($vis['favorite']) {
                $h .= '<button type="button" class="mx-card__fav' . ($fav ? ' is-active' : '') . '"'
                    . ' data-fav-toggle="' . $c['id'] . '" aria-pressed="' . ($fav ? 'true' : 'false') . '"'
                    . ' aria-label="ذخیره ملک">' . IconRegistry::svg($fav ? 'heart-fill' : 'heart', 20) . '</button>';
            }
        }
        $h .= '<div class="mx-card__body">';
        if ($vis['title']) {
            $h .= '<h3 class="mx-card__title"><a href="' . e($url) . '">' . e($c['title']) . '</a></h3>';
        }
        if ($vis['location'] && $c['location'] !== '') {
            $h .= '<p class="mx-card__loc">' . IconRegistry::svg('pin', 14) . '<span>' . e($c['location']) . '</span></p>';
        }
        if ($vis['features'] && $c['features']) {
            $h .= '<ul class="mx-card__feats">';
            foreach ($c['features'] as $f) {
                $icon = is_array($f) ? (string)($f['icon'] ?? 'check') : 'check';
                $label = is_array($f) ? (string)($f['label'] ?? '') : (string)$f;
                $h .= '<li>' . IconRegistry::svg($icon, 14) . '<span>' . e($label) . '</span></li>';
            }
            $h .= '</ul>';
        }
        if ($vis['price']) {
            $h .= '<p class="mx-card__price">' . e((string)($c['price']['short'] ?? $c['price']['label'] ?? '')) . '</p>';
        }
        $h .= '<div class="mx-card__actions">';
        if ($vis['cta']) {
            $h .= '<a class="mx-btn mx-btn--primary mx-btn--sm" href="' . e($url) . '">مشاهده ملک</a>';
        }
        if ($vis['compare']) {
            $h .= '<button type="button" class="mx-btn mx-btn--ghost mx-btn--sm" data-compare-toggle="' . $c['id'] . '"'
                . ' aria-pressed="' . ($c['compare'] ? 'true' : 'false') . '">'
                . IconRegistry::svg('compare', 16) . '<span>مقایسه</span></button>';
        }
        $h .= '<button type="button" class="mx-btn mx-btn--ghost mx-btn--sm" data-share-ad="' . $c['id'] . '"'
            . ' data-share-title="' . e($c['title']) . '" aria-label="اشتراک‌گذاری آگهی" title="اشتراک‌گذاری آگهی">'
            . IconRegistry::svg('share', 16) . '<span>اشتراک</span></button>';
        $h .= '</div></div></article>';
        return $h;
    }

    /** @param array<int,array> $dtos */
    public static function grid(array $dtos, array $opts = []): string
    {
        if (!$dtos) {
            return melkinoPartial('empty-state.php', [
                'icon' => 'search',
                'title' => $opts['empty_title'] ?? 'ملکی پیدا نشد',
                'text' => $opts['empty_text'] ?? 'فیلترها را تغییر دهید یا جستجوی دیگری امتحان کنید.',
                'action_url' => $opts['empty_action_url'] ?? 'properties.php',
                'action_label' => $opts['empty_action_label'] ?? 'مشاهده همه آگهی‌ها',
            ]);
        }
        $h = '<div class="mx-grid">';
        foreach ($dtos as $dto) {
            $h .= self::render($dto, $opts);
        }
        return $h . '</div>';
    }
}
