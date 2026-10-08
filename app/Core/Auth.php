<?php
declare(strict_types=1);

namespace Melkino\Core;

/**
 * Melkino V2 — canonical auth state (spec §32, §128).
 * Mirrors legacy session keys (reg_telegram_id / reg_bale_id / reg_eitaa_id / user_phone / is_admin)
 * so old and new code share one login state.
 */
final class Auth
{
    public static function id(): ?int
    {
        $v = Session::get('melkino_user_id');
        if (is_numeric($v) && (int)$v > 0) {
            return (int)$v;
        }
        /*
         * مسیرهای ورود قدیمی (موبایل/OTP و لینک ?t=) کلید melkino_user_id را
         * در سشن ست نمی‌کنند؛ بدون این شناسه، اندپوینت‌های V2 (مثل ذخیرهٔ
         * علاقه‌مندی) برای این کاربران با خطا مواجه می‌شوند. شناسه را
         * حداکثر یک‌بار در هر درخواست، از روی هویت تأییدشدهٔ سشن
         * (تلگرام/بله/ایتا/موبایل) بازیابی و در سشن ماندگار می‌کنیم تا هم
         * فراخوانی‌های بعدی همین درخواست و هم درخواست‌های بعدی بدون
         * کوئری اضافه کار کنند. عمداً از static برای نتیجهٔ موفق استفاده
         * نشده چون ورکر FPM بین درخواست‌ها/کاربران مشترک است و باعث
         * نشت هویت کاربر دیگری می‌شد.
         */
        try {
            $sigParts = [self::telegramId(), self::baleId(), self::eitaaId(), self::phone()];
            if (implode('', $sigParts) === '') {
                return null;
            }
            $pdo = Database::pdo();
            if (!($pdo instanceof \PDO)) {
                return null;
            }
            $cols = ['telegram_id', 'bale_id', 'eitaa_id', 'phone'];
            foreach ($cols as $i => $col) {
                $val = $sigParts[$i];
                if ($val === '') {
                    continue;
                }
                $st = $pdo->prepare("SELECT id FROM users WHERE `$col` = ? ORDER BY id DESC LIMIT 1");
                $st->execute([$val]);
                $id = $st->fetchColumn();
                if (is_numeric($id) && (int)$id > 0) {
                    $id = (int)$id;
                    Session::set('melkino_user_id', $id);
                    return $id;
                }
            }
        } catch (\Throwable $e) {
            // بدون دیتابیس (CLI/تست) مثل رفتار قبلی null برمی‌گردد.
        }
        return null;
    }

    public static function phone(): string
    {
        return trim((string)Session::get('user_phone', ''));
    }

    public static function name(): string
    {
        return trim((string)Session::get('user_name', ''));
    }

    public static function telegramId(): string
    {
        return trim((string)Session::get('reg_telegram_id', ''));
    }

    public static function baleId(): string
    {
        return trim((string)Session::get('reg_bale_id', ''));
    }

    public static function eitaaId(): string
    {
        return trim((string)Session::get('reg_eitaa_id', ''));
    }

    public static function check(): bool
    {
        return self::id() !== null
            || self::phone() !== ''
            || self::telegramId() !== ''
            || self::baleId() !== ''
            || self::eitaaId() !== '';
    }

    public static function isAdmin(): bool
    {
        return !empty($_SESSION['is_admin']);
    }

    public static function adminId(): ?int
    {
        $v = $_SESSION['admin_id'] ?? null;
        return is_numeric($v) && (int)$v > 0 ? (int)$v : null;
    }

    public static function adminRole(): string
    {
        return trim((string)($_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? '')));
    }

    /** Establish a user login (regenerates session, syncs legacy keys). */
    public static function loginUser(?int $userId, string $phone = '', string $name = '', array $ids = []): void
    {
        Session::regenerate();
        if ($userId !== null && $userId > 0) {
            Session::set('melkino_user_id', $userId);
        }
        if ($phone !== '') {
            Session::set('user_phone', $phone);
        }
        if ($name !== '') {
            Session::set('user_name', $name);
        }
        foreach (['telegram' => 'reg_telegram_id', 'bale' => 'reg_bale_id', 'eitaa' => 'reg_eitaa_id'] as $k => $sess) {
            if (!empty($ids[$k])) {
                Session::set($sess, (string)$ids[$k]);
            }
        }
    }

    public static function loginAdmin(int $adminId, string $username, string $displayName = '', string $role = ''): void
    {
        Session::regenerate();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_display_name'] = $displayName;
        $_SESSION['admin_login_at'] = time();
        if ($role !== '') {
            $_SESSION['user_role'] = $role;
        }
    }

    /** @return array{user_id:?int,phone:string,name:string,telegram_id:string,bale_id:string,eitaa_id:string,logged_in:bool} */
    public static function identity(): array
    {
        return [
            'user_id' => self::id(),
            'phone' => self::phone(),
            'name' => self::name(),
            'telegram_id' => self::telegramId(),
            'bale_id' => self::baleId(),
            'eitaa_id' => self::eitaaId(),
            'logged_in' => self::check(),
        ];
    }
}
