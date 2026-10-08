<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — کتابخانه تنظیمات (مرحله ۱۶)
 *--------------------------------------------------------------------------
 * ویرایش گزینه‌های کمبوباکس فرم‌ها با همان توابع سایت
 * (melkinoFormComboCatalog/Save در form-options.php — تک‌منبع، جدول settings).
 * تغییر ساختار جدول‌ها: هیچ.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

$__ofSetF = dirname(__DIR__) . '/form-options.php';
if (is_file($__ofSetF)) {
    require_once $__ofSetF;
}
unset($__ofSetF);
if (!function_exists('melkinoFormComboDefaults')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/form-options.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}
$__ofSetD = dirname(__DIR__) . '/db-settings.php';
if (is_file($__ofSetD)) {
    require_once $__ofSetD;
}
unset($__ofSetD);
if (!function_exists('dbSettingGet')) {
    // فالبک هاست قدیمی: اگر فایل سایت نباشد/قدیمی باشد، کپی وندور داخل زیپ.
    $__ofVendor = __DIR__ . '/_vendor/db-settings.php';
    if (is_file($__ofVendor)) {
        require_once $__ofVendor;
    }
    unset($__ofVendor);
}

if (!function_exists('office_settings_boot')) {
    /**
     * آماده‌سازی اتصال برای توابع سایت (global $pdo‎).
     */
    function office_settings_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
        if (function_exists('melkinoEnsureSettingsTable')) {
            try {
                melkinoEnsureSettingsTable($pdo);
            } catch (Throwable $e) {
            }
        }
    }
}

if (!function_exists('office_combos_catalog')) {
    /** @return array<string,array{label:string,items:string[]}> */
    function office_combos_catalog(): array
    {
        if (function_exists('melkinoFormComboCatalog')) {
            try {
                return melkinoFormComboCatalog();
            } catch (Throwable $e) {
                return [];
            }
        }
        return [];
    }
}

if (!function_exists('office_combos_save')) {
    /**
     * @param array<string,mixed> $data نگاشت کلید => لیست گزینه‌ها (هر خط یک گزینه).
     * @return string|null null یعنی موفق، وگرنه متن خطا.
     */
    function office_combos_save(array $data): ?string
    {
        if (!function_exists('melkinoFormComboSave')) {
            return 'فایل form-options.php روی هاست نیست.';
        }
        try {
            $ok = melkinoFormComboSave($data);
        } catch (Throwable $e) {
            return 'خطای ذخیره: ' . $e->getMessage();
        }
        if ($ok) {
            return null;
        }
        $err = function_exists('melkinoFormComboLastError') ? melkinoFormComboLastError() : '';
        return $err !== '' ? $err : 'ذخیره ناموفق بود.';
    }
}

if (!function_exists('office_global_defaults')) {
    /** پیش‌فرض‌های عین getGlobalSettings سایت (config.php). @return array<string,mixed> */
    function office_global_defaults(): array
    {
        return [
            'site_name' => 'ملکینو',
            'city' => 'شاهرود',
            'slogan' => 'ملکینو؛ انتخابی فراتر از یک ملک',
            'show_prices' => true,
            'hide_all_prices' => false,
            'enable_favorites' => true,
            'enable_property_requests' => true,
            'enable_notifications' => true,
            'enable_property_calculator' => true,
            'items_per_page' => 4,
            'default_theme' => 'dark',
            'maintenance_mode' => false,
            'card_layout' => 'photo-top',
        ];
    }
}

if (!function_exists('office_global_bools')) {
    /** @return string[] */
    function office_global_bools(): array
    {
        return ['show_prices', 'hide_all_prices', 'enable_favorites', 'enable_property_requests', 'enable_notifications', 'enable_property_calculator', 'maintenance_mode'];
    }
}

if (!function_exists('office_global_layouts')) {
    /** @return string[] */
    function office_global_layouts(): array
    {
        return ['photo-top', 'photo-full', 'photo-left', 'photo-right', 'photo-float', 'photo-collage', 'photo-portrait', 'photo-editorial'];
    }
}

if (!function_exists('office_global_get')) {
    /** @return array<string,mixed> */
    function office_global_get(PDO $pdo): array
    {
        $out = office_global_defaults();
        if (!function_exists('dbSettingGet')) {
            return $out;
        }
        foreach ($out as $k => $v) {
            try {
                $out[$k] = dbSettingGet($pdo, 'global', $k, $v);
            } catch (Throwable $e) {
            }
        }
        return $out;
    }
}

if (!function_exists('office_global_save')) {
    /**
     * ذخیره با همان اعتبارسنجی save_global_settings.php سایت.
     * @param array<string,mixed> $data
     * @return string|null null یعنی موفق.
     */
    function office_global_save(PDO $pdo, array $data, ?int $adminId = null): ?string
    {
        if (!function_exists('dbSettingSet')) {
            return 'فایل db-settings.php روی هاست نیست.';
        }
        $keys = array_keys(office_global_defaults());
        $wasMaint = false;
        try {
            $wasMaint = (bool)dbSettingGet($pdo, 'global', 'maintenance_mode', false);
        } catch (Throwable $e) {
        }
        $saved = 0;
        foreach ($keys as $k) {
            if (!array_key_exists($k, $data)) {
                continue;
            }
            $v = $data[$k];
            if ($k === 'items_per_page') {
                $v = max(4, min(100, (int)$v));
            } elseif ($k === 'default_theme') {
                $v = $v === 'dark' ? 'dark' : 'light';
            } elseif ($k === 'card_layout') {
                $v = (string)$v;
                if (!in_array($v, office_global_layouts(), true)) {
                    $v = 'photo-top';
                }
            } elseif (in_array($k, office_global_bools(), true)) {
                $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
            } else {
                $v = trim((string)$v);
            }
            $type = is_bool($v) ? 'boolean' : (is_int($v) ? 'integer' : 'string');
            try {
                if (!dbSettingSet($pdo, 'global', $k, $v, $type, $adminId)) {
                    return 'ذخیره تنظیمات عمومی انجام نشد.';
                }
            } catch (Throwable $e) {
                return 'ذخیره تنظیمات عمومی انجام نشد.';
            }
            $saved++;
        }
        if ($saved === 0) {
            return 'هیچ فیلدی برای ذخیره ارسال نشد.';
        }
        if (array_key_exists('maintenance_mode', $data)) {
            try {
                $nowMaint = (bool)dbSettingGet($pdo, 'global', 'maintenance_mode', false);
                if ($nowMaint !== $wasMaint) {
                    dbSettingSet($pdo, 'global', 'maintenance_started_at', $nowMaint ? time() : 0, 'integer', $adminId);
                }
            } catch (Throwable $e) {
            }
        }
        return null;
    }
}

if (!function_exists('office_combos_reset')) {
    /**
     * حذف سفارشی‌سازی‌ها و بازگشت به پیش‌فرض‌های سایت.
     */
    function office_combos_reset(PDO $pdo): bool
    {
        try {
            $pdo->prepare('DELETE FROM settings WHERE (setting_group = ? AND setting_key = ?) OR (setting_group = ? AND setting_key = ?)')
                ->execute(['global', 'form_combos', 'forms', 'combos']);
        } catch (Throwable $e) {
            return false;
        }
        if (function_exists('melkinoFormComboSaved')) {
            try {
                melkinoFormComboSaved(true);
            } catch (Throwable $e) {
            }
        }
        return true;
    }
}
