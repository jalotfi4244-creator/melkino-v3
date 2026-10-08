<?php
declare(strict_types=1);

namespace Melkino\UI;

use Melkino\Core\Session;
use Melkino\UI\Icons\IconRegistry;

/** Melkino V2 — unified toasts (spec §91): success/error/warning/info. No browser alert(). */
final class Toasts
{
    public static function push(string $type, string $message): void
    {
        if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $type = 'info';
        }
        Session::start();
        $_SESSION['_toasts'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public static function pull(): array
    {
        Session::start();
        $t = $_SESSION['_toasts'] ?? [];
        unset($_SESSION['_toasts']);
        $flash = Session::flash('error');
        if (is_string($flash) && $flash !== '') {
            $t[] = ['type' => 'error', 'message' => $flash];
        }
        $flashOk = Session::flash('success');
        if (is_string($flashOk) && $flashOk !== '') {
            $t[] = ['type' => 'success', 'message' => $flashOk];
        }
        return is_array($t) ? $t : [];
    }

    public static function render(): string
    {
        $toasts = self::pull();
        if (!$toasts) {
            return '<div class="mx-toasts" id="mxToasts" aria-live="polite"></div>';
        }
        $h = '<div class="mx-toasts" id="mxToasts" aria-live="polite">';
        foreach ($toasts as $t) {
            $icon = match ($t['type']) {
                'success' => 'check', 'error' => 'x', 'warning' => 'warn', default => 'info',
            };
            $h .= '<div class="mx-toast mx-toast--' . e($t['type']) . '" role="status">'
                . IconRegistry::svg($icon, 18) . '<span>' . e($t['message']) . '</span>'
                . '<button type="button" class="mx-toast__close" data-toast-close aria-label="بستن">'
                . IconRegistry::svg('x', 14) . '</button></div>';
        }
        return $h . '</div>';
    }
}
