<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\Request;
use Melkino\Domain\Compare\CompareService;
use Melkino\Http\Gate;
use Melkino\Domain\Properties\PropertyFormatter;
use Melkino\Domain\Properties\PropertyRepository;
use Melkino\UI\Fields\PropertySpecCatalog;

/** Melkino V2 — compare (spec §21): grouped rows, mobile-friendly. Rules preserved via compare-lib. */
final class CompareController
{
    public function render(): string
    {
        Gate::check('compare-page.php');
        $group = Request::int('group', 1, 1, 3);
        $ids = CompareService::items($group);
        if (!$ids) {
            foreach ([1, 2, 3] as $g) {
                $try = CompareService::items($g);
                if ($try) {
                    $group = $g;
                    $ids = $try;
                    break;
                }
            }
        }
        $ads = [];
        foreach (array_slice($ids, 0, 4) as $id) {
            $ad = PropertyRepository::find($id);
            if ($ad) {
                $ads[] = $ad;
            }
        }
        if ($ads) {
            $images = PropertyRepository::primaryImages(array_column($ads, 'id'));
            foreach ($ads as &$a) {
                $a['primary_image'] = $images[(int)$a['id']] ?? null;
            }
            unset($a);
        }
        $dtos = array_map(static fn($a) => PropertyFormatter::card($a, ['compare' => true]), $ads);
        $rows = $this->rows($ads);
        return melkinoView('layouts/public.php', [
            'title' => 'مقایسه ملک‌ها | ملکینو',
            'active_nav' => 'search',
            'scripts' => ['compare', 'favorites'],
            'content_view' => 'pages/compare.php',
            'content_data' => [
                'items' => $dtos, 'rows' => $rows,
                'groups' => CompareService::groups(), 'group' => $group,
            ],
        ]);
    }

    /** @return array<int,array{label:string,values:array<int,string>}> */
    private function rows(array $ads): array
    {
        if (!$ads) {
            return [];
        }
        $specs = [];
        $labels = [];
        foreach ($ads as $i => $ad) {
            $full = PropertySpecCatalog::full($ad);
            foreach ($full as $s) {
                $labels[$s['label']] = true;
                $specs[$i][$s['label']] = $s['value'];
            }
        }
        $rows = [];
        $rows[] = ['label' => 'قیمت', 'values' => array_map(static fn($a) => \Melkino\Support\Price::short($a), $ads)];
        $rows[] = ['label' => 'موقعیت', 'values' => array_map(static fn($a) => (string)($a['location'] ?? ''), $ads)];
        foreach (array_keys($labels) as $label) {
            $vals = [];
            foreach ($ads as $i => $a) {
                $vals[] = $specs[$i][$label] ?? '—';
            }
            $rows[] = ['label' => $label, 'values' => $vals];
        }
        return $rows;
    }
}
