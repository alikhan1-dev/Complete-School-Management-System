<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Services\LeaveRequestService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CI admin/staff/leaverequest — staff self-apply leave portal (privilege apply_leave).
 */
class StaffApplyLeaveController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LeaveRequestService $requests,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('apply_leave', 'can_view'), 403);

        $staffId = (int) (Auth::guard('staff')->id() ?? 0);

        return view('shared::layouts.admin', [
            'title' => 'Apply Leave',
            'contentView' => 'leave::admin.staff_apply.index',
            'leave_request' => $this->requests->listRequests($staffId),
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
            'canAdd' => $this->permissions->hasPrivilege('apply_leave', 'can_add'),
            'authStaffId' => $staffId,
        ]);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('apply_leave', 'can_add'), 403);

        $staffId = (int) (Auth::guard('staff')->id() ?? 0);

        return view('shared::layouts.admin', [
            'title' => 'Apply Leave',
            'contentView' => 'leave::admin.staff_apply.form',
            'availableLeaves' => $this->requests->availableLeaveTypes($staffId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('apply_leave', 'can_add'), 403);

        $validated = $request->validate([
            'applieddate' => ['required', 'date'],
            'leave_from_date' => ['required', 'date'],
            'leave_to_date' => ['required', 'date', 'after_or_equal:leave_from_date'],
            'leave_type' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:200'],
            'half_day_leave' => ['nullable'],
            'userfile' => ['nullable', 'file', 'max:5120'],
        ]);

        $this->requests->saveSelfApply($validated, $request->file('userfile'));

        return redirect()
            ->route('leave.staff_apply.index')
            ->with('success', 'Leave applied successfully.');
    }

    public function view(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('apply_leave', 'can_view'), 403);

        $record = $this->requests->getRecord($id);
        abort_if($record === null, 404);

        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless((int) $record['staff_id'] === $staffId, 403);

        return view('shared::layouts.admin', [
            'title' => 'Leave Details',
            'contentView' => 'leave::admin.staff_apply.view',
            'record' => $record,
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('apply_leave', 'can_view'), 403);

        $record = $this->requests->getRecord($id);
        abort_if($record === null, 404);

        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless((int) $record['staff_id'] === $staffId, 403);

        $status = (string) ($record['status'] ?? '');
        if (in_array($status, ['approve', 'approved'], true)) {
            return redirect()
                ->route('leave.staff_apply.index')
                ->with('warning', 'Approved leave cannot be deleted.');
        }

        $this->requests->delete($id);

        return redirect()
            ->route('leave.staff_apply.index')
            ->with('success', 'Leave deleted successfully.');
    }
}
