<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Exams\Services\ExamLinkService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examgroup connectexams + ajaxConnectForm —
 * link exams in a group with weightages (form POST; AJAX modal deferred).
 */
class ExamLinkController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamLinkService $links
    ) {
    }

    public function index(int $groupId): View
    {
        abort_unless($this->permissions->hasPrivilege('link_exam', 'can_view'), 403);

        $group = $this->examGroups->findGroup($groupId);

        return view('shared::layouts.admin', [
            'title' => 'Link Exams',
            'contentView' => 'exams::admin.exam_link.index',
            'group' => $group,
            'examTypes' => $this->examGroups->examTypes(),
            'exams' => $this->links->examsForLink($groupId),
            'canEdit' => $this->permissions->hasPrivilege('link_exam', 'can_edit'),
        ]);
    }

    public function save(Request $request, int $groupId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('link_exam', 'can_edit'), 403);

        $this->examGroups->findGroup($groupId);

        $data = $request->validate([
            'exam' => ['required', 'array', 'min:1'],
            'exam.*' => ['integer'],
            'weightage' => ['nullable', 'array'],
            'weightage.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $weightageByExamId = [];
        foreach ($data['exam'] as $examId) {
            $examId = (int) $examId;
            $weightageByExamId[$examId] = $data['weightage'][$examId] ?? 0;
        }

        $this->links->connectExams($groupId, $weightageByExamId);

        return redirect()
            ->route('exams.exam_links.index', $groupId)
            ->with('success', 'Exam connected successfully.');
    }

    public function reset(int $groupId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('link_exam', 'can_edit'), 403);

        $this->examGroups->findGroup($groupId);
        $this->links->resetConnections($groupId);

        return redirect()
            ->route('exams.exam_links.index', $groupId)
            ->with('success', 'Exam link reset successfully.');
    }
}
