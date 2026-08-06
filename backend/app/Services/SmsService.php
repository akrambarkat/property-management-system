<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Models\SmsQueue;
use App\Services\Sms\Providers\CustomProvider;
use App\Services\Sms\Providers\JawwalProvider;
use App\Services\Sms\Providers\TwilioProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    private const PROVIDER_CLASSES = [
        'custom' => CustomProvider::class,
        'twilio' => TwilioProvider::class,
        'jawwal' => JawwalProvider::class,
        // Vonage, MessageBird, Ooredoo and any future provider fall back to
        // the configurable CustomProvider until a dedicated class is added.
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ActivityLogService $audit,
    ) {
    }

    /**
     * List all registered providers (for the dropdown) with availability flags.
     */
    public function providers(): array
    {
        $rows = SmsProvider::orderBy('is_default', 'desc')->orderBy('name')->get();

        return $rows->map(fn (SmsProvider $p) => [
            'id' => $p->id,
            'key' => $p->key,
            'name' => $p->name,
            'is_active' => $p->is_active,
            'is_default' => $p->is_default,
        ])->all();
    }

    public function activeProvider(): SmsProvider
    {
        return Cache::rememberForever('sms_active_provider', function () {
            return SmsProvider::where('is_active', true)->where('is_default', true)->first()
                ?? SmsProvider::where('is_active', true)->first()
                ?? SmsProvider::first();
        });
    }

    /**
     * Resolve a provider implementation for a given provider row, injecting
     * its stored connection config + global SMS settings.
     */
    public function resolveProvider(?SmsProvider $provider = null): SmsProviderInterface
    {
        $provider = $provider ?? $this->activeProvider();
        $class = self::PROVIDER_CLASSES[$provider->key] ?? CustomProvider::class;

        /** @var SmsProviderInterface $instance */
        $instance = app($class);
        $instance->setConfig([
            'api_url' => $provider->api_url,
            'api_key' => $provider->api_key,
            'username' => $provider->username,
            'password' => $provider->password,
            'sender_name' => $provider->sender_name,
            'sender_id' => $provider->sender_id,
            'timeout' => $provider->timeout,
            'retries' => $provider->retries,
            'http_method' => $provider->http_method,
            'content_type' => $provider->content_type,
            'authorization_type' => $provider->authorization_type,
            'custom_headers' => $provider->custom_headers ?? [],
            'default_country_code' => $this->settings->get('sms_default_country_code', '970', 'sms'),
        ]);

        return $instance;
    }

    /**
     * Queue a message for delivery. Returns the created log record.
     */
    public function queue(string $recipient, string $message, array $options = []): SmsLog
    {
        $provider = $this->activeProvider();
        $dedupeKey = $options['dedupe_key'] ?? $this->dedupeKey($recipient, $message);

        // Prevent duplicate sending
        $existing = SmsQueue::where('dedupe_key', $dedupeKey)
            ->whereIn('status', [SmsQueue::STATUS_PENDING, SmsQueue::STATUS_PROCESSING])
            ->exists();
        if ($existing) {
            Log::info('SMS duplicate suppressed', ['dedupe_key' => $dedupeKey]);
            $log = SmsLog::create([
                'uuid' => (string) Str::uuid(),
                'provider_id' => $provider->id,
                'template_id' => $options['template_id'] ?? null,
                'recipient' => $recipient,
                'message' => $message,
                'status' => SmsLog::STATUS_QUEUED,
                'created_by' => $options['created_by'] ?? auth()->id(),
            ]);
            return $log;
        }

        $log = SmsLog::create([
            'uuid' => (string) Str::uuid(),
            'provider_id' => $provider->id,
            'template_id' => $options['template_id'] ?? null,
            'recipient' => $recipient,
            'message' => $message,
            'status' => SmsLog::STATUS_QUEUED,
            'created_by' => $options['created_by'] ?? auth()->id(),
        ]);

        SmsQueue::create([
            'sms_log_id' => $log->id,
            'recipient' => $recipient,
            'message' => $message,
            'provider_id' => $provider->id,
            'status' => SmsQueue::STATUS_PENDING,
            'max_attempts' => (int) $this->settings->get('sms_retries', 3, 'sms') + 1,
            'dedupe_key' => $dedupeKey,
            'available_at' => now()->addSeconds((int) ($options['delay_seconds'] ?? 0)),
        ]);

        return $log;
    }

    /**
     * Process a queue item through the provider (called by the job).
     * Returns ['success' => bool, 'log' => SmsLog].
     */
    public function processQueueItem(SmsQueue $item): array
    {
        $item->update([
            'status' => SmsQueue::STATUS_PROCESSING,
            'attempts' => $item->attempts + 1,
            'locked_at' => now(),
        ]);

        $log = $item->log;
        if (!$log) {
            $item->update(['status' => SmsQueue::STATUS_FAILED]);
            return ['success' => false, 'log' => null];
        }

        $log->update(['status' => SmsLog::STATUS_RETRYING, 'attempts' => $item->attempts]);

        $provider = $item->provider ?? $this->activeProvider();
        $instance = $this->resolveProvider($provider);

        try {
            $result = $instance->send($item->recipient, $item->message);

            if (!empty($result['success'])) {
                $log->update([
                    'status' => SmsLog::STATUS_SENT,
                    'attempts' => $item->attempts,
                    'message_id' => $result['message_id'] ?? null,
                    'provider_status' => $result['status'] ?? null,
                    'http_status' => $result['http_status'] ?? null,
                    'duration_ms' => $result['duration_ms'] ?? null,
                    'response_payload' => ['body' => $result['response'] ?? null],
                    'sent_at' => now(),
                ]);
                $item->update(['status' => SmsQueue::STATUS_SENT, 'processed_at' => now()]);
                $this->audit->log('sms.sent', $log, null, "إرسال رسالة SMS إلى {$item->recipient}");
                return ['success' => true, 'log' => $log];
            }

            return $this->handleFailure($item, $log, $result);
        } catch (\Throwable $e) {
            Log::error('SMS processing failed', ['error' => $e->getMessage(), 'queue_id' => $item->id]);
            return $this->handleFailure($item, $log, ['error' => $e->getMessage()]);
        }
    }

    protected function handleFailure(SmsQueue $item, SmsLog $log, array $result): array
    {
        $error = $result['error'] ?? 'فشل إرسال الرسالة';
        $httpStatus = $result['http_status'] ?? null;

        $log->failures()->create([
            'provider_id' => $item->provider_id,
            'attempt' => $item->attempts,
            'error_code' => $result['error_code'] ?? null,
            'error_message' => $error,
            'http_status' => $httpStatus,
        ]);

        if ($item->attempts >= $item->max_attempts) {
            $log->update([
                'status' => SmsLog::STATUS_FAILED,
                'failure_reason' => $error,
                'http_status' => $httpStatus,
            ]);
            $item->update(['status' => SmsQueue::STATUS_FAILED, 'processed_at' => now()]);
            $this->audit->log('sms.failed', $log, null, "فشل إرسال رسالة SMS إلى {$item->recipient}: {$error}");
            return ['success' => false, 'log' => $log];
        }

        $log->update(['status' => SmsLog::STATUS_RETRYING, 'failure_reason' => $error]);
        $item->update([
            'status' => SmsQueue::STATUS_PENDING,
            'available_at' => now()->addMinutes($item->attempts * 2),
        ]);
        return ['success' => false, 'log' => $log, 'retry' => true];
    }

    /**
     * Test connectivity and return a human-friendly result array.
     */
    public function testConnection(?SmsProvider $provider = null): array
    {
        $provider = $provider ?? $this->activeProvider();
        $instance = $this->resolveProvider($provider);

        $result = $instance->testConnection();
        if (!empty($result['success'])) {
            $provider->update(['last_connected_at' => now()]);
        }
        $this->audit->log('sms.test_connection', $provider, null, 'اختبار اتصال مزود SMS');

        return array_merge([
            'provider' => $provider->name,
            'provider_key' => $provider->key,
        ], $result);
    }

    /**
     * Send a test SMS (goes through the queue).
     */
    public function sendTestSms(string $recipient, ?SmsProvider $provider = null): SmsLog
    {
        $message = 'رسالة اختبار من نظام ' . $this->settings->get('app_name', 'AqarMaster');
        return $this->queue($recipient, $message, ['created_by' => auth()->id()]);
    }

    protected function dedupeKey(string $recipient, string $message): string
    {
        return hash('sha256', strtolower(trim($recipient)) . '|' . trim($message));
    }
}
