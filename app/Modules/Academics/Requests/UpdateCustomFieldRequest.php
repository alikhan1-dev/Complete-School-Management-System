<?php

namespace App\Modules\Academics\Requests;

use App\Modules\Academics\Support\CustomFieldConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'belong_to' => ['required', 'string', Rule::in(array_keys(CustomFieldConfig::tables()))],
            'type' => ['required', 'string', Rule::in(array_keys(CustomFieldConfig::types()))],
            'name' => ['required', 'string', 'max:255'],
            'column' => ['nullable', 'integer', 'min:1', 'max:12'], // CI edit does not require column
            'field_values' => ['nullable', 'string'],
            'validation' => ['nullable'],
            'display_tbl' => ['nullable'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = (string) $this->input('type');
            if (in_array($type, CustomFieldConfig::typesRequiringValues(), true)
                && ! filled($this->input('field_values'))) {
                $validator->errors()->add('field_values', 'Field values are required for this field type.');
            }
        });
    }
}
