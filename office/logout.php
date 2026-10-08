<?php
/**
 * خروج از دفتر — فقط کلیدهای ادمین پاک می‌شود (نشست کاربری سایت دست نمی‌خورد).
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

foreach (['is_admin', 'user_role', 'admin_id', 'admin_username', 'admin_display_name', 'admin_login_at', 'melkino_session_started_at', 'admin_last_activity'] as $k) {
    unset($_SESSION[$k]);
}
if (!headers_sent()) {
    session_regenerate_id(true);
}

require_once __DIR__ . '/_lib.php';
office_redirect('login.php?out=1');
