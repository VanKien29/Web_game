<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Keep min:1 so accounts created before the six-character rule can still log in.
            'username' => ['bail', 'required', 'string', 'min:1', 'max:18', 'regex:/\A[A-Za-z0-9]+\z/'],
            'password' => ['bail', 'required', 'string', 'min:1', 'max:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim((string) $this->input('username')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.max' => 'Tên đăng nhập không được vượt quá 18 ký tự.',
            'username.regex' => 'Tên đăng nhập chỉ được gồm chữ cái và chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.max' => 'Mật khẩu không hợp lệ.',
        ];
    }
}
