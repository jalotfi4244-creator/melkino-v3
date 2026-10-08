<?php
/**
 * میان‌بر قدیمی «مدیریت سایت» — صفحه اصلی به promotions.php منتقل شد.
 * اگر همین فایل هم روی هاست پاسخ خالی داد، مشکل از مسیر/نام site.php در سطح سرور است.
 */
declare(strict_types=1);

require_once __DIR__ . '/_lib.php';
office_redirect('promotions.php');
