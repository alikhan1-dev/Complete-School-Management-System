<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI admin/Staffattendance — staff day attendance mark + save.
 * Deferred: SMS, profile month view, biometric/QR auto-mark, superadmin-visibility filter.
 */
class StaffAttendanceController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StaffAttendanceService $attendance
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_attendance', 'can_view'), 403);

        $resultList = null;
        $types = collect();
        $staffSettings = [];
        $filters = [
            'user_id' => $request->input('user_id', 'select'),
            'date' => $request->input('date', date('Y-m-d')),
        ];
        $isFirstTime = true;
        $canSave = $this->permissions->hasPrivilege('staff_attendance', 'can_add')
            || $this->permissions->hasPrivilege('staff_attendance', 'can_edit');

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'user_id' => ['required', 'string'],
                'date' => ['required', 'date'],
                'search' => ['nullable', 'string'],
            ]);

            $filters['user_id'] = $data['user_id'];
            $filters['date'] = $data['date'];
            $types = $this->attendance->activeTypes();
            $staffSettings = $this->attendance->schedulesForRole($data['user_id']);

            if (($data['search'] ?? '') === 'saveattendence') {
                abort_unless($canSave, 403);

                $staffIds = $request->input('student_session', []);
                if (! is_array($staffIds) || $staffIds === []) {
                    return back()->withInput()->withErrors(['student_session' => 'No staff selected for attendance.']);
                }

                $rows = [];
                foreach ($staffIds as $staffId) {
                    $staffId = (int) $staffId;
                    $typeId = (int) $request->input('attendencetype'.$staffId);
                    if ($typeId <= 0) {
                        return back()->withInput()->withErrors([
                            'attendencetype'.$staffId => 'Attendance type is required for each staff member.',
                        ]);
                    }

                    $rows[] = [
                        'staff_id' => $staffId,
                        'staff_attendance_type_id' => $typeId,
                        'date' => $data['date'],
                        'remark' => $request->input('remark'.$staffId, ''),
                        'in_time' => $request->input('in_time_'.$staffId),
                        'out_time' => $request->input('out_time_'.$staffId),
                    ];
                }

                try {
                    $count = $this->attendance->addOrUpdate($rows);
                } catch (InvalidArgumentException $e) {
                    return back()->withInput()->withErrors(['search' => $e->getMessage()]);
                }

                return redirect()
                    ->route('attendance.staffattendance.index')
                    ->with('success', "Staff attendance saved for {$count} staff member(s).");
            }

            $resultList = $this->attendance->searchByRole($data['user_id'], $data['date']);

            foreach ($resultList as $row) {
                if (! empty($row->staff_attendance_type_id)) {
                    $isFirstTime = false;
                    break;
                }
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Staff Attendance',
            'contentView' => 'attendance::admin.staffattendance.index',
            'roles' => $this->attendance->rolesForFilter(),
            'types' => $types,
            'resultList' => $resultList,
            'filters' => $filters,
            'isFirstTime' => $isFirstTime,
            'staffSettings' => $staffSettings,
            'canSave' => $canSave,
        ]);
    }
}
