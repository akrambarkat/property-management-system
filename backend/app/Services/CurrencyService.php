<?php

namespace App\Services;

use App\Models\Currency;

class CurrencyService
{
    public function convertAmount(float $amountInILS, string $targetCurrency): float
    {
        $currency = Currency::where('code', $targetCurrency)->first();
        if (!$currency) return $amountInILS;
        return round($amountInILS * $currency->exchange_rate, 2);
    }

    public function getDefaultCurrency(): Currency
    {
        return Currency::where('is_default', true)->first() ?? Currency::where('code', 'ILS')->first();
    }

    public function formatAmount(float $amountInILS, ?string $currencyCode = null): array
    {
        $code = $currencyCode ?? request('currency', 'ILS');
        $currency = Currency::where('code', $code)->first();
        $rate = $currency ? $currency->exchange_rate : 1;
        $converted = round($amountInILS * $rate, 2);

        return [
            'amount' => $converted,
            'currency' => $code,
            'symbol' => $currency->symbol ?? '₪',
            'rate' => $rate,
        ];
    }
}
