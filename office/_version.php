<?php
/**
 * نسخه بیلد دفتر — هر تحویل یک شماره بالاتر تا روی هاست قابل راستی‌آزمایی باشد.
 * باز کردن مستقیم این فایل در مرورگر هم شماره بیلد را نشان می‌دهد.
 */
declare(strict_types=1);

if (!defined('OFFICE_BUILD')) {
    define('OFFICE_BUILD', 'v32');
}

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string)$_SERVER['SCRIPT_FILENAME']) === '_version.php'
) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'office build ' . OFFICE_BUILD;
    exit;
}
