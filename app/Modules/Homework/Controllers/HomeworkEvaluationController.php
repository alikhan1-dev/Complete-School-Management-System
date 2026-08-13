<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Homework\Services\HomeworkEvaluationService;
use App\Modules\Homework\Services\HomeworkService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Homework evaluation / add_evaluation / assigmnetDownload.
 * Deferred: mail/SMS, evaluation reports.
 */
class HomeworkEvaluationController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HomeworkEvaluationService $evaluation,
        protected HomeworkService $homework,
    ) {
    }

    public function show(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('homework_evaluation', 'can_view'), 403);

        $payload = $this->evaluation->evaluationPayload($id);

        return view('shared::layouts.admin', [
            'title' => 'Homework Evaluation',
            'contentView' => 'homework::admin.evaluation',
            'homework' => $payload['homework'],
            'students' => $payload['students'],
            'maxMarks' => $payload['maxMarks'],
            'hasMaxMarks' => $payload['hasMaxMarks'],
            'canSave' => $this->permissions->hasPrivilege('homework_evaluation', 'can_add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('homework_evaluation', 'can_add'), 403);

        $data = $request->validate([
            'homework_id' => ['required', 'integer'],
            'evaluation_date' => ['required', 'date'],
            'student_list' => ['required', 'array', 'min:1'],
            'student_list.*' => ['nullable', 'integer'],
            'student_id' => ['required', 'array'],
            'student_id.*' => ['required', 'integer'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:255'],
        ]);

        $homeworkId = (int) $data['homework_id'];
        $this->homework->find($homeworkId);

        $this->evaluation->save(
            $homeworkId,
            (string) $data['evaluation_date'],
            (array) $data['student_list'],
            (array) $data['student_id'],
            (array) ($data['marks'] ?? []),
            (array) ($data['note'] ?? [])
        );

        $homework = $this->homework->find($homeworkId);

        return redirect()
            ->route('homework.index', [
                'class_id' => $homework->class_id,
                'section_id' => $homework->section_id,
            ])
            ->with('success', 'Homework evaluation completed successfully.');
    }

    /**
     * CI assigmnetDownload/{id} — id is submit_assignment.id
     */
    public function downloadAssignment(int $id): BinaryFileResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('homework_evaluation', 'can_view')
            || $this->permissions->hasPrivilege('homework', 'can_view'),
            403
        );

        return $this->evaluation->downloadAssignment($id);
    }
}
