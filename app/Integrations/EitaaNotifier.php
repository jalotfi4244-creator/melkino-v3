<?php
declare(strict_types=1);

namespace Melkino\Integrations;

use Melkino\Core\Logger;

/** Melkino V2 — Eitaa adapter. Endpoint shape mirrors the legacy eitaa.php sender. */
final class EitaaNotifier implements NotifierInterface
{
    public function name(): string { return 'eitaa'; }

    public function available(): bool
    {
        return defined('EITAA_BOT_TOKEN') && EITAA_BOT_TOKEN !== '';
    }

    public function send(string $to, string $message, array $options = []): bool
    {
        if (!$this->available() || trim($to) === '' || trim($message) === '') {
            return false;
        }
        // Legacy eitaa.php behavior: POST to the configured gateway; keep it fail-safe.
        $url = 'https://eitaayar.ir/api/' . EITAA_BOT_TOKEN . '/sendMessage';
        $ch = curl_init($url);
        if (!$ch) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $to, 'text' => $message]),
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code < 200 || $code >= 300) {
            Logger::warning('eitaa api failed', ['http' => $code]);
            return false;
        }
        return true;
    }
}
