<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Csp;
use Melkino\Http\Gate;

/**
 * Melkino V2 — index landing (standalone first page; same preamble + same
 * miniapp auto-login bootstrap as legacy index.php).
 *
 * Legacy computed UA flags ($melkinoIsBale/$melkinoIsTelegram) but never used
 * them, so the V2 controller does not reproduce that dead code.
 */
final class IndexController
{
    public function render(): string
    {
        Gate::check('index.php');

        foreach (['db_helpers.php', 'melkino-logo.php', 'melkino-auth-gate.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }

        if (function_exists('melkinoAuthGateBoot')) {
            melkinoAuthGateBoot();
        }

        $isLoggedIn = !empty($_SESSION['reg_telegram_id']) || !empty($_SESSION['reg_bale_id']);

        $profile = ['logged_in' => false, 'name' => '', 'phone' => ''];
        if (function_exists('melkinoProfilePrefill')) {
            try {
                $profile = (array) melkinoProfilePrefill();
            } catch (\Throwable $ignored) {
            }
        }

        // Round 69: shared versioned logo — same helper as legacy.
        $logoUrl = function_exists('melkinoSiteLogoUrl') ? (string) melkinoSiteLogoUrl() : '';
        $firstPage = function_exists('melkinoOnboardingFirstPage')
            ? (array) melkinoOnboardingFirstPage()
            : ['title' => 'به ملکینو خوش آمدید', 'text' => 'سامانه جامع جستجو و ثبت ملک.'];
        $firstLogo = function_exists('melkinoOnboardingFirstLogoUrl')
            ? (string) melkinoOnboardingFirstLogoUrl()
            : '';
        if ($firstLogo === '' && $logoUrl !== '') {
            $firstLogo = $logoUrl;
        }

        Csp::sendHtmlHeaders();

        return melkinoView('pages/index.php', [
            'logged_in'   => $isLoggedIn,
            'profile'     => $profile,
            'logo_url'    => $logoUrl !== '' ? $logoUrl : null,
            'first_logo'  => $firstLogo,
            'first_title' => (string) ($firstPage['title'] ?? 'به ملکینو خوش آمدید'),
            'first_text'  => (string) ($firstPage['text'] ?? ''),
        ]);
    }
}
