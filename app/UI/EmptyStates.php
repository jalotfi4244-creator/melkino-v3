<?php
declare(strict_types=1);

namespace Melkino\UI;

use Melkino\UI\Icons\IconRegistry;

/** Melkino V2 — empty-state component contract (spec §60): icon + title + text + action. */
final class EmptyStates
{
    public static function render(string $icon, string $title, string $text = '', string $actionUrl = '', string $actionLabel = ''): string
    {
        $h = '<div class="mx-empty">'
            . '<div class="mx-empty__icon">' . IconRegistry::svg($icon, 40) . '</div>'
            . '<h3 class="mx-empty__title">' . e($title) . '</h3>';
        if ($text !== '') {
            $h .= '<p class="mx-empty__text">' . e($text) . '</p>';
        }
        if ($actionUrl !== '' && $actionLabel !== '') {
            $h .= '<a class="mx-btn mx-btn--primary" href="' . e($actionUrl) . '">' . e($actionLabel) . '</a>';
        }
        return $h . '</div>';
    }
}
