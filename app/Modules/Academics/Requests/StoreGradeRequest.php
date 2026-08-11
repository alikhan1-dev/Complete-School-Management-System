<?php

namespace App\Modules\Academics\Requests;

use App\Modules\Academics\Support\ExamTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type' => ['required', 'string', Rule::in(ExamTypes::keys())],
            'name' => ['required', 'string', 'max:100'],
            'mark_from' => ['required', 'numeric'],
            'mark_upto' => ['required', 'numeric'],
            'grade_point' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
        ];
    }
}
