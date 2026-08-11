<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // CI edit only requires name; type/code are saved if posted.
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(['theory', 'practical'])],
            'code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
