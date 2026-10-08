<?php
/** Melkino V2 — detail (spec §18). Vars: $identity,$detail,$similar,$is_favorite,$in_compare */
use Melkino\UI\Card\PropertyCardRenderer;
use Melkino\UI\Icons\IconRegistry;

$p = $detail['property'];
$consultPhone = '';
try { $consultPhone = function_exists('getConsultantPhone') ? (string)@getConsultantPhone() : ''; } catch (\Throwable $ignored) {}
$gallery = $detail['gallery'];
?>
<div class="mx-detail mx-detail__pad">
  <div>
    <section aria-label="گالری تصاویر">
      <?php if ($gallery): ?>
      <button type="button" class="mx-gallery__main" data-gallery-main aria-label="مشاهده تمام‌صفحه تصویر">
        <img src="<?= e($gallery[0]['src']) ?>" alt="<?= e($gallery[0]['alt']) ?>" width="1200" height="750" fetchpriority="high">
      </button>
      <?php if (count($gallery) > 1): ?>
      <div class="mx-gallery__thumbs" role="group" aria-label="تصاویر">
        <?php foreach (array_slice($gallery, 0, 8) as $i => $g): ?>
        <button type="button" data-gallery-thumb data-full="<?= e($g['src']) ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="تصویر <?= fa($i + 1) ?>">
          <img src="<?= e($g['src']) ?>" alt="" loading="lazy" width="300" height="225">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="mx-gallery__main"><img src="assets/defaults/property-placeholder.svg" alt="<?= e($p['title']) ?>" width="1200" height="750"></div>
      <?php endif; ?>
    </section>

    <section class="mx-mt-4" aria-label="مشخصات اصلی">
      <div class="mx-mb-2"><span class="mx-badge"><?= e($p['transaction'] !== '' ? $p['transaction'] : $p['type']) ?></span></div>
      <h1 class="mx-h2"><?= e($p['title']) ?></h1>
      <?php if ($detail['location']['text'] !== ''): ?>
      <p class="mx-text-muted"><?= IconRegistry::svg('pin', 16) ?> <?= e($detail['location']['text']) ?></p>
      <?php endif; ?>
      <p class="mx-h2 mx-mt-2" style="color:var(--mx-primary-700)"><?= e($detail['pricing']['label']) ?></p>
    </section>

    <section class="mx-mt-4 mx-hide-md" aria-label="اقدامات">
      <div class="mx-flex mx-gap-2">
        <?php if ($consultPhone !== ''): ?>
        <a class="mx-btn mx-btn--primary mx-btn--block" href="tel:<?= e(preg_replace('/\D/', '', $consultPhone)) ?>"><?= IconRegistry::svg('phone', 18) ?><span>تماس</span></a>
        <?php endif; ?>
        <button class="mx-btn mx-btn--secondary mx-btn--block" type="button" data-modal-open="mkVisit">درخواست بازدید</button>
      </div>
    </section>

    <?php if ($detail['facts']): ?>
    <section class="mx-mt-6" aria-label="ویژگی‌های کلیدی"><div class="mx-facts">
      <?php foreach ($detail['facts'] as $f): ?>
      <div><b><?= e($f['value']) ?></b><span><?= e($f['label']) ?></span></div>
      <?php endforeach; ?>
    </div></section>
    <?php endif; ?>

    <?php if ($p['description'] !== ''): ?>
    <section class="mx-mt-6" aria-label="درباره ملک">
      <h2 class="mx-h3 mx-mb-2">درباره ملک</h2>
      <p><?= nl2br(e($p['description'])) ?></p>
    </section>
    <?php endif; ?>

    <?php if ($detail['amenities']): ?>
    <section class="mx-mt-6" aria-label="امکانات">
      <h2 class="mx-h3 mx-mb-2">امکانات</h2>
      <ul class="mx-amenities">
        <?php foreach ($detail['amenities'] as $a): ?>
        <li><?= IconRegistry::svg('check', 14) ?><span><?= e((string)$a) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <?php if (!empty($detail['specs'])): ?>
    <section class="mx-mt-6" aria-label="مشخصات کامل">
      <h2 class="mx-h3 mx-mb-2">مشخصات کامل</h2>
      <table class="mx-spec-table"><tbody>
        <?php foreach ($detail['specs'] as $s): ?>
        <tr><th><?= e($s['label']) ?></th><td><?= e($s['value']) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </section>
    <?php endif; ?>

    <?php if ($detail['location']['lat'] !== null): ?>
    <section class="mx-mt-6" aria-label="موقعیت">
      <h2 class="mx-h3 mx-mb-2">موقعیت</h2>
      <p class="mx-small">مختصات: <?= fa($detail['location']['lat']) ?>، <?= fa($detail['location']['lng']) ?></p>
    </section>
    <?php endif; ?>
  </div>

  <aside class="mx-sticky-cta mx-hide-sm" aria-label="تماس و بازدید">
    <p class="mx-price"><?= e($detail['pricing']['label']) ?></p>
    <?php if ($consultPhone !== ''): ?>
    <a class="mx-btn mx-btn--primary mx-btn--block mx-mb-2" href="tel:<?= e(preg_replace('/\D/', '', $consultPhone)) ?>"><?= IconRegistry::svg('phone', 18) ?><span>تماس</span></a>
    <?php endif; ?>
    <button class="mx-btn mx-btn--secondary mx-btn--block" type="button" data-modal-open="mkVisit">درخواست بازدید</button>
    <div class="mx-flex mx-gap-2 mx-mt-4">
      <button class="mx-btn mx-btn--ghost mx-btn--sm" type="button" data-fav-toggle="<?= (int)$p['id'] ?>" aria-pressed="<?= $is_favorite ? 'true' : 'false' ?>">
        <?= IconRegistry::svg($is_favorite ? 'heart-fill' : 'heart', 16) ?><span>ذخیره</span>
      </button>
      <button class="mx-btn mx-btn--ghost mx-btn--sm" type="button" data-compare-toggle="<?= (int)$p['id'] ?>" aria-pressed="<?= $in_compare ? 'true' : 'false' ?>">
        <?= IconRegistry::svg('compare', 16) ?><span>مقایسه</span>
      </button>
    </div>
    <p class="mx-tiny mx-mt-4">کد آگهی: <?= e($p['code'] !== '' ? $p['code'] : (string)$p['id']) ?> · بازدید: <?= fa($detail['metadata']['views']) ?></p>
  </aside>
</div>

<?php if ($similar): ?>
<section class="mx-section" aria-label="فایل‌های مشابه">
  <div class="mx-section__head"><h2>فایل‌های مشابه</h2></div>
  <?= PropertyCardRenderer::grid($similar) ?>
</section>
<?php endif; ?>

<div class="mx-mobile-bar">
  <?php if ($consultPhone !== ''): ?>
  <a class="mx-btn mx-btn--primary mx-btn--sm" href="tel:<?= e(preg_replace('/\D/', '', $consultPhone)) ?>"><?= IconRegistry::svg('phone', 16) ?><span>تماس</span></a>
  <?php endif; ?>
  <button class="mx-btn mx-btn--secondary mx-btn--sm" type="button" data-modal-open="mkVisit">بازدید</button>
  <button class="mx-btn mx-btn--ghost mx-btn--sm" type="button" data-fav-toggle="<?= (int)$p['id'] ?>" aria-pressed="<?= $is_favorite ? 'true' : 'false' ?>"><?= IconRegistry::svg('heart', 16) ?><span>ذخیره</span></button>
</div>

<div class="mx-modal" id="mkVisit" role="dialog" aria-modal="true" aria-label="درخواست بازدید">
  <div class="mx-modal__backdrop"></div>
  <form class="mx-modal__dialog" action="api/properties/request-visit.php" method="post" data-guard>
    <?= \Melkino\Core\Csrf::field() ?>
    <input type="hidden" name="ad_id" value="<?= (int)$p['id'] ?>">
    <div class="mx-modal__header"><h3>درخواست بازدید</h3>
      <button type="button" class="mx-icon-btn" data-modal-close aria-label="بستن"><?= IconRegistry::svg('x', 20) ?></button>
    </div>
    <div class="mx-modal__body">
      <p class="mx-small mx-mb-4">نام و شماره از حساب تأییدشده شما استفاده می‌شود.</p>
      <div class="mx-field"><label for="v-date">روز بازدید</label>
        <select class="mx-select" id="v-date" name="preferred_date" required data-visit-days>
          <option value="">در حال بارگذاری روزها…</option>
        </select></div>
      <div class="mx-field"><label for="v-slot">بازه زمانی</label>
        <select class="mx-select" id="v-slot" name="time_slot" required>
          <option value="">انتخاب کنید</option>
          <option value="morning">صبح</option>
          <option value="evening">عصر</option>
        </select></div>
      <div class="mx-field"><label for="v-alt">توضیح اضافه (اختیاری)</label>
        <input class="mx-input" id="v-alt" name="alternative_datetime" maxlength="500"></div>
      <p class="mx-error" data-visit-err style="display:none"></p>
    </div>
    <script<?= csp_nonce_attr() ?>>
    (function () {
      var sel = document.querySelector('[data-visit-days]');
      var form = sel ? sel.closest('form') : null;
      if (!sel || !form) return;
      fetch('visit-request-api.php?action=days', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
        sel.innerHTML = '<option value="">انتخاب کنید</option>';
        var slots = (res && res.slots) || {};
        var slotSel = form.querySelector('[name="time_slot"]');
        if (slotSel && Object.keys(slots).length) {
          slotSel.innerHTML = '<option value="">انتخاب کنید</option>' + Object.keys(slots).map(function (k) {
            return '<option value="' + k + '">' + slots[k] + '</option>';
          }).join('');
        }
        ((res && res.days) || []).forEach(function (d) {
          if (d.selectable === false || d.closed || d.full) return;
          var o = document.createElement('option');
          o.value = d.date; o.textContent = d.label || d.date;
          sel.appendChild(o);
        });
      }).catch(function () { sel.innerHTML = '<option value="">خطا در بارگذاری روزها</option>'; });
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var err = form.querySelector('[data-visit-err]');
        var fd = new FormData(form);
        fd.append('__json', '1');
        fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (res) {
          if (res && res.success) {
            Melkino.UI.toast('success', res.message || 'درخواست بازدید ثبت شد.');
            Melkino.UI.closeModal('mkVisit');
          } else if (err) { err.style.display = ''; err.textContent = (res && res.message) || 'ثبت نشد.'; }
        }).catch(function () { if (err) { err.style.display = ''; err.textContent = 'اتصال برقرار نشد.'; } });
      });
    })();
    </script>
    <div class="mx-modal__footer">
      <button type="button" class="mx-btn mx-btn--ghost" data-modal-close>انصراف</button>
      <button class="mx-btn mx-btn--primary" type="submit">ثبت درخواست</button>
    </div>
  </form>
</div>
