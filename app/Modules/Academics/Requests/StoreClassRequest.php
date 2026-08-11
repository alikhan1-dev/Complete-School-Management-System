<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class' => ['required', 'string', 'max:255', Rule::unique('classes', 'class')],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['integer', 'exists:sections,id'],
        ];
    }
}
