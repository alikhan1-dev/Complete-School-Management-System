<?php

namespace App\Modules\Roles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*.can_view' => ['sometimes'],
            'permissions.*.can_add' => ['sometimes'],
            'permissions.*.can_edit' => ['sometimes'],
            'permissions.*.can_delete' => ['sometimes'],
        ];
    }
}
