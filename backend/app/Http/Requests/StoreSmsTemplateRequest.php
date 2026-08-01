<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-templates');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:191',
            'key' => 'nullable|string|max:191|alpha_dash|unique:sms_templates,key',
            'subject' => 'nullable|string|max:191',
            'message' => 'required|string|max:5000',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'message.required' => 'نص الرسالة مطلوب',
            'key.unique' => 'المعرف مستخدم مسبقًا',
        ];
    }
}
