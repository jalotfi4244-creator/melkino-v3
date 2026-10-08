<?php
declare(strict_types=1);

namespace Melkino\Integrations;

use Melkino\Core\Logger;

/** Melkino V2 — Bale Bot API adapter (Telegram-compatible API shape). */
final class BaleNotifier implements NotifierInterface
{
    public const API_BASE = 'https://tapi.bale.ai/bot';

    public function name(): string { return 'bale'; }

    public function available(): bool
    {
        return defined('BALE_BOT_TOKEN') && BALE_BOT_TOKEN !== '';
    }

    public function send(string $to, string $message, array $options = []): bool
    {
        if (!$this->available() || trim($to) === '' || trim($message) === '') {
            return false;
        }
        $payload = ['chat_id' => $to, 'text' => $message];
        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = is_string($options['reply_markup']) ? $options['reply_markup'] : json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE);
        }
        return $this->api('sendMessage', $payload);
    }

    public function api(string $method, array $payload): bool
    {
        if (!$this->available()) {
            return false;
        }
        $ch = curl_init(self::API_BASE . BALE_BOT_TOKEN . '/' . $method);
        if (!$ch) {
            return false;
        }
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
        if ($resp === false || $code < 200 || $code >= 300) {
            Logger::warning('bale api failed', ['method' => $method, 'http' => $code]);
            return false;
        }
        $data = json_decode((string)$resp, true);
        return is_array($data) && ($data['ok'] ?? false) === true;
    }
}
