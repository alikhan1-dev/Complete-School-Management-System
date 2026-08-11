<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200', Rule::unique('fee_groups', 'name')],
            'description' => ['nullable', 'string'],
        ];
    }
}
