<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-templates');
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:191',
            'key' => ['sometimes', 'string', 'max:191', 'alpha_dash', Rule::unique('sms_templates', 'key')->ignore($this->route('template'))],
            'subject' => 'nullable|string|max:191',
            'message' => 'sometimes|required|string|max:5000',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'message.required' => 'نص الرسالة مطلوب',
        ];
    }
}
