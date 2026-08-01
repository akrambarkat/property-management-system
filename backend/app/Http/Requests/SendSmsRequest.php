<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('send-sms');
    }

    public function rules(): array
    {
        return [
            'recipient' => 'required|string|max:50',
            'message' => 'required|string|max:5000',
            'template_id' => 'nullable|exists:sms_templates,id',
            'provider_id' => 'nullable|exists:sms_providers,id',
            'schedule_at' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.required' => 'رقم المستلم مطلوب',
            'message.required' => 'نص الرسالة مطلوب',
        ];
    }
}
