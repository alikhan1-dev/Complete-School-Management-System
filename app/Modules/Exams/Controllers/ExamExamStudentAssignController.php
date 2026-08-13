<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Exams\Services\ExamAssignService;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examgroup examstudent + entrystudents —
 * assign students to a batch exam (exam_group_class_batch_exam_students).
 */
class ExamExamStudentAssignController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamAssignService $assign
    ) {
    }

    public function assign(Request $request, int $examId): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_assign_view_student', 'can_view'), 403);

        $exam = $this->examGroups->findExam($examId);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);
        $resultList = null;
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        $shouldSearch = $request->isMethod('post')
            || ($request->filled('class_id') && $request->filled('section_id'));

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
            ]);
            $filters = $data;
            $resultList = $this->assign->searchExamStudents(
                $examId,
                (int) $data['class_id'],
                (int) $data['section_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign / View Student',
            'contentView' => 'exams::admin.exam_assign.exam',
            'group' => $group,
            'exam' => $exam,
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'resultList' => $resultList,
            'filters' => $filters,
            'canSave' => $this->permissions->hasPrivilege('exam_assign_view_student', 'can_edit'),
        ]);
    }

    public function save(Request $request, int $examId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_assign_view_student', 'can_edit'), 403);

        $this->examGroups->findExam($examId);

        $data = $request->validate([
            'student_session_id' => ['nullable', 'array'],
            'student_session_id.*' => ['integer'],
            'all_students' => ['required', 'array', 'min:1'],
            'all_students.*' => ['integer'],
            'student' => ['nullable', 'array'],
            'student.*' => ['integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
        ]);

        $studentMap = [];
        foreach ($data['student'] ?? [] as $studentSessionId => $studentId) {
            $studentMap[(int) $studentSessionId] = (int) $studentId;
        }

        $this->assign->syncExamStudents(
            $examId,
            $data['student_session_id'] ?? [],
            $data['all_students'],
            $studentMap
        );

        return redirect()
            ->route('exams.exam_students.assign', [
                'examId' => $examId,
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
            ])
            ->with('success', 'Students assigned to exam successfully.');
    }
}
