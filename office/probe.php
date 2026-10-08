<?php
/**
 * پروب تشخیصی موقت دفتر (فقط ادمین لاگین‌کرده) — برای ردیابی مشکل هاست.
 * در تحویل بعدی حذف می‌شود.
 */
declare(strict_types=1);

require_once __DIR__ . '/_lib.php';

if (!office_is_logged_in()) {
    office_redirect('login.php');
}
if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}
echo 'office build: ' . (defined('OFFICE_BUILD') ? OFFICE_BUILD : '?') . "\n";
echo 'php: ' . PHP_VERSION . "\n";
echo 'sapi: ' . PHP_SAPI . "\n";
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'json', 'session'] as $ext) {
    echo 'ext ' . $ext . ': ' . (extension_loaded($ext) ? 'yes' : 'NO') . "\n";
}
echo 'promotions.php: ' . (is_file(dirname(__DIR__) . '/promotions.php') ? 'exists' : 'MISSING') . "\n";
echo 'uploads/promotions: ' . (is_dir(dirname(__DIR__) . '/uploads/promotions') ? 'exists' : 'absent') . "\n";
