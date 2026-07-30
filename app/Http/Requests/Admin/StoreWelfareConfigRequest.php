<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWelfareConfigRequest extends FormRequest
{
    public const TYPES = [
        'attendance_daily',
        'attendance_milestone',
        'level',
        'online',
        'daily_package',
        'vip_package',
        'first_topup',
        'message',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasRewards = $this->input('type') !== 'message';

        return [
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'ref_id' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'cash' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'rewards' => [
                Rule::requiredIf(fn () => $hasRewards),
                'array',
                Rule::when($hasRewards, ['min:1']),
            ],
            'rewards.*.item_id' => ['required', 'integer', 'min:0'],
            'rewards.*.amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'rewards.*.options' => ['nullable', 'array', 'max:30'],
            'rewards.*.options.*.id' => ['required', 'integer', 'min:0'],
            'rewards.*.options.*.param' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'msg_key' => [
                Rule::requiredIf(fn () => ! $hasRewards),
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
            ],
            'msg_value' => [
                Rule::requiredIf(fn () => ! $hasRewards),
                'nullable',
                'string',
                'max:5000',
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Loại phúc lợi không hợp lệ.',
            'ref_id.min' => 'Mốc/ID tham chiếu không được âm.',
            'rewards.required' => 'Cần thêm ít nhất một vật phẩm thưởng.',
            'rewards.min' => 'Cần thêm ít nhất một vật phẩm thưởng.',
            'rewards.*.item_id.required' => 'Vật phẩm thưởng chưa có ID.',
            'rewards.*.amount.min' => 'Số lượng vật phẩm phải lớn hơn 0.',
            'rewards.*.options.*.id.required' => 'Option vật phẩm chưa có ID.',
            'rewards.*.options.*.param.required' => 'Option vật phẩm chưa có chỉ số.',
            'msg_key.required' => 'Mã nội dung là bắt buộc.',
            'msg_key.regex' => 'Mã nội dung chỉ gồm chữ thường, số và dấu gạch dưới.',
            'msg_value.required' => 'Nội dung thông báo là bắt buộc.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label', '')),
            'description' => trim((string) $this->input('description', '')),
            'msg_key' => trim((string) $this->input('msg_key', '')),
            'msg_value' => trim((string) $this->input('msg_value', '')),
        ]);
    }
}
