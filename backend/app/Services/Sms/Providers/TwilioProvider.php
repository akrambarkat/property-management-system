<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Twilio provider using the Programmable SMS REST API.
 * Uses HTTP Basic auth with Account SID : password (Auth Token).
 */
class TwilioProvider extends AbstractProvider implements SmsProviderInterface
{
    public function getName(): string
    {
        return 'Twilio';
    }

    public function testConnection(): array
    {
        $accountSid = $this->config['username'] ?? null; // account SID
        if (!$accountSid) {
            return ['success' => false, 'message' => 'معرّف الحساب (Account SID) مطلوب', 'code' => 400];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}.json";
        $start = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($accountSid, $this->config['password'] ?? '')
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->get($url);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'تم الاتصال بنجاح' : "فشل الاتصال (HTTP {$response->status()})",
                'code' => $response->status(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio test connection failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 0];
        }
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $accountSid = $this->config['username'] ?? null;
        if (!$accountSid) {
            return ['success' => false, 'error' => 'Account SID غير محدد'];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $start = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($accountSid, $this->config['password'] ?? '')
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->asForm()
                ->post($url, [
                    'To' => $this->normalizeNumber($recipient),
                    'From' => $this->config['sender_id'] ?? null,
                    'Body' => $message,
                ]);

            $duration = (int) round((microtime(true) - $start) * 1000);
            $json = $response->json();

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'duration_ms' => $duration,
                'message_id' => $json['sid'] ?? null,
                'status' => $json['status'] ?? ($response->successful() ? 'sent' : 'failed'),
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'http_status' => 0, 'duration_ms' => 0];
        }
    }
}
