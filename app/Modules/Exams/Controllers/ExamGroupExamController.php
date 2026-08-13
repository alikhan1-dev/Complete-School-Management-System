<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CI admin/examgroup/addexam + ajaxaddexam — exams within an exam group.
 * Publish Exam (is_publish) / Publish Result (is_active) are set here.
 * Deferred: marksheet selection + SMS when publishing.
 */
class ExamGroupExamController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function index(int $groupId): View
    {
        abort_unless($this->permissions->hasPrivilege('exam', 'can_view'), 403);

        $group = $this->examGroups->findGroup($groupId);

        return view('shared::layouts.admin', [
            'title' => 'Exams',
            'contentView' => 'exams::admin.exam_group_exams.index',
            'group' => $group,
            'exams' => $this->examGroups->examsForGroup($groupId),
            'examTypes' => $this->examGroups->examTypes(),
            'sessions' => AcademicSession::query()->orderBy('id')->get(),
            'currentSessionId' => $this->currentSession->id(),
            'editing' => null,
            'canShowExamForm' => $this->permissions->hasPrivilege('exam', 'can_add'),
        ]);
    }

    public function store(Request $request, int $groupId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam', 'can_add'), 403);

        $group = $this->examGroups->findGroup($groupId);
        $data = $this->validatedExam($request, $group->exam_type);
        $data['exam_group_id'] = $groupId;

        $this->examGroups->saveExam($data);

        return redirect()
            ->route('exams.exam_group_exams.index', $groupId)
            ->with('success', 'Exam created successfully.');
    }

    public function edit(int $groupId, int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('exam', 'can_edit'), 403);

        $group = $this->examGroups->findGroup($groupId);
        $exam = $this->examGroups->findExam($id);
        abort_unless((int) $exam->exam_group_id === $groupId, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Exam',
            'contentView' => 'exams::admin.exam_group_exams.index',
            'group' => $group,
            'exams' => $this->examGroups->examsForGroup($groupId),
            'examTypes' => $this->examGroups->examTypes(),
            'sessions' => AcademicSession::query()->orderBy('id')->get(),
            'currentSessionId' => $this->currentSession->id(),
            'editing' => $exam,
            'canShowExamForm' => $this->permissions->hasPrivilege('exam', 'can_edit'),
        ]);
    }

    public function update(Request $request, int $groupId, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam', 'can_edit'), 403);

        $group = $this->examGroups->findGroup($groupId);
        $exam = $this->examGroups->findExam($id);
        abort_unless((int) $exam->exam_group_id === $groupId, 404);

        $data = $this->validatedExam($request, $group->exam_type);
        $data['exam_group_id'] = $groupId;
        $this->examGroups->saveExam($data, $id);

        return redirect()
            ->route('exams.exam_group_exams.index', $groupId)
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(int $groupId, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam', 'can_delete'), 403);

        $exam = $this->examGroups->findExam($id);
        abort_unless((int) $exam->exam_group_id === $groupId, 404);
        $this->examGroups->deleteExam($exam);

        return redirect()
            ->route('exams.exam_group_exams.index', $groupId)
            ->with('success', 'Exam deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedExam(Request $request, string $examType): array
    {
        $rules = [
            'exam' => ['required', 'string', 'max:200'],
            'session_id' => ['required', 'integer', 'exists:sessions,id'],
            'description' => ['nullable', 'string'],
            'use_exam_roll_no' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_publish' => ['nullable', 'boolean'],
            'passing_percentage' => [
                Rule::requiredIf($examType === 'average_passing'),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];

        $data = $request->validate($rules);
        $data['use_exam_roll_no'] = $request->boolean('use_exam_roll_no') ? 1 : 0;
        $data['is_active'] = $request->boolean('is_active') ? 1 : 0;
        $data['is_publish'] = $request->boolean('is_publish') ? 1 : 0;

        if ($examType !== 'average_passing') {
            $data['passing_percentage'] = null;
        }

        return $data;
    }
}
