<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Exams\Services\ExamSubjectService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examgroup getexamSubjects + addexamsubject —
 * form-based subject CRUD on an exam (deferred AJAX modal parity).
 */
class ExamGroupExamSubjectController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamSubjectService $examSubjects
    ) {
    }

    public function index(int $examId): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_subject', 'can_view'), 403);

        $exam = $this->examGroups->findExam($examId);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);

        return view('shared::layouts.admin', [
            'title' => 'Exam Subjects',
            'contentView' => 'exams::admin.exam_subjects.index',
            'group' => $group,
            'exam' => $exam,
            'examTypes' => $this->examGroups->examTypes(),
            'subjects' => $this->examSubjects->subjectsForExam($examId),
            'availableSubjects' => $this->examSubjects->availableSubjects(),
            'editing' => null,
            'canShowForm' => $this->permissions->hasPrivilege('exam_subject', 'can_add'),
        ]);
    }

    public function store(Request $request, int $examId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_subject', 'can_add'), 403);

        $exam = $this->examGroups->findExam($examId);
        $data = $this->validated($request);
        $this->examSubjects->saveSubject($exam, $data);

        return redirect()
            ->route('exams.exam_subjects.index', $examId)
            ->with('success', 'Exam subject added successfully.');
    }

    public function edit(int $examId, int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_subject', 'can_edit'), 403);

        $exam = $this->examGroups->findExam($examId);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);
        $editing = $this->examSubjects->findSubject($id);
        abort_unless((int) $editing->exam_group_class_batch_exams_id === $examId, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Exam Subject',
            'contentView' => 'exams::admin.exam_subjects.index',
            'group' => $group,
            'exam' => $exam,
            'examTypes' => $this->examGroups->examTypes(),
            'subjects' => $this->examSubjects->subjectsForExam($examId),
            'availableSubjects' => $this->examSubjects->availableSubjects(),
            'editing' => $editing,
            'canShowForm' => true,
        ]);
    }

    public function update(Request $request, int $examId, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_subject', 'can_edit'), 403);

        $exam = $this->examGroups->findExam($examId);
        $data = $this->validated($request);
        $this->examSubjects->saveSubject($exam, $data, $id);

        return redirect()
            ->route('exams.exam_subjects.index', $examId)
            ->with('success', 'Exam subject updated successfully.');
    }

    public function destroy(int $examId, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_subject', 'can_delete'), 403);

        $row = $this->examSubjects->findSubject($id);
        abort_unless((int) $row->exam_group_class_batch_exams_id === $examId, 404);
        $this->examSubjects->deleteSubject($row);

        return redirect()
            ->route('exams.exam_subjects.index', $examId)
            ->with('success', 'Exam subject deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'date_from' => ['required', 'date'],
            'time_from' => ['required', 'string', 'max:20'],
            'duration' => ['required', 'string', 'max:50'],
            'credit_hours' => ['required', 'numeric', 'min:0'],
            'room_no' => ['required', 'string', 'max:100'],
            'max_marks' => ['required', 'numeric', 'gt:0'],
            'min_marks' => ['required', 'numeric', 'gt:0'],
        ]);
    }
}
