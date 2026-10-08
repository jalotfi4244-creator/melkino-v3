<?php
/** Melkino V2 — support (ticket list + chat + new ticket; same POST contract as legacy). */
use Melkino\Http\Controllers\SupportController;
$tickets = $tickets ?? [];
$active = $active ?? null;
$messages = $messages ?? [];
?>
<div class="mx-page-head"><h1>پشتیبانی</h1><p class="mx-small">گفتگو با تیم پشتیبانی ملکینو.</p></div>

<?php if (!$active): ?>
<div class="mx-list">
  <?php foreach ($tickets as $t): ?>
  <a class="mx-card-surface mx-p-4 mx-ticket" href="support.php?ticket=<?= (int)$t['id'] ?>">
    <b><?= e((string)($t['subject'] ?? 'بدون موضوع')) ?></b>
    <p class="mx-tiny mx-muted mx-mt-2"><?= fa((string)($t['message_count'] ?? 0)) ?> پیام · <?= e(SupportController::statusLabel((string)($t['status'] ?? ''))) ?> · <?= fa(substr((string)($t['updated_at'] ?? ''), 0, 16)) ?></p>
  </a>
  <?php endforeach; ?>
  <?php if (!$tickets): ?>
  <?= melkinoPartial('empty-state.php', ['icon' => 'chat', 'title' => 'هنوز درخواستی ثبت نکرده‌اید.', 'text' => 'از فرم زیر اولین درخواست خود را برای پشتیبانی ارسال کنید.']) ?>
  <?php endif; ?>
</div>

<section class="mx-card-surface mx-p-4 mx-mt-4">
  <h2 class="mx-h3 mx-mb-4">درخواست جدید</h2>
  <form id="mxTicketNew" method="post" action="support.php">
    <?= \Melkino\Core\Csrf::field() ?>
    <input type="hidden" name="action" value="create_ticket">
    <div class="mx-field"><label for="mxTicketSubject">موضوع درخواست</label>
      <input class="mx-input" id="mxTicketSubject" name="subject" required maxlength="255"></div>
    <div class="mx-field"><label for="mxTicketMsg">مشکل یا سوال شما</label>
      <textarea class="mx-textarea" id="mxTicketMsg" name="message" required maxlength="10000" rows="4"></textarea></div>
    <button class="mx-btn mx-btn--primary" type="submit">ارسال برای پشتیبانی</button>
  </form>
</section>
<?php else: ?>
<p class="mx-mb-4"><a class="mx-link" href="support.php">› بازگشت به همه درخواست‌ها</a></p>
<div class="mx-card-surface mx-p-4">
  <div class="mx-visit__head">
    <b><?= e((string)($active['subject'] ?? 'بدون موضوع')) ?></b>
    <span class="mx-badge"><?= e(SupportController::statusLabel((string)($active['status'] ?? ''))) ?></span>
  </div>
  <div class="mx-chat mx-mt-4" id="mxChat">
    <?php foreach ($messages as $m): ?>
    <?php $mine = ($m['sender_type'] ?? '') === 'user'; ?>
    <div class="mx-msg <?= $mine ? 'mx-msg--user' : 'mx-msg--admin' ?>">
      <div><?= nl2br(e((string)($m['message'] ?? ''))) ?></div>
      <div class="mx-msg__meta"><?= fa(substr((string)($m['created_at'] ?? ''), 0, 16)) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (!$messages): ?><p class="mx-muted mx-small">هنوز پیامی ثبت نشده است.</p><?php endif; ?>
  </div>
  <?php if (($active['status'] ?? '') !== 'closed'): ?>
  <form id="mxMsgForm" class="mx-mt-4" method="post" action="support.php">
    <?= \Melkino\Core\Csrf::field() ?>
    <input type="hidden" name="action" value="send_message">
    <input type="hidden" name="ticket_id" value="<?= (int)$active['id'] ?>">
    <div class="mx-field"><label for="mxMsgText">پیام جدید</label>
      <textarea class="mx-textarea" id="mxMsgText" name="message" required maxlength="10000" rows="3"></textarea></div>
    <div class="mx-flex" style="gap:8px">
      <button class="mx-btn mx-btn--primary" type="submit">ارسال پیام</button>
      <button class="mx-btn mx-btn--ghost" type="button" id="mxTicketClose" data-ticket="<?= (int)$active['id'] ?>">بستن درخواست</button>
    </div>
  </form>
  <?php else: ?>
  <p class="mx-tiny mx-muted mx-mt-4">این درخواست بسته شده است. برای مشکل جدید یک درخواست جدید ایجاد کنید.</p>
  <?php endif; ?>
</div>
<?php endif; ?>
