<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortalForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
            'user.*' => ['required', 'string', 'in:student,parent'],
        ];
    }
}
