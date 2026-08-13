<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Homework\Services\HomeworkReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI homework reports hub + homework / evaluation / marks reports.
 * Deferred: dailyassignmentreport.
 */
class HomeworkReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HomeworkReportService $reports,
    ) {
    }

    public function hub(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', [
            'title' => 'Homework Report',
            'contentView' => 'homework::admin.reports.hub',
            'canHomeworkReport' => $this->permissions->hasPrivilege('homework', 'can_view'),
            'canEvaluationReport' => $this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view'),
            'canDailyReport' => false, // deferred
            'canMarksReport' => $this->permissions->hasPrivilege('homework_marks_report', 'can_view'),
        ]);
    }

    public function homeworkReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();
        if ($request->filled('search') || $request->filled('class_id')) {
            $rows = $this->reports->homeworkReport($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Homework Report',
            'contentView' => 'homework::admin.reports.homework',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'canHomeworkReport' => true,
            'canEvaluationReport' => $this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view'),
            'canDailyReport' => false,
            'canMarksReport' => $this->permissions->hasPrivilege('homework_marks_report', 'can_view'),
        ]);
    }

    public function homeworkReportStudents(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);

        $data = $request->validate([
            'homework_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'type' => ['required', 'in:student_count,homework_submitted,pending_student'],
        ]);

        $labels = [
            'student_count' => 'Student Count',
            'homework_submitted' => 'Homework Submitted',
            'pending_student' => 'Pending Student',
        ];

        return view('shared::layouts.admin', [
            'title' => $labels[$data['type']] ?? 'Student List',
            'contentView' => 'homework::admin.reports.students',
            'type' => $data['type'],
            'typeLabel' => $labels[$data['type']] ?? 'Student List',
            'students' => $this->reports->homeworkReportStudents(
                (int) $data['homework_id'],
                (string) $data['type'],
                (int) $data['class_id'],
                (int) $data['section_id']
            ),
            'backUrl' => route('homework.reports.homework', $request->only([
                'class_id', 'section_id', 'subject_group_id', 'subject_id', 'search',
            ])),
        ]);
    }

    public function evaluationReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();
        $stats = [];

        if ($request->filled('class_id')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
                'subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
            ]);
            $payload = $this->reports->evaluationReport($filters);
            $rows = $payload['rows'];
            $stats = $payload['stats'];
        }

        return view('shared::layouts.admin', [
            'title' => 'Homework Evaluation Report',
            'contentView' => 'homework::admin.reports.evaluation',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'stats' => $stats,
            'requireAllFilters' => true,
            'canHomeworkReport' => $this->permissions->hasPrivilege('homework', 'can_view'),
            'canEvaluationReport' => true,
            'canDailyReport' => false,
            'canMarksReport' => $this->permissions->hasPrivilege('homework_marks_report', 'can_view'),
        ]);
    }

    public function marksReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework_marks_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();

        if ($request->filled('class_id')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['nullable', 'integer', 'exists:subject_groups,id'],
                'subject_id' => ['nullable', 'integer', 'exists:subject_group_subjects,id'],
            ]);
            $rows = $this->reports->marksReport($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Homework Marks Report',
            'contentView' => 'homework::admin.reports.marks',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'requireClassOnly' => true,
            'canHomeworkReport' => $this->permissions->hasPrivilege('homework', 'can_view'),
            'canEvaluationReport' => $this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view'),
            'canDailyReport' => false,
            'canMarksReport' => true,
        ]);
    }

    /**
     * @return array{class_id:?string,section_id:?string,subject_group_id:?string,subject_id:?string}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_group_id' => $request->input('subject_group_id'),
            'subject_id' => $request->input('subject_id'),
        ];
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('homework', 'can_view')
            || $this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view')
            || $this->permissions->hasPrivilege('daily_assignment', 'can_view')
            || $this->permissions->hasPrivilege('homework_marks_report', 'can_view');
    }
}
