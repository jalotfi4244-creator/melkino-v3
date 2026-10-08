/* Melkino V2 — contact admin tab JS VERBATIM from admin-panel.php inline region
 * (L6202-7422: settings + custom cards + bale/map uploads + office map +
 * live preview + consultants) + MK_IC block from panel.
 * 18 template + 8 fragment handlers -> data-* + delegation (verbatim behaviour).
 */
window.MK_IC = Object.assign(window.MK_IC || {}, {
            megaphone: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10v4l11 5V5z"/><path d="M14 8a4 4 0 0 1 0 8"/><path d="M6 14v5"/></svg>',
            trash: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/></svg>',
            chat: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>',
            plus: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
            search: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
            send: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 3 3 10.5l7 3 3 7z"/><path d="M21 3 10 13.5"/></svg>',
            star: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.8 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 3 1.2-6.1L3 9.6l6.3-.8z"/></svg>',
            bank: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18M12 3 3 10h18z"/></svg>',
            coins: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>',
            home: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11l8-7 8 7"/><path d="M6 9.5V21h12V9.5"/></svg>',
    save:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h11l3 3v15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M8 3v5h7V3"/><path d="M8 21v-7h8v7"/></svg>',
    eye:      '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>',
    x:        '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    upload:   '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 20h14"/></svg>',
    download: '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
    bulb:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-4 10.5c.8.7 1 1.5 1 2.5h6c0-1 .2-1.8 1-2.5A6 6 0 0 0 12 3Z"/></svg>',
    lock:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
    gear:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/></svg>',
    user:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>',
    users:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.4 2.9-5.5 6.5-5.5s6.5 2.1 6.5 5.5"/><circle cx="17" cy="9" r="3"/><path d="M17.5 14.6c2.4.5 4 2.2 4 4.4"/></svg>',
    phone:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>',
    headset:  '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/></svg>',
    puzzle:   '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg>',
    map:      '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/></svg>',
    image:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-4.5-4.5L7 21"/></svg>',
    list:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>',
    edit:     '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>',
    globe:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18Z"/></svg>',
    check:    '<svg class="mk-icon mk-icon--sm" width="16" height="16" viewBox="0 0 24 24" style="vertical-align:-3px;" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 5-5.5"/></svg>'
});

const MELKINO_CONTACT_STORAGE_KEY =
    'melkino_contact_info';


/* =========================================================
   هلپرهای فیلدهای اطلاعات تماس
   (قبلاً این توابع تعریف نشده بودند و تب «ارتباط با ما»
   با خطای ReferenceError می‌شکست؛ فرم کارت‌ها رندر نمی‌شد)
   ========================================================= */

function getContactFieldValue(id) {

    const el =
        document.getElementById(id);

    if (!el) {
        return '';
    }

    return String(
        el.value ?? ''
    ).trim();
}


function setContactFieldValue(id, value) {

    const el =
        document.getElementById(id);

    if (!el) {
        return;
    }

    el.value =
        value === null ||
        value === undefined
            ? ''
            : String(value);
}


function updateContactPreview() {

    const pairs = [
        ['previewAgencyName', 'adminContactAgencyName'],
        ['previewPhone', 'adminContactPhone'],
        ['previewTelegram', 'adminContactTelegram'],
        ['previewInstagram', 'adminContactInstagram']
    ];

    pairs.forEach(function (pair) {

        const previewEl =
            document.getElementById(pair[0]);

        if (!previewEl) {
            return;
        }

        const value =
            getContactFieldValue(pair[1]);

        previewEl.textContent =
            value !== ''
                ? value
                : '-';
    });
}


/* =========================================================
   تصویر نقشه دفتر (جایگزین نقشه زنده نشان)
   ادمین از نقشه اسکرین‌شات می‌گیرد و اینجا آپلود می‌کند؛
   همین عکس در صفحه «ارتباط با ما» نمایش داده می‌شود.
   ========================================================= */

let mapImagePath = '';
let baleLogoPath = '';

function setBaleLogoPreview(path) {
    baleLogoPath = String(path || '');
    const img = document.getElementById('adminBaleLogoPreview');
    if (!img) return;
    if (baleLogoPath) {
        img.src = baleLogoPath;
        img.style.display = 'block';
    } else {
        img.removeAttribute('src');
        img.style.display = 'none';
    }
}
async function uploadBaleLogo() {
    const input = document.getElementById('adminBaleLogoFile');
    const file = input && input.files && input.files[0];
    if (!file) { alert('اول یک تصویر انتخاب کن.'); return; }
    const fd = new FormData();
    fd.append('icon', file);
    if (window.MELKINO_CSRF) fd.append('csrf_token', window.MELKINO_CSRF);
    try {
        const r = await fetch('upload-card-icon.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j && j.success && j.path) {
            setBaleLogoPreview(j.path);
        } else {
            alert((j && j.message) || 'آپلود لوگو ناموفق بود.');
        }
    } catch (e) {
        alert('خطا در آپلود لوگو.');
    }
}
function removeBaleLogo() {
    setBaleLogoPreview('');
}


function setMapImagePreview(path, quiet) {

    mapImagePath =
        String(path || '');

    const img =
        document.getElementById(
            'adminMapImagePreview'
        );

    if (img) {

        if (mapImagePath !== '') {
            img.src = mapImagePath;
            img.style.display = 'block';
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
        }
    }

    if (!quiet) {

        const state =
            document.getElementById(
                'mapImageState'
            );

        if (state) {
            state.textContent =
                mapImagePath !== ''
                    ? 'تصویر نقشه انتخاب شده است. با دکمه «ذخیره» پایین صفحه ذخیره‌اش کن.'
                    : '';
        }
    }
}


async function uploadMapImage() {

    const input =
        document.getElementById(
            'adminMapImageFile'
        );

    const file =
        input &&
        input.files &&
        input.files[0];

    if (!file) {
        alert('اول یک عکس انتخاب کن.');
        return;
    }

    const state =
        document.getElementById(
            'mapImageState'
        );

    if (state) {
        state.textContent = 'در حال آپلود…';
    }

    const formData =
        new FormData();

    formData.append('map', file);

    try {

        const response =
            await fetch(
                'upload-contact-map.php',
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            );

        const result =
            await response.json();

        if (result && result.success) {
            setMapImagePreview(result.path);
        } else if (state) {
            state.textContent =
                'آپلود ناموفق بود: ' +
                (
                    (result && result.message) ||
                    'خطای ناشناخته'
                );
        }

    } catch (error) {

        console.error(
            'upload map image error:',
            error
        );

        if (state) {
            state.textContent =
                'خطا در ارتباط با سرور.';
        }
    }
}


function removeMapImage() {

    const input =
        document.getElementById(
            'adminMapImageFile'
        );

    if (input) {
        input.value = '';
    }

    setMapImagePreview('');

    const state =
        document.getElementById(
            'mapImageState'
        );

    if (state) {
        state.textContent =
            'تصویر حذف شد. با دکمه «ذخیره» پایین صفحه ثبتش کن.';
    }
}

function getDefaultContactSettings() {

    return {

        agencyName:
            'املاک ملکینو شاهرود',

        address:
            '',

        phone:
            '',

        email:
            '',

        whatsapp:
            '',

        telegram:
            '',

        instagram:
            '',

        workingHours:
            '',

        mapImage:
            '',

        officeLat: '',
        officeLng: '',
        officeZoom: 15,

        bale:
            '',

        telegramColor: '#174D46',
        instagramColor: '#4B3D32',
        baleColor: '#4AB06A',
        telegramDescription: 'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو',
        instagramDescription: 'تصاویر، فایل‌ها و محتوای اختصاصی ملکینو',
        baleDescription: 'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو',

        customCards: []
    };
}


let customCardsData = [];


function customCardsDefaultArray() {
    return [];
}
function customCardBlank() {
    return { label: '', messenger: '', url: '', icon: '', description: '', color: '#2A4A62' };
}
function addCustomCard() {
    if (customCardsData.length >= 8) { alert('حداکثر ۸ کارت سفارشی.'); return; }
    customCardsData.push(customCardBlank());
    renderCustomCards();
}
function removeCustomCard(index) {
    customCardsData.splice(index, 1);
    renderCustomCards();
}


function customCardEsc(value) {

    return String(
        value ?? ''
    ).replace(/[&<>"']/g, function (ch) {

        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[ch];
    });
}


function updateCustomCard(index, key, value) {

    if (!customCardsData[index]) {

        customCardsData[index] = {
            label: '',
            messenger: '',
            url: '',
            icon: '',
            description: '',
            color: '#2A4A62'
        };
    }

    customCardsData[index][key] = value;
}


function renderCustomCards() {

    const host =
        document.getElementById(
            'customCardsManager'
        );

    if (!host) {
        return;
    }

    let html = '';
    if (!Array.isArray(customCardsData)) customCardsData = [];

    for (let i = 0; i < customCardsData.length; i++) {

        const card =
            customCardsData[i] ||
            { label: '', messenger: '', url: '', icon: '', description: '', color: '#2A4A62' };

        const previewIcon =
            card.icon
                ? '<img src="' + customCardEsc(card.icon) + '" alt="" style="width:34px;height:34px;object-fit:contain;border-radius:8px;background:var(--bg-secondary);flex-shrink:0">'
                : '<div style="width:34px;height:34px;border-radius:8px;background:var(--bg-secondary);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px">' + MK_IC.globe + '</div>';

        html +=
            '<div class="consultant-item" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;align-items:end;padding:14px;border:1px solid var(--border);border-radius:12px;margin-bottom:12px">' +

            '<div class="admin-field">' +
            '<label>کارت ' + (i + 1) + ' — متنِ نمایشی</label>' +
            '<input placeholder="مثال: کانال ایتا" value="' + customCardEsc(card.label) + '" data-cc="1" data-i="' + i + '" data-k="label">' +
            '</div>' +

            '<div class="admin-field">' +
            '<label>نام پیام‌رسان</label>' +
            '<input placeholder="مثال: ایتا" value="' + customCardEsc(card.messenger) + '" data-cc="1" data-i="' + i + '" data-k="messenger">' +
            '</div>' +

            '<div class="admin-field">' +
            '<label>آدرسِ کانال / لینک</label>' +
            '<input dir="ltr" placeholder="https://eitaa.com/..." value="' + customCardEsc(card.url) + '" data-cc="1" data-i="' + i + '" data-k="url">' +
            '</div>' +

            '<div class="admin-field">' +
            '<label>آیکونِ پیام‌رسان</label>' +
            '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">' +
            previewIcon +
            '<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-cc-file="1" data-i="' + i + '" style="font-size:11px;max-width:170px">' +
            '</div>' +
            '</div>' +

            '<div class="admin-field" style="grid-column:1/-1">' +
            '<label>خط سوم کارت (توضیح زیر نام)</label>' +
            '<input placeholder="مثلاً مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو" value="' + customCardEsc(card.description) + '" data-cc="1" data-i="' + i + '" data-k="description">' +
            '</div>' +
            '<div class="admin-field">' +
            '<label>رنگ کارت</label>' +
            '<input type="color" value="' + customCardEsc(card.color || '#2A4A62') + '" data-cc="1" data-i="' + i + '" data-k="color" style="width:52px;height:36px;padding:2px;border:1px solid var(--border);border-radius:8px;background:transparent">' +
            '</div>' +
            '<div class="admin-field"><button type="button" class="btn-secondary" data-cc-del="' + i + '">حذف کارت</button></div>' +

            '</div>';
    }

    html += '<button type="button" class="btn-icon-sm gold" data-act="ct-add-card" style="min-width:160px;height:44px;">＋ افزودن کارت سفارشی</button>';
    host.innerHTML = html;
}


async function uploadCustomCardIcon(index, input) {

    const file =
        input.files && input.files[0];

    if (!file) {
        return;
    }

    const formData =
        new FormData();

    formData.append(
        'icon',
        file
    );

    try {

        const response =
            await fetch(
                'upload-card-icon.php',
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            );

        const result =
            await response.json();

        if (result && result.success) {

            updateCustomCard(
                index,
                'icon',
                result.path
            );

            renderCustomCards();

        } else {

            alert(
                'آپلود آیکون ناموفق بود: ' +
                (result && result.message
                    ? result.message
                    : 'خطای ناشناخته')
            );
        }

    } catch (error) {

        console.error(
            'upload card icon error:',
            error
        );

        alert(
            'خطا در ارتباط با سرور هنگام آپلود آیکون.'
        );
    }
}


async function loadContactSettingsData() {

    const defaults =
        getDefaultContactSettings();


    let merged =
        Object.assign({}, defaults);


    /* ۱) ابتدا localStorage (سازگاری با نسخه‌های قبل) */

    try {

        const saved =
            localStorage.getItem(
                MELKINO_CONTACT_STORAGE_KEY
            );

        if (saved) {

            const parsed =
                JSON.parse(saved);

            if (parsed && typeof parsed === 'object') {

                merged =
                    Object.assign(
                        {},
                        merged,
                        parsed
                    );
            }
        }

    } catch (error) {

        console.error(
            'خطا در خواندن اطلاعات تماس:',
            error
        );
    }


    /* ۲) سپس سرور — اولویت با مقدارِ سرور است
          چون برای همه‌ی بازدیدکنندگان یکسان است */

    try {

        const response =
            await fetch(
                'save-contact-settings.php',
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

        if (response.ok) {

            const result =
                await response.json();

            if (
                result &&
                result.success &&
                result.data &&
                typeof result.data === 'object'
            ) {

                merged =
                    Object.assign(
                        {},
                        merged,
                        result.data
                    );
            }
        }

    } catch (error) {

        console.warn(
            'دریافت اطلاعات تماس از سرور ناموفق بود:',
            error
        );
    }


    if (!Array.isArray(merged.customCards)) {
        merged.customCards = [];
    }

    return merged;
}


async function loadContactSettings() {

    const data =
        await loadContactSettingsData();


    setContactFieldValue(
        'adminContactAgencyName',
        data.agencyName
    );

    setContactFieldValue(
        'adminContactAddress',
        data.address
    );

    setContactFieldValue(
        'adminContactPhone',
        data.phone
    );

    setContactFieldValue(
        'adminContactEmail',
        data.email
    );

    setContactFieldValue(
        'adminContactWorkingHours',
        data.workingHours
    );

    setContactFieldValue(
        'adminContactWhatsapp',
        data.whatsapp
    );

    setContactFieldValue(
        'adminContactTelegram',
        data.telegram
    );

    setContactFieldValue(
        'adminContactInstagram',
        data.instagram
    );

    setMapImagePreview(data.mapImage, true);
    setBaleLogoPreview(data.baleLogo || '');

    setContactFieldValue('adminOfficeLat', data.officeLat || '');
    setContactFieldValue('adminOfficeLng', data.officeLng || '');
    setContactFieldValue('adminOfficeZoom', data.officeZoom || 15);
    if (typeof window.mkRefreshOfficeMap === 'function') {
        setTimeout(window.mkRefreshOfficeMap, 120);
    }

    setContactFieldValue(
        'adminContactBale',
        data.bale
    );

    setContactFieldValue('adminContactTelegramColor', data.telegramColor || '#174D46');
    setContactFieldValue('adminContactInstagramColor', data.instagramColor || '#4B3D32');
    setContactFieldValue('adminContactBaleColor', data.baleColor || '#4AB06A');
    setContactFieldValue('adminContactTelegramDesc', data.telegramDescription || 'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو');
    setContactFieldValue('adminContactInstagramDesc', data.instagramDescription || 'تصاویر، فایل‌ها و محتوای اختصاصی ملکینو');
    setContactFieldValue('adminContactBaleDesc', data.baleDescription || 'مشاهده فایل‌ها و جدیدترین آگهی‌های ملکینو');


    const incomingCards =
        Array.isArray(data.customCards)
            ? data.customCards
            : [];

    customCardsData = incomingCards
        .map(function (card) {
            card = card || {};
            return {
                label: String(card.label || ''),
                messenger: String(card.messenger || ''),
                url: String(card.url || ''),
                icon: String(card.icon || ''),
                description: String(card.description || ''),
                color: String(card.color || '#2A4A62')
            };
        })
        .filter(function (card) {
            return String(card.label || '').trim() !== '' || String(card.url || '').trim() !== '';
        });

    renderCustomCards();


    updateContactPreview();


    const state =
        document.getElementById(
            'contactSaveState'
        );


    if (state) {

        state.textContent =
            'اطلاعات ذخیره‌شده بارگذاری شد';

        state.style.color =
            'var(--text-secondary)';
    }
}


/* راند ۷۴: نقشهٔ انتخاب موقعیت دفتر (Leaflet + OSM، بدون کلید API) */
window.__mkOfficeMap = null;
window.__mkOfficeMarker = null;
var MK_DEFAULT_LAT = 36.4181;
var MK_DEFAULT_LNG = 54.9763;

function mkEnsureLeaflet(cb) {
    if (window.L && window.L.map) { cb(); return; }
    if (window.__mkLeafletLoading) { window.__mkLeafletLoading.push(cb); return; }
    window.__mkLeafletLoading = [cb];
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
    document.head.appendChild(css);
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
    s.onload = function () {
        var q = window.__mkLeafletLoading || [];
        window.__mkLeafletLoading = null;
        q.forEach(function (fn) { try { fn(); } catch (e) {} });
    };
    s.onerror = function () { window.__mkLeafletLoading = null; };
    document.head.appendChild(s);
}

function mkSetOfficeLatLng(lat, lng, zoom) {
    var latEl = document.getElementById('adminOfficeLat');
    var lngEl = document.getElementById('adminOfficeLng');
    var zEl = document.getElementById('adminOfficeZoom');
    if (latEl) latEl.value = Number(lat).toFixed(6);
    if (lngEl) lngEl.value = Number(lng).toFixed(6);
    if (zoom && zEl) zEl.value = String(zoom);
    if (window.__mkOfficeMarker) window.__mkOfficeMarker.setLatLng([lat, lng]);
}

function mkRefreshOfficeMap() {
    mkEnsureLeaflet(function () {
        var el = document.getElementById('adminOfficeMap');
        if (!el || !window.L) return;
        var lat = parseFloat((document.getElementById('adminOfficeLat') || {}).value) || MK_DEFAULT_LAT;
        var lng = parseFloat((document.getElementById('adminOfficeLng') || {}).value) || MK_DEFAULT_LNG;
        var zoom = parseInt((document.getElementById('adminOfficeZoom') || {}).value, 10) || 15;
        if (!window.__mkOfficeMap) {
            window.__mkOfficeMap = L.map(el).setView([lat, lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(window.__mkOfficeMap);
            window.__mkOfficeMarker = L.marker([lat, lng], { draggable: true }).addTo(window.__mkOfficeMap);
            window.__mkOfficeMap.on('click', function (e) {
                mkSetOfficeLatLng(e.latlng.lat, e.latlng.lng, window.__mkOfficeMap.getZoom());
            });
            window.__mkOfficeMarker.on('dragend', function (e) {
                var p = e.target.getLatLng();
                mkSetOfficeLatLng(p.lat, p.lng, window.__mkOfficeMap.getZoom());
            });
            window.__mkOfficeMap.on('zoomend', function () {
                var zEl = document.getElementById('adminOfficeZoom');
                if (zEl) zEl.value = String(window.__mkOfficeMap.getZoom());
            });
        } else {
            window.__mkOfficeMap.setView([lat, lng], zoom);
            if (window.__mkOfficeMarker) window.__mkOfficeMarker.setLatLng([lat, lng]);
        }
        setTimeout(function () { try { window.__mkOfficeMap.invalidateSize(); } catch (e) {} }, 80);
    });
}
window.mkRefreshOfficeMap = mkRefreshOfficeMap;

function mkUseMyLocation() {
    if (!navigator.geolocation) { alert('موقعیت‌یاب در این مرورگر در دسترس نیست.'); return; }
    navigator.geolocation.getCurrentPosition(function (pos) {
        mkSetOfficeLatLng(pos.coords.latitude, pos.coords.longitude, 16);
        mkRefreshOfficeMap();
    }, function () { alert('دسترسی به موقعیت مکانی رد شد.'); });
}

async function saveContactSettings() {

    const telegram =
        getContactFieldValue(
            'adminContactTelegram'
        );

    const instagram =
        getContactFieldValue(
            'adminContactInstagram'
        );

    const bale =
        getContactFieldValue(
            'adminContactBale'
        );


    const contactData = {

        agencyName:
            getContactFieldValue(
                'adminContactAgencyName'
            ),

        address:
            getContactFieldValue(
                'adminContactAddress'
            ),

        phone:
            getContactFieldValue(
                'adminContactPhone'
            ),

        email:
            getContactFieldValue(
                'adminContactEmail'
            ),

        whatsapp:
            getContactFieldValue(
                'adminContactWhatsapp'
            ),

        telegram:
            telegram,

        instagram:
            instagram,

        bale:
            bale,

        telegramColor:
            getContactFieldValue('adminContactTelegramColor') || '#174D46',
        instagramColor:
            getContactFieldValue('adminContactInstagramColor') || '#4B3D32',
        baleColor:
            getContactFieldValue('adminContactBaleColor') || '#4AB06A',
        telegramDescription:
            getContactFieldValue('adminContactTelegramDesc'),
        instagramDescription:
            getContactFieldValue('adminContactInstagramDesc'),
        baleDescription:
            getContactFieldValue('adminContactBaleDesc'),

        mapImage:
            mapImagePath,
        baleLogo: baleLogoPath,

        officeLat:
            getContactFieldValue('adminOfficeLat'),

        officeLng:
            getContactFieldValue('adminOfficeLng'),

        officeZoom:
            getContactFieldValue('adminOfficeZoom'),

        workingHours:
            getContactFieldValue(
                'adminContactWorkingHours'
            ),

        customCards:
            (customCardsData || []).filter(function (c) {
                c = c || {};
                return String(c.label || '').trim() !== '' || String(c.url || '').trim() !== '';
            })
    };


    if (
        telegram &&
        !/^https?:\/\//i.test(telegram)
    ) {

        alert(
            'لینک تلگرام باید با http:// یا https:// شروع شود.'
        );

        return;
    }


    if (
        instagram &&
        !/^https?:\/\//i.test(instagram)
    ) {

        alert(
            'لینک اینستاگرام باید با http:// یا https:// شروع شود.'
        );

        return;
    }


    if (
        bale &&
        !/^https?:\/\//i.test(bale)
    ) {

        alert(
            'لینک بله باید با http:// یا https:// شروع شود.'
        );

        return;
    }


    /* بررسیِ آدرسِ کارت‌های سفارشی */

    for (let i = 0; i < customCardsData.length; i++) {

        const card =
            customCardsData[i] || {};

        const cardUrl =
            String(card.url || '').trim();

        const cardLabel =
            String(card.label || '').trim();

        if (cardUrl && !/^https?:\/\//i.test(cardUrl)) {

            alert(
                'آدرسِ کارت ' + (i + 1) +
                ' باید با http:// یا https:// شروع شود.'
            );

            return;
        }

        if (cardLabel && !cardUrl) {

            alert(
                'کارت ' + (i + 1) +
                ' متن دارد اما آدرس ندارد. لطفاً آدرس را هم وارد کنید.'
            );

            return;
        }
    }


    const state =
        document.getElementById(
            'contactSaveState'
        );


    /* ۱) ذخیره‌ی محلی (سازگاری با قبل) */

    try {

        localStorage.setItem(
            MELKINO_CONTACT_STORAGE_KEY,
            JSON.stringify(
                contactData
            )
        );

    } catch (error) {

        console.error(
            'خطا در ذخیره‌ی محلی اطلاعات تماس:',
            error
        );
    }


    /* ۲) ذخیره روی سرور — این همان چیزی است که
          بازدیدکنندگان واقعاً می‌بینند */

    try {

        if (state) {

            state.textContent =
                'در حال ذخیره روی سرور…';

            state.style.color =
                'var(--text-secondary)';
        }

        const response =
            await fetch(
                'save-contact-settings.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(
                        contactData
                    )
                }
            );

        const result =
            await response.json();

        updateContactPreview();


        if (result && result.success) {

            if (state) {

                state.textContent =
                    'اطلاعات با موفقیت روی سرور ذخیره شد';

                state.style.color =
                    '#059669';
            }

            alert(
                'اطلاعات تماس با موفقیت روی سرور ذخیره شد و در صفحه «ارتباط با ما» نمایش داده می‌شود.'
            );

            return;
        }


        if (state) {

            state.textContent =
                'ذخیره روی سرور ناموفق بود';

            state.style.color =
                '#dc2626';
        }

        alert(
            'ذخیره روی سرور انجام نشد:\n' +
            (result && result.message
                ? result.message
                : 'خطای ناشناخته')
        );

    } catch (error) {

        console.error(
            'خطا در ذخیره اطلاعات تماس:',
            error
        );


        if (state) {

            state.textContent =
                'خطا در ارتباط با سرور';

            state.style.color =
                '#dc2626';
        }

        alert(
            'ارتباط با سرور برقرار نشد؛ اطلاعات فقط در این مرورگر ذخیره شد.'
        );
    }
}


function bindContactLivePreview() {

    const fieldIds = [

        'adminContactAgencyName',
        'adminContactAddress',
        'adminContactPhone',
        'adminContactEmail',
        'adminContactWorkingHours',
        'adminContactWhatsapp',
        'adminContactTelegram',
        'adminContactInstagram',
        'adminContactBale'

    ];


    fieldIds.forEach(function (id) {

        const element =
            document.getElementById(id);


        if (!element) {
            return;
        }


        element.addEventListener(
            'input',
            updateContactPreview
        );
    });
}


// ==============================================
// مدیریت حرفه‌ای مشاوران
// ==============================================

const CONSULTANT_TYPES = ['آپارتمان','ویلا','زمین','باغ','تجاری','اداری','مغازه'];
const CONSULTANT_TRANSACTIONS = ['فروش','اجاره','رهن کامل','رهن و اجاره','پیش فروش'];
let consultantsData = [];

function consultantEsc(value){
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function consultantDefaultItem(index){
    return {id:'c'+(index+1),name:'',phone:'',telegram_username:'',telegram_link:'',active:true,is_default:false,priority:index+1,specialties:[]};
}

async function loadConsultants(){
    const state=document.getElementById('consultantsSaveState');
    if(state) state.textContent='در حال بارگذاری...';
    try{
        const response=await fetch('save_consultants.php?action=list&t='+Date.now(),{cache:'no-store'});
        const result=await response.json();
        if(!response.ok || !result.success) throw new Error(result.message||'خطا در بارگذاری مشاوران');
        consultantsData=Array.isArray(result.consultants)?result.consultants:[];
    }catch(error){
        consultantsData=[{id:'c1',name:'مشاور پیش‌فرض ملکینو',phone:'',telegram_username:'',telegram_link:'',active:true,is_default:true,priority:1,specialties:[]}];
        if(state) state.textContent='مشاور پیش‌فرض آماده ثبت است';
    }
    renderConsultants();
    if(state && !state.textContent) state.textContent='';
}

function addConsultant(){
    if(consultantsData.length>=10){alert('حداکثر ۱۰ مشاور مجاز است.');return;}
    consultantsData.push(consultantDefaultItem(consultantsData.length));
    if(!consultantsData.some(x=>x.is_default)) consultantsData[consultantsData.length-1].is_default=true;
    renderConsultants();
}

function removeConsultant(index){
    if(consultantsData.length<=1){alert('حداقل یک مشاور باید باقی بماند.');return;}
    const removed=consultantsData.splice(index,1)[0];
    if(removed?.is_default && consultantsData[0]) consultantsData[0].is_default=true;
    renderConsultants();
}

function updateConsultant(index,key,value){
    if(!consultantsData[index]) return;
    consultantsData[index][key]=value;
    if(key==='is_default' && value){consultantsData.forEach((c,i)=>{if(i!==index)c.is_default=false;});}
}

function addConsultantSpecialty(index){
    const c=consultantsData[index]; if(!c)return;
    c.specialties=Array.isArray(c.specialties)?c.specialties:[];
    if(c.specialties.length>=10){alert('برای هر مشاور حداکثر ۱۰ تخصص مجاز است.');return;}
    const pt=document.getElementById('spec-p-'+index)?.value||'';
    const tt=document.getElementById('spec-t-'+index)?.value||'';
    if(!pt||!tt){alert('نوع ملک و نوع معامله را انتخاب کنید.');return;}
    if(c.specialties.some(s=>s.property_type===pt&&s.transaction_type===tt)){alert('این ترکیب قبلاً برای مشاور ثبت شده است.');return;}
    c.specialties.push({property_type:pt,transaction_type:tt});
    renderConsultants();
}

function removeConsultantSpecialty(index,sIndex){
    if(!consultantsData[index])return;
    consultantsData[index].specialties.splice(sIndex,1);
    renderConsultants();
}

function renderConsultants(){
    const container=document.getElementById('consultantsManager'); if(!container)return;
    if(!consultantsData.length){container.innerHTML='<div class="consultant-empty">هنوز مشاوری ثبت نشده است.</div>';return;}
    container.innerHTML=consultantsData.map((c,i)=>{
        const specs=Array.isArray(c.specialties)?c.specialties:[];
        return `<div class="consultant-manager-card">
            <div class="consultant-manager-head">
                <div class="consultant-manager-title"><span>${MK_IC.user}</span> مشاور ${i+1} ${c.is_default?'<span class="consultant-default-pill">پیش‌فرض</span>':''}</div>
                <div class="consultant-manager-actions">
                    <button type="button" class="btn-icon-sm" data-con-del="${i}">${MK_IC.trash} حذف</button>
                </div>
            </div>
            <div style="padding:14px;display:grid;gap:11px;">
                <div class="admin-grid-2">
                    <div class="admin-field"><label>نام مشاور</label><input value="${consultantEsc(c.name)}" data-con="${i}" data-k="name"></div>
                    <div class="admin-field"><label>شماره تماس</label><input dir="ltr" inputmode="tel" value="${consultantEsc(c.phone)}" data-con="${i}" data-k="phone"></div>
                    <div class="admin-field"><label>اکانت تلگرام</label><input dir="ltr" placeholder="@username" value="${consultantEsc(c.telegram_username)}" data-con="${i}" data-k="telegram_username"></div>
                    <div class="admin-field"><label>لینک مستقیم تلگرام</label><input dir="ltr" placeholder="https://t.me/..." value="${consultantEsc(c.telegram_link)}" data-con="${i}" data-k="telegram_link"></div>
                    <div class="admin-field"><label>اولویت</label><input type="number" min="1" max="100" value="${Number(c.priority||i+1)}" data-con="${i}" data-k="priority"></div>
                    <div class="security-switch"><div><span>فعال باشد</span><small>مشاور غیرفعال در انتخاب خودکار وارد نمی‌شود.</small></div><input type="checkbox" ${c.active!==false?'checked':''} data-con-chk="${i}" data-k="active"></div>
                    <div class="security-switch"><div><span>مشاور پیش‌فرض</span><small>Fallback برای ترکیب بدون مشاور تخصصی.</small></div><input type="checkbox" ${c.is_default?'checked':''} data-con-chk="${i}" data-k="is_default"></div>
                </div>
                <div>
                    <div class="admin-section-help" style="margin-bottom:7px;">تخصص‌ها (${specs.length}/10)</div>
                    <div class="consultant-specialties">${specs.length?specs.map((sp,si)=>`<span class="consultant-specialty">${consultantEsc(sp.property_type)} + ${consultantEsc(sp.transaction_type)} <button type="button" data-con-delsp="${i}" data-si="${si}">✕</button></span>`).join(''):'<span class="admin-section-help">تخصصی ثبت نشده است.</span>'}</div>
                    <div class="consultant-add-specialty">
                        <select id="spec-p-${i}" class="form-select">${CONSULTANT_TYPES.map(x=>`<option value="${consultantEsc(x)}">${consultantEsc(x)}</option>`).join('')}</select>
                        <select id="spec-t-${i}" class="form-select">${CONSULTANT_TRANSACTIONS.map(x=>`<option value="${consultantEsc(x)}">${consultantEsc(x)}</option>`).join('')}</select>
                        <button type="button" class="btn-icon-sm gold" data-con-addsp="${i}">＋ افزودن تخصص</button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function saveConsultants(){
    const state=document.getElementById('consultantsSaveState');
    const cleaned=consultantsData.map((c,i)=>({...c,id:c.id||('c'+(i+1)),priority:Number(c.priority||i+1),specialties:(Array.isArray(c.specialties)?c.specialties:[]).slice(0,10)})).slice(0,10);
    if(!cleaned.some(c=>c.is_default) && cleaned[0]) cleaned[0].is_default=true;
    if(state){state.textContent='⏳ در حال ذخیره...';state.style.color='var(--text-secondary)';}
    try{
        const response=await fetch('save_consultants.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({consultants:cleaned})});
        const result=await response.json();
        if(!response.ok||!result.success)throw new Error(result.message||'ذخیره مشاوران انجام نشد.');
        consultantsData=result.consultants||cleaned; renderConsultants();
        if(state){state.textContent='ذخیره شد';state.style.color='var(--success)';}
    }catch(error){if(state){state.textContent=''+error.message;state.style.color='var(--danger)';}alert(''+error.message);}
}

/* V2: delegation for template + fragment controls (verbatim panel behaviour). */
document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-cc-del],[data-con-del],[data-con-addsp],[data-con-delsp],[data-act]') : null;
    if (!el) return;
    if (el.hasAttribute('data-cc-del')) { removeCustomCard(+el.getAttribute('data-cc-del')); return; }
    if (el.hasAttribute('data-con-del')) { removeConsultant(+el.getAttribute('data-con-del')); return; }
    if (el.hasAttribute('data-con-addsp')) { addConsultantSpecialty(+el.getAttribute('data-con-addsp')); return; }
    if (el.hasAttribute('data-con-delsp')) { removeConsultantSpecialty(+el.getAttribute('data-con-delsp'), +el.getAttribute('data-si')); return; }
    var act = el.getAttribute('data-act');
    if (act === 'ct-add-card') { addCustomCard(); }
    else if (act === 'ct-add-con') { addConsultant(); }
    else if (act === 'ct-load-con') { loadConsultants(); }
    else if (act === 'ct-load') { loadContactSettings(); }
    else if (act === 'ct-save') { saveContactSettings(); }
    else if (act === 'ct-save-con') { saveConsultants(); }
    else if (act === 'ct-loc') { mkUseMyLocation(); }
    else if (act === 'ct-bale-up') { uploadBaleLogo(); }
    else if (act === 'ct-bale-rm') { removeBaleLogo(); }
});
document.addEventListener('input', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-cc],[data-con]') : null;
    if (!el) return;
    if (el.hasAttribute('data-cc')) { updateCustomCard(+el.getAttribute('data-i'), el.getAttribute('data-k'), el.value); return; }
    var k = el.getAttribute('data-k'), v = el.value;
    if (k === 'priority') v = Math.max(1, Math.min(100, parseInt(v || 1, 10)));
    updateConsultant(+el.getAttribute('data-con'), k, v);
});
document.addEventListener('change', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('[data-cc-file],[data-con-chk]') : null;
    if (!el) return;
    if (el.hasAttribute('data-cc-file')) { uploadCustomCardIcon(+el.getAttribute('data-i'), el); return; }
    var k = el.getAttribute('data-k');
    updateConsultant(+el.getAttribute('data-con-chk'), k, el.checked);
    if (k === 'is_default') renderConsultants();
});
/* V2 boot: same as panel switchTab('contact'). */
document.addEventListener('DOMContentLoaded', function () {
    try { if (typeof loadContactSettings === 'function') loadContactSettings(); } catch (e) {}
    try { if (typeof bindContactLivePreview === 'function') bindContactLivePreview(); } catch (e) {}
    try { if (typeof loadConsultants === 'function') loadConsultants(); } catch (e) {}
});
