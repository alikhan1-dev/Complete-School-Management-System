<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineExam\Services\OnlineExamReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report online examinations hub + onlineexams + onlineexamattend + result + rank.
 * Deferred: DataTables AJAX / modal print.
 */
class OnlineExamReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamReportService $reports,
    ) {
    }

    public function hub(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.online_examinations_report'),
            'contentView' => 'onlineexam::admin.reports.hub',
        ], $this->navFlags()));
    }

    public function onlineexams(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('online_exams_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = [];
        $searched = false;
        $rangeLabel = '';

        if ($request->isMethod('post')) {
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $rangeLabel = $this->reports->formatDate($range['from']).' '.__('system.to').' '.$this->reports->formatDate($range['to']);
            $rows = $this->reports->examsReport(
                (string) $filters['search_type'],
                (string) $filters['date_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.exams_report'),
            'contentView' => 'onlineexam::admin.reports.exams',
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'rangeLabel' => $rangeLabel,
            'searchTypes' => $this->reports->searchTypes(),
            'dateTypes' => $this->reports->dateTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function onlineexamattend(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('online_exams_attempt_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = [];
        $searched = false;
        $rangeLabel = '';

        if ($request->isMethod('post')) {
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $rangeLabel = $this->reports->formatDate($range['from']).' '.__('system.to').' '.$this->reports->formatDate($range['to']);
            $rows = $this->reports->attemptReport(
                (string) $filters['search_type'],
                (string) $filters['date_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.exam_attempt_report'),
            'contentView' => 'onlineexam::admin.reports.attempt',
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'rangeLabel' => $rangeLabel,
            'searchTypes' => $this->reports->searchTypes(),
            'dateTypes' => $this->reports->dateTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    /**
     * CI admin/onlineexam/report (online_exam_wise_report).
     */
    public function report(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('online_exam_wise_report', 'can_view'), 403);

        $filters = [
            'exam_id' => $request->input('exam_id', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $rows = [];
        $searched = false;
        $errors = [];

        if ($request->isMethod('post')) {
            $searched = true;
            if ($filters['exam_id'] === '' || $filters['exam_id'] === null) {
                $errors['exam_id'] = 'The '.__('system.exam').' field is required.';
            }
            if ($filters['class_id'] === '' || $filters['class_id'] === null) {
                $errors['class_id'] = 'The '.__('system.class').' field is required.';
            }
            if ($filters['section_id'] === '' || $filters['section_id'] === null) {
                $errors['section_id'] = 'The '.__('system.section').' field is required.';
            }
            if ($errors === []) {
                $rows = $this->reports->resultReport(
                    (int) $filters['exam_id'],
                    (int) $filters['class_id'],
                    (int) $filters['section_id']
                );
            }
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.result_report'),
            'contentView' => 'onlineexam::admin.reports.result',
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'errors' => $errors,
            'examList' => $this->reports->examsForCurrentSession(),
            'classlist' => $this->reports->classes(),
            'sectionOptions' => $this->reports->sectionsForClass((int) ($filters['class_id'] ?: 0)),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    /**
     * CI report/onlineexamrank (online_exams_rank_report).
     * exam_id required; class/section optional. Ranking generation deferred.
     */
    public function onlineexamrank(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('online_exams_rank_report', 'can_view'), 403);

        $filters = [
            'exam_id' => $request->input('exam_id', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $exam = null;
        $rows = [];
        $searched = false;
        $errors = [];

        if ($request->isMethod('post')) {
            $searched = true;
            if ($filters['exam_id'] === '' || $filters['exam_id'] === null) {
                $errors['exam_id'] = 'The '.__('system.exam').' field is required.';
            }
            if ($errors === []) {
                $result = $this->reports->rankReport(
                    (int) $filters['exam_id'],
                    $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
                    $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                );
                $exam = $result['exam'];
                $rows = $result['rows'];
            }
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.exam_rank_report'),
            'contentView' => 'onlineexam::admin.reports.rank',
            'filters' => $filters,
            'exam' => $exam,
            'rows' => $rows,
            'searched' => $searched,
            'errors' => $errors,
            'examList' => $this->reports->examsForCurrentSession(),
            'classlist' => $this->reports->classes(),
            'sectionOptions' => $this->reports->sectionsForClass((int) ($filters['class_id'] ?: 0)),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    /**
     * @return array{search_type: mixed, date_type: mixed, date_from: mixed, date_to: mixed}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'search_type' => $request->input('search_type', ''),
            'date_type' => $request->input('date_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('online_exam_wise_report', 'can_view')
            || $this->permissions->hasPrivilege('online_exams_report', 'can_view')
            || $this->permissions->hasPrivilege('online_exams_attempt_report', 'can_view')
            || $this->permissions->hasPrivilege('online_exams_rank_report', 'can_view');
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canOnlineExamWiseReport' => $this->permissions->hasPrivilege('online_exam_wise_report', 'can_view'),
            'canOnlineExamsReport' => $this->permissions->hasPrivilege('online_exams_report', 'can_view'),
            'canOnlineExamsAttemptReport' => $this->permissions->hasPrivilege('online_exams_attempt_report', 'can_view'),
            'canOnlineExamsRankReport' => $this->permissions->hasPrivilege('online_exams_rank_report', 'can_view'),
        ];
    }
}
