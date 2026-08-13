<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Homework\Services\AdminDailyAssignmentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI homework/dailyassignment — admin list + remark.
 * Deferred: reports.
 */
class AdminDailyAssignmentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected AdminDailyAssignmentService $daily,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_group_id' => $request->input('subject_group_id'),
            'subject_id' => $request->input('subject_id'),
            'date' => $request->input('date'),
        ];

        $rows = collect();
        if ($request->filled('class_id')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
                'subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
                'date' => ['required', 'date'],
            ]);
            $rows = $this->daily->search($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Daily Assignment',
            'contentView' => 'homework::admin.daily.index',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'canEvaluate' => $this->permissions->hasPrivilege('daily_assignment', 'can_view'),
        ]);
    }

    public function evaluate(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Evaluate Daily Assignment',
            'contentView' => 'homework::admin.daily.evaluate',
            'row' => $this->daily->findDetailed($id),
        ]);
    }

    public function saveRemark(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        $data = $request->validate([
            'assigment_id' => ['required', 'integer'],
            'evaluation_date' => ['required', 'date'],
            'remark' => ['nullable', 'string'],
        ]);

        $this->daily->saveRemark(
            (int) $data['assigment_id'],
            (string) $data['evaluation_date'],
            $data['remark'] ?? ''
        );

        $detail = $this->daily->findDetailed((int) $data['assigment_id']);

        return redirect()
            ->route('homework.daily.index', [
                'class_id' => $detail->class_id,
                'section_id' => $detail->section_id,
                'subject_group_id' => $detail->subject_group_id,
                'subject_id' => $detail->subject_group_subject_id,
                'date' => $detail->date,
            ])
            ->with('success', 'Daily assignment evaluation completed successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        return $this->daily->download($id);
    }
}
