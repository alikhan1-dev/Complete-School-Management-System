<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = (string) $this->input('account_type', 'fix');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(['fix', 'percentage'])],
            'discount_limit' => ['required', 'integer', 'min:1'],
            'expire_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($type === 'percentage') {
            $rules['percentage'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }
}
