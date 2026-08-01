<?php

namespace App\Services;

use App\Models\SmsTemplate;

class SmsTemplateService
{
    /**
     * The global variable registry. Extend here to support future variables.
     */
    public function availableVariables(): array
    {
        return [
            'tenant_name' => 'اسم المستأجر',
            'property' => 'العقار',
            'building' => 'المبنى',
            'unit' => 'الوحدة',
            'invoice_number' => 'رقم الفاتورة',
            'amount' => 'المبلغ',
            'remaining' => 'المبلغ المتبقي',
            'due_date' => 'تاريخ الاستحقاق',
            'payment_date' => 'تاريخ الدفع',
            'contract_end' => 'نهاية العقد',
            'company' => 'اسم الشركة',
            'phone' => 'رقم الهاتف',
            'website' => 'الموقع الإلكتروني',
        ];
    }

    /**
     * Render a template message by replacing {{variable}} placeholders.
     * Unknown placeholders are left untouched.
     */
    public function render(string $message, array $data = []): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($data) {
            $key = strtolower($m[1]);
            return array_key_exists($key, $data) ? (string) $data[$key] : $m[0];
        }, $message);
    }

    /**
     * Apply a template to context data and return both subject and body.
     */
    public function apply(SmsTemplate $template, array $data = []): array
    {
        return [
            'subject' => $template->subject ? $this->render($template->subject, $data) : null,
            'message' => $this->render($template->message, $data),
        ];
    }
}
