<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarkDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'percentage_from' => ['required', 'numeric'],
            'percentage_to' => ['required', 'numeric'],
        ];
    }
}
