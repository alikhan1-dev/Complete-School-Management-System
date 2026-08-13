<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Services\LeaveRequestService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CI report/leaverequestreport + myleaverequestreport.
 */
class LeaveReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LeaveRequestService $requests,
    ) {
    }

    public function leaveRequestReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_leave_request_report', 'can_view'), 403);

        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'joining_date' => $request->input('joining_date'),
            'staff_id' => $request->input('staff_name') ?: null,
            'leave_status' => $request->input('leave_status') ?: null,
        ];

        $resultlist = [];
        if ($request->isMethod('post') || $request->filled('search')) {
            $resultlist = $this->requests->leaveRequestReport($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Leave Request Report',
            'contentView' => 'leave::admin.reports.leaverequestreport',
            'from_date' => $filters['from_date'],
            'to_date' => $filters['to_date'],
            'joining_date' => $filters['joining_date'],
            'staff_name' => $filters['staff_id'],
            'leave_status' => $filters['leave_status'],
            'statusOptions' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'disapprove' => 'Disapproved',
            ],
            'staff_list' => $this->requests->activeStaffList(),
            'resultlist' => $resultlist,
            'searched' => $request->isMethod('post') || $request->filled('search'),
        ]);
    }

    public function myLeaveRequestReport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('my_leave_request_report', 'can_view'), 403);

        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'staff_id' => $staffId,
            'leave_status' => $request->input('leave_status') ?: null,
        ];

        $resultlist = [];
        if ($request->isMethod('post') || $request->filled('search')) {
            $resultlist = $this->requests->leaveRequestReport($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'My Leave Request Report',
            'contentView' => 'leave::admin.reports.myleaverequestreport',
            'from_date' => $filters['from_date'],
            'to_date' => $filters['to_date'],
            'leave_status' => $filters['leave_status'],
            'statusOptions' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'disapprove' => 'Disapproved',
            ],
            'resultlist' => $resultlist,
            'searched' => $request->isMethod('post') || $request->filled('search'),
        ]);
    }
}
