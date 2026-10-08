/* Melkino V2 — admin sms tab boot.
 * Logic lives in root ./admin-sms.js (unchanged, panel-lazy-loaded there too).
 * Its own boot hooks switchTab/.active (absent in V2), so the V2 tab calls the
 * same four loaders directly. 7 fragment onclick -> data-act + delegation.
 */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var map = {
        'sms-token': 'smsProgGenToken', 'sms-run': 'smsProgRunNow',
        'sms-balance': 'smsProgBalance', 'sms-test': 'smsProgTest',
        'sms-outbox': 'smsProgLoadOutbox', 'sms-optout-add': 'smsProgOptoutAdd',
        'sms-save': 'smsProgSave'
    };
    var fn = map[el.getAttribute('data-act')];
    if (fn && typeof window[fn] === 'function') window[fn]();
});
document.addEventListener('DOMContentLoaded', function () {
    ['smsProgLoad', 'smsProgLoadOutbox', 'smsProgLoadOptouts', 'smsProgLoadSearches'].forEach(function (fn) {
        try { if (typeof window[fn] === 'function') window[fn](); } catch (e) {}
    });
});
