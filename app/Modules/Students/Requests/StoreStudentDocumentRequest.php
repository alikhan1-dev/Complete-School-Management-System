<?php

namespace App\Modules\Students\Requests;

use App\Modules\Students\Services\StudentDocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rulesMeta = app(StudentDocumentService::class)->uploadRulesFromFiletypes();

        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'first_title' => ['required', 'string', 'max:200'],
            'first_doc' => ['required', 'array', 'min:1'],
            'first_doc.*' => [
                'required',
                'file',
                File::types($rulesMeta['extensions'])->max($rulesMeta['max_kb']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_title.required' => 'Title is required.',
            'first_doc.required' => 'The document field is required.',
            'first_doc.*.required' => 'The document field is required.',
        ];
    }
}
