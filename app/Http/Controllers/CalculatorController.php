<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Http\Gate;

/** Melkino V2 — property calculator (same rates/engine/flags as legacy). */
final class CalculatorController
{
    public function render(): string
    {
        Gate::check('property-calculator.php');
        $f = MELKINO_ROOT . '/melkino-calc-rates.php';
        if (is_file($f)) {
            require_once $f;
        }
        $rates = function_exists('melkinoCalcRates') ? (array)melkinoCalcRates() : [];
        $enabled = function_exists('melkinoCalcEnabled') ? (bool)melkinoCalcEnabled() : true;
        $isAdmin = !empty($_SESSION['is_admin']);
        $extras = [];
        if (!empty($rates['EXTRAS']) && is_array($rates['EXTRAS'])) {
            foreach ($rates['EXTRAS'] as $ex) {
                if (is_array($ex) && !empty($ex['enabled']) && trim((string)($ex['label'] ?? '')) !== '') {
                    $extras[] = $ex;
                }
            }
        }
        return melkinoView('layouts/public.php', [
            'title' => 'ماشین‌حساب قیمت‌گذاری ملک | ملکینو',
            'description' => 'محاسبه قیمت ملک، رهن و اجاره',
            'active_nav' => '',
            'head_extra' => \Melkino\Support\Assets::css('assets/css/calculator-legacy.css'),
            'scripts' => ['./property-calculator-engine.js', 'calculator'],
            'content_view' => 'pages/calculator.php',
            'content_data' => ['calcRates' => $rates, 'calcEnabled' => $enabled, 'calcIsAdmin' => $isAdmin, 'calcExtras' => $extras],
        ]);
    }
}
