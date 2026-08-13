<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Exams\Services\ExamPrintTemplateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examresult/marksheet + admitcard — search & HTML print.
 * Deferred: mPDF download/email.
 */
class ExamPrintController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamPrintTemplateService $templates,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function marksheet(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('print_marksheet', 'can_view'), 403);

        return $this->printSearchPage($request, 'marksheet');
    }

    public function admitcard(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('print_admit_card', 'can_view'), 403);

        return $this->printSearchPage($request, 'admitcard');
    }

    public function printMarksheet(Request $request, int $examStudentId): View
    {
        abort_unless($this->permissions->hasPrivilege('print_marksheet', 'can_view'), 403);

        $data = $request->validate([
            'marksheet' => ['required', 'integer', 'exists:template_marksheets,id'],
            'exam_id' => ['required', 'integer', 'exists:exam_group_class_batch_exams,id'],
        ]);

        $exam = $this->examGroups->findExam((int) $data['exam_id']);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);
        $template = $this->templates->findMarksheet((int) $data['marksheet']);
        $student = $this->findExamStudentRow($examStudentId, (int) $data['exam_id']);

        return view('exams::admin.print.marksheet', [
            'template' => $template,
            'exam' => $exam,
            'group' => $group,
            'student' => $student,
            'marks' => $this->templates->studentSubjectMarks((int) $data['exam_id'], $examStudentId),
            'folder' => ExamPrintTemplateService::MARKSHEET_FOLDER,
        ]);
    }

    public function printAdmitcard(Request $request, int $examStudentId): View
    {
        abort_unless($this->permissions->hasPrivilege('print_admit_card', 'can_view'), 403);

        $data = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exam_group_class_batch_exams,id'],
        ]);

        $template = $this->templates->activeAdmitcard();
        abort_unless($template, 404, 'No active admit card template. Activate one under Design Admit Card.');

        $exam = $this->examGroups->findExam((int) $data['exam_id']);
        $group = $this->examGroups->findGroup((int) $exam->exam_group_id);
        $student = $this->findExamStudentRow($examStudentId, (int) $data['exam_id']);

        return view('exams::admin.print.admitcard', [
            'template' => $template,
            'exam' => $exam,
            'group' => $group,
            'student' => $student,
            'subjects' => $this->templates->examSubjectsForPrint((int) $data['exam_id']),
            'folder' => ExamPrintTemplateService::ADMITCARD_FOLDER,
        ]);
    }

    public function examsByGroup(int $groupId): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('print_marksheet', 'can_view')
            || $this->permissions->hasPrivilege('print_admit_card', 'can_view'),
            403
        );

        $this->examGroups->findGroup($groupId);
        $exams = $this->examGroups->examsForGroup($groupId)->map(fn ($e) => [
            'id' => $e->id,
            'exam' => $e->exam,
        ]);

        return response()->json($exams);
    }

    protected function printSearchPage(Request $request, string $type): View
    {
        $filters = [
            'exam_group_id' => $request->input('exam_group_id'),
            'exam_id' => $request->input('exam_id'),
            'session_id' => $request->input('session_id', $this->currentSession->id()),
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'marksheet' => $request->input('marksheet'),
        ];

        $studentList = null;
        $shouldSearch = $request->isMethod('post')
            || (
                $request->filled('exam_group_id')
                && $request->filled('exam_id')
                && $request->filled('session_id')
                && $request->filled('class_id')
                && $request->filled('section_id')
                && ($type === 'admitcard' || $request->filled('marksheet'))
            );

        if ($shouldSearch) {
            $rules = [
                'exam_group_id' => ['required', 'integer', 'exists:exam_groups,id'],
                'exam_id' => ['required', 'integer', 'exists:exam_group_class_batch_exams,id'],
                'session_id' => ['required', 'integer', 'exists:sessions,id'],
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
            ];
            if ($type === 'marksheet') {
                $rules['marksheet'] = ['required', 'integer', 'exists:template_marksheets,id'];
            }
            $filters = $request->validate($rules);

            $exam = $this->examGroups->findExam((int) $filters['exam_id']);
            abort_unless((int) $exam->exam_group_id === (int) $filters['exam_group_id'], 404);

            $studentList = $this->templates->searchExamStudentsForPrint(
                (int) $filters['exam_id'],
                (int) $filters['class_id'],
                (int) $filters['section_id'],
                (int) $filters['session_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => $type === 'marksheet' ? 'Print Marksheet' : 'Print Admit Card',
            'contentView' => 'exams::admin.print.search',
            'printType' => $type,
            'examGroups' => $this->examGroups->listGroups(),
            'marksheets' => $this->templates->listMarksheets(),
            'activeAdmitcard' => $this->templates->activeAdmitcard(),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'sessions' => AcademicSession::query()->orderBy('id')->get(),
            'filters' => $filters,
            'studentList' => $studentList,
        ]);
    }

    protected function findExamStudentRow(int $examStudentId, int $examId): object
    {
        $row = \Illuminate\Support\Facades\DB::table('exam_group_class_batch_exam_students')
            ->join('student_session', 'student_session.id', '=', 'exam_group_class_batch_exam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'student_session.section_id', '=', 'sections.id')
            ->where('exam_group_class_batch_exam_students.id', $examStudentId)
            ->where('exam_group_class_batch_exam_students.exam_group_class_batch_exam_id', $examId)
            ->select([
                'exam_group_class_batch_exam_students.id as exam_student_id',
                'exam_group_class_batch_exam_students.roll_no as exam_roll_no',
                'exam_group_class_batch_exam_students.teacher_remark',
                'students.*',
                'classes.class',
                'sections.section',
            ])
            ->first();

        abort_unless($row, 404);

        return $row;
    }
}
