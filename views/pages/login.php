<?php
/** Melkino V2 — login (spec §31). Vars: $redirect,$providers */
use Melkino\UI\Icons\IconRegistry;
?>
<div class="mx-auth">
  <div class="mx-auth__logo">
    <div style="font-size:40px;font-weight:700;color:var(--mx-primary)">ملکینو</div>
    <h1 class="mx-h3 mx-mt-2">ورود به ملکینو</h1>
  </div>
  <div class="mx-form-card">
    <?php if (!empty($providers['otp'])): ?>
    <div id="mkOtpStep1">
      <div class="mx-field"><label for="mx-phone">شماره موبایل</label>
        <input class="mx-input mx-num" id="mx-phone" inputmode="tel" placeholder="۰۹…" autocomplete="tel" dir="ltr" style="text-align:center">
        <p class="mx-error" data-err></p></div>
      <button class="mx-btn mx-btn--primary mx-btn--block" type="button" id="mx-otp-send">دریافت کد</button>
    </div>
    <div id="mkOtpStep2" hidden>
      <p class="mx-text-center">کد ۶ رقمی به <b class="mx-num" id="mx-phone-echo"></b> ارسال شد.</p>
      <div class="mx-otp" dir="ltr">
        <input inputmode="numeric" maxlength="1" aria-label="رقم ۱"><input inputmode="numeric" maxlength="1" aria-label="رقم ۲">
        <input inputmode="numeric" maxlength="1" aria-label="رقم ۳"><input inputmode="numeric" maxlength="1" aria-label="رقم ۴">
        <input inputmode="numeric" maxlength="1" aria-label="رقم ۵"><input inputmode="numeric" maxlength="1" aria-label="رقم ۶">
      </div>
      <input type="hidden" data-otp-value>
      <p class="mx-error mx-text-center" data-err2></p>
      <button class="mx-btn mx-btn--primary mx-btn--block" type="button" id="mx-otp-verify">ورود</button>
      <p class="mx-text-center mx-mt-4"><button class="mx-link" type="button" id="mx-otp-resend" disabled>ارسال مجدد (۰:۰۰)</button></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($providers['telegram']) || !empty($providers['bale']) || !empty($providers['eitaa'])): ?>
    <?php if (!empty($providers['otp'])): ?><div class="mx-divider">یا</div><?php endif; ?>
    <div class="mx-providers">
      <?php if (!empty($providers['telegram'])): ?><a class="mx-provider" data-mx-auth="telegram" href="auth-telegram.php?redirect=<?= e(rawurlencode($redirect)) ?>"><?= IconRegistry::svg('send', 18) ?><span>ورود با تلگرام</span></a><?php endif; ?>
      <?php if (!empty($providers['bale'])): ?><a class="mx-provider" data-mx-auth="bale" href="auth-bale.php?redirect=<?= e(rawurlencode($redirect)) ?>"><?= IconRegistry::svg('send', 18) ?><span>ورود با بله</span></a><?php endif; ?>
      <?php if (!empty($providers['eitaa'])): ?><a class="mx-provider" href="<?= e(melkinoEitaaMiniappUrl() ?: ('eitaa-app.php?redirect=' . rawurlencode($redirect))) ?>"><?= IconRegistry::svg('send', 18) ?><span>ورود با ایتا</span></a><?php endif; ?>
    </div>
    <p class="mx-error mx-text-center" id="mx-auth-msg" style="margin-top:12px"></p>
    <?php endif; ?>
  </div>
</div>
<?php if (!empty($providers['telegram']) || !empty($providers['bale']) || !empty($providers['eitaa'])): ?>
<script<?= csp_nonce_attr() ?>>
/* ورود پیام‌رسانی: initData را با POST می‌فرستیم (GET مستقیم به auth-*.php خطای ۴۰۵ می‌دهد). */
(function () {
  var redirect = <?= json_encode($redirect, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var msgBox = document.getElementById('mx-auth-msg');
  function sdkData(p) {
    try {
      if (p === 'bale' && window.Bale && window.Bale.WebApp) return window.Bale.WebApp.initData || '';
      if (p === 'eitaa' && window.Eitaa && window.Eitaa.WebApp) return window.Eitaa.WebApp.initData || '';
      if (window.Telegram && window.Telegram.WebApp) return window.Telegram.WebApp.initData || '';
    } catch (e) {}
    return '';
  }
  function hashData() {
    // راند ۶۴: هش اولیهٔ ذخیره‌شده (ممکن است SDK دیگری هش زنده را پاک کرده باشد)، بعد هش زنده، بعد کوئری.
    try { if (window.__melkinoEarlyHash) { var er = new URLSearchParams(String(window.__melkinoEarlyHash).replace(/^#/, '')).get('tgWebAppData') || ''; if (er) return er; } } catch (e0) {}
    try {
      var h = (location.hash || '').replace(/^#/, '');
      if (h) { var lr = new URLSearchParams(h).get('tgWebAppData') || ''; if (lr) return lr; }
    } catch (e) {}
    try { var q = new URLSearchParams(location.search).get('tgWebAppData') || ''; if (q) return q; } catch (e2) {}
    try {
      var s = (sessionStorage.getItem('melkino_tg_hash') || '').replace(/^#/, '');
      if (s) return new URLSearchParams(s).get('tgWebAppData') || '';
    } catch (e3) {}
    return '';
  }
  function anySdk() {
    var t = '', b = '', ei = '';
    try { if (window.Telegram && window.Telegram.WebApp) t = window.Telegram.WebApp.initData || ''; } catch (e) {}
    try { if (window.Bale && window.Bale.WebApp) b = window.Bale.WebApp.initData || ''; } catch (e2) {}
    try { if (window.Eitaa && window.Eitaa.WebApp) ei = window.Eitaa.WebApp.initData || ''; } catch (e3) {}
    return t || b || ei || '';
  }
  function say(t) { if (msgBox) msgBox.textContent = t || ''; }
  document.querySelectorAll('[data-mx-auth]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
      ev.preventDefault();
      var plat = a.getAttribute('data-mx-auth') || 'telegram';
      // راند ۶۴: حامل داده ممکن است آبجکت دیگری باشد (قاپیدن هش)؛ چون کاربر دکمهٔ همین سکو را زده، هر رشته‌ای متعلق به همین سکوست.
      var data = sdkData(plat) || hashData() || anySdk() || '';
      if (!data) {
        say('این صفحه داخل مینی‌اپ باز نشده؛ از دکمهٔ منوی ربات وارد شوید.');
        return;
      }
      say('در حال ورود…');
      fetch('auth-' + plat + '.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ init_data: data })
      }).then(function (r) { return r.text().then(function (t) { return { s: r.status, t: t }; }); }).then(function (x) {
        var d = null;
        try { d = JSON.parse(x.t); } catch (e) {}
        if (d && d.success) {
          say('ورود موفق — در حال انتقال…');
          var url = redirect || 'profile.php';
          try {
            var u = new URL(url, location.href);
            if (d.login_token) u.searchParams.set('t', d.login_token);
            url = u.toString();
          } catch (e2) {}
          setTimeout(function () { location.replace(url); }, 350);
        } else {
          say((d && d.message) || ('ورود ناموفق بود. (کد ' + x.s + ')'));
        }
      }).catch(function () { say('اتصال برقرار نشد. دوباره تلاش کنید.'); });
    });
  });
})();
</script>
<?php endif; ?>
<?php if (!empty($providers['otp'])): ?>
<script<?= csp_nonce_attr() ?>>
(function () {
  var redirect = <?= json_encode($redirect, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var s1 = document.getElementById('mkOtpStep1'), s2 = document.getElementById('mkOtpStep2');
  var phone = document.getElementById('mx-phone');
  var err = document.querySelector('[data-err]'), err2 = document.querySelector('[data-err2]');
  var resend = document.getElementById('mx-otp-resend');
  var timer = null, left = 0;
  function tick() {
    left--;
    if (left <= 0) { resend.disabled = false; resend.textContent = 'ارسال مجدد کد'; clearInterval(timer); return; }
    var m = Math.floor(left / 60), s = left % 60;
    resend.textContent = 'ارسال مجدد (' + m + ':' + (s < 10 ? '0' : '') + s + ')';
  }
  function cooldown(sec) { left = sec; resend.disabled = true; clearInterval(timer); timer = setInterval(tick, 1000); tick(); }
  function send() {
    err.textContent = '';
    Melkino.api('request-otp.php', { method: 'POST', json: { phone: phone.value } }).then(function (res) {
      if (res && res.success) {
        s1.hidden = true; s2.hidden = false;
        document.getElementById('mx-phone-echo').textContent = phone.value;
        cooldown((res.data && res.data.cooldown) || 60);
        var first = s2.querySelector('.mx-otp input'); if (first) first.focus();
      } else { err.textContent = (res && res.message) || 'ارسال کد ناموفق بود.'; }
    }).catch(function () { err.textContent = 'اتصال برقرار نشد. دوباره تلاش کنید.'; });
  }
  document.getElementById('mx-otp-send').addEventListener('click', send);
  resend.addEventListener('click', send);
  document.getElementById('mx-otp-verify').addEventListener('click', function () {
    err2.textContent = '';
    var code = (document.querySelector('[data-otp-value]') || {}).value || '';
    Melkino.api('verify-otp.php', { method: 'POST', json: { phone: phone.value, code: code, redirect: redirect } }).then(function (res) {
      if (res && res.success) { location.href = (res.data && res.data.redirect) || redirect || 'profile.php'; }
      else { err2.textContent = (res && res.message) || 'کد اشتباه است.'; }
    }).catch(function () { err2.textContent = 'اتصال برقرار نشد. دوباره تلاش کنید.'; });
  });
})();
</script>
<?php endif; ?>
