/* Melkino V2 — index landing body-end block VERBATIM (binds goHomeBtn/skipBtn; must run after DOM). */
(function () {
    function goHome() { window.location.href = 'home.php'; }
    var a = document.getElementById('goHomeBtn');
    var b = document.getElementById('skipBtn');
    if (a) a.addEventListener('click', goHome);
    if (b) b.addEventListener('click', goHome);
})();
