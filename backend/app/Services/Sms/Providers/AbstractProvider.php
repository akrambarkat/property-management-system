<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

abstract class AbstractProvider implements SmsProviderInterface
{
    protected array $config = [];

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        $timeout = (int) ($this->config['timeout'] ?? 15);

        $headers = (array) ($this->config['custom_headers'] ?? []);
        $authType = $this->config['authorization_type'] ?? 'bearer';
        $apiKey = $this->config['api_key'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        switch ($authType) {
            case 'basic':
                $headers['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");
                break;
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $apiKey;
                break;
            case 'api_key_header':
                $headers['X-API-Key'] = $apiKey;
                break;
        }

        $contentType = $this->config['content_type'] ?? 'application/json';
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = $contentType;
        }

        $client = Http::timeout($timeout)->withHeaders($headers);

        return $client;
    }

    /**
     * Normalize recipient to E.164 international format using the default country code.
     */
    protected function normalizeNumber(string $recipient): string
    {
        $recipient = preg_replace('/[^0-9+]/', '', $recipient);
        if (str_starts_with($recipient, '+')) {
            return $recipient;
        }
        $countryCode = ltrim((string) ($this->config['default_country_code'] ?? '970'), '+');
        if (str_starts_with($recipient, '0')) {
            $recipient = substr($recipient, 1);
        }
        return '+' . $countryCode . $recipient;
    }
}
