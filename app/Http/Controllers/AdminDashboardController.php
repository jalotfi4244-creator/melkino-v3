<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\AdminTotp;
use Melkino\Core\Auth;
use Melkino\Core\Database;
use Melkino\Core\Response;

/** Melkino V2 — admin dashboard shell (spec §33). Read-only KPIs + queues + audit tail. */
final class AdminDashboardController
{
    public function render(): string
    {
        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php?redirect=' . rawurlencode('admin.php'));
        }
        // 2FA gate (spec §38–§39): enrolled admins must pass TOTP this session.
        if (AdminTotp::needsCheck()) {
            Response::redirect('admin-2fa.php?redirect=' . rawurlencode('admin.php'));
        }
        $pdo = Database::pdo();
        $count = static function (string $sql) use ($pdo): int {
            if (!$pdo) {
                return 0;
            }
            try {
                return (int)$pdo->query($sql)->fetchColumn();
            } catch (\Throwable $ignored) {
                return 0;
            }
        };
        $kpis = [
            ['n' => $count("SELECT COUNT(*) FROM ads WHERE status='published'"), 'label' => 'آگهی منتشرشده', 'href' => 'admin-panel.php'],
            ['n' => $count("SELECT COUNT(*) FROM ads WHERE status='pending'"), 'label' => 'در انتظار تأیید', 'href' => 'admin-panel.php'],
            ['n' => $count('SELECT COUNT(*) FROM property_requests'), 'label' => 'درخواست‌ها', 'href' => 'admin-panel.php?tab=requests'],
            ['n' => $count("SELECT COUNT(*) FROM visit_requests WHERE status='new'"), 'label' => 'بازدید جدید', 'href' => 'admin-panel.php?tab=visits'],
            ['n' => $count('SELECT COUNT(*) FROM users'), 'label' => 'کاربران', 'href' => 'admin-panel.php?tab=users'],
            ['n' => $count("SELECT COUNT(*) FROM notifications WHERE is_read=0"), 'label' => 'اعلان خوانده‌نشده', 'href' => 'admin-notifications.php'],
        ];
        $queues = array_values(array_filter([
            $kpis[1]['n'] > 0 ? ['n' => $kpis[1]['n'], 'label' => 'آگهی در انتظار تأیید', 'href' => 'admin-panel.php'] : null,
            $kpis[3]['n'] > 0 ? ['n' => $kpis[3]['n'], 'label' => 'درخواست بازدید جدید', 'href' => 'admin-panel.php?tab=visits'] : null,
            ($n = $count("SELECT COUNT(*) FROM support_tickets WHERE status='open'")) > 0 ? ['n' => $n, 'label' => 'تیکت پشتیبانی باز', 'href' => 'admin-support.php'] : null,
        ]));
        $health = [
            ['ok' => (bool)$pdo, 'label' => 'اتصال دیتابیس'],
            ['ok' => is_writable(MELKINO_ROOT . '/uploads'), 'label' => 'پوشه آپلود قابل نوشتن'],
            ['ok' => is_file(MELKINO_ROOT . '/uploads/.htaccess'), 'label' => 'محافظت اجرایی آپلودها'],
            ['ok' => !is_file(MELKINO_ROOT . '/config.secrets.php') || !self::secretsTracked(), 'label' => 'secretها خارج از گیت'],
        ];
        $audit = [];
        if ($pdo) {
            try {
                // NOTE: the real table is melkino_audit_log (security-lib); `audit_log` never existed.
                $audit = $pdo->query('SELECT action, entity, entity_id, details, created_at FROM melkino_audit_log ORDER BY id DESC LIMIT 10')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $ignored) {
            }
        }
        return melkinoView('layouts/admin.php', [
            'title' => 'داشبورد مدیریت', 'active' => 'dashboard',
            'kpis' => $kpis, 'identity' => Auth::identity(),
            'content_view' => 'pages/admin-dashboard.php',
            'content_data' => ['queues' => $queues, 'health' => $health, 'audit' => $audit],
        ]);
    }

    private static function secretsTracked(): bool
    {
        $out = (string)@shell_exec('git -C ' . escapeshellarg(MELKINO_ROOT) . ' ls-files config.secrets.php 2>/dev/null');
        return trim($out) !== '';
    }
}
