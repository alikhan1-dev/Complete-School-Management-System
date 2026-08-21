<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineExam\Services\OnlineExamReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report online examinations hub + onlineexams (exams report).
 * Deferred: attempt / rank / result reports, DataTables AJAX, class-teacher scope.
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

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_type' => $request->input('date_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
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
