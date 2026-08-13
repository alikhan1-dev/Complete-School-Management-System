<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Subject;
use App\Modules\OnlineExam\Services\QuestionBankService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CI admin/Question — question bank CRUD (form POST; AJAX/CSV/images deferred).
 */
class QuestionBankController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected QuestionBankService $questions
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Question Bank',
            'contentView' => 'onlineexam::admin.question.index',
            'questions' => $this->questions->listQuestions(),
            'editing' => null,
            'formData' => $this->formSharedData(),
            'canAdd' => $this->permissions->hasPrivilege('question_bank', 'can_add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_add'), 403);

        $this->validateQuestion($request);
        $this->questions->create($this->questions->buildPayload($request));

        return redirect()->route('onlineexam.questions.index')->with('success', 'Question saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_edit'), 403);

        $editing = $this->questions->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Question',
            'contentView' => 'onlineexam::admin.question.index',
            'questions' => $this->questions->listQuestions(),
            'editing' => $editing,
            'selectedAnswers' => $this->questions->decodedMultichoiceAnswers($editing->correct),
            'formData' => $this->formSharedData(),
            'canAdd' => false,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_edit'), 403);

        $question = $this->questions->find($id);
        $this->validateQuestion($request);
        $this->questions->update($question, $this->questions->buildPayload($request, $question));

        return redirect()->route('onlineexam.questions.index')->with('success', 'Question updated successfully.');
    }

    public function read(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_view'), 403);

        $question = $this->questions->find($id);
        $row = \Illuminate\Support\Facades\DB::table('questions')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->leftJoin('classes', 'classes.id', '=', 'questions.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'questions.section_id')
            ->where('questions.id', $id)
            ->select([
                'questions.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'classes.class as class_name',
                'sections.section as section_name',
            ])
            ->first();

        abort_unless($row, 404);

        return view('shared::layouts.admin', [
            'title' => 'View Question',
            'contentView' => 'onlineexam::admin.question.read',
            'question' => $row,
            'questionTypes' => $this->questions->questionTypes(),
            'questionLevels' => $this->questions->questionLevels(),
            'optionKeys' => $this->questions->optionKeys(),
            'selectedAnswers' => $this->questions->decodedMultichoiceAnswers($question->correct),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('question_bank', 'can_delete'), 403);

        $this->questions->delete($this->questions->find($id));

        return redirect()->route('onlineexam.questions.index')->with('success', 'Question deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formSharedData(): array
    {
        return [
            'subjects' => Subject::query()->orderBy('name')->get(),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'questionTypes' => $this->questions->questionTypes(),
            'questionLevels' => $this->questions->questionLevels(),
            'optionKeys' => $this->questions->optionKeys(),
            'trueFalseOptions' => $this->questions->trueFalseOptions(),
        ];
    }

    protected function validateQuestion(Request $request): void
    {
        $type = (string) $request->input('question_type');
        $rules = [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string'],
            'question_type' => ['required', 'string', Rule::in(array_keys($this->questions->questionTypes()))],
            'question_level' => ['required', 'string', Rule::in(array_keys($this->questions->questionLevels()))],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'opt_a' => ['nullable', 'string'],
            'opt_b' => ['nullable', 'string'],
            'opt_c' => ['nullable', 'string'],
            'opt_d' => ['nullable', 'string'],
            'opt_e' => ['nullable', 'string'],
            'correct' => ['nullable', 'string'],
            'correct_true_false' => ['nullable', 'string'],
            'ans' => ['nullable', 'array'],
            'ans.*' => ['string', Rule::in(array_keys($this->questions->optionKeys()))],
        ];

        if ($type === 'singlechoice') {
            $rules['opt_a'] = ['required', 'string'];
            $rules['opt_b'] = ['required', 'string'];
            $rules['correct'] = ['required', 'string', Rule::in(array_keys($this->questions->optionKeys()))];
        } elseif ($type === 'true_false') {
            $rules['correct_true_false'] = ['required', 'string', Rule::in(array_keys($this->questions->trueFalseOptions()))];
        } elseif ($type === 'multichoice') {
            $rules['opt_a'] = ['required', 'string'];
            $rules['opt_b'] = ['required', 'string'];
            $rules['ans'] = ['required', 'array', 'min:1'];
        }

        $request->validate($rules);
    }
}
