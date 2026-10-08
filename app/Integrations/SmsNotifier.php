<?php
declare(strict_types=1);

namespace Melkino\Integrations;

use Melkino\Core\Database;
use Melkino\Core\Logger;

/**
 * Melkino V2 — SMS adapter (spec §101).
 * Sends via configured gateway; ALWAYS journals to sms_outbox for audit/retry.
 */
final class SmsNotifier implements NotifierInterface
{
    public function name(): string { return 'sms'; }

    public function available(): bool
    {
        return defined('SMS_API_KEY') && SMS_API_KEY !== '' && defined('SMS_API_URL') && SMS_API_URL !== '';
    }

    public function send(string $to, string $message, array $options = []): bool
    {
        $ok = false;
        if ($this->available() && trim($to) !== '' && trim($message) !== '') {
            $ok = $this->gatewaySend($to, $message);
        }
        self::journal($to, $message, $ok ? 'sent' : 'failed', $options['purpose'] ?? 'general');
        return $ok;
    }

    public static function sendOtp(string $phone, string $code): bool
    {
        $me = new self();
        if (!$me->available()) {
            return false;
        }
        return $me->send($phone, 'کد تأیید ملکینو: ' . $code, ['purpose' => 'otp']);
    }

    private function gatewaySend(string $to, string $message): bool
    {
        try {
            $ch = curl_init((string)SMS_API_URL);
            if (!$ch) {
                return false;
            }
            $payload = [
                'api_key' => SMS_API_KEY,
                'sender' => defined('SMS_SENDER_LINE') ? SMS_SENDER_LINE : '',
                'to' => $to,
                'message' => $message,
            ];
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_TIMEOUT => 12,
                CURLOPT_CONNECTTIMEOUT => 6,
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $resp !== false && $code >= 200 && $code < 300;
        } catch (\Throwable $e) {
            Logger::warning('sms gateway failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private static function journal(string $to, string $message, string $status, string $purpose): void
    {
        $pdo = Database::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $st = $pdo->prepare('INSERT INTO sms_outbox (phone, message, status, purpose, created_at) VALUES (?,?,?,?,NOW())');
            $st->execute([$to, mb_substr($message, 0, 1000), $status, substr($purpose, 0, 32)]);
        } catch (\Throwable $ignored) {
            // Table shape may vary on legacy installs; journal is best-effort.
        }
    }
}
