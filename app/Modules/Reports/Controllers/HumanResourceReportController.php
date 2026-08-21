<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\HumanResourceReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report::human_resource + staff_report.
 */
class HumanResourceReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HumanResourceReportService $reports,
    ) {
    }

    public function human_resource(): View
    {
        return view('shared::layouts.admin', array_merge([
            'title' => __('system.human_resource_report'),
            'contentView' => 'reports::admin.human_resource.hub',
        ], $this->navFlags()));
    }

    public function staff_report(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'staff_status' => $request->input('staff_status', '1'),
            'role' => $request->input('role', ''),
            'designation' => $request->input('designation', ''),
        ];

        // CI always loads resultlist; status filter only when staff_status was POSTed.
        $applyStatus = $request->isMethod('post') && $request->has('staff_status');

        $filterLabel = '';
        $searchType = trim((string) $filters['search_type']);
        if ($searchType !== '') {
            $range = $this->reports->dateRange(
                $searchType,
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $filterLabel = $this->reports->formatDate($range['from']).' To '.$this->reports->formatDate($range['to']);
        }

        $resultlist = $this->reports->staffReport([
            'search_type' => $searchType,
            'date_from' => (string) $filters['date_from'],
            'date_to' => (string) $filters['date_to'],
            'staff_status' => (string) $filters['staff_status'],
            'role' => $filters['role'],
            'designation' => $filters['designation'],
            'apply_status' => $applyStatus,
        ]);

        $leaveTypeMap = $this->reports->leaveTypeMap();
        $customFields = $this->reports->staffTableCustomFields();

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.staff_report'),
            'contentView' => 'reports::admin.human_resource.staff_report',
            'filters' => $filters,
            'filterLabel' => $filterLabel,
            'resultlist' => $resultlist,
            'leaveTypeMap' => $leaveTypeMap,
            'fields' => $customFields,
            'statusOptions' => $this->reports->staffStatuses(),
            'roles' => $this->reports->roles(),
            'designations' => $this->reports->designations(),
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canStaffReport' => $this->permissions->hasPrivilege('staff_report', 'can_view'),
            'canPayrollReport' => $this->permissions->hasPrivilege('payroll_report', 'can_view'),
            'canStaffLeaveRequestReport' => $this->permissions->hasPrivilege('staff_leave_request_report', 'can_view'),
            'canMyLeaveRequestReport' => $this->permissions->hasPrivilege('my_leave_request_report', 'can_view'),
        ];
    }
}
