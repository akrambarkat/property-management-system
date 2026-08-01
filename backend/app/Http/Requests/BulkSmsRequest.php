<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('send-sms');
    }

    public function rules(): array
    {
        return [
            'recipients' => 'required|array|min:1|max:5000',
            'recipients.*' => 'required|string|max:50',
            'message' => 'required|string|max:5000',
            'template_id' => 'nullable|exists:sms_templates,id',
            'scope' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'recipients.required' => 'اختر مستلمًا واحدًا على الأقل',
            'recipients.max' => 'الحد الأقصى 5000 مستلم في الرسالة الواحدة',
            'message.required' => 'نص الرسالة مطلوب',
        ];
    }
}
