<?php
declare(strict_types=1);

namespace Melkino\Domain\Compare;

use Melkino\Core\Auth;
use Melkino\Core\Database;
use PDO;

/**
 * Melkino V2 — compare facade (spec §21).
 * Rules preserved EXACTLY from compare.php round-24:
 * - ops require login (identity user_id); guests get 401 + login flag
 * - 3 groups, max 5 published ads per group, one property_type per group
 * - guest_token rows merge into the account on first logged-in hit
 * All persistence calls use compare-lib.php's exact ($pdo, $ownerWhere, $ownerParams, ...) contracts.
 */
final class CompareService
{
    public static function boot(): bool
    {
        if (!function_exists('melkinoEnsureCompareTables')) {
            @require_once MELKINO_ROOT . '/compare-lib.php';
        }
        if (!function_exists('melkinoCurrentIdentity')) {
            @require_once MELKINO_ROOT . '/db_helpers.php';
        }
        return function_exists('melkinoEnsureCompareTables') && function_exists('melkinoCurrentIdentity');
    }

    /** Legacy identity shape (has user_id/user keys) used by compare-lib owner SQL. */
    public static function legacyIdentity(): array
    {
        if (self::boot()) {
            try {
                $id = melkinoCurrentIdentity();
                if (is_array($id)) {
                    return $id;
                }
            } catch (\Throwable $ignored) {
            }
        }
        $me = Auth::identity();
        return ['user_id' => $me['user_id'], 'telegram_id' => $me['telegram_id'], 'phone' => $me['phone'], 'user' => []];
    }

    public static function loggedIn(): bool
    {
        $id = self::legacyIdentity();
        return !empty($id['user_id']);
    }

    /** @return array{where:string,params:array,wherePlain:string,paramsPlain:array}|null */
    private static function owner(): ?array
    {
        $pdo = Database::pdo();
        if (!$pdo || !self::boot()) {
            return null;
        }
        try {
            melkinoEnsureCompareTables($pdo);
        } catch (\Throwable $e) {
            return null;
        }
        $identity = self::legacyIdentity();
        if (!empty($identity['user_id'])) {
            try {
                melkinoCompareMergeGuest($pdo, $identity);
            } catch (\Throwable $ignored) {
            }
        }
        [$w, $p] = melkinoCompareOwner($identity, 'ci');
        [$wp, $pp] = melkinoCompareOwner($identity);
        if ($w === '') {
            return null;
        }
        return ['where' => $w, 'params' => $p, 'wherePlain' => $wp, 'paramsPlain' => $pp];
    }

    /** @return array<int,array{no:int,name:string,count:int}> */
    public static function groups(): array
    {
        $pdo = Database::pdo();
        $o = self::owner();
        if (!$pdo || !$o) {
            return [];
        }
        try {
            $groups = melkinoCompareGroups($pdo, $o['where'], $o['params'], $o['wherePlain'], $o['paramsPlain']);
            return is_array($groups) ? array_values($groups) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function count(): int
    {
        $pdo = Database::pdo();
        $o = self::owner();
        if (!$pdo || !$o || !self::loggedIn()) {
            return 0;
        }
        try {
            return (int)melkinoCompareCount($pdo, $o['where'], $o['params']);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @return array<int> ad ids in group (1..3). */
    public static function items(int $groupNo): array
    {
        $pdo = Database::pdo();
        $o = self::owner();
        if (!$pdo || !$o || $groupNo < 1 || $groupNo > 3) {
            return [];
        }
        try {
            $items = melkinoCompareItems($pdo, $o['where'], $o['params'], $groupNo);
            if (!is_array($items)) {
                return [];
            }
            $out = [];
            foreach ($items as $it) {
                $id = (int)(is_array($it) ? ($it['ad_id'] ?? 0) : $it);
                if ($id > 0) {
                    $out[] = $id;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int> all compared ad ids across groups. */
    public static function allIds(): array
    {
        $out = [];
        foreach ([1, 2, 3] as $g) {
            foreach (self::items($g) as $id) {
                $out[] = $id;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Toggle add/remove with the exact round-24 rules.
     * @return array{ok:bool,added:bool,removed:bool,already:bool,count:int,message:string,code:int}
     */
    public static function toggle(int $adId): array
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => 0, 'message' => 'اتصال دیتابیس برقرار نیست.', 'code' => 500];
        }
        if (!self::loggedIn()) {
            return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => 0, 'message' => 'برای استفاده از مقایسهٔ ملک‌ها، اول وارد حساب کاربری شوید.', 'code' => 401];
        }
        $o = self::owner();
        if (!$o) {
            return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => 0, 'message' => 'برای مقایسه، اول وارد حساب شو.', 'code' => 401];
        }
        if ($adId <= 0) {
            return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => self::count(), 'message' => 'شناسه آگهی الزامی است.', 'code' => 422];
        }
        try {
            // Remove path (click again = remove).
            $st = $pdo->prepare("SELECT id FROM compare_items ci WHERE {$o['where']} AND ci.ad_id = ? LIMIT 1");
            $st->execute([...$o['params'], $adId]);
            if ($st->fetchColumn() !== false) {
                $del = $pdo->prepare("DELETE FROM compare_items WHERE {$o['wherePlain']} AND ad_id = ?");
                $del->execute([...$o['paramsPlain'], $adId]);
                return ['ok' => true, 'added' => false, 'removed' => true, 'already' => false, 'count' => self::count(), 'message' => 'از مقایسه حذف شد.', 'code' => 200];
            }
            // Add path: published ad, auto-group with same-type rule, max 5.
            $st = $pdo->prepare("SELECT id, property_type FROM ads WHERE id = ? AND status = 'published' LIMIT 1");
            $st->execute([$adId]);
            $ad = $st->fetch(PDO::FETCH_ASSOC);
            if (!$ad) {
                return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => self::count(), 'message' => 'آگهی پیدا نشد یا منتشر نیست.', 'code' => 404];
            }
            $newType = trim((string)($ad['property_type'] ?? ''));
            $groupNo = 0;
            for ($g = 1; $g <= 3; $g++) {
                $c = melkinoCompareCountInGroup($pdo, $o['where'], $o['params'], $g);
                if ($c >= 5) {
                    continue;
                }
                $types = self::groupTypes($pdo, $o, $g);
                if ($types === [] || ($newType !== '' && in_array($newType, $types, true))) {
                    $groupNo = $g;
                    break;
                }
            }
            if ($groupNo === 0) {
                return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => self::count(), 'message' => 'دستهٔ هم‌نوع جا ندارد؛ یک دسته را خالی کنید (حداکثر ۵ ملک هم‌نوع در هر دسته).', 'code' => 422];
            }
            $id = self::legacyIdentity();
            $ins = $pdo->prepare(
                'INSERT INTO compare_items (user_id, telegram_id, guest_token, ad_id, group_no, created_at)
                 VALUES (?,?,?,?,?,NOW())'
            );
            $ins->execute([
                !empty($id['user_id']) ? (int)$id['user_id'] : null,
                trim((string)($id['telegram_id'] ?? '')) !== '' ? trim((string)$id['telegram_id']) : null,
                null, $adId, $groupNo,
            ]);
            return ['ok' => true, 'added' => true, 'removed' => false, 'already' => false, 'count' => self::count(), 'message' => 'به مقایسه اضافه شد.', 'code' => 200];
        } catch (\Throwable $e) {
            return ['ok' => false, 'added' => false, 'removed' => false, 'already' => false, 'count' => 0, 'message' => 'خطا در مقایسه.', 'code' => 500];
        }
    }

    /** @return string[] */
    private static function groupTypes(PDO $pdo, array $o, int $g): array
    {
        $st = $pdo->prepare(
            "SELECT DISTINCT a.property_type FROM compare_items ci
             INNER JOIN ads a ON a.id = ci.ad_id AND a.status = 'published'
             WHERE {$o['where']} AND ci.group_no = ?"
        );
        $st->execute([...$o['params'], $g]);
        return array_values(array_filter(array_map('trim', $st->fetchAll(PDO::FETCH_COLUMN)), static fn($t) => $t !== ''));
    }

    public static function mergeGuestAfterLogin(): void
    {
        $pdo = Database::pdo();
        if (!$pdo || !self::boot()) {
            return;
        }
        try {
            melkinoCompareMergeGuest($pdo, self::legacyIdentity());
        } catch (\Throwable $ignored) {
        }
    }
}
