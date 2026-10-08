<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\AdminTotp;
use Melkino\Core\Auth;
use Melkino\Core\Database;
use Melkino\Core\Response;

/**
 * Melkino V2 — audit-log viewer (spec §37, §40).
 * Read-only over melkino_audit_log (the table melkinoAudit() writes to):
 * action filter + pagination. Admin session + 2FA gate, like the dashboard.
 */
final class AdminAuditController
{
    private const PER_PAGE = 50;

    public function render(): string
    {
        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php?redirect=' . rawurlencode('admin-audit-log.php'));
        }
        if (AdminTotp::needsCheck()) {
            Response::redirect('admin-2fa.php?redirect=' . rawurlencode('admin-audit-log.php'));
        }

        $pdo = Database::pdo();
        $action = substr(trim((string)($_GET['action'] ?? '')), 0, 80);
        $page = max(1, (int)($_GET['page'] ?? 1));

        $rows = [];
        $total = 0;
        $actions = [];
        if ($pdo) {
            try {
                $actions = $pdo->query('SELECT DISTINCT action FROM melkino_audit_log ORDER BY action LIMIT 200')
                    ->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            } catch (\Throwable $ignored) {
            }
            $where = '';
            $params = [];
            if ($action !== '') {
                $where = 'WHERE action = ?';
                $params[] = $action;
            }
            try {
                $st = $pdo->prepare('SELECT COUNT(*) FROM melkino_audit_log ' . $where);
                $st->execute($params);
                $total = max(0, (int)$st->fetchColumn());
            } catch (\Throwable $ignored) {
            }
            $pages = max(1, (int)ceil($total / self::PER_PAGE));
            $page = min($page, $pages);
            try {
                $st = $pdo->prepare(
                    'SELECT id, actor_type, actor_id, actor_name, action, entity, entity_id,'
                    . ' details, ip_address, created_at FROM melkino_audit_log '
                    . $where . ' ORDER BY id DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
                );
                $st->execute($params);
                $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $ignored) {
            }
        }
        $pages = max(1, (int)ceil($total / self::PER_PAGE));

        return melkinoView('layouts/admin.php', [
            'title' => 'گزارش حسابرسی | مدیریت ملکینو',
            'active' => 'audit',
            'identity' => Auth::identity(),
            'content_view' => 'pages/admin-audit-log.php',
            'content_data' => [
                'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages,
                'filter' => $action, 'actions' => array_values(array_filter(array_map('strval', $actions))),
            ],
        ]);
    }
}
