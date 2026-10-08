<?php
declare(strict_types=1);

namespace Melkino\Auth;

use Melkino\Core\Database;
use Melkino\Core\Logger;
use Melkino\Integrations\SmsNotifier;
use Melkino\Support\Persian;

/**
 * Melkino V2 — canonical OTP service (spec §32).
 * request-otp.php / verify-otp.php delegate here; behavior preserved:
 * hashed codes, expiry, attempt limits, rate limits, SMS-or-onscreen fallback.
 */
final class OtpService
{
    public const CODE_TTL_SECONDS = 120;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN = 60;
    public const MAX_PER_HOUR = 5;

    public static function ensureTable(): bool
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS otp_codes (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    phone VARCHAR(32) NOT NULL,
                    code_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    attempts INT NOT NULL DEFAULT 0,
                    consumed_at DATETIME NULL,
                    ip VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_otp_phone (phone),
                    KEY idx_otp_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{ok:bool,message:string,cooldown:int,dev_code:string} */
    public static function request(string $rawPhone): array
    {
        $phone = Persian::normalizePhone($rawPhone);
        if (!Persian::isValidPhone($phone)) {
            return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست.', 'cooldown' => 0, 'dev_code' => ''];
        }
        $pdo = Database::pdo();
        if (!$pdo || !self::ensureTable()) {
            return ['ok' => false, 'message' => 'سرویس پیامک موقتاً در دسترس نیست.', 'cooldown' => 0, 'dev_code' => ''];
        }
        try {
            // Cooldown: last unconsumed code for this phone.
            $st = $pdo->prepare('SELECT created_at FROM otp_codes WHERE phone = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1');
            $st->execute([$phone]);
            $last = $st->fetchColumn();
            if ($last) {
                $elapsed = time() - (int)strtotime((string)$last);
                if ($elapsed < self::RESEND_COOLDOWN) {
                    return ['ok' => true, 'message' => 'کد قبلاً ارسال شده است.', 'cooldown' => self::RESEND_COOLDOWN - $elapsed, 'dev_code' => ''];
                }
            }
            // Hourly cap.
            $st = $pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
            $st->execute([$phone]);
            if ((int)$st->fetchColumn() >= self::MAX_PER_HOUR) {
                return ['ok' => false, 'message' => 'تعداد درخواست‌ها زیاد است. یک ساعت بعد تلاش کنید.', 'cooldown' => 0, 'dev_code' => ''];
            }

            $code = (string)random_int(100000, 999999);
            if (!function_exists('melkinoOtpHash')) {
                @require_once MELKINO_ROOT . '/security-lib.php';
            }
            // Legacy contract: melkinoOtpHash(string): sha256 (2-minute rows paired with phone).
            $hash = function_exists('melkinoOtpHash') ? melkinoOtpHash($code) : hash('sha256', $code);
            $st = $pdo->prepare(
                'INSERT INTO otp_codes (phone, code_hash, expires_at, ip) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)'
            );
            $st->execute([$phone, $hash, self::CODE_TTL_SECONDS, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64)]);

            $sent = SmsNotifier::sendOtp($phone, $code);
            $devCode = '';
            if (!$sent && !\Melkino\Config\Environment::isProduction()) {
                $devCode = $code; // Non-production fallback: show on screen (legacy behavior).
            }
            return ['ok' => true, 'message' => 'کد تأیید ارسال شد.', 'cooldown' => self::RESEND_COOLDOWN, 'dev_code' => $devCode];
        } catch (\Throwable $e) {
            Logger::error('OTP request failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'خطا در ارسال کد. دوباره تلاش کنید.', 'cooldown' => 0, 'dev_code' => ''];
        }
    }

    /** @return array{ok:bool,message:string} */
    public static function verify(string $rawPhone, string $rawCode): array
    {
        $phone = Persian::normalizePhone($rawPhone);
        $code = Persian::toEnglishDigits(trim($rawCode));
        if (!Persian::isValidPhone($phone) || !preg_match('/^\d{4,8}$/', $code)) {
            return ['ok' => false, 'message' => 'کد واردشده معتبر نیست.'];
        }
        $pdo = Database::pdo();
        if (!$pdo || !self::ensureTable()) {
            return ['ok' => false, 'message' => 'سرویس موقتاً در دسترس نیست.'];
        }
        try {
            $st = $pdo->prepare(
                'SELECT id, code_hash, attempts FROM otp_codes
                  WHERE phone = ? AND consumed_at IS NULL AND expires_at > NOW()
                  ORDER BY id DESC LIMIT 1'
            );
            $st->execute([$phone]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                return ['ok' => false, 'message' => 'کد منقضی شده است. کد جدید بگیرید.'];
            }
            if ((int)$row['attempts'] >= self::MAX_ATTEMPTS) {
                return ['ok' => false, 'message' => 'تعداد تلاش‌ها تمام شد. کد جدید بگیرید.'];
            }
            if (!function_exists('melkinoOtpCodeMatches')) {
                @require_once MELKINO_ROOT . '/security-lib.php';
            }
            // Legacy contract: melkinoOtpCodeMatches(array $row, string $input) — supports
            // both code_hash and legacy plaintext `code` columns.
            $match = function_exists('melkinoOtpCodeMatches')
                ? melkinoOtpCodeMatches(['code_hash' => (string)$row['code_hash'], 'code' => (string)($row['code'] ?? '')], $code)
                : hash_equals((string)$row['code_hash'], hash('sha256', $code));
            if (!$match) {
                $pdo->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
                $left = self::MAX_ATTEMPTS - (int)$row['attempts'] - 1;
                return ['ok' => false, 'message' => 'کد اشتباه است.' . ($left > 0 ? ' (' . $left . ' تلاش باقی مانده)' : '')];
            }
            $pdo->prepare('UPDATE otp_codes SET consumed_at = NOW() WHERE id = ?')->execute([$row['id']]);
            return ['ok' => true, 'message' => 'تأیید شد.'];
        } catch (\Throwable $e) {
            Logger::error('OTP verify failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'خطا در بررسی کد.'];
        }
    }
}
