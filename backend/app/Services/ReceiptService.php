<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;

class ReceiptService
{
    public function generateReceiptNumber(): string
    {
        $prefix = Setting::where('key', 'receipt_prefix')->value('value') ?? 'REC-';
        $last = Payment::max('id') ?? 0;
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
