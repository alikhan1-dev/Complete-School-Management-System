<?php

namespace App\Modules\Students\Requests;

use App\Modules\Students\Services\StudentTimelineService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateStudentTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $meta = app(StudentTimelineService::class)->uploadRulesFromFiletypes();

        return [
            'id' => ['required', 'integer', 'exists:student_timeline,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'timeline_title' => ['required', 'string', 'max:200'],
            'timeline_date' => ['required', 'date'],
            'timeline_desc' => ['nullable', 'string'],
            'visible_check' => ['nullable', 'string'],
            'timeline_doc' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ];
    }
}
