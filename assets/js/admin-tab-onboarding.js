/* Melkino V2 — onboarding admin tab (logo upload), extracted VERBATIM from
 * admin-panel.php. Self-contained (id globals + fetch upload_onboarding_logo.php).
 * Listeners bind at parse time; layout loads scripts after content, same as panel.
 */
function uploadOnboardingLogo() {
    const file = logoFileInput.files[0];
    if (!file) {
        logoStatus.textContent = 'لطفاً یک فایل انتخاب کنید.';
        logoStatus.style.color = 'var(--text-secondary)';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        logoStatus.textContent = 'حجم فایل بیشتر از ۵ مگابایت است.';
        logoStatus.style.color = 'red';
        return;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        logoStatus.textContent = 'فرمت فایل مجاز نیست (فقط PNG, JPG, JPEG, WEBP).';
        logoStatus.style.color = 'red';
        return;
    }

    uploadLogoBtn.disabled = true;
    uploadLogoBtn.style.opacity = '0.6';
    uploadProgress.style.display = 'block';
    progressBar.style.width = '0%';
    logoStatus.textContent = '⏳ در حال آپلود...';
    logoStatus.style.color = 'var(--text-secondary)';

    const formData = new FormData();
    formData.append('onboarding_logo', file);

    // مسیر آپلود - اگر پروژه در پوشه melkino است
    const uploadUrl = 'upload_onboarding_logo.php';
    // اگر پروژه در ریشه است، خط بالا رو کامنت کنید و این خط رو فعال کنید:
    // const uploadUrl = window.location.origin + '/upload_onboarding_logo.php';

    fetch(uploadUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(text || 'خطا در پاسخ سرور');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const logoUrl = data.logo_url || data.url || '';
            if (logoUrl) {
                logoPreview.src = logoUrl + '?t=' + new Date().getTime();
                logoStatus.textContent = '' + data.message;
                logoStatus.style.color = 'green';
            } else {
                logoStatus.textContent = 'لوگو آپلود شد، اما نشانی دریافت نشد. صفحه را رفرش کنید.';
                logoStatus.style.color = 'orange';
            }
            logoFileInput.value = '';
            fileNameDisplay.textContent = '';
            alert('لوگو با موفقیت آپلود و جایگزین شد.');
        } else {
            logoStatus.textContent = '' + data.message;
            logoStatus.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        logoStatus.textContent = 'خطا: ' + error.message;
        logoStatus.style.color = 'red';
    })
    .finally(() => {
        uploadProgress.style.display = 'none';
        uploadLogoBtn.disabled = false;
        uploadLogoBtn.style.opacity = '1';
    });
}

uploadLogoBtn.addEventListener('click', uploadOnboardingLogo);

logoFileInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        uploadLogoBtn.click();
    }
});

