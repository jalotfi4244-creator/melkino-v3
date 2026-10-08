<?php
declare(strict_types=1);

namespace Melkino\Http\Controllers;

use Melkino\Core\AdminTotp;
use Melkino\Core\Auth;
use Melkino\Core\Csp;
use Melkino\Core\Csrf;
use Melkino\Core\Database;
use Melkino\Core\Response;
use Melkino\Core\Session;
use Melkino\Core\Totp;

/**
 * Melkino V2 — admin two-factor auth (TOTP, spec §38–§39).
 * Password login stays legacy; this page handles setup / verify / revoke.
 * No inline scripts (CSP-safe); plain POST forms with canonical CSRF.
 */
final class Admin2faController
{
    private const MAX_ATTEMPTS = 10;
    private const LOCK_SECONDS = 600;

    public function handle(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }

        foreach (['config.php', 'db_helpers.php'] as $lib) {
            $f = MELKINO_ROOT . '/' . $lib;
            if (is_file($f)) {
                require_once $f;
            }
        }

        if (!Auth::isAdmin()) {
            Response::redirect('admin-login.php?redirect=' . rawurlencode('admin-2fa.php'));
        }

        $pdo = Database::pdo();
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $username = (string)($_SESSION['admin_username'] ?? 'admin');
        $redirect = $this->safeRedirect((string)(($_POST['redirect'] ?? '') !== '' ? $_POST['redirect'] : ($_GET['redirect'] ?? '')));

        if (!$pdo || $adminId <= 0) {
            Csp::sendHtmlHeaders();
            return melkinoView('pages/admin-2fa.php', [
                'mode' => 'unavailable', 'error' => 'سرویس دوعاملی در دسترس نیست (اتصال پایگاه داده).',
                'success' => '', 'secret' => '', 'uri' => '', 'redirect' => $redirect, 'csrf' => Csrf::field(),
            ]);
        }

        $enrolled = AdminTotp::enrolled($pdo, $adminId);
        $satisfied = AdminTotp::guardSatisfied();
        $error = '';
        $success = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $body = Csrf::readBody();
            if (!Csrf::validate((string)($body[Csrf::FIELD] ?? ''))) {
                http_response_code(419);
                $error = 'توکن امنیتی نامعتبر است؛ صفحه را تازه‌سازی کنید.';
            } elseif ($this->throttled()) {
                $error = 'به‌دلیل تلاش‌های ناموفق پیاپی، چند دقیقه دیگر دوباره تلاش کنید.';
            } else {
                $action = (string)($body['action'] ?? '');
                $code = (string)($body['code'] ?? '');
                if ($action === 'setup_confirm' && !$enrolled) {
                    $pending = (string)Session::get('admin_2fa_pending', '');
                    if ($pending !== '' && Totp::verify($pending, $code)) {
                        AdminTotp::activate($pdo, $adminId, $pending);
                        Session::set('admin_2fa_pending', '');
                        $this->resetThrottle();
                        AdminTotp::markSatisfied();
                        $this->audit('admin.2fa_enrolled', $adminId);
                        Response::redirect($redirect);
                    }
                    $this->hitThrottle();
                    $this->audit('admin.2fa_failed', $adminId);
                    $error = 'کد واردشده درست نیست؛ ساعت گوشی و کد جدید را بررسی کنید.';
                } elseif ($action === 'verify' && $enrolled) {
                    $secret = AdminTotp::secretFor($pdo, $adminId);
                    if ($secret !== null && Totp::verify($secret, $code)) {
                        $this->resetThrottle();
                        AdminTotp::markSatisfied();
                        $this->audit('admin.2fa_verified', $adminId);
                        Response::redirect($redirect);
                    }
                    $this->hitThrottle();
                    $this->audit('admin.2fa_failed', $adminId);
                    $error = 'کد واردشده درست نیست؛ کد جدید اپ را وارد کنید.';
                } elseif ($action === 'revoke' && $enrolled) {
                    $secret = AdminTotp::secretFor($pdo, $adminId);
                    if ($secret !== null && Totp::verify($secret, $code)) {
                        AdminTotp::revoke($pdo, $adminId);
                        unset($_SESSION['admin_2fa_ok']);
                        $this->resetThrottle();
                        $this->audit('admin.2fa_revoked', $adminId);
                        $enrolled = false;
                        $satisfied = false;
                        $success = 'دوعاملی غیرفعال شد. در صورت نیاز دوباره فعال‌سازی کنید.';
                    } else {
                        $this->hitThrottle();
                        $error = 'برای غیرفعال‌سازی، کد صحیح فعلی را وارد کنید.';
                    }
                } else {
                    $error = 'درخواست نامعتبر است.';
                }
            }
        }

        // Pending setup secret lives in session until the first code verifies
        // (never stored unverified in the DB).
        $secret = '';
        if (!$enrolled) {
            $secret = (string)Session::get('admin_2fa_pending', '');
            if ($secret === '') {
                $secret = Totp::generateSecret();
                Session::set('admin_2fa_pending', $secret);
            }
        }

        $mode = !$enrolled ? 'setup' : ($satisfied ? 'manage' : 'verify');

        Csp::sendHtmlHeaders();
        return melkinoView('pages/admin-2fa.php', [
            'mode' => $mode,
            'error' => $error,
            'success' => $success,
            'secret' => $secret,
            'uri' => $secret !== '' ? Totp::uri($secret, $username) : '',
            'username' => $username,
            'redirect' => $redirect,
            'csrf' => Csrf::field(),
        ]);
    }

    /** Only same-app admin destinations (open-redirect guard). */
    private function safeRedirect(string $to): string
    {
        if ($to === 'admin.php' || $to === 'admin-panel.php') {
            return $to;
        }
        if (preg_match('/^admin\.php\?tab=([a-z0-9_-]+)$/', $to, $m) === 1
            && isset(AdminTabController::TABS[$m[1]])) {
            return $to;
        }
        return 'admin.php';
    }

    /** @return array{n:int,until:int} */
    private function throttleState(): array
    {
        $s = Session::get('admin_2fa_throttle', ['n' => 0, 'until' => 0]);
        return is_array($s) ? ['n' => (int)($s['n'] ?? 0), 'until' => (int)($s['until'] ?? 0)] : ['n' => 0, 'until' => 0];
    }

    private function throttled(): bool
    {
        $s = $this->throttleState();
        if ($s['until'] > time()) {
            return true;
        }
        if ($s['n'] >= self::MAX_ATTEMPTS) {
            Session::set('admin_2fa_throttle', ['n' => $s['n'], 'until' => time() + self::LOCK_SECONDS]);
            return true;
        }
        return false;
    }

    private function hitThrottle(): void
    {
        $s = $this->throttleState();
        Session::set('admin_2fa_throttle', ['n' => $s['n'] + 1, 'until' => $s['until']]);
    }

    private function resetThrottle(): void
    {
        Session::set('admin_2fa_throttle', ['n' => 0, 'until' => 0]);
    }

    private function audit(string $action, int $adminId): void
    {
        if (function_exists('melkinoAudit')) {
            try {
                melkinoAudit($action, 'admin', $adminId, []);
            } catch (\Throwable $ignored) {
            }
        }
    }
}
