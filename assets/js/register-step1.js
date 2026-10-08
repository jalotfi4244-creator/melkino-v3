/* Melkino V2 — register step1 wizard. Same contract as legacy register-step1.php:
 * sessionStorage keys reg_telegram_id/reg_gender/reg_last_name/reg_phone/reg_transaction_type,
 * localStorage melkino_user_phone, Telegram WebApp contact, profile-sync.php update_contact. */
(function () {
  'use strict';
  var currentStep = 1, totalSteps = 3, isTelegramUser = false;
  var $ = function (id) { return document.getElementById(id); };

  function fa2en(v) {
    return String(v || '').replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
      .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); }).trim();
  }
  function validPhone(v) { return /^09\d{9}$/.test(fa2en(v)); }

  function loadTelegramUser() {
    try {
      var tg = window.Telegram && window.Telegram.WebApp;
      var user = tg && tg.initDataUnsafe && tg.initDataUnsafe.user;
      if (user && user.id) {
        isTelegramUser = true;
        $('regTelegramId').value = user.id;
        if (user.phone_number) {
          $('regPhone').value = user.phone_number;
          $('requestContactBtn').style.display = 'none';
        }
      }
    } catch (e) { /* ignore */ }
    if (!isTelegramUser) {
      try {
        var saved = localStorage.getItem('melkino_user_phone');
        if (saved && !$('regPhone').value) $('regPhone').value = saved;
      } catch (e) { /* ignore */ }
      $('requestContactBtn').style.display = 'none';
    }
  }

  function selectOption(btn, groupId) {
    var parent = $(groupId);
    parent.querySelectorAll('.mx-option').forEach(function (b) { b.classList.remove('is-selected'); });
    btn.classList.add('is-selected');
    var v = btn.getAttribute('data-value') || btn.textContent.trim();
    if (groupId === 'regGender') $('genderInput').value = v;
    if (groupId === 'transactionType') $('transactionTypeInput').value = v;
  }

  document.querySelectorAll('#regGender .mx-option, #transactionType .mx-option').forEach(function (b) {
    b.addEventListener('click', function () { selectOption(b, b.parentElement.id); });
  });
  $('requestContactBtn').addEventListener('click', function () {
    try { window.Telegram.WebApp.requestContact(function () {}); } catch (e) { /* ignore */ }
  });

  function saveContact(name, phone) {
    if (typeof window.melkinoSaveContact === 'function') {
      try { window.melkinoSaveContact(name, phone); return; } catch (e) { /* fallthrough */ }
    }
    try {
      fetch('profile-sync.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_contact', name: name, phone: fa2en(phone) })
      }).catch(function () {});
    } catch (e) { /* ignore */ }
  }

  function faNum(n) {
    return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; });
  }

  function show(step) {
    document.querySelectorAll('[data-rstep]').forEach(function (s) {
      s.hidden = +s.getAttribute('data-rstep') !== step;
    });
    $('stepCounter').textContent = faNum(step);
    $('prevBtn').hidden = step === 1;
    $('nextBtn').style.display = step === totalSteps ? 'none' : '';
    $('nextBtn').textContent = step === totalSteps - 1 ? 'انتخاب نوع ملک' : 'مرحله بعد';
    window.scrollTo(0, 0);
  }

  function changeStep(dir) {
    if (currentStep === 1 && dir === 1) {
      var lastName = $('regLastName').value.trim();
      var phone = $('regPhone').value.trim();
      if (!lastName) { Melkino.UI.toast('error', 'لطفاً نام خانوادگی خود را وارد کنید.'); return; }
      if (!validPhone(phone)) { Melkino.UI.toast('error', 'لطفاً یک شماره موبایل معتبر وارد کنید (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).'); return; }
      var normalized = fa2en(phone);
      $('regPhone').value = normalized;
      try { localStorage.setItem('melkino_user_phone', normalized); } catch (e) { /* ignore */ }
      saveContact(lastName, normalized);
    }
    try {
      sessionStorage.setItem('reg_telegram_id', $('regTelegramId').value);
      sessionStorage.setItem('reg_gender', $('genderInput').value);
      sessionStorage.setItem('reg_last_name', $('regLastName').value);
      sessionStorage.setItem('reg_phone', $('regPhone').value);
      sessionStorage.setItem('reg_transaction_type', $('transactionTypeInput').value);
    } catch (e) { /* ignore */ }
    if (currentStep === 2 && dir === 1 && $('transactionTypeInput').value === 'مشارکت در ساخت') {
      window.location.href = 'register-partnership.php';
      return;
    }
    if (currentStep === totalSteps && dir === 1) return;
    currentStep = Math.min(totalSteps, Math.max(1, currentStep + dir));
    show(currentStep);
  }

  $('prevBtn').addEventListener('click', function () { changeStep(-1); });
  $('nextBtn').addEventListener('click', function () { changeStep(1); });
  loadTelegramUser();
  show(1);
})();
