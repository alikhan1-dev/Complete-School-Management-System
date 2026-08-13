<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Subject;
use App\Modules\OnlineExam\Services\OnlineExamQuestionService;
use App\Modules\OnlineExam\Services\QuestionBankService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/onlineexam questionAdd / deleteExamQuestions / getExamQuestions.
 * Form POST attach/detach (CI checkbox toggle deferred).
 */
class OnlineExamQuestionController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamQuestionService $examQuestions,
        protected QuestionBankService $questionBank
    ) {
    }

    public function index(Request $request, int $examId): View
    {
        abort_unless($this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'), 403);

        $exam = $this->examQuestions->exam($examId);
        $filters = [
            'subject_id' => $request->input('subject_id'),
            'question_type' => $request->input('question_type'),
            'question_level' => $request->input('question_level'),
            'class_id' => $request->input('class_id'),
            'keyword' => $request->input('keyword'),
        ];

        return view('shared::layouts.admin', [
            'title' => 'Add Questions in Exam',
            'contentView' => 'onlineexam::admin.exam_questions.index',
            'exam' => $exam,
            'attached' => $this->examQuestions->attachedQuestions($examId),
            'available' => $this->examQuestions->availableQuestions($exam, $filters),
            'filters' => $filters,
            'subjects' => Subject::query()->orderBy('name')->get(),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'questionTypes' => $this->questionBank->questionTypes(),
            'questionLevels' => $this->questionBank->questionLevels(),
            'canManage' => $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
                || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
        ]);
    }

    public function attach(Request $request, int $examId): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
            || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
            403
        );

        $exam = $this->examQuestions->exam($examId);
        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'marks' => ['required', 'numeric', 'min:0'],
            'neg_marks' => ['required', 'numeric', 'min:0'],
        ]);

        $this->examQuestions->attach(
            $exam,
            (int) $data['question_id'],
            (float) $data['marks'],
            (float) $data['neg_marks']
        );

        return redirect()
            ->route('onlineexam.exam_questions.index', $examId)
            ->with('success', 'Question attached successfully.');
    }

    public function updateMarks(Request $request, int $examId, int $id): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
            || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
            403
        );

        $exam = $this->examQuestions->exam($examId);
        $data = $request->validate([
            'marks' => ['required', 'numeric', 'min:0'],
            'neg_marks' => ['required', 'numeric', 'min:0'],
        ]);

        $this->examQuestions->updateMarks(
            $exam,
            $id,
            (float) $data['marks'],
            (float) $data['neg_marks']
        );

        return redirect()
            ->route('onlineexam.exam_questions.index', $examId)
            ->with('success', 'Question marks updated successfully.');
    }

    public function detach(int $examId, int $id): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('add_questions_in_exam', 'can_edit')
            || $this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'),
            403
        );

        $exam = $this->examQuestions->exam($examId);
        $this->examQuestions->detach($exam, $id);

        return redirect()
            ->route('onlineexam.exam_questions.index', $examId)
            ->with('success', 'Question removed from exam.');
    }
}
