<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — TOTP (RFC 6238, SHA-1) for admin two-factor auth.
 * Zero-dependency: base32 codec + HMAC-SHA1 dynamic truncation.
 * Test vectors: tests/TotpTest.php (RFC 6238 Appendix B, SHA-1).
 */
final class Totp
{
    public const STEP = 30;
    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** New random secret, base32 without padding (Google Authenticator style). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes(max(10, $bytes)));
    }

    public static function base32Encode(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        $bits = '';
        $len = strlen($raw);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($raw[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::B32[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    /** @throws \InvalidArgumentException on invalid input */
    public static function base32Decode(string $b32): string
    {
        $s = strtoupper(preg_replace('/[\s\-]+/', '', $b32) ?? '');
        $s = rtrim($s, '=');
        if ($s === '' || preg_match('/[^A-Z2-7]/', $s) === 1) {
            throw new \InvalidArgumentException('Invalid base32 secret.');
        }
        $bits = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos(self::B32, $s[$i]);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid base32 secret.');
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

    /** otpauth:// URI for manual entry / QR generators. */
    public static function uri(string $secret, string $account, string $issuer = 'Melkino'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=' . self::STEP;
    }

    /** Current code for a base32 secret (digits 6 or 8). */
    public static function code(string $secret, ?int $time = null, int $digits = 6): string
    {
        if ($digits !== 6 && $digits !== 8) {
            throw new \InvalidArgumentException('Digits must be 6 or 8.');
        }
        $key = self::base32Decode($secret);
        $counter = (int)floor(($time ?? time()) / self::STEP);
        // 8-byte big-endian counter (intdiv keeps exactness past 32 bits).
        $msg = pack('N2', intdiv($counter, 4294967296), $counter % 4294967296);
        $hash = hash_hmac('sha1', $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);
        $mod = $digits === 8 ? 100000000 : 1000000;
        return str_pad((string)($code % $mod), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a user-typed code (accepts Persian/Arabic digits + spaces).
     * $window = ± steps tolerated for clock skew (default 1).
     */
    public static function verify(string $secret, string $code, ?int $time = null, int $digits = 6, int $window = 1): bool
    {
        $norm = strtr(trim($code), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $norm = preg_replace('/\s+/', '', $norm) ?? '';
        if (!preg_match('/^\d{' . $digits . '}$/', $norm)) {
            return false;
        }
        try {
            $now = $time ?? time();
            for ($i = -$window; $i <= $window; $i++) {
                if (hash_equals(self::code($secret, $now + $i * self::STEP, $digits), $norm)) {
                    return true;
                }
            }
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return false;
    }
}
