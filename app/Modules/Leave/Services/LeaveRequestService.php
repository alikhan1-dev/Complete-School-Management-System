<?php

namespace App\Modules\Leave\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Roles\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * CI Leaverequest_model + admin leave request flows.
 * Deferred: SaaS storage quota, mail/SMS, superadmin_visible filter, staff self-apply portal.
 */
class LeaveRequestService
{
    /** @var array<string, string> CI payroll.php status (keys used in UI labels) */
    public const STATUS_LABELS = [
        'approve' => 'Approved',
        'disapprove' => 'Disapproved',
        'pending' => 'Pending',
    ];

    public function __construct(
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    public function currentSessionId(): int
    {
        $id = $this->currentSession->id();
        if ($id <= 0) {
            throw new RuntimeException('Current academic session is not configured in sch_settings.');
        }

        return $id;
    }

    /**
     * @return Collection<int, object{id: int, type: string}>
     */
    public function staffRoles(): Collection
    {
        return Role::query()
            ->where('is_active', 'yes')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => (object) [
                'id' => (int) $role->id,
                'type' => (string) $role->name,
            ]);
    }

    /**
     * CI staff_leave_request list for current session.
     *
     * @return list<array<string, mixed>>
     */
    public function listRequests(?int $staffId = null): array
    {
        $sessionId = $this->currentSessionId();

        $query = DB::table('staff_leave_request')
            ->join('staff', 'staff.id', '=', 'staff_leave_request.staff_id')
            ->join('leave_types', 'leave_types.id', '=', 'staff_leave_request.leave_type_id')
            ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->join('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff.is_active', 1)
            ->where('staff_leave_request.session_id', $sessionId)
            ->orderByDesc('staff_leave_request.id')
            ->select([
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff_leave_request.*',
                'leave_types.type',
                'staff_leave_request.applied_by as applied_by_id',
            ]);

        if ($staffId !== null) {
            $query->where('staff_leave_request.staff_id', $staffId);
        }

        $rows = $query->get()->map(fn ($row) => (array) $row)->all();

        foreach ($rows as $key => $value) {
            $appliedById = (int) ($value['applied_by_id'] ?? $value['applied_by'] ?? 0);
            $applied = $appliedById > 0
                ? DB::table('staff')->where('id', $appliedById)->first(['name', 'surname', 'employee_id'])
                : null;
            if ($applied && ! empty($applied->employee_id)) {
                $rows[$key]['applied_by_label'] = trim($applied->name.' '.$applied->surname).' ('.$applied->employee_id.')';
            } else {
                $rows[$key]['applied_by_label'] = '';
            }
            $rows[$key]['applied_by_id'] = $appliedById;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRecord(int $id): ?array
    {
        $row = DB::table('staff_leave_request')
            ->join('leave_types', 'leave_types.id', '=', 'staff_leave_request.leave_type_id')
            ->join('staff', 'staff.id', '=', 'staff_leave_request.staff_id')
            ->join('staff_roles', 'staff.id', '=', 'staff_roles.staff_id')
            ->join('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff_leave_request.id', $id)
            ->select([
                'leave_types.type',
                'leave_types.id as lid',
                'roles.id as staff_role',
                'staff.name',
                'staff.surname',
                'staff.id as staff_id',
                'roles.name as user_type',
                'staff.employee_id',
                'staff_leave_request.*',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        $data = (array) $row;
        $appliedById = (int) ($data['applied_by'] ?? 0);
        $applied = $appliedById > 0
            ? DB::table('staff')->where('id', $appliedById)->first(['name', 'surname', 'employee_id'])
            : null;
        $data['applied_by_id'] = $appliedById;
        $data['applied_by_label'] = ($applied && ! empty($applied->employee_id))
            ? trim($applied->name.' '.$applied->surname).' ('.$applied->employee_id.')'
            : '';
        $data['days'] = $this->dateDifference((string) $data['leave_from'], (string) $data['leave_to']);

        return $data;
    }

    /**
     * Staff by role name (CI getEmployee uses role name in some places; here by role id for form).
     *
     * @return list<array<string, mixed>>
     */
    public function employeesByRoleId(int $roleId): array
    {
        return DB::table('staff')
            ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->join('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.is_active', 1)
            ->where('roles.id', $roleId)
            ->orderBy('staff.name')
            ->select([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'roles.name as role',
            ])
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * CI allotedLeaveType + available balance for dropdown.
     *
     * @return list<array{id: int, type: string, alloted_leave: float|int|string, approve_leave: float|int|string, available: float}>
     */
    public function availableLeaveTypes(int $staffId): array
    {
        $sessionId = $this->currentSessionId();
        $alloted = DB::table('staff_leave_details')
            ->join('leave_types', 'staff_leave_details.leave_type_id', '=', 'leave_types.id')
            ->where('staff_leave_details.staff_id', $staffId)
            ->where('staff_leave_details.session_id', $sessionId)
            ->select([
                'staff_leave_details.*',
                'leave_types.type',
                'leave_types.id as leave_type_id',
            ])
            ->get();

        $out = [];
        foreach ($alloted as $value) {
            $approveLeave = (float) (DB::table('staff_leave_request')
                ->where('staff_id', $staffId)
                ->where('leave_type_id', $value->leave_type_id)
                ->where('session_id', $sessionId)
                ->where('status', '!=', 'disapprove')
                ->sum('leave_days') ?: 0);
            $allotedLeave = (float) ($value->alloted_leave ?? 0);
            if ($allotedLeave <= 0) {
                continue;
            }
            $available = $allotedLeave - $approveLeave;
            if ($available <= 0) {
                continue;
            }
            $out[] = [
                'id' => (int) $value->leave_type_id,
                'type' => (string) $value->type,
                'alloted_leave' => $allotedLeave,
                'approve_leave' => $approveLeave,
                'available' => $available,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function myAllotedLeaveType(int $staffId, int $leaveTypeId): ?array
    {
        $sessionId = $this->currentSessionId();
        $row = DB::table('staff_leave_details')
            ->join('leave_types', 'staff_leave_details.leave_type_id', '=', 'leave_types.id')
            ->where('staff_leave_details.staff_id', $staffId)
            ->where('leave_types.id', $leaveTypeId)
            ->where('staff_leave_details.session_id', $sessionId)
            ->select([
                'staff_leave_details.*',
                'leave_types.type',
                'leave_types.id as typeid',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        $data = (array) $row;
        $data['total_applied'] = (float) (DB::table('staff_leave_request')
            ->where('leave_type_id', $leaveTypeId)
            ->where('staff_id', $staffId)
            ->where('status', '!=', 'disapprove')
            ->where('session_id', $sessionId)
            ->sum('leave_days') ?: 0);

        return $data;
    }

    public function dateDifference(string $date1, string $date2): float|int
    {
        $datetime1 = date_create($date1);
        $datetime2 = date_create($date2);
        if ($datetime1 === false || $datetime2 === false) {
            return 0;
        }
        $interval = date_diff($datetime1, $datetime2);

        return ((int) $interval->format('%a')) + 1;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  \Illuminate\Http\UploadedFile|null  $file
     */
    public function saveRequest(array $input, $file = null, ?int $requestId = null): StaffLeaveRequest
    {
        $staffId = (int) $input['empname'];
        $leaveTypeId = (int) $input['leave_type'];
        $leaveFrom = (string) $input['leave_from_date'];
        $leaveTo = (string) $input['leave_to_date'];
        $status = (string) ($input['addstatus'] ?? 'pending');
        $halfDay = ! empty($input['half_day_leave']);

        $leaveDays = (float) $this->dateDifference($leaveFrom, $leaveTo);
        $halfDayLeave = null;
        if ($halfDay) {
            if ($leaveDays > 1) {
                $halfDayLeave = 'first_half';
            } else {
                $leaveDays = 0.5;
                $halfDayLeave = 'second_half';
            }
        }

        $myLeave = $this->myAllotedLeaveType($staffId, $leaveTypeId);
        if ($myLeave === null) {
            throw ValidationException::withMessages([
                'leave_type' => 'Leave type is not allotted for this staff.',
            ]);
        }

        $totalRemain = (float) $myLeave['alloted_leave'] - (float) $myLeave['total_applied'];
        if ($requestId !== null) {
            $existing = StaffLeaveRequest::query()->find($requestId);
            if ($existing && (int) $existing->leave_type_id === $leaveTypeId
                && (string) $existing->status !== 'disapprove') {
                $totalRemain += (float) $existing->leave_days;
            }
        }

        if ($totalRemain < $leaveDays) {
            throw ValidationException::withMessages([
                'leave_from_date' => 'Selected leave days > available leaves',
            ]);
        }

        $document = '';
        if ($requestId !== null) {
            $existingDoc = StaffLeaveRequest::query()->where('id', $requestId)->value('document_file');
            $document = (string) ($existingDoc ?? '');
        }

        if ($file !== null) {
            $dir = public_path('uploads/staff_documents/'.$staffId);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $document = uniqid('leave_', true).'_'.$file->getClientOriginalName();
            $file->move($dir, $document);
        }

        $approveDate = $status === 'approved' ? date('Y-m-d') : null;
        $appliedBy = (int) (Auth::guard('staff')->id() ?? 0);

        $payload = [
            'staff_id' => $staffId,
            'date' => (string) $input['applieddate'],
            'leave_days' => $leaveDays,
            'leave_type_id' => $leaveTypeId,
            'leave_from' => $leaveFrom,
            'leave_to' => $leaveTo,
            'employee_remark' => (string) ($input['reason'] ?? ''),
            'status' => $status,
            'admin_remark' => (string) ($input['remark'] ?? ''),
            'applied_by' => $appliedBy > 0 ? $appliedBy : null,
            'document_file' => $document !== '' ? $document : '',
            'approve_date' => $approveDate,
            'session_id' => $this->currentSessionId(),
            'half_day_leave' => $halfDayLeave,
        ];

        if ($requestId !== null) {
            $req = StaffLeaveRequest::query()->findOrFail($requestId);
            $req->fill($payload);
            $req->save();

            return $req;
        }

        return StaffLeaveRequest::query()->create($payload);
    }

    /**
     * @param  array{status: string, admin_remark?: string}  $data
     */
    public function changeStatus(int $leaveRequestId, array $data): void
    {
        $status = (string) $data['status'];
        $payload = [
            'status' => $status,
            'admin_remark' => (string) ($data['admin_remark'] ?? ''),
            'approve_date' => $status !== 'pending' ? date('Y-m-d') : null,
        ];
        StaffLeaveRequest::query()->where('id', $leaveRequestId)->update($payload);
    }

    public function delete(int $id): void
    {
        $row = StaffLeaveRequest::query()->findOrFail($id);
        if ($row->document_file) {
            $path = public_path('uploads/staff_documents/'.$row->staff_id.'/'.$row->document_file);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $row->delete();
    }

    public function documentPath(int $staffId, string $filename): string
    {
        return public_path('uploads/staff_documents/'.$staffId.'/'.$filename);
    }

    /**
     * CI add_staff_leave — staff applies for themselves (status always pending).
     *
     * @param  array<string, mixed>  $input
     * @param  \Illuminate\Http\UploadedFile|null  $file
     */
    public function saveSelfApply(array $input, $file = null): StaffLeaveRequest
    {
        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        if ($staffId <= 0) {
            throw ValidationException::withMessages([
                'applieddate' => 'Staff session required.',
            ]);
        }

        $input['empname'] = $staffId;
        $input['addstatus'] = 'pending';
        $input['remark'] = '';

        return $this->saveRequest($input, $file);
    }

    /**
     * Active staff list for report filter (CI searchFullText active).
     *
     * @return list<array<string, mixed>>
     */
    public function activeStaffList(): array
    {
        return DB::table('staff')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'surname', 'employee_id'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * CI getleaverequestreport / getmyleaverequestreport.
     *
     * @param  array{from_date?: string|null, to_date?: string|null, joining_date?: string|null, staff_id?: int|null, leave_status?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function leaveRequestReport(array $filters): array
    {
        $query = DB::table('staff_leave_request')
            ->leftJoin('staff', 'staff.id', '=', 'staff_leave_request.staff_id')
            ->leftJoin('leave_types', 'leave_types.id', '=', 'staff_leave_request.leave_type_id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff.is_active', 1)
            ->select([
                'staff_leave_request.*',
                'staff.name',
                'staff.date_of_joining',
                'staff.surname',
                'staff.employee_id',
                'leave_types.type',
            ]);

        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;
        if (! empty($from) && ! empty($to)) {
            $query->whereRaw("date_format(staff_leave_request.leave_from,'%Y-%m-%d') >= ?", [$from])
                ->whereRaw("date_format(staff_leave_request.leave_from,'%Y-%m-%d') <= ?", [$to]);
        }

        if (! empty($filters['joining_date'])) {
            $query->whereRaw("date_format(staff.date_of_joining,'%Y-%m-%d') = ?", [$filters['joining_date']]);
        }

        if (! empty($filters['staff_id'])) {
            $query->where('staff_leave_request.staff_id', (int) $filters['staff_id']);
        }

        if (! empty($filters['leave_status'])) {
            $status = strtolower((string) $filters['leave_status']);
            $query->where('staff_leave_request.status', $status);
        }

        return $query->orderByDesc('staff_leave_request.id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }
}
