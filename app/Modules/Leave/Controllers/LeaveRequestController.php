<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Services\LeaveRequestService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/leaverequest — approve leave request list + form add/edit/status/delete.
 * Prefer form pages over CI AJAX modals.
 */
class LeaveRequestController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LeaveRequestService $requests,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Approve Leave Request',
            'contentView' => 'leave::admin.staffleaverequest',
            'leave_request' => $this->requests->listRequests(),
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
            'canAdd' => $this->permissions->hasPrivilege('approve_leave_request', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('approve_leave_request', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('approve_leave_request', 'can_delete'),
            'authStaffId' => (int) (Auth::guard('staff')->id() ?? 0),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_add'), 403);

        $roleId = (int) $request->input('role', 0);
        $staffId = (int) $request->input('empname', 0);

        return view('shared::layouts.admin', [
            'title' => 'Add Leave Request',
            'contentView' => 'leave::admin.leave_form',
            'editing' => null,
            'staffrole' => $this->requests->staffRoles(),
            'employees' => $roleId > 0 ? $this->requests->employeesByRoleId($roleId) : [],
            'availableLeaves' => $staffId > 0 ? $this->requests->availableLeaveTypes($staffId) : [],
            'selectedRole' => $roleId,
            'selectedStaff' => $staffId,
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_add'), 403);

        $validated = $this->validatedLeave($request);
        $this->requests->saveRequest($validated, $request->file('userfile'));

        return redirect()
            ->route('leave.requests.index')
            ->with('success', 'Leave request saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_edit'), 403);

        $record = $this->requests->getRecord($id);
        abort_if($record === null, 404);

        $roleId = (int) ($record['staff_role'] ?? 0);
        $staffId = (int) ($record['staff_id'] ?? 0);
        $availableLeaves = $staffId > 0 ? $this->requests->availableLeaveTypes($staffId) : [];
        $currentTypeId = (int) ($record['leave_type_id'] ?? $record['lid'] ?? 0);
        if ($currentTypeId > 0 && ! collect($availableLeaves)->contains(fn ($l) => (int) $l['id'] === $currentTypeId)) {
            $availableLeaves[] = [
                'id' => $currentTypeId,
                'type' => (string) ($record['type'] ?? 'Leave'),
                'alloted_leave' => 0,
                'approve_leave' => 0,
                'available' => (float) ($record['leave_days'] ?? 0),
            ];
        }

        return view('shared::layouts.admin', [
            'title' => 'Edit Leave Request',
            'contentView' => 'leave::admin.leave_form',
            'editing' => $record,
            'staffrole' => $this->requests->staffRoles(),
            'employees' => $roleId > 0 ? $this->requests->employeesByRoleId($roleId) : [],
            'availableLeaves' => $availableLeaves,
            'selectedRole' => $roleId,
            'selectedStaff' => $staffId,
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_edit'), 403);

        $validated = $this->validatedLeave($request);
        $this->requests->saveRequest($validated, $request->file('userfile'), $id);

        return redirect()
            ->route('leave.requests.index')
            ->with('success', 'Leave request updated successfully.');
    }

    public function statusForm(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_edit'), 403);

        $record = $this->requests->getRecord($id);
        abort_if($record === null, 404);

        return view('shared::layouts.admin', [
            'title' => 'Leave Details',
            'contentView' => 'leave::admin.leave_status',
            'record' => $record,
            'statusLabels' => LeaveRequestService::STATUS_LABELS,
            'canEdit' => $this->permissions->hasPrivilege('approve_leave_request', 'can_edit'),
        ]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_edit'), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,disapprove'],
            'detailremark' => ['nullable', 'string', 'max:200'],
        ]);

        $this->requests->changeStatus($id, [
            'status' => $validated['status'],
            'admin_remark' => $validated['detailremark'] ?? '',
        ]);

        return redirect()
            ->route('leave.requests.index')
            ->with('success', 'Leave status updated successfully.');
    }

    public function destroy(int $id, ?int $staffId = null): RedirectResponse
    {
        $record = $this->requests->getRecord($id);
        abort_if($record === null, 404);

        $authId = (int) (Auth::guard('staff')->id() ?? 0);
        $isOwn = (int) ($record['applied_by_id'] ?? 0) === $authId;
        if (! $isOwn) {
            abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_delete'), 403);
        }

        $this->requests->delete($id);

        return redirect()
            ->route('leave.requests.index')
            ->with('success', 'Leave request deleted successfully.');
    }

    public function download(int $staffId, int $id): BinaryFileResponse|Response
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_view'), 403);

        $record = $this->requests->getRecord($id);
        abort_if($record === null || (int) $record['staff_id'] !== $staffId, 404);
        abort_if(empty($record['document_file']), 404);

        $path = $this->requests->documentPath($staffId, (string) $record['document_file']);
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }

    /**
     * CI countLeave — HTML options for available leave types.
     */
    public function countLeave(int $id, Request $request): Response
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave_request', 'can_view'), 403);

        $lid = (int) $request->input('lid', 0);
        $html = "<select name='leave_type' id='leave_type' class='form-control'><option value=''>Select</option>";
        foreach ($this->requests->availableLeaveTypes($id) as $dvalue) {
            $selected = $lid === (int) $dvalue['id'] ? 'selected' : '';
            $html .= '<option value="'.$dvalue['id'].'" '.$selected.'>'
                .e($dvalue['type']).' ('.$dvalue['available'].')</option>';
        }
        $html .= '</select>';

        return response($html);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedLeave(Request $request): array
    {
        return $request->validate([
            'role' => ['required', 'integer'],
            'empname' => ['required', 'integer'],
            'applieddate' => ['required', 'date'],
            'leave_from_date' => ['required', 'date'],
            'leave_to_date' => ['required', 'date', 'after_or_equal:leave_from_date'],
            'leave_type' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:200'],
            'remark' => ['nullable', 'string', 'max:200'],
            'addstatus' => ['required', 'in:pending,approved,disapprove'],
            'half_day_leave' => ['nullable'],
            'userfile' => ['nullable', 'file', 'max:5120'],
        ]);
    }
}
