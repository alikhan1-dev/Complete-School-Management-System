<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineExam\Services\OnlineExamRankService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CI admin/onlineexam rankgenerate + saverank.
 */
class OnlineExamRankController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamRankService $ranks,
    ) {
    }

    public function show(int $examId): View
    {
        $this->assertCanView();

        $exam = $this->ranks->exam($examId);
        abort_unless($this->ranks->canGenerateRank($exam), 403);

        return view('shared::layouts.admin', [
            'title' => __('system.generate_rank').' — '.$exam->exam,
            'contentView' => 'onlineexam::admin.rank.generate',
            'exam' => $exam,
            'students' => $this->ranks->attemptedStudents($examId),
            'ranks' => $this->ranks,
        ]);
    }

    public function save(int $examId): RedirectResponse
    {
        $this->assertCanView();

        $exam = $this->ranks->exam($examId);
        $this->ranks->saveRanks($exam);

        return redirect()
            ->route('onlineexam.rank.show', $examId)
            ->with('success', __('system.success_message'));
    }

    protected function assertCanView(): void
    {
        abort_unless($this->permissions->hasPrivilege('add_questions_in_exam', 'can_view'), 403);
    }
}
