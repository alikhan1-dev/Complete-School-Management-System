<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Exams\Services\ExamMarksService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examgroup subjectstudent + entrymarks —
 * form-based marks entry (CSV import deferred).
 */
class ExamMarksController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamMarksService $marks,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function index(Request $request, int $examId): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_marks', 'can_view'), 403);

        $exam = $this->examGroups->findExam($examId);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);

        $filters = [
            'exam_group_class_batch_exam_subject_id' => $request->input('exam_group_class_batch_exam_subject_id'),
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'session_id' => $request->input('session_id', $this->currentSession->id()),
        ];

        $resultList = null;
        $subjectDetail = null;

        $shouldSearch = $request->isMethod('post')
            || (
                $request->filled('exam_group_class_batch_exam_subject_id')
                && $request->filled('class_id')
                && $request->filled('section_id')
                && $request->filled('session_id')
            );

        if ($shouldSearch) {
            $data = $request->validate([
                'exam_group_class_batch_exam_subject_id' => ['required', 'integer', 'exists:exam_group_class_batch_exam_subjects,id'],
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'session_id' => ['required', 'integer', 'exists:sessions,id'],
            ]);
            $filters = $data;

            $subjectDetail = $this->marks->findExamSubject((int) $data['exam_group_class_batch_exam_subject_id']);
            abort_unless(
                (int) $subjectDetail->exam_group_class_batch_exams_id === $examId,
                404
            );

            $resultList = $this->marks->studentsForSubjectMarks(
                (int) $data['exam_group_class_batch_exam_subject_id'],
                (int) $data['class_id'],
                (int) $data['section_id'],
                (int) $data['session_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Exam Marks',
            'contentView' => 'exams::admin.exam_marks.index',
            'group' => $group,
            'exam' => $exam,
            'subjects' => $this->marks->subjectsForExam($examId),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'sessions' => AcademicSession::query()->orderBy('id')->get(),
            'attendanceOptions' => $this->marks->attendanceOptions(),
            'filters' => $filters,
            'resultList' => $resultList,
            'subjectDetail' => $subjectDetail,
            'canSave' => $this->permissions->hasPrivilege('exam_marks', 'can_edit'),
        ]);
    }

    public function save(Request $request, int $examId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_marks', 'can_edit'), 403);

        $this->examGroups->findExam($examId);

        $data = $request->validate([
            'exam_group_class_batch_exam_subject_id' => ['required', 'integer', 'exists:exam_group_class_batch_exam_subjects,id'],
            'exam_group_student_id' => ['required', 'array', 'min:1'],
            'exam_group_student_id.*' => ['integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
        ]);

        $subjectDetail = $this->marks->findExamSubject((int) $data['exam_group_class_batch_exam_subject_id']);
        abort_unless((int) $subjectDetail->exam_group_class_batch_exams_id === $examId, 404);

        $this->marks->saveMarks(
            (int) $data['exam_group_class_batch_exam_subject_id'],
            $data['exam_group_student_id'],
            $request->all()
        );

        return redirect()
            ->route('exams.exam_marks.index', [
                'examId' => $examId,
                'exam_group_class_batch_exam_subject_id' => $data['exam_group_class_batch_exam_subject_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'session_id' => $data['session_id'],
            ])
            ->with('success', 'Exam marks saved successfully.');
    }
}
