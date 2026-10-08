<?php
declare(strict_types=1);

/**
 * Melkino V2 — automatic session restore from single-use login token (?t=...).
 * Same rules as legacy config.php: only when no identity is in session yet.
 */

use Melkino\Auth\AuthService;
use Melkino\Core\Database;

$melkinoLoginToken = trim((string)($_GET['t'] ?? ''));
if ($melkinoLoginToken !== '') {
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_start();
    }
    if (session_status() === PHP_SESSION_ACTIVE
        && empty($_SESSION['reg_telegram_id'])
        && empty($_SESSION['reg_bale_id'])
        && empty($_SESSION['reg_eitaa_id'])
    ) {
        $tok = AuthService::consumeLoginToken($melkinoLoginToken);
        if (!empty($tok['telegram_id'])) {
            $_SESSION['reg_telegram_id'] = (string)$tok['telegram_id'];
        }
        if (!empty($tok['bale_id'])) {
            $_SESSION['reg_bale_id'] = (string)$tok['bale_id'];
        }
        if (!empty($tok['user_id'])) {
            $pdo = Database::pdo();
            if ($pdo) {
                try {
                    $st = $pdo->prepare('SELECT phone, eitaa_id, telegram_id, bale_id FROM users WHERE id = ? LIMIT 1');
                    $st->execute([(int)$tok['user_id']]);
                    $urow = $st->fetch(PDO::FETCH_ASSOC) ?: [];
                    $phone = trim((string)($urow['phone'] ?? ''));
                    if ($phone !== '') {
                        $_SESSION['user_phone'] = $phone;
                    }
                    foreach (['eitaa_id' => 'reg_eitaa_id', 'telegram_id' => 'reg_telegram_id', 'bale_id' => 'reg_bale_id'] as $col => $sess) {
                        if (empty($_SESSION[$sess]) && !empty($urow[$col])) {
                            $_SESSION[$sess] = (string)$urow[$col];
                        }
                    }
                } catch (Throwable $e) {
                    try {
                        $st = $pdo->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
                        $st->execute([(int)$tok['user_id']]);
                        $phone = trim((string)$st->fetchColumn());
                        if ($phone !== '') {
                            $_SESSION['user_phone'] = $phone;
                        }
                    } catch (Throwable $ignored) {
                    }
                }
            }
        }
    }
}
