<?php

namespace App\Http\Resources;

use App\Services\CurrencyService;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = parent::toArray($request);

        if (in_array($request->method(), ['GET', 'HEAD'])) {
            $service = app(CurrencyService::class);
            $currency = $request->query('currency', $request->user()?->preferred_currency ?? 'ILS');

            foreach (['rent_amount', 'amount', 'total_amount', 'paid_amount', 'cost', 'total'] as $field) {
                if (isset($data[$field]) && is_numeric($data[$field])) {
                    $formatted = $service->formatAmount($data[$field], $currency);
                    $data[$field] = $formatted['amount'];
                    $data[$field . '_currency'] = $formatted;
                }
            }
        }

        return $data;
    }
}
