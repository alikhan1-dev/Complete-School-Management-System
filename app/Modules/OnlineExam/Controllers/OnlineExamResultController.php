<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\OnlineExam\Services\OnlineExamResultService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin onlineexam evaluation / student result views.
 * Keeps CI route typo evalution. Deferred: portal answer creation, ranking, reports.
 */
class OnlineExamResultController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamResultService $results
    ) {
    }

    public function index(int $examId): View
    {
        $this->assertCanView();

        $exam = $this->results->exam($examId);

        return view('shared::layouts.admin', [
            'title' => 'Exam Results',
            'contentView' => 'onlineexam::admin.results.index',
            'exam' => $exam,
            'students' => $this->results->assignedStudents($examId),
        ]);
    }

    public function studentResult(int $examId, int $onlineexamStudentId): View
    {
        $this->assertCanView();

        $exam = $this->results->exam($examId);
        $student = $this->results->findAssignedStudent($examId, $onlineexamStudentId);
        $rows = $this->results->resultRows($onlineexamStudentId, $examId);
        $summary = $this->results->scoreSummary($exam, $rows);

        return view('shared::layouts.admin', [
            'title' => 'Student Result',
            'contentView' => 'onlineexam::admin.results.student',
            'exam' => $exam,
            'student' => $student,
            'summary' => $summary,
            'hasAnswers' => $rows->contains(fn ($r) => $r->onlineexam_student_result_id !== null),
            'questionTypes' => [
                'singlechoice' => 'Single Choice',
                'multichoice' => 'Multiple Choice',
                'true_false' => 'True/False',
                'descriptive' => 'Descriptive',
            ],
        ]);
    }

    public function evaluation(Request $request, int $examId): View
    {
        $this->assertCanView();

        $exam = $this->results->exam($examId);
        $filters = [
            'question_id' => $request->input('question_id'),
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        $request->validate([
            'question_id' => ['nullable', 'integer', 'exists:questions,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        return view('shared::layouts.admin', [
            'title' => 'Exam Evaluation',
            'contentView' => 'onlineexam::admin.evaluation.index',
            'exam' => $exam,
            'descriptiveQuestions' => $this->results->descriptiveQuestions($examId),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'filters' => $filters,
            'answers' => $this->results->descriptiveAnswers($examId, $filters),
            'canGrade' => $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
                || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
        ]);
    }

    public function fillMarks(Request $request, int $examId): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
            || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
            403
        );

        $this->results->exam($examId);

        $data = $request->validate([
            'onlineexam_student_result_id' => ['required', 'integer'],
            'question_marks' => ['required', 'numeric', 'min:0'],
            'fill_mark' => ['required', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string'],
        ]);

        // Ensure result belongs to this exam
        $belongs = \Illuminate\Support\Facades\DB::table('onlineexam_student_results')
            ->join('onlineexam_questions', 'onlineexam_questions.id', '=', 'onlineexam_student_results.onlineexam_question_id')
            ->where('onlineexam_student_results.id', (int) $data['onlineexam_student_result_id'])
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->exists();
        abort_unless($belongs, 404);

        $this->results->fillMarks(
            (int) $data['onlineexam_student_result_id'],
            (float) $data['fill_mark'],
            $data['remark'] ?? '',
            (float) $data['question_marks']
        );

        return redirect()
            ->back()
            ->with('success', 'Marks saved successfully.');
    }

    public function downloadAttachment(string $doc): BinaryFileResponse
    {
        $this->assertCanView();

        return $this->results->downloadAttachment($doc);
    }

    protected function assertCanView(): void
    {
        abort_unless(
            $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view')
            || $this->permissions->hasPrivilege('online_examination', 'can_view')
            || $this->permissions->hasPrivilege('online_exam_wise_report', 'can_view'),
            403
        );
    }
}
