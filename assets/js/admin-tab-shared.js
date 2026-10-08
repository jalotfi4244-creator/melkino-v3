/* Melkino V2 — shared admin-tab helpers, extracted VERBATIM from admin-panel.php
 * (escapeHtml block + closeModal). Loaded before per-tab scripts. */
if (typeof window.escapeHtml !== 'function') {
    window.escapeHtml = function (value) {
        return String(value === null || value === undefined ? '' : value).replace(/[&<>'"]/g, function (ch) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[ch];
        });
    };
}

function closeModal(id) {
    const element = document.getElementById(id);
    if (element) {
        element.classList.remove('active');
    }
}
