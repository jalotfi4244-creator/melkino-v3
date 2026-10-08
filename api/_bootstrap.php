<?php
declare(strict_types=1);

/**
 * Melkino V2 — canonical API bootstrap (spec §77–§79).
 * Unified envelope {success,data,message,errors} + correct HTTP status + CSRF on mutations.
 */

require_once dirname(__DIR__) . '/app/Bootstrap/app.php';

use Melkino\Core\Auth;
use Melkino\Core\Csrf;
use Melkino\Core\Response;

if (Csrf::isMutatingRequest()) {
    Csrf::check();
}

if (!function_exists('melkinoApiIdentity')) {
    function melkinoApiIdentity(): array
    {
        return Auth::identity();
    }
}

if (!function_exists('melkinoApiRequireLogin')) {
    function melkinoApiRequireLogin(): array
    {
        $id = Auth::identity();
        if (!Auth::check()) {
            Response::fail('ابتدا وارد شوید.', 401);
        }
        return $id;
    }
}
