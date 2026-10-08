<?php
/** Melkino V2 — matches (spec §22, §25). Vars: $requests,$active,$matches */
use Melkino\UI\Card\PropertyCardRenderer;
use Melkino\UI\Icons\IconRegistry;

if (!$active) {
    echo \Melkino\UI\EmptyStates::render('doc', 'درخواست فعالی ندارید', 'اول نیازتان را ثبت کنید تا ملک‌های مناسب را پیدا کنیم.', 'property-request.php', 'ثبت درخواست ملک');
    return;
}
?>
<div class="mx-page-head"><h1>فایل‌های مناسب من</h1></div>
<div class="mx-req-summary">
  <div class="mx-flex mx-justify-between mx-items-center mx-mb-2">
    <b>درخواست فعال شما</b>
    <a class="mx-link" href="property-request.php">ویرایش درخواست</a>
  </div>
  <dl>
    <div><dt>نوع معامله</dt><dd><?= e((string)($active['transaction_type'] ?? '')) ?></dd></div>
    <div><dt>نوع ملک</dt><dd><?= e((string)($active['property_type'] ?? '')) ?></dd></div>
    <div><dt>موقعیت</dt><dd><?= e((string)($active['location'] ?? '')) ?></dd></div>
    <div><dt>کد پیگیری</dt><dd class="mx-num"><?= e((string)($active['tracking_code'] ?? '')) ?></dd></div>
  </dl>
  <?php if (count($requests) > 1): ?>
  <form action="my-request-matches.php" method="get" class="mx-mt-2 mx-flex mx-gap-2">
    <select class="mx-select" name="request_id" aria-label="انتخاب درخواست">
      <?php foreach ($requests as $r): ?>
      <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)$active['id'] ? 'selected' : '' ?>>
        <?= e((string)($r['tracking_code'] ?? ('#' . $r['id']))) ?> — <?= e((string)($r['property_type'] ?? '')) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button class="mx-btn mx-btn--secondary" type="submit">نمایش</button>
  </form>
  <?php endif; ?>
</div>

<p class="mx-mb-4"><b><?= fa(count($matches)) ?> ملک پیدا شده</b></p>
<?php if (!$matches): ?>
<?= \Melkino\UI\EmptyStates::render('search', 'هنوز ملک مناسبی پیدا نشده', 'به‌محض پیدا شدن، همین‌جا و با اعلان خبرتان می‌کنیم.', '', '') ?>
<?php else: ?>
<div class="mx-grid">
<?php foreach ($matches as $m): ?>
  <div>
    <p class="mx-mb-2"><span class="mx-match"><?= IconRegistry::svg('spark', 16) ?> <?= fa((int)($m['match_percent'] ?? 0)) ?>٪ تطابق</span></p>
    <?= PropertyCardRenderer::render($m) ?>
    <?php if (!empty($m['breakdown'])): ?>
    <ul class="mx-amenities mx-mt-2">
      <?php foreach ($m['breakdown'] as $b): ?>
      <li><?= IconRegistry::svg(((float)$b['score'] >= 0.6) ? 'check' : 'warn', 14) ?><span><?= e($b['label']) ?> <?= fa((int)((float)$b['score'] * 100)) ?>٪</span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <details class="mx-mt-2"><summary class="mx-link" style="cursor:pointer">مناسب نیست؟ دلیل را بگویید</summary>
      <form action="my-request-matches.php" method="post" class="mx-mt-2 mx-feedback-reasons" data-guard>
        <?= \Melkino\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="feedback">
        <input type="hidden" name="match_id" value="<?= (int)($m['match_id'] ?? 0) ?>">
        <?php foreach (['price_high' => 'قیمت زیاد است', 'bad_area' => 'منطقه مناسب نیست', 'small_area' => 'متراژ کم است', 'few_rooms' => 'اتاق کم است', 'found_other' => 'ملک مشابه پیدا کردم', 'other' => 'دلیل دیگر'] as $k => $v): ?>
        <button class="mx-chip" type="submit" name="feedback" value="<?= e($k) ?>"><?= e($v) ?></button>
        <?php endforeach; ?>
      </form>
    </details>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
