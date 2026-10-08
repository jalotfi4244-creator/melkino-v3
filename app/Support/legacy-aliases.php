<?php
declare(strict_types=1);

/**
 * Melkino V2 — legacy function aliases (spec §132).
 * Old global function names keep working; new implementation lives in Melkino\* classes.
 * Every alias is guarded so load order never fatals.
 */

use Melkino\Config\Environment;
use Melkino\Core\Csrf;
use Melkino\Core\Database;
use Melkino\Support\Persian;

if (!function_exists('asset')) {
    // FIX: views/layouts/admin.php (و احتمالاً دیگران) تابع سراسری asset() را
    // صدا می‌زنند ولی فقط Melkino\\Support\\asset() تعریف شده بود (۵۰۰ کل شل V2).
    function asset(string $path): string
    {
        return \Melkino\Support\Assets::url($path);
    }
}

if (!function_exists('melkinoEnv')) {
    function melkinoEnv(string $key, string $default = ''): string
    {
        return Environment::get($key, $default);
    }
}

if (!function_exists('melkinoLoadDotEnv')) {
    function melkinoLoadDotEnv(string $path): void
    {
        Environment::loadDotEnv($path);
    }
}

if (!function_exists('melkinoDebugAllowed')) {
    function melkinoDebugAllowed(): bool
    {
        return Environment::debugAllowed();
    }
}

if (!function_exists('melkinoInitDbGlobal')) {
    function melkinoInitDbGlobal(): ?PDO
    {
        return Database::pdo();
    }
}

if (!function_exists('melkinoRequireDb')) {
    function melkinoRequireDb(): void
    {
        Database::requirePdo();
    }
}

// --- CSRF (canonical: Melkino\Core\Csrf; csrf-shim.php = compat layer) ---
if (!function_exists('melkinoCsrfToken')) {
    function melkinoCsrfToken(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('melkinoCsrfField')) {
    function melkinoCsrfField(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('melkinoReadRequestBody')) {
    function melkinoReadRequestBody(): array
    {
        return Csrf::readBody();
    }
}

if (!function_exists('melkinoRequestIsSameOrigin')) {
    function melkinoRequestIsSameOrigin(): bool
    {
        return Csrf::isSameOrigin();
    }
}

if (!function_exists('melkinoCsrfCheck')) {
    function melkinoCsrfCheck(): void
    {
        Csrf::check();
    }
}

if (!function_exists('melkinoIsMutatingRequest')) {
    function melkinoIsMutatingRequest(): bool
    {
        return Csrf::isMutatingRequest();
    }
}

// --- Digits / phone (canonical: Melkino\Support\Persian) ---
if (!function_exists('toEnglishDigits')) {
    function toEnglishDigits(mixed $v): string
    {
        return Persian::toEnglishDigits((string)$v);
    }
}

if (!function_exists('toPersianDigits')) {
    function toPersianDigits(mixed $v): string
    {
        return Persian::toPersianDigits((string)$v);
    }
}

if (!function_exists('melkinoNormalizePhone')) {
    function melkinoNormalizePhone(mixed $v): string
    {
        return Persian::normalizePhone((string)$v);
    }
}

// NOTE: melkinoJsonResponse() is intentionally NOT aliased here.
// Legacy signature is melkinoJsonResponse(array $payload, int $status): never (db_helpers.php,
// unguarded). New code uses Melkino\Core\Response::json() instead. Do not add a same-named alias.

// NOTE: melkinoCurrentIdentity() is intentionally NOT aliased here.
// Legacy signature/semantics (['user_id','telegram_id','phone','user']) live in db_helpers.php
// (unguarded). New code uses Melkino\Core\Auth::identity(). Do not add a same-named alias.

// --- Auth state (canonical: Melkino\Core\Auth) ---

// --- Consultant settings (moved out of config.php; same storage keys) ---
if (!function_exists('getConsultantSettings')) {
    function getConsultantSettings(): array
    {
        global $pdo;
        $defaults = ['name' => 'مشاور ملکینو', 'phone' => '', 'telegram_username' => '', 'telegram_link' => ''];
        if (!($pdo instanceof PDO) || !function_exists('dbSettingGet')) {
            return $defaults;
        }
        foreach ($defaults as $k => $v) {
            $x = dbSettingGet($pdo, 'consultant_default', $k, $v);
            $defaults[$k] = is_string($x) ? $x : $v;
        }
        return array_merge($defaults, ['updated_at' => date('Y-m-d H:i:s')]);
    }
}

if (!function_exists('saveConsultantSettings')) {
    function saveConsultantSettings(array $settings): bool
    {
        global $pdo;
        if (!($pdo instanceof PDO) || !function_exists('dbSettingSet')) {
            return false;
        }
        $vals = [
            'name' => trim((string)($settings['name'] ?? '')) ?: 'مشاور ملکینو',
            'phone' => trim((string)($settings['phone'] ?? '')),
            'telegram_username' => ltrim(trim((string)($settings['telegram_username'] ?? '')), '@'),
            'telegram_link' => trim((string)($settings['telegram_link'] ?? '')),
        ];
        if ($vals['telegram_link'] !== '' && !preg_match('#^https?://t\.me/#i', $vals['telegram_link'])) {
            return false;
        }
        foreach ($vals as $k => $v) {
            if (!dbSettingSet($pdo, 'consultant_default', $k, $v, 'string')) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('getConsultantPhone')) {
    function getConsultantPhone(): string
    {
        return trim((string)(getConsultantSettings()['phone'] ?? ''));
    }
}

if (!function_exists('getConsultantName')) {
    function getConsultantName(): string
    {
        return trim((string)(getConsultantSettings()['name'] ?? 'مشاور ملکینو')) ?: 'مشاور ملکینو';
    }
}

if (!function_exists('getConsultantTelegramLink')) {
    function getConsultantTelegramLink(): string
    {
        $settings = getConsultantSettings();
        $link = trim((string)($settings['telegram_link'] ?? ''));
        if ($link !== '' && preg_match('#^https?://#i', $link)) {
            return $link;
        }
        $username = ltrim(trim((string)($settings['telegram_username'] ?? '')), '@');
        return $username !== '' ? 'https://t.me/' . $username : '';
    }
}

if (!function_exists('getGlobalSettings')) {
    function getGlobalSettings(): array
    {
        global $pdo;
        $defaults = ['site_name' => 'ملکینو', 'city' => 'شاهرود', 'slogan' => 'ملکینو؛ انتخابی فراتر از یک ملک', 'show_prices' => true, 'hide_all_prices' => false, 'enable_favorites' => true, 'enable_property_requests' => true, 'enable_notifications' => true, 'enable_property_calculator' => true, 'items_per_page' => 4, 'default_theme' => 'dark', 'maintenance_mode' => false, 'card_layout' => 'photo-top'];
        if (!($pdo instanceof PDO) || !function_exists('dbSettingGet')) {
            return $defaults;
        }
        foreach ($defaults as $k => $v) {
            $defaults[$k] = dbSettingGet($pdo, 'global', $k, $v);
        }
        return $defaults;
    }
}

if (!function_exists('shouldHidePublicPrice')) {
    function shouldHidePublicPrice(array $ad): bool
    {
        $settings = getGlobalSettings();
        return !empty($settings['hide_all_prices']) || empty($settings['show_prices']) || !empty($ad['price_hidden']);
    }
}

if (!function_exists('getFirstImage')) {
    function getFirstImage($selectedImages = null, $pdoArg = null, $adId = null)
    {
        if (is_array($selectedImages) && count($selectedImages) > 0) {
            return $selectedImages[0];
        }
        if (is_string($selectedImages) && $selectedImages !== '') {
            $decoded = json_decode($selectedImages, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded[0];
            }
        }
        if (defined('MOCK_MODE') && MOCK_MODE) {
            return 'default-house.jpg';
        }
        if ($pdoArg instanceof PDO && $adId) {
            try {
                $stmt = $pdoArg->prepare('SELECT filename FROM images WHERE ad_id = ? ORDER BY id ASC LIMIT 1');
                $stmt->execute([$adId]);
                $img = $stmt->fetch(PDO::FETCH_ASSOC);
                return $img ? $img['filename'] : null;
            } catch (Throwable $e) {
                return null;
            }
        }
        return null;
    }
}

// --- Login tokens (canonical: Melkino\Auth\AuthService; behavior preserved) ---
if (!function_exists('melkinoEnsureLoginTokenTable')) {
    function melkinoEnsureLoginTokenTable(): bool
    {
        return \Melkino\Auth\AuthService::ensureLoginTokenTable();
    }
}

if (!function_exists('melkinoMintLoginToken')) {
    function melkinoMintLoginToken($userId, ?string $telegramId = null, ?string $baleId = null, int $ttlMinutes = 10): string
    {
        return \Melkino\Auth\AuthService::mintLoginToken($userId, $telegramId, $baleId, $ttlMinutes);
    }
}

if (!function_exists('melkinoConsumeLoginToken')) {
    function melkinoConsumeLoginToken(string $token): array
    {
        return \Melkino\Auth\AuthService::consumeLoginToken($token);
    }
}
