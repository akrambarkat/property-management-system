<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:191',
            'electricity_unit_price' => 'numeric|min:0',
            'water_unit_price' => 'numeric|min:0',
            'invoice_prefix' => 'nullable|string',
            'contract_prefix' => 'nullable|string',
            'receipt_prefix' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['success' => true, 'message' => 'تم حفظ الإعدادات']);
    }

    public function currencies(): JsonResponse
    {
        $currencies = Currency::where('is_active', true)->get();
        return response()->json(['success' => true, 'data' => $currencies]);
    }

    public function updateCurrency(Request $request, Currency $currency): JsonResponse
    {
        $validated = $request->validate(['exchange_rate' => 'required|numeric|min:0.0001']);
        $currency->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث سعر العملة']);
    }

    public function setDefaultCurrency(Currency $currency): JsonResponse
    {
        Currency::where('is_default', true)->update(['is_default' => false]);
        $currency->update(['is_default' => true]);
        return response()->json(['success' => true, 'message' => 'تم تعيين العملة الافتراضية']);
    }
}
