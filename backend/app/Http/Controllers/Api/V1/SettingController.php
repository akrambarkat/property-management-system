<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ActivityLogService $audit,
    ) {
    }

    /**
     * Return all settings grouped by category.
     */
    public function index(): JsonResponse
    {
        $groups = [];
        foreach (Setting::supportedGroups() as $group) {
            $groups[$group] = $this->settings->group($group);
        }
        $groups['currencies'] = $this->currencies();

        return response()->json(['success' => true, 'data' => $groups]);
    }

    /**
     * Persist a batch of settings scoped to a single group.
     */
    public function update(Request $request): JsonResponse
    {
        $group = $request->input('group', 'general');

        abort_unless(in_array($group, Setting::supportedGroups(), true), 422, 'مجموعة إعدادات غير معروفة');

        $payload = is_array($request->input('values')) ? $request->input('values') : $request->all();

        $rules = $this->rulesForGroup($group);
        $validated = [];
        foreach ($rules as $key => $rule) {
            if (array_key_exists($key, $payload)) {
                $validated[$key] = $payload[$key];
            }
        }
        if ($validated) {
            $validator = validator($validated, $rules, [
                'required' => 'هذا الحقل مطلوب',
                'email' => 'صيغة البريد الإلكتروني غير صحيحة',
                'numeric' => 'يجب أن تكون قيمة رقمية',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
        }

        if (!Gate::allows('edit-settings', Setting::class)) {
            return response()->json(['success' => false, 'message' => 'لا تملك صلاحية تعديل الإعدادات'], 403);
        }

        $changed = $this->settings->setGroup($group, $validated);
        if ($changed) {
            $this->audit->log(
                "settings.updated",
                null,
                ['old' => null, 'new' => $changed],
                "تحديث إعدادات مجموعة {$group}",
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح',
            'changed' => array_keys($changed),
            'data' => $this->settings->group($group),
        ]);
    }

    public function currencies(): array
    {
        return Currency::where('is_active', true)->get()->toArray();
    }

    public function listCurrencies(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->currencies()]);
    }

    public function updateCurrency(Request $request, Currency $currency): JsonResponse
    {
        $validated = $request->validate(['exchange_rate' => 'required|numeric|min:0.0001']);
        $old = $currency->exchange_rate;
        $currency->update($validated);
        $this->audit->log(
            "currency.updated",
            $currency,
            ['old' => $old, 'new' => $validated['exchange_rate']],
            "تحديث سعر صرف {$currency->name}",
        );
        return response()->json(['success' => true, 'message' => 'تم تحديث سعر العملة']);
    }

    public function setDefaultCurrency(Currency $currency): JsonResponse
    {
        $currency->update(['is_default' => true]);
        Currency::where('id', '!=', $currency->id)->where('is_default', true)->update(['is_default' => false]);
        $this->audit->log("currency.set_default", $currency, null, "تعيين {$currency->name} كعملة افتراضية");
        return response()->json(['success' => true, 'message' => 'تم تعيين العملة الافتراضية']);
    }

    private function rulesForGroup(string $group): array
    {
        $currencyCodes = Currency::pluck('code')->all();

        return match ($group) {
            'general' => [
                'app_name' => 'nullable|string|max:191',
                'default_currency' => ['nullable', Rule::in($currencyCodes)],
                'electricity_unit_price' => 'nullable|numeric|min:0',
                'water_unit_price' => 'nullable|numeric|min:0',
                'invoice_prefix' => 'nullable|string|max:20',
                'contract_prefix' => 'nullable|string|max:20',
                'receipt_prefix' => 'nullable|string|max:20',
            ],
            'company' => [
                'company_name' => 'nullable|string|max:191',
                'tax_number' => 'nullable|string|max:191',
                'commercial_registration' => 'nullable|string|max:191',
                'phone' => 'nullable|string|max:50',
                'mobile' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:191',
                'website' => 'nullable|string|max:191',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:191',
                'country' => 'nullable|string|max:191',
                'timezone' => 'nullable|string|max:50',
                'date_format' => 'nullable|string|max:50',
                'language' => 'nullable|string|max:50',
                'default_vat' => 'nullable|numeric|min:0|max:100',
                'invoice_footer' => 'nullable|string|max:2000',
                'invoice_header' => 'nullable|string|max:2000',
                'payment_terms' => 'nullable|string|max:500',
            ],
            'invoices' => [
                'invoice_prefix' => 'nullable|string|max:20',
                'invoice_tax_number' => 'nullable|string|max:191',
                'invoice_due_days' => 'nullable|integer|min:0|max:365',
                'invoice_late_fee' => 'nullable|numeric|min:0',
                'invoice_show_discount' => 'nullable|boolean',
                'invoice_auto_generate' => 'nullable|boolean',
            ],
            'contracts' => [
                'contract_prefix' => 'nullable|string|max:20',
                'contract_reminder_days' => 'nullable|integer|min:0|max:365',
                'contract_auto_renew' => 'nullable|boolean',
                'contract_terms' => 'nullable|string|max:5000',
            ],
            'notifications' => [
                'notify_email' => 'nullable|boolean',
                'notify_sms' => 'nullable|boolean',
                'notify_system' => 'nullable|boolean',
                'notify_on_payment' => 'nullable|boolean',
                'notify_on_contract' => 'nullable|boolean',
                'notify_on_maintenance' => 'nullable|boolean',
            ],
            'appearance' => [
                'theme' => ['nullable', Rule::in(['light', 'dark', 'system'])],
                'language' => ['nullable', Rule::in(['ar', 'en'])],
                'date_format' => 'nullable|string|max:50',
                'compact_mode' => 'nullable|boolean',
            ],
            'security' => [
                'password_min_length' => 'nullable|integer|min:6|max:64',
                'session_timeout' => 'nullable|integer|min:1|max:1440',
                'two_factor' => 'nullable|boolean',
                'lockout_attempts' => 'nullable|integer|min:1|max:20',
            ],
            'backup' => [
                'backup_enabled' => 'nullable|boolean',
                'backup_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
                'backup_retention_days' => 'nullable|integer|min:1|max:365',
                'backup_destination' => ['nullable', Rule::in(['local', 'cloud'])],
            ],
            'system' => [
                'debug_mode' => 'nullable|boolean',
                'maintenance_mode' => 'nullable|boolean',
                'max_upload_size' => 'nullable|integer|min:1',
                'log_retention_days' => 'nullable|integer|min:1',
            ],
            'sms' => $this->smsRules(),
            default => [],
        };
    }

    private function smsRules(): array
    {
        return [
            'sms_provider' => 'nullable|string|max:50',
            'sms_api_url' => 'nullable|string|max:500',
            'sms_api_key' => 'nullable|string|max:500',
            'sms_username' => 'nullable|string|max:191',
            'sms_password' => 'nullable|string|max:500',
            'sms_sender_id' => 'nullable|string|max:191',
            'sms_timeout' => 'nullable|integer|min:1|max:120',
            'sms_retries' => 'nullable|integer|min:0|max:10',
            'sms_http_method' => ['nullable', Rule::in(['GET', 'POST', 'PUT'])],
            'sms_content_type' => ['nullable', Rule::in(['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data', 'text/xml'])],
            'sms_authorization_type' => ['nullable', Rule::in(['bearer', 'basic', 'api_key_header', 'none'])],
            'sms_custom_headers' => 'nullable|array',
        ];
    }
}
