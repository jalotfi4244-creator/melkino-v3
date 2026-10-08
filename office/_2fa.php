<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — تأیید دومرحله‌ای TOTP (مرحله ۳۶)
 *--------------------------------------------------------------------------
 * عین منطق Admin2faController/AdminTotp سایت، با همان جدول مشترک
 * admin_totp و همان کلیدهای سشن (admin_2fa_ok/admin_2fa_pending/
 * admin_2fa_throttle)؛ یعنی تأیید کد در دفتر، شل V2 سایت را هم در
 * همان سشن راضی می‌کند و برعکس. ریاضی TOTP عین RFC 6238 در همین
 * فایل است چون Totp::code سایت باگ دارد (hash_hmac با آرگومان
 * جابه‌جا — تست TotpTest سایت هم به همین دلیل قرمز است) و ملکینو
 * نباید دست بخورد؛ وقتی سایت درست شود، سکرت‌ها/ردیف‌های مشترک
 * بدون هیچ تغییری با همان الگوریتم سازگار می‌مانند.
 */

declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!function_exists('office_2fa_available')) {
    function office_2fa_available(): bool
    {
        return true;
    }
}

if (!function_exists('office_2fa_b32encode')) {
    function office_2fa_b32encode(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        $b32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $len = strlen($raw);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($raw[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= $b32[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }
}

if (!function_exists('office_2fa_b32decode')) {
    /** @throws InvalidArgumentException */
    function office_2fa_b32decode(string $b32): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $s = strtoupper((string)preg_replace('/[\\s\\-]+/', '', $b32));
        $s = rtrim($s, '=');
        if ($s === '' || preg_match('/[^A-Z2-7]/', $s) === 1) {
            throw new InvalidArgumentException('Invalid base32 secret.');
        }
        $bits = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($map, $s[$i]);
            if ($pos === false) {
                throw new InvalidArgumentException('Invalid base32 secret.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int)bindec($byte));
            }
        }
        return $out;
    }
}

if (!function_exists('office_2fa_new_secret')) {
    function office_2fa_new_secret(int $bytes = 20): string
    {
        return office_2fa_b32encode(random_bytes(max(10, $bytes)));
    }
}

if (!function_exists('office_2fa_uri')) {
    function office_2fa_uri(string $secret, string $account, string $issuer = 'MelkinoShahr'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }
}

if (!function_exists('office_2fa_code')) {
    /** @throws InvalidArgumentException */
    function office_2fa_code(string $secret, ?int $time = null, int $digits = 6): string
    {
        if ($digits !== 6 && $digits !== 8) {
            throw new InvalidArgumentException('Digits must be 6 or 8.');
        }
        $key = office_2fa_b32decode($secret);
        $counter = (int)floor(($time ?? time()) / 30);
        $msg = pack('N2', intdiv($counter, 4294967296), $counter % 4294967296);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);
        $mod = $digits === 8 ? 100000000 : 1000000;
        return str_pad((string)($code % $mod), $digits, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('office_2fa_boot')) {
    /** Self-heal مشترک با سایت: همان CREATE مهاجرت 002؛ هیچ تغییری در جدول موجود نمی‌دهد. */
    function office_2fa_boot(PDO $pdo): void
    {
        $GLOBALS['pdo'] = $pdo;
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS admin_totp (
                admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
                secret VARCHAR(64) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                verified_at TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('office_2fa_password_ok')) {
    /** ورود رمزی انجام شده (با یا بدون دوعاملی)؟ */
    function office_2fa_password_ok(): bool
    {
        return !empty($_SESSION['is_admin']);
    }
}

if (!function_exists('office_2fa_admin_id')) {
    function office_2fa_admin_id(): int
    {
        return (int)($_SESSION['admin_id'] ?? 0);
    }
}

if (!function_exists('office_2fa_enrolled')) {
    function office_2fa_enrolled(?PDO $pdo, int $adminId): bool
    {
        if ($adminId <= 0 || !($pdo instanceof PDO)) {
            return false;
        }
        try {
            $st = $pdo->prepare('SELECT verified_at FROM admin_totp WHERE admin_id = ? LIMIT 1');
            $st->execute([$adminId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) && $row['verified_at'] !== null;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('office_2fa_secret_for')) {
    /** فقط ردیف تأییدشده؛ هیچ‌وقت سکرت تأییدنشده لو نمی‌رود. */
    function office_2fa_secret_for(?PDO $pdo, int $adminId): ?string
    {
        if ($adminId <= 0 || !($pdo instanceof PDO)) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT secret FROM admin_totp WHERE admin_id = ? AND verified_at IS NOT NULL LIMIT 1');
            $st->execute([$adminId]);
            $s = $st->fetchColumn();
            return is_string($s) && $s !== '' ? $s : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('office_2fa_activate')) {
    /** فقط بعد از تأیید یک کد صدا زده شود. */
    function office_2fa_activate(PDO $pdo, int $adminId, string $secret): void
    {
        $st = $pdo->prepare('INSERT INTO admin_totp (admin_id, secret, verified_at) VALUES (?, ?, NOW())'
            . ' ON DUPLICATE KEY UPDATE secret = VALUES(secret), verified_at = NOW()');
        $st->execute([$adminId, $secret]);
    }
}

if (!function_exists('office_2fa_revoke')) {
    function office_2fa_revoke(PDO $pdo, int $adminId): void
    {
        $st = $pdo->prepare('DELETE FROM admin_totp WHERE admin_id = ?');
        $st->execute([$adminId]);
    }
}

if (!function_exists('office_2fa_guard_satisfied')) {
    /**
     * عین AdminTotp::guardSatisfied: مقید به admin_id و admin_login_at تا
     * هر ورود تازه، تأیید تازه بخواهد. ساختار سشن عین سایت است.
     */
    function office_2fa_guard_satisfied(): bool
    {
        $ok = $_SESSION['admin_2fa_ok'] ?? null;
        if (!is_array($ok) || (int)($ok['v'] ?? 0) !== 1) {
            return false;
        }
        return (int)($ok['admin_id'] ?? 0) === office_2fa_admin_id()
            && (int)($ok['login_at'] ?? 0) === (int)($_SESSION['admin_login_at'] ?? 0)
            && office_2fa_admin_id() > 0;
    }
}

if (!function_exists('office_2fa_mark_satisfied')) {
    function office_2fa_mark_satisfied(): void
    {
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
        $_SESSION['admin_2fa_ok'] = [
            'v' => 1,
            'admin_id' => office_2fa_admin_id(),
            'login_at' => (int)($_SESSION['admin_login_at'] ?? 0),
            'at' => time(),
        ];
    }
}

if (!function_exists('office_2fa_needs_check')) {
    /**
     * گیت یکدست دفتر: ادمینِ دارای ثبتِ تأییدنشدهٔ این سشن باید کد بزند.
     * بدون PDO (دیتابیس قطع) هرگز مسدود نمی‌کند تا رفتار موجود صفحات
     * (خطای 503 خودشان) عوض نشود؛ fail-open مثل سایت.
     */
    function office_2fa_needs_check(?PDO $pdo = null): bool
    {
        if (!office_2fa_password_ok() || office_2fa_guard_satisfied()) {
            return false;
        }
        $pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!($pdo instanceof PDO)) {
            return false;
        }
        return office_2fa_enrolled($pdo, office_2fa_admin_id());
    }
}

if (!function_exists('office_2fa_pending')) {
    function office_2fa_pending(): string
    {
        $s = (string)($_SESSION['admin_2fa_pending'] ?? '');
        if ($s === '') {
            $s = office_2fa_new_secret();
            $_SESSION['admin_2fa_pending'] = $s;
        }
        return $s;
    }
}

if (!function_exists('office_2fa_clear_pending')) {
    function office_2fa_clear_pending(): void
    {
        unset($_SESSION['admin_2fa_pending']);
    }
}

if (!function_exists('office_2fa_verify')) {
    /**
     * تأیید کد کاربر (ارقام فارسی/عربی + فاصله پذیرفته می‌شود).
     * $window = تلرانس ± گام برای اختلاف ساعت (پیش‌فرض ۱).
     */
    function office_2fa_verify(string $secret, string $code, ?int $time = null, int $digits = 6, int $window = 1): bool
    {
        if ($secret === '') {
            return false;
        }
        $norm = strtr(trim($code), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $norm = (string)preg_replace('/\\s+/', '', $norm);
        if (!preg_match('/^\\d{' . $digits . '}$/', $norm)) {
            return false;
        }
        try {
            $now = $time ?? time();
            for ($i = -$window; $i <= $window; $i++) {
                if (hash_equals(office_2fa_code($secret, $now + $i * 30, $digits), $norm)) {
                    return true;
                }
            }
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return false;
    }
}

if (!function_exists('office_2fa_throttle_state')) {
    /** @return array{n:int,until:int} */
    function office_2fa_throttle_state(): array
    {
        $s = $_SESSION['admin_2fa_throttle'] ?? ['n' => 0, 'until' => 0];
        return is_array($s) ? ['n' => (int)($s['n'] ?? 0), 'until' => (int)($s['until'] ?? 0)] : ['n' => 0, 'until' => 0];
    }
}

if (!function_exists('office_2fa_throttled')) {
    function office_2fa_throttled(): bool
    {
        $s = office_2fa_throttle_state();
        if ($s['until'] > time()) {
            return true;
        }
        if ($s['n'] >= 10) {
            $_SESSION['admin_2fa_throttle'] = ['n' => $s['n'], 'until' => time() + 600];
            return true;
        }
        return false;
    }
}

if (!function_exists('office_2fa_hit_throttle')) {
    function office_2fa_hit_throttle(): void
    {
        $s = office_2fa_throttle_state();
        $_SESSION['admin_2fa_throttle'] = ['n' => $s['n'] + 1, 'until' => $s['until']];
    }
}

if (!function_exists('office_2fa_reset_throttle')) {
    function office_2fa_reset_throttle(): void
    {
        $_SESSION['admin_2fa_throttle'] = ['n' => 0, 'until' => 0];
    }
}

if (!function_exists('office_2fa_audit')) {
    function office_2fa_audit(string $action, int $adminId): void
    {
        if (function_exists('melkinoAudit')) {
            try {
                melkinoAudit($action, 'admin', $adminId, []);
            } catch (Throwable $e) {
            }
        }
    }
}

if (!function_exists('office_2fa_safe_redirect')) {
    /** فقط مقصدهای همان‌پوشه‌ای دفتر (ضد open-redirect). */
    function office_2fa_safe_redirect(string $to): string
    {
        if (preg_match('/^[a-z0-9_-]+\\.php(\\?[a-z0-9_=&%\\-]+)?$/i', $to) === 1) {
            return $to;
        }
        return 'index.php';
    }
}
