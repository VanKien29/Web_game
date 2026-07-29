<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'username' => [
                'bail',
                'required',
                'string',
                'min:6',
                'max:18',
                'regex:/\A[A-Za-z0-9]+\z/',
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'confirmed',
                'max:18',
                'regex:/\A[\x21-\x7E]+\z/',
                Password::min(6)->letters()->numbers(),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower(trim((string) $this->input('username'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.min' => 'Tên đăng nhập phải có ít nhất 6 ký tự.',
            'username.max' => 'Tên đăng nhập không được vượt quá 18 ký tự.',
            'username.regex' => 'Tên đăng nhập chỉ được gồm chữ cái và chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp.',
            'password.max' => 'Mật khẩu không được vượt quá 18 ký tự.',
            'password.regex' => 'Mật khẩu không được có khoảng trắng hoặc ký tự Unicode.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.letters' => 'Mật khẩu phải có ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu phải có ít nhất một chữ số.',
        ];
    }
}
