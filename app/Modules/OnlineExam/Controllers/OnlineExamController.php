<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineExam\Services\OnlineExamRankService;
use App\Modules\OnlineExam\Services\OnlineExamService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Onlineexam — exam definition CRUD + open/closed lists.
 * Deferred: mail/SMS, print, SaaS storage quota.
 */
class OnlineExamController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamService $exams,
        protected OnlineExamRankService $ranks,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('online_examination', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Online Examinations',
            'contentView' => 'onlineexam::admin.exam.index',
            'openExams' => $this->exams->listOpenExams(),
            'closedExams' => $this->exams->listClosedExams(),
            'editing' => null,
            'examFromInput' => '',
            'examToInput' => '',
            'autoPublishInput' => '',
            'canAdd' => $this->permissions->hasPrivilege('online_examination', 'can_add'),
            'ranks' => $this->ranks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_examination', 'can_add'), 403);

        $this->validateExam($request);
        $this->exams->create($this->exams->buildPayload($request));

        return redirect()->route('onlineexam.exams.index')->with('success', 'Online exam saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('online_examination', 'can_edit'), 403);

        $editing = $this->exams->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Online Exam',
            'contentView' => 'onlineexam::admin.exam.index',
            'openExams' => $this->exams->listOpenExams(),
            'closedExams' => $this->exams->listClosedExams(),
            'editing' => $editing,
            'examFromInput' => $this->exams->toInputDateTime($editing->exam_from),
            'examToInput' => $this->exams->toInputDateTime($editing->exam_to),
            'autoPublishInput' => $this->exams->toInputDateTime($editing->auto_publish_date),
            'canAdd' => false,
            'ranks' => $this->ranks,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_examination', 'can_edit'), 403);

        $exam = $this->exams->find($id);
        $this->validateExam($request);
        $this->exams->update($exam, $this->exams->buildPayload($request, $exam));

        return redirect()->route('onlineexam.exams.index')->with('success', 'Online exam updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_examination', 'can_delete'), 403);

        $this->exams->delete($this->exams->find($id));

        return redirect()->route('onlineexam.exams.index')->with('success', 'Online exam deleted successfully.');
    }

    protected function validateExam(Request $request): void
    {
        $request->validate([
            'exam' => ['required', 'string'],
            'attempt' => ['required', 'integer', 'min:1'],
            'exam_from' => ['required', 'string'],
            'exam_to' => ['required', 'string'],
            'duration' => ['required', 'string', 'regex:/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', 'not_in:00:00:00'],
            'description' => ['required', 'string'],
            'passing_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'word_limit' => ['required', 'integer', 'not_in:0'],
            'auto_publish_date' => ['nullable', 'string'],
            'is_active' => ['nullable'],
            'publish_result' => ['nullable'],
            'is_marks_display' => ['nullable'],
            'is_neg_marking' => ['nullable'],
            'is_random_question' => ['nullable'],
            'is_quiz' => ['nullable'],
        ], [
            'duration.regex' => 'Duration must be HH:MM:SS (00–23 hours).',
            'duration.not_in' => 'Duration cannot be 00:00:00.',
            'word_limit.not_in' => 'Answer word limit cannot be zero (use -1 for unlimited).',
        ]);
    }
}
