/* Melkino V2 — admin requests tab boot.
 * Top-level globals use the panel's exact names (admin-requests.js reads bare
 * `requestsData`/`adsData`); enrichment chunks come from the panel's own
 * ads_chunk endpoint (same normalized shape as the panel bootstrap).
 */
let requestsData = [];
let adsData = [];

(function () {
    try {
        var el = document.getElementById('mxAdminReqData');
        if (el) {
            requestsData = JSON.parse(el.textContent || '{}').rows || [];
        }
    } catch (e) {
        requestsData = [];
    }

    /* Modal buttons (replaces 5 inline onclick in the fragment). */
    document.addEventListener('click', function (ev) {
        var t = ev.target && ev.target.closest ? ev.target.closest('[data-act]') : null;
        if (!t) {
            return;
        }
        var act = t.getAttribute('data-act');
        if (act === 'rq-open-create' && typeof openRequestCreate === 'function') {
            openRequestCreate();
        } else if (act === 'rq-close' && typeof closeModal === 'function') {
            closeModal(t.getAttribute('data-modal'));
        } else if (act === 'rq-save-create' && typeof saveRequestCreate === 'function') {
            saveRequestCreate();
        } else if (act === 'rq-save-edit' && typeof saveRequestEdit === 'function') {
            saveRequestEdit();
        }
    });

    /* Ads enrichment, then first render (panel loads newest ads with the page
     * and the rest on demand; here chunks arrive before the first render). */
    function step(offset) {
        fetch('admin-panel.php?action=ads_chunk&offset=' + offset + '&limit=200', {
            cache: 'no-store',
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                var arr = (j && j.ads) || [];
                for (var i = 0; i < arr.length; i++) {
                    adsData.push(arr[i]);
                }
                if (j && j.success && j.hasMore) {
                    step(adsData.length);
                    return;
                }
                if (typeof renderRequests === 'function') {
                    renderRequests();
                }
            })
            .catch(function () {
                if (typeof renderRequests === 'function') {
                    renderRequests();
                }
            });
    }

    function init() {
        step(0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
