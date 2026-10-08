<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Database;
use Melkino\Http\Gate;

/** Melkino V2 — contact page (same contact.info setting + same logo helper as legacy). */
final class ContactController
{
    public function render(): string
    {
        Gate::check('contact.php');
        $f = MELKINO_ROOT . '/melkino-logo.php';
        if (is_file($f)) {
            require_once $f;
        }
        $pdo = Database::pdo();
        $contact = null;
        try {
            if ($pdo && function_exists('dbSettingGet')) {
                $contact = dbSettingGet($pdo, 'contact', 'info', null);
            }
        } catch (\Throwable $e) {
            error_log('contact.php: خطا در خواندن اطلاعات تماس — ' . $e->getMessage());
            $contact = null;
        }
        $logoPath = function_exists('melkinoSiteLogoUrl') ? (string)melkinoSiteLogoUrl() : '';

        return melkinoView('layouts/public.php', [
            'title' => 'ارتباط با ما | ملکینو',
            'description' => 'راه‌های ارتباط با کارشناسان ملکینو',
            'active_nav' => '',
            'head_extra' => \Melkino\Support\Assets::css('assets/css/contact-legacy.css'),
            'scripts' => ['contact'],
            'content_view' => 'pages/contact.php',
            'content_data' => ['contact' => $contact, 'logoPath' => $logoPath],
        ]);
    }
}
