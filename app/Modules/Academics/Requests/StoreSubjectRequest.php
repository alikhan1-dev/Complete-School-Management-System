<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')],
            'type' => ['required', 'string', Rule::in(['theory', 'practical'])],
            'code' => ['nullable', 'string', 'max:255'],
        ];

        if (filled($this->input('code'))) {
            $rules['code'] = ['required', 'string', 'max:255', Rule::unique('subjects', 'code')];
        }

        return $rules;
    }
}
