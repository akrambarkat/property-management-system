<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-providers');
    }

    public function rules(): array
    {
        return [
            'api_url' => 'nullable|string|max:500',
            'api_key' => 'nullable|string|max:500',
            'username' => 'nullable|string|max:191',
            'password' => 'nullable|string|max:500',
            'sender_id' => 'nullable|string|max:191',
            'timeout' => 'nullable|integer|min:1|max:120',
            'retries' => 'nullable|integer|min:0|max:10',
            'http_method' => ['nullable', Rule::in(['GET', 'POST', 'PUT'])],
            'content_type' => ['nullable', Rule::in(['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data', 'text/xml'])],
            'authorization_type' => ['nullable', Rule::in(['bearer', 'basic', 'api_key_header', 'none'])],
            'custom_headers' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'http_method.in' => 'طريقة HTTP غير مدعومة',
            'authorization_type.in' => 'نوع المصادقة غير مدعوم',
        ];
    }
}
