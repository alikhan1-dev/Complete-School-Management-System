<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Section;
use App\Modules\Homework\Services\HomeworkReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI homework reports hub + homework / evaluation / marks / daily assignment reports.
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

        return view('shared::layouts.admin', array_merge([
            'title' => 'Homework Report',
            'contentView' => 'homework::admin.reports.hub',
        ], $this->navFlags()));
    }

    public function homeworkReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        $filters = $this->filterInput($request);
        $rows = collect();
        if ($request->filled('search') || $request->filled('class_id')) {
            $rows = $this->reports->homeworkReport($filters);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Homework Report',
            'contentView' => 'homework::admin.reports.homework',
            'classes' => $this->reports->classes(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
        ], $this->navFlags()));
    }

    public function homeworkReportStudents(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

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

        return view('shared::layouts.admin', array_merge([
            'title' => 'Homework Evaluation Report',
            'contentView' => 'homework::admin.reports.evaluation',
            'classes' => $this->reports->classes(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'stats' => $stats,
            'requireAllFilters' => true,
        ], $this->navFlags()));
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

        return view('shared::layouts.admin', array_merge([
            'title' => 'Homework Marks Report',
            'contentView' => 'homework::admin.reports.marks',
            'classes' => $this->reports->classes(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'requireClassOnly' => true,
        ], $this->navFlags()));
    }

    public function dailyAssignmentReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        $filters = array_merge($this->filterInput($request), [
            'search_type' => $request->input('search_type', 'this_year'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);

        $rows = collect();
        $range = null;

        if ($request->filled('class_id') || $request->filled('search')) {
            $rules = [
                'search_type' => ['required', 'in:'.implode(',', array_keys($this->reports->searchTypes()))],
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
                'subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
            ];
            if ($request->input('search_type') === 'period') {
                $rules['date_from'] = ['required', 'date'];
                $rules['date_to'] = ['required', 'date', 'after_or_equal:date_from'];
            }
            $request->validate($rules);

            $payload = $this->reports->dailyAssignmentReport($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Daily Assignment Report',
            'contentView' => 'homework::admin.reports.daily',
            'classes' => $this->reports->classes(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searchTypes' => $this->reports->searchTypes(),
            'requireAllFilters' => true,
        ], $this->navFlags()));
    }

    public function dailyAssignmentDetails(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_assignment', 'can_view'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
            'search_type' => ['required', 'in:'.implode(',', array_keys($this->reports->searchTypes()))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'subject_group_id' => ['nullable', 'integer'],
        ]);

        $assignments = $this->reports->dailyAssignmentDetails(
            (int) $data['student_id'],
            (int) $data['subject_id'],
            (string) $data['search_type'],
            $data['date_from'] ?? null,
            $data['date_to'] ?? null
        );

        return view('shared::layouts.admin', [
            'title' => 'Daily Assignment Details',
            'contentView' => 'homework::admin.reports.daily_details',
            'assignments' => $assignments,
            'backUrl' => route('homework.reports.daily', $request->only([
                'search_type', 'date_from', 'date_to', 'class_id', 'section_id', 'subject_group_id', 'subject_id', 'search',
            ])),
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

    /**
     * @return array{canHomeworkReport:bool,canEvaluationReport:bool,canDailyReport:bool,canMarksReport:bool}
     */
    protected function navFlags(): array
    {
        return [
            'canHomeworkReport' => $this->permissions->hasPrivilege('homework', 'can_view'),
            'canEvaluationReport' => $this->permissions->hasPrivilege('homehork_evaluation_report', 'can_view'),
            'canDailyReport' => $this->permissions->hasPrivilege('daily_assignment', 'can_view'),
            'canMarksReport' => $this->permissions->hasPrivilege('homework_marks_report', 'can_view'),
        ];
    }

    protected function canOpenHub(): bool
    {
        $flags = $this->navFlags();

        return $flags['canHomeworkReport']
            || $flags['canEvaluationReport']
            || $flags['canDailyReport']
            || $flags['canMarksReport'];
    }
}
