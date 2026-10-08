<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Database;
use Melkino\Core\Request;
use Melkino\Core\Response;
use Melkino\Core\Session;
use Melkino\Http\Gate;

/**
 * Melkino V2 — saved searches (spec: same SQL + same ownership rule as legacy).
 * POST actions: toggle / delete / unsub_all / resub (identical statements).
 */
final class SavedSearchesController
{
    public function handle(): string
    {
        Gate::check('saved-searches.php');
        foreach (['db_helpers.php', 'sms-program.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        $pdo = Database::pdo();
        if ($pdo && function_exists('smsProgramEnsureSchema')) {
            try {
                smsProgramEnsureSchema($pdo);
            } catch (\Throwable $ignored) {
            }
        }
        $identity = function_exists('melkinoCurrentIdentity')
            ? (array)melkinoCurrentIdentity($_GET['telegram_id'] ?? $_POST['telegram_id'] ?? null)
            : [];
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $phone = function_exists('smsProgramNormPhone')
            ? smsProgramNormPhone((string)($identity['phone'] ?? '')) : '';
        if ($phone === '' && !empty($_SESSION['user_phone']) && function_exists('smsProgramNormPhone')) {
            $phone = smsProgramNormPhone((string)$_SESSION['user_phone']);
        }

        if (Request::method() === 'POST' && $pdo) {
            \Melkino\Core\Csrf::check();
            $act = Request::string('action');
            $sid = Request::int('id', 0);
            if ($act === 'toggle' && $sid > 0) {
                $st = $pdo->prepare('UPDATE saved_searches SET notify = 1 - notify WHERE id = ? AND (user_id = ? OR phone = ?)');
                $st->execute([$sid, $userId, $phone]);
                Session::flash('success', 'وضعیت اطلاع‌رسانی تغییر کرد.');
            } elseif ($act === 'delete' && $sid > 0) {
                $st = $pdo->prepare('DELETE FROM saved_searches WHERE id = ? AND (user_id = ? OR phone = ?)');
                $st->execute([$sid, $userId, $phone]);
                Session::flash('success', 'جستجو حذف شد.');
            } elseif ($act === 'unsub_all') {
                try {
                    $st = $pdo->prepare("INSERT IGNORE INTO sms_optouts (phone, scope, source) VALUES (?, 'alerts', 'panel')");
                    $st->execute([$phone]);
                    $pdo->prepare('UPDATE saved_searches SET notify = 0 WHERE phone = ?')->execute([$phone]);
                    Session::flash('success', 'دریافت پیامک‌های اطلاع‌رسانی برای شمارهٔ شما لغو شد.');
                } catch (\Throwable $e) {
                    Session::flash('error', 'انجام نشد؛ دوباره تلاش کنید.');
                }
            } elseif ($act === 'resub') {
                $pdo->prepare("DELETE FROM sms_optouts WHERE phone = ? AND scope = 'alerts'")->execute([$phone]);
                $pdo->prepare('UPDATE saved_searches SET notify = 1 WHERE phone = ?')->execute([$phone]);
                Session::flash('success', 'دریافت پیامک‌های اطلاع‌رسانی دوباره فعال شد.');
            }
            Response::redirect('saved-searches.php');
        }

        $searches = [];
        $optedOut = false;
        if ($pdo && $phone !== '') {
            try {
                $st = $pdo->prepare("SELECT COUNT(*) FROM sms_optouts WHERE phone = ? AND scope IN (?, 'all')");
                $st->execute([$phone, 'alerts']);
                $optedOut = (int)$st->fetchColumn() > 0;
                $st = $pdo->prepare('SELECT * FROM saved_searches WHERE phone = ? OR user_id = ? ORDER BY id DESC');
                $st->execute([$phone, $userId]);
                $searches = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $searches = [];
            }
        }
        foreach ($searches as &$s) {
            $s['_summary'] = self::summary($s);
        }
        unset($s);

        return melkinoView('layouts/public.php', [
            'title' => 'جستجوهای ذخیره‌شده | ملکینو',
            'description' => 'مدیریت جستجوهای ذخیره‌شده و اطلاع‌رسانی پیامکی',
            'active_nav' => 'search',
            'content_view' => 'pages/saved-searches.php',
            'content_data' => ['searches' => $searches, 'optedOut' => $optedOut],
        ]);
    }

    /** Same summary format as legacy ssSummary(). */
    public static function summary(array $s): string
    {
        $bits = [];
        $txMap = ['فروش' => 'فروش', 'پیش فروش' => 'پیش فروش', 'اجاره' => 'اجاره و رهن'];
        $tx = trim((string)($s['tx'] ?? ''));
        if ($tx !== '') {
            $bits[] = $txMap[$tx] ?? $tx;
        }
        if (trim((string)($s['property_type'] ?? '')) !== '') {
            $bits[] = trim((string)$s['property_type']);
        }
        if (trim((string)($s['district'] ?? '')) !== '') {
            $bits[] = trim((string)$s['district']);
        }
        $digits = static fn($v) => function_exists('smsProgramDigits')
            ? (int)smsProgramDigits((string)$v)
            : (int)preg_replace('/\D+/', '', \Melkino\Support\Persian::toEnglishDigits((string)$v));
        $minA = $digits($s['min_area'] ?? '');
        $maxA = $digits($s['max_area'] ?? '');
        if ($minA > 0 || $maxA > 0) {
            $bits[] = 'متراژ ' . fa($minA ?: '؟') . ' تا ' . fa($maxA ?: '؟');
        }
        $minP = $digits($s['min_price'] ?? '');
        $maxP = $digits($s['max_price'] ?? '');
        if ($minP > 0 || $maxP > 0) {
            $bits[] = 'بودجه ' . fa(number_format($minP ?: 0)) . ' تا ' . fa(number_format($maxP ?: 0));
        }
        if (trim((string)($s['rooms'] ?? '')) !== '' && trim((string)($s['rooms'] ?? '')) !== '0') {
            $bits[] = fa((string)$s['rooms']) . ' خواب';
        }
        return $bits ? implode(' · ', $bits) : 'بدون فیلتر (همهٔ آگهی‌های جدید)';
    }
}
