<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generic HTTP provider. Sends to the configured api_url using the
 * configured method / content type / authorization. Payload supports the
 * variables :to, :message, :sender, :apiKey, :username, :password.
 */
class CustomProvider extends AbstractProvider implements SmsProviderInterface
{
    public function getName(): string
    {
        return 'Custom';
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        $url = $this->config['api_url'] ?? null;
        if (!$url) {
            return ['success' => false, 'message' => 'لم يتم تحديد عنوان API', 'code' => 400];
        }

        try {
            $response = $this->client()->get($url);
            $latency = (int) round((microtime(true) - $start) * 1000);
            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'تم الاتصال بنجاح' : "فشل الاتصال (HTTP {$response->status()})",
                'code' => $response->status(),
                'latency_ms' => $latency,
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('SMS custom provider test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 0,
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $url = $this->config['api_url'] ?? null;
        if (!$url) {
            return ['success' => false, 'error' => 'API URL غير محدد'];
        }

        $payload = $this->buildPayload($recipient, $message, $options);
        $method = strtolower($this->config['http_method'] ?? 'POST');

        try {
            $start = microtime(true);
            $response = $method === 'get'
                ? $this->client()->get($url, $payload)
                : $this->client()->send($method, $url, ['json' => $payload]);
            $duration = (int) round((microtime(true) - $start) * 1000);
            $body = $response->body();

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'duration_ms' => $duration,
                'message_id' => $this->extractMessageId($body, $response->json()),
                'status' => $response->successful() ? 'sent' : 'failed',
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('SMS custom provider send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'http_status' => 0, 'duration_ms' => 0];
        }
    }

    protected function buildPayload(string $recipient, string $message, array $options): array
    {
        return [
            'to' => $this->normalizeNumber($recipient),
            'message' => $message,
            'sender' => $this->config['sender_id'] ?? null,
            'apiKey' => $this->config['api_key'] ?? null,
            'username' => $this->config['username'] ?? null,
            'password' => $this->config['password'] ?? null,
        ];
    }

    protected function extractMessageId(?string $body, mixed $json): ?string
    {
        if (is_array($json)) {
            foreach (['message_id', 'messageId', 'id', 'msgid', 'sid'] as $key) {
                if (isset($json[$key]) && is_scalar($json[$key])) {
                    return (string) $json[$key];
                }
            }
        }
        return null;
    }
}
