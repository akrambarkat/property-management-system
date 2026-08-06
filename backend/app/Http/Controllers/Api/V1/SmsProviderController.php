<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendSmsRequest;
use App\Http\Requests\UpdateSmsProviderRequest;
use App\Models\SmsProvider;
use App\Services\ActivityLogService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SmsProviderController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly ActivityLogService $audit,
    ) {
    }

    public function index(): JsonResponse
    {
        $providers = SmsProvider::withTrashed()->get()->map(fn (SmsProvider $p) => [
            'id' => $p->id,
            'key' => $p->key,
            'name' => $p->name,
            'class' => $p->class,
            'api_url' => $p->api_url,
            'username' => $p->username,
            'sender_name' => $p->sender_name,
            'sender_id' => $p->sender_id,
            'timeout' => $p->timeout,
            'retries' => $p->retries,
            'http_method' => $p->http_method,
            'content_type' => $p->content_type,
            'authorization_type' => $p->authorization_type,
            'has_api_key' => !empty($p->api_key),
            'has_password' => !empty($p->password),
            'is_active' => $p->is_active,
            'is_default' => $p->is_default,
            'last_connected_at' => $p->last_connected_at?->toISOString(),
        ]);

        return response()->json(['success' => true, 'data' => $providers]);
    }

    public function show(SmsProvider $provider): JsonResponse
    {
        $data = $provider->only([
            'id', 'key', 'name', 'class', 'api_url', 'username', 'sender_name',
            'sender_id', 'timeout', 'retries', 'http_method', 'content_type',
            'authorization_type', 'custom_headers', 'is_active', 'is_default',
            'last_connected_at',
        ]);
        $data['has_api_key'] = !empty($provider->api_key);
        $data['has_password'] = !empty($provider->password);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(UpdateSmsProviderRequest $request, SmsProvider $provider): JsonResponse
    {
        $payload = $request->validated();

        // Keep existing secrets when fields are submitted as empty sentinels
        if (isset($payload['api_key']) && ($payload['api_key'] === null || $payload['api_key'] === '******')) {
            unset($payload['api_key']);
        }
        if (isset($payload['password']) && ($payload['password'] === null || $payload['password'] === '******')) {
            unset($payload['password']);
        }

        if (!empty($payload['is_default'])) {
            SmsProvider::where('is_default', true)->where('id', '!=', $provider->id)->update(['is_default' => false]);
        }
        if (!empty($payload['is_active'])) {
            $provider->update(['is_active' => true]);
        } elseif (array_key_exists('is_active', $payload)) {
            $provider->update(['is_active' => $payload['is_active']]);
        }

        $provider->update($payload);

        Cache::forget('sms_active_provider');

        $this->audit->log('sms.provider.updated', $provider, ['new' => $payload], "تحديث مزود SMS: {$provider->name}");

        return response()->json(['success' => true, 'message' => 'تم حفظ إعدادات المزود', 'data' => $provider->fresh()->makeHidden(['api_key', 'password'])]);
    }

    public function testConnection(SmsProvider $provider): JsonResponse
    {
        if (!$provider->is_active) {
            $provider->update(['is_active' => true]);
            Cache::forget('sms_active_provider');
        }
        $result = $this->sms->testConnection($provider);
        return response()->json(['success' => $result['success'], ...$result]);
    }

    public function sendTestSms(SendSmsRequest $request): JsonResponse
    {
        $provider = SmsProvider::find($request->input('provider_id'));
        $log = $this->sms->sendTestSms($request->input('recipient'), $provider);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رسالة الاختبار إلى قائمة الانتظار',
            'data' => ['log_id' => $log->id],
        ]);
    }
}
