<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Auth;
use Melkino\Http\Gate;

/** Melkino V2 — register entry: choose path + step1 contact wizard (client-side, same storage keys). */
final class RegisterController
{
    public function choose(): string
    {
        Gate::check('register-choose.php');
        return melkinoView('layouts/public.php', [
            'title' => 'ثبت ملک یا درخواست | ملکینو',
            'description' => 'ثبت ملک برای فروش/اجاره یا ثبت درخواست خرید',
            'active_nav' => '',
            'content_view' => 'pages/register-choose.php',
            'content_data' => [],
        ]);
    }

    public function step1(): string
    {
        Gate::check('register-step1.php');
        $me = Auth::identity();
        return melkinoView('layouts/public.php', [
            'title' => 'ثبت ملک — مرحله ۱ | ملکینو',
            'description' => 'اطلاعات تماس و نوع معامله و نوع ملک',
            'active_nav' => '',
            'scripts' => ['register-step1'],
            'content_view' => 'pages/register-step1.php',
            'content_data' => ['identity' => $me],
        ]);
    }

    /**
     * Type wizards (API-shell): every POST (wizard submit + XHR upload_images)
     * is served byte-identically by the legacy include; GET renders the V2 shell.
     */
    private function wizard(string $page, string $legacy, string $title, string $asset): string
    {
        Gate::check($page);

        foreach (['config.php', 'db_helpers.php', 'security-lib.php', 'jalali-lib.php', 'form-options.php', 'auth.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            require MELKINO_VIEWS . '/legacy/' . $legacy;
            exit;
        }

        return melkinoView('layouts/public.php', [
            'title' => $title,
            'description' => 'ثبت آگهی ملک مرحله‌به‌مرحله',
            'active_nav' => 'register',
            'head_extra' => \Melkino\Support\Assets::css('assets/css/' . $asset . '-legacy.css'),
            'scripts' => ['./form-wizard.js', $asset],
            'content_view' => 'pages/' . $asset . '.php',
            'content_data' => [],
        ]);
    }

    public function apartment(): string
    {
        return $this->wizard('register-apartment.php', 'register-apartment-legacy.php', 'ثبت آپارتمان | ملکینو', 'register-apartment');
    }

    public function villa(): string
    {
        return $this->wizard('register-villa.php', 'register-villa-legacy.php', 'ثبت ویلا | ملکینو', 'register-villa');
    }

    public function land(): string
    {
        return $this->wizard('register-land.php', 'register-land-legacy.php', 'ثبت زمین | ملکینو', 'register-land');
    }

    public function garden(): string
    {
        return $this->wizard('register-garden.php', 'register-garden-legacy.php', 'ثبت باغ | ملکینو', 'register-garden');
    }

    public function office(): string
    {
        return $this->wizard('register-office.php', 'register-office-legacy.php', 'ثبت دفتر اداری | ملکینو', 'register-office');
    }

    public function commercial(): string
    {
        return $this->wizard('register-commercial.php', 'register-commercial-legacy.php', 'ثبت ملک تجاری | ملکینو', 'register-commercial');
    }

    public function partnership(): string
    {
        Gate::check('register-partnership.php');

        $dbh = MELKINO_ROOT . '/db_helpers.php';
        if (is_file(MELKINO_ROOT . '/config.php')) {
            require_once MELKINO_ROOT . '/config.php';
        }
        if (is_file($dbh)) {
            require_once $dbh;
        }
        if (is_file(MELKINO_ROOT . '/partnership-lib.php')) {
            require_once MELKINO_ROOT . '/partnership-lib.php';
        }
        if (function_exists('melkinoEnsurePartnershipSchema')) {
            melkinoEnsurePartnershipSchema();
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            require MELKINO_VIEWS . '/legacy/register-partnership-legacy.php';
            exit;
        }

        $prefill = function_exists('melkinoProfilePrefill')
            ? (array) melkinoProfilePrefill()
            : ['logged_in' => false, 'name' => '', 'phone' => ''];

        return melkinoView('layouts/public.php', [
            'title' => 'ثبت مشارکت در ساخت | ملکینو',
            'description' => 'ثبت درخواست مشارکت در ساخت مرحله‌به‌مرحله',
            'active_nav' => 'register',
            'head_extra' => \Melkino\Support\Assets::css('assets/css/register-partnership-legacy.css'),
            'scripts' => ['./form-wizard.js', 'register-partnership'],
            'content_view' => 'pages/register-partnership.php',
            'content_data' => [
                'mkOwnerName' => trim((string) ($prefill['name'] ?? ($_SESSION['user_name'] ?? ''))),
                'mkOwnerPhone' => trim((string) ($prefill['phone'] ?? ($_SESSION['user_phone'] ?? ''))),
                'opt' => function_exists('melkinoPartOptions') ? (array) melkinoPartOptions() : [],
            ],
        ]);
    }
}
