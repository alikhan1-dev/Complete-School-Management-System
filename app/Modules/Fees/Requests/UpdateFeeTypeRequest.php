<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('feetype', 'type')->ignore($id)],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }
}
