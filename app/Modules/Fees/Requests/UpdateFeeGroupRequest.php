<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:200', Rule::unique('fee_groups', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
        ];
    }
}
