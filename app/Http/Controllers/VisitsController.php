<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Database;
use Melkino\Http\Gate;

/** Melkino V2 — my visits (same owner query + same card prep as legacy visits.php). */
final class VisitsController
{
    public function render(): string
    {
        Gate::check('visits.php');
        foreach (['db_helpers.php', 'visit-request-lib.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }
        require_once MELKINO_APP . '/Domain/Visits/visit-view-fns.php';

        $pdo = Database::pdo();
        $identity = function_exists('melkinoCurrentIdentity') ? (array)melkinoCurrentIdentity() : ['user_id' => null];
        $logged = !empty($identity['user_id'])
            || trim((string)($identity['telegram_id'] ?? '')) !== ''
            || trim((string)($identity['phone'] ?? '')) !== '';

        $items = [];
        $statuses = function_exists('melkinoVisitStatuses') ? melkinoVisitStatuses() : ['new' => 'جدید'];
        if ($logged && $pdo) {
            try {
                if (function_exists('melkinoEnsureVisitRequestSchema')) {
                    melkinoEnsureVisitRequestSchema();
                }
            } catch (\Throwable $ignored) {
            }
            $where = '';
            $params = [];
            if (function_exists('melkinoVisitOwnerWhere')) {
                [$where, $params] = melkinoVisitOwnerWhere($identity, 'vr');
            }
            if ($where !== '') {
                try {
                    $st = $pdo->prepare('SELECT vr.* FROM visit_requests vr WHERE ' . $where . ' ORDER BY vr.created_at DESC, vr.id DESC');
                    $st->execute($params);
                    $items = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) {
                    try {
                        [$w2, $p2] = melkinoVisitOwnerWhere($identity);
                        $st = $pdo->prepare('SELECT * FROM visit_requests WHERE ' . $w2 . ' ORDER BY created_at DESC, id DESC');
                        $st->execute($p2);
                        $items = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    } catch (\Throwable $e2) {
                        $items = [];
                    }
                }
                $adIds = [];
                foreach ($items as $row) {
                    $adIds[] = (string)($row['ad_id'] ?? '');
                }
                if ($adIds && function_exists('melkinoVisitLoadAdsMap')) {
                    $adsMap = melkinoVisitLoadAdsMap($pdo, $adIds);
                    $amenMap = function_exists('melkinoVisitLoadAmenitiesMap')
                        ? melkinoVisitLoadAmenitiesMap($pdo, $adIds) : [];
                    foreach ($items as $i => $row) {
                        $aid = (string)($row['ad_id'] ?? '');
                        $adRow = $adsMap[$aid] ?? ((ctype_digit($aid) && isset($adsMap[(string)(int)$aid])) ? $adsMap[(string)(int)$aid] : null);
                        if (is_array($adRow)) {
                            $items[$i]['_ad'] = $adRow;
                        }
                        $items[$i]['_amenities'] = $amenMap[$aid] ?? $amenMap[(string)(int)$aid] ?? [];
                    }
                }
            }
        }

        $cards = [];
        foreach ($items as $it) {
            $cards[] = $this->card($it, $statuses, $pdo);
        }

        return melkinoView('layouts/public.php', [
            'title' => 'بازدیدهای من | ملکینو',
            'description' => 'درخواست‌های بازدید ثبت‌شده شما',
            'active_nav' => 'requests',
            'scripts' => ['visits'],
            'content_view' => 'pages/visits.php',
            'content_data' => ['logged' => $logged, 'cards' => $cards],
        ]);
    }

    /** Same card prep as the legacy loop (verbatim logic, collected into an array). */
    private function card(array $it, array $statuses, ?\PDO $pdo): array
    {
        $id = (int)($it['id'] ?? 0);
        $card = [
            'id' => $id, 'status' => 'جدید', 'track' => 'VR-' . str_pad((string)max(0, $id), 5, '0', STR_PAD_LEFT),
            'title' => 'آگهی', 'ad_id' => '', 'when' => ['weekday' => '—', 'date' => '—', 'slot' => '—'],
            'alt' => '', 'can_delete' => true, 'facts' => [], 'amenities' => [], 'extra' => [],
        ];
        try {
            $stKey = function_exists('melkinoVisitNormalizeStatus')
                ? melkinoVisitNormalizeStatus((string)($it['status'] ?? 'new'))
                : trim((string)($it['status'] ?? 'new'));
            if ($stKey === '') {
                $stKey = 'new';
            }
            $card['status'] = $statuses[$stKey] ?? $stKey;
            $ad = function_exists('melkinoVisitResolveAdInfo') ? melkinoVisitResolveAdInfo($it) : [];
            $title = trim((string)($ad['title'] ?? ($it['ad_title'] ?? '')));
            $card['title'] = $title !== '' ? $title : 'آگهی';
            $card['ad_id'] = trim((string)($it['ad_id'] ?? ($ad['id'] ?? '')));
            $when = function_exists('melkinoVisitsPageDate')
                ? melkinoVisitsPageDate($it) : ['weekday' => '—', 'date' => '—', 'slot' => '—'];
            if (trim((string)($when['date'] ?? '')) === '') {
                $when['date'] = '—';
            }
            $card['when'] = $when;
            $card['alt'] = trim((string)($it['alternative_datetime'] ?? ''));
            if ($pdo && function_exists('melkinoVisitEnsureTracking')) {
                MelkinoVisitEnsureTracking($pdo, $it);
            }
            $track = trim((string)($it['tracking_code'] ?? ''));
            if ($track === '' && function_exists('melkinoVisitTrackingCode')) {
                $track = melkinoVisitTrackingCode($id);
            }
            if ($track !== '') {
                $card['track'] = $track;
            }
            $card['can_delete'] = ($stKey === 'new');
            $card['facts'] = function_exists('melkinoVisitsPageFacts')
                ? melkinoVisitsPageFacts($it, is_array($ad) ? $ad : [], $card['title'], $card['ad_id']) : [];
            $card['amenities'] = function_exists('melkinoVisitAmenityList')
                ? melkinoVisitAmenityList($it, is_array($it['_amenities'] ?? null) ? $it['_amenities'] : []) : [];
            $card['extra'] = function_exists('melkinoVisitExtraDetails') ? melkinoVisitExtraDetails($it) : [];
        } catch (\Throwable $ignored) {
        }
        return $card;
    }
}
