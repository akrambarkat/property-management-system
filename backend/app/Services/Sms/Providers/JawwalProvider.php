<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Jawwal SMS gateway provider (HTTP based).
 * The gateway is reached at the configured api_url; payload uses Jawwal's
 * common field names (mobile, msg, sender). If the gateway exposes a
 * different contract, switch the payload builder via configuration.
 */
class JawwalProvider extends AbstractProvider implements SmsProviderInterface
{
    public function getName(): string
    {
        return 'Jawwal SMS';
    }

    public function testConnection(): array
    {
        $url = $this->config['api_url'] ?? null;
        if (!$url) {
            return ['success' => false, 'message' => 'لم يتم تحديد عنوان API', 'code' => 400];
        }

        $start = microtime(true);
        try {
            $response = $this->client()->get($url);
            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'تم الاتصال بنجاح' : "فشل الاتصال (HTTP {$response->status()})",
                'code' => $response->status(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Jawwal test connection failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 0];
        }
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $url = $this->config['api_url'] ?? null;
        if (!$url) {
            return ['success' => false, 'error' => 'API URL غير محدد'];
        }

        $method = strtolower($this->config['http_method'] ?? 'POST');
        $payload = [
            'mobile' => $this->normalizeNumber($recipient),
            'msg' => $message,
            'sender' => $this->config['sender_id'] ?? '',
            'apiusername' => $this->config['username'] ?? '',
            'apipassword' => $this->config['password'] ?? '',
        ];

        try {
            $start = microtime(true);
            $response = $method === 'get'
                ? $this->client()->get($url, $payload)
                : $this->client()->send($method, $url, ['json' => $payload]);
            $duration = (int) round((microtime(true) - $start) * 1000);
            $body = $response->body();
            $json = $response->json();

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'duration_ms' => $duration,
                'message_id' => is_array($json) ? ($json['message_id'] ?? $json['id'] ?? null) : null,
                'status' => $response->successful() ? 'sent' : 'failed',
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Jawwal send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'http_status' => 0, 'duration_ms' => 0];
        }
    }
}
