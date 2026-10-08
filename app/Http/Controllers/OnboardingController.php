<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Csp;
use Melkino\Http\Gate;

/** Melkino V2 — onboarding splash (same config.json + first-page overrides as legacy). */
final class OnboardingController
{
    public function render(): string
    {
        Gate::check('onboarding.php');
        $data = [];
        $logo = '';
        $configPath = MELKINO_ROOT . '/config.json';
        if (is_file($configPath)) {
            $cfg = json_decode((string)@file_get_contents($configPath), true);
            if (is_array($cfg)) {
                $logo = (string)($cfg['onboarding']['logo'] ?? '');
                $data = is_array($cfg['onboarding']['pages'] ?? null) ? $cfg['onboarding']['pages'] : [];
            }
        }
        $firstLogo = '';
        $firstTitle = '';
        $firstText = '';
        $lf = MELKINO_ROOT . '/melkino-logo.php';
        if (is_file($lf)) {
            require_once $lf;
            if (function_exists('melkinoOnboardingFirstLogoUrl')) {
                $firstLogo = (string)melkinoOnboardingFirstLogoUrl();
            }
            if (function_exists('melkinoOnboardingFirstPage')) {
                $fp = (array)melkinoOnboardingFirstPage();
                $firstTitle = (string)($fp['title'] ?? '');
                $firstText = (string)($fp['text'] ?? '');
            }
        }
        if (!$data) {
            $data = [
                ['title' => 'به ملکینو خوش آمدید!', 'text' => 'سامانه جامع جستجو و ثبت ملک. تجربه‌ای جدید در خرید و فروش املاک.', 'icon' => '🏠'],
                ['title' => 'جستجوی هوشمند', 'text' => 'با فیلترهای پیشرفته، املاک مورد نظر خود را به سادگی پیدا کنید.', 'icon' => '🔍'],
                ['title' => 'ثبت آگهی آسان', 'text' => 'با فرم‌های ویزاردی ما، ملک خود را در چند مرحله ساده ثبت کنید.', 'icon' => '📝'],
                ['title' => 'همیشه در کنار شما', 'text' => 'از پشتیبانی ۲۴ ساعته و تیم حرفه‌ای ملکینو لذت ببرید.', 'icon' => '🤝'],
            ];
        }
        Csp::sendHtmlHeaders();
        return melkinoView('pages/onboarding.php', [
            'steps' => $data, 'logo' => $logo,
            'firstLogo' => $firstLogo, 'firstTitle' => $firstTitle, 'firstText' => $firstText,
        ]);
    }
}
