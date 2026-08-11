<?php

namespace App\Modules\Academics\Requests;

use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubjectGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sessionId = app(CurrentSessionResolver::class)->id();
        $groupId = (int) $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subject_groups', 'name')
                    ->where(fn ($q) => $q->where('session_id', $sessionId))
                    ->ignore($groupId),
            ],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['integer', 'exists:class_sections,id'],
            'subject' => ['required', 'array', 'min:1'],
            'subject.*' => ['integer', 'exists:subjects,id'],
            'description' => ['nullable', 'string'],
            'prev_sections' => ['nullable', 'array'],
            'prev_subjects' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sessionId = app(CurrentSessionResolver::class)->id();
            $groupId = (int) $this->route('id');
            $sectionIds = $this->input('sections', []);

            foreach ($sectionIds as $classSectionId) {
                $exists = SubjectGroupClassSection::query()
                    ->where('session_id', $sessionId)
                    ->where('class_section_id', (int) $classSectionId)
                    ->where('subject_group_id', '!=', $groupId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('sections', 'One or more sections are already assigned to another subject group in this session.');
                    break;
                }
            }
        });
    }
}
