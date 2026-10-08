<?php
/**
 *--------------------------------------------------------------------------
 * دفتر ملکینو شهر — دستیار (مرحله ۳۰)
 *--------------------------------------------------------------------------
 * آینهٔ دستیار پنل سایت: برد تحلیل (KPI + یافته‌ها)، تازه‌سازی از
 * دیتابیس، گفت‌وگوی فارسی روی آخرین تحلیل، تاریخچه گفت‌وگو.
 */

declare(strict_types=1);

require_once __DIR__ . '/_assistant.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
$pdo = office_db();
$flash = '';
$flashErr = '';
$chatAns = null;

$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
if ($isPost) {
    if (!office_csrf_valid()) {
        $flashErr = 'توکن امنیتی نامعتبر است؛ لطفاً دوباره تلاش کنید.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'refresh') {
            [$ok, $msg] = office_ast_refresh($pdo);
            if ($ok) {
                $flash = $msg;
            } else {
                $flashErr = $msg;
            }
        } elseif ($action === 'chat') {
            $chatAns = office_ast_chat($pdo, (string)($_POST['message'] ?? ''));
        } else {
            $flashErr = 'عملیات ناشناخته.';
        }
    }
}

$boardRes = office_ast_board($pdo);
$board = $boardRes['board'];
$run = $board['run'] ?? null;
$kpis = $board['kpis'] ?? [];
$insights = $board['insights'] ?? [];
$history = office_ast_history($pdo, 30);

office_shell_open('promotions.php', 'دستیار');
?>
<?php if ($flash !== '') : ?><p class="of-alert ok"><?= office_h($flash) ?></p><?php endif; ?>
<?php if ($flashErr !== '') : ?><p class="of-alert err"><?= office_h($flashErr) ?></p><?php endif; ?>
<?php if ($boardRes['message'] !== '') : ?><p class="of-alert err"><?= office_h($boardRes['message']) ?></p><?php endif; ?>

<div class="of-panel">
<?php if ($run) : ?>
<strong>آخرین تحلیل:</strong> <?= office_h(office_fa((string)($run['finished_at'] ?? ''))) ?>
&nbsp;•&nbsp; بازدید <?= office_num((int)($run['views_n'] ?? 0)) ?> · درخواست <?= office_num((int)($run['requests_n'] ?? 0)) ?> · تطبیق <?= office_num((int)($run['matches_n'] ?? 0)) ?> · آگهی <?= office_num((int)($run['ads_n'] ?? 0)) ?>
<?php else : ?>
<span class="of-muted">هنوز تحلیلی ساخته نشده است.</span>
<?php endif; ?>
<form method="post" style="display:inline">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="refresh">
<button class="of-btn" type="submit">🔄 تازه‌سازی تحلیل از دیتابیس</button>
</form>
</div>

<div class="of-panel">
<strong>یافته‌ها:</strong> <?= office_num(count($insights)) ?>
&nbsp;•&nbsp; داغ <?= office_num((int)($kpis['hot'] ?? 0)) ?> · تطبیق <?= office_num((int)($kpis['match'] ?? 0)) ?> · درخواست <?= office_num((int)($kpis['request'] ?? 0)) ?> · بازدید <?= office_num((int)($kpis['visit'] ?? 0)) ?> · آگهی <?= office_num((int)($kpis['ad'] ?? 0)) ?>
</div>

<?php if ($chatAns !== null) : ?>
<div class="of-panel">
<h3 style="margin-top:0">🧠 پاسخ دستیار</h3>
<p><?= office_h($chatAns['reply']) ?></p>
<?php if ($chatAns['insights']) : ?>
<p class="of-muted"><?= office_num(count($chatAns['insights'])) ?> یافته مرتبط:</p>
<?php foreach (array_slice($chatAns['insights'], 0, 8) as $i) : ?>
<div>• <strong><?= office_h((string)($i['title'] ?? '')) ?></strong> <span class="of-muted">(<?= office_h((string)($i['type_label'] ?? '')) ?>)</span></div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="of-panel">
<h3 style="margin-top:0">💬 گفت‌وگو با دستیار</h3>
<form method="post">
<?= office_csrf_field() ?>
<input type="hidden" name="action" value="chat">
<input type="text" name="message" placeholder="مثلاً: لیدهای داغ؟ درخواست‌های بدون تطبیق؟" style="min-width:min(420px,90%)" value="">
<button class="of-btn" type="submit">ارسال</button>
</form>
<?php if ($history) : ?>
<h4>تاریخچه</h4>
<?php foreach (array_reverse($history) as $h) : ?>
<div class="of-msg <?= (($h['role'] ?? '') === 'assistant') ? 'admin' : 'user' ?>"><strong><?= (($h['role'] ?? '') === 'assistant') ? '🧠 دستیار' : '👤 شما' ?>:</strong> <?= office_h((string)($h['message'] ?? '')) ?></div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<h3>📌 یافته‌های آخرین تحلیل (<?= office_num(count($insights)) ?>)</h3>
<?php if (!$insights) : ?><p class="of-muted">یافته‌ای نیست.</p><?php endif; ?>
<?php foreach (array_slice($insights, 0, 60) as $i) : ?>
<div class="of-panel">
<div><span class="of-badge"><?= office_h((string)($i['priority'] ?? '')) ?></span> <strong><?= office_h((string)($i['title'] ?? '')) ?></strong> <span class="of-muted"><?= office_h((string)($i['type_label'] ?? '')) ?></span></div>
<p><?= office_h((string)($i['what_happened'] ?? '')) ?></p>
<?php if (!empty($i['why_it_matters'])) : ?><p class="of-muted"><?= office_h((string)$i['why_it_matters']) ?></p><?php endif; ?>
<?php if (!empty($i['action'])) : ?><p>👉 <?= office_h((string)$i['action']) ?></p><?php endif; ?>
<?php if (!empty($i['facts'])) : ?>
<ul class="of-muted" style="font-size:12px">
<?php foreach (array_slice((array)$i['facts'], 0, 5) as $f) : ?><li><?= office_h((string)$f) ?></li><?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php office_shell_close(); ?>
