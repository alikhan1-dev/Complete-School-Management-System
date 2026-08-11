<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $classId = (int) $this->route('id');

        return [
            'class' => ['required', 'string', 'max:255', Rule::unique('classes', 'class')->ignore($classId)],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['integer', 'exists:sections,id'],
            'prev_sections' => ['nullable', 'array'],
            'prev_sections.*' => ['integer'],
        ];
    }
}
