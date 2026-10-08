/* Melkino V2 — onboarding flow (same steps/dots/redirect contract as legacy). */
(function () {
  'use strict';
  var payload = { steps: [], logo: '', firstLogo: '', firstTitle: '', firstText: '' };
  try {
    var el = document.getElementById('mxOnboardData');
    if (el) payload = Object.assign(payload, JSON.parse(el.textContent || '{}'));
  } catch (e) { /* ignore */ }
  var steps = payload.steps || [];
  var currentStep = 0, totalSteps = steps.length;
  var titleEl = document.getElementById('stepTitle');
  var textEl = document.getElementById('stepText');
  var logoBox = document.getElementById('logoContainer');
  var btnEl = document.getElementById('actionBtn');
  var dotsBox = document.getElementById('dotsContainer');

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
  }
  function renderStep(index) {
    var step = steps[index] || {};
    var isFirst = index === 0;
    if (isFirst) {
      titleEl.textContent = payload.firstTitle || step.title || 'به ملکینو خوش آمدید';
      textEl.textContent = payload.firstText || step.text || '';
    } else {
      titleEl.textContent = step.title || 'بدون عنوان';
      textEl.textContent = step.text || 'بدون توضیحات';
    }
    if (isFirst && payload.firstLogo) {
      logoBox.innerHTML = '<img src="' + esc(payload.firstLogo) + '" alt="لوگو">';
    } else if (!isFirst && payload.logo) {
      logoBox.innerHTML = '<img src="' + esc(payload.logo) + '" alt="لوگو">';
    } else if (isFirst) {
      logoBox.innerHTML = '';
    } else {
      logoBox.innerHTML = '<span class="mx-onboard__icon">' + esc(step.icon || '🏠') + '</span>';
    }
    dotsBox.querySelectorAll('.dot').forEach(function (dot, i) {
      dot.classList.toggle('active', i === index);
    });
    if (index === totalSteps - 1) {
      btnEl.textContent = 'ورود به خانه';
      btnEl.onclick = function () { window.location.href = 'home.php'; };
    } else {
      btnEl.textContent = 'مرحله بعد';
      btnEl.onclick = function () { nextStep(); };
    }
  }
  function nextStep() {
    if (currentStep < totalSteps - 1) { currentStep++; renderStep(currentStep); }
  }
  if (totalSteps > 0) {
    dotsBox.innerHTML = '';
    for (var i = 0; i < totalSteps; i++) {
      var dot = document.createElement('div');
      dot.className = 'dot' + (i === 0 ? ' active' : '');
      dotsBox.appendChild(dot);
    }
    renderStep(0);
  } else {
    window.location.href = 'home.php';
  }
})();
