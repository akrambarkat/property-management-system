<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * Human-readable provider name.
     */
    public function getName(): string;

    /**
     * Provider config (api url, credentials, headers...) resolved from storage.
     */
    public function setConfig(array $config): void;

    /**
     * Verify connectivity with the provider. Returns [success, message, code, latency_ms].
     */
    public function testConnection(): array;

    /**
     * Send a single SMS. Returns [success, message_id, status, cost, response].
     */
    public function send(string $recipient, string $message, array $options = []): array;
}
