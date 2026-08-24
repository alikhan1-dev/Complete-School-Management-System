<?php

namespace App\Modules\Staff\Requests;

use App\Modules\Staff\Services\StaffTimelineService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateStaffTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $meta = app(StaffTimelineService::class)->uploadRulesFromFiletypes();

        return [
            'id' => ['required', 'integer', 'exists:staff_timeline,id'],
            'edit_staff_id' => ['required', 'integer', 'exists:staff,id'],
            'timeline_title' => ['required', 'string', 'max:200'],
            'timeline_date' => ['required', 'date'],
            'timeline_desc' => ['nullable', 'string', 'max:300'],
            'visible_check' => ['nullable', 'string'],
            'timeline_doc' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ];
    }
}
