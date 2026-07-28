<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $prefix = Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV-';
        $last = Invoice::max('id') ?? 0;
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotal(array $data): float
    {
        return round(
            ($data['rent_amount'] ?? 0) +
            ($data['electricity_amount'] ?? 0) +
            ($data['water_amount'] ?? 0) +
            ($data['internet_amount'] ?? 0) +
            ($data['services_amount'] ?? 0),
            2
        );
    }

    public function updateStatus(Invoice $invoice): void
    {
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } elseif ($invoice->paid_amount > 0) {
            $invoice->status = 'partial';
        } elseif ($invoice->due_date < now() && $invoice->paid_amount == 0) {
            $invoice->status = 'overdue';
        } else {
            $invoice->status = 'unpaid';
        }
        $invoice->save();
    }
}
