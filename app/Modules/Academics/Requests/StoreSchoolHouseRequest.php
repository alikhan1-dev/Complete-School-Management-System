<?php

namespace App\Modules\Academics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolHouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'house_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:400'],
        ];
    }
}
