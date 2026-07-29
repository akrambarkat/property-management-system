<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required_without:phone|string',
            'phone' => 'required_without:identifier|string',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required_without' => 'رقم الهاتف أو البريد الإلكتروني مطلوب',
            'phone.required_without' => 'رقم الهاتف أو البريد الإلكتروني مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ];
    }
}
