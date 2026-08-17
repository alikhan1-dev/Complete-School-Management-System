<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolAttendanceScheduleService;
use App\Modules\Settings\Services\SchoolAttendanceTypeSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::attendancetype + saveattendancetype + schedule forms.
 */
class SchoolAttendanceTypeSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolAttendanceTypeSettingService $attendanceType,
        protected SchoolAttendanceScheduleService $schedules,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->attendanceType->current();
        abort_unless($setting !== null, 404);

        $classId = (int) $request->input('class_id', 0);

        return view('shared::layouts.admin', [
            'title' => __('system.attendance_type'),
            'contentView' => 'settings::admin.attendance_type.index',
            'pageTitle' => __('system.attendance_type'),
            'result' => $setting,
            'classid' => $classId,
            'classlist' => $this->schedules->classes(),
            'class_list' => $this->schedules->classListWithTimes(),
            'list_attendance' => $this->schedules->groupedStaffSchedules(),
            'attendance_type' => $this->schedules->staffScheduleTypes(),
            'student_list_attendance' => $this->schedules->groupedStudentSchedules($classId > 0 ? $classId : null),
            'student_attendance_type' => $this->schedules->studentScheduleTypes(),
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
            'canEditStudentSchedules' => $this->permissions->hasPrivilege('multi_class_student', 'can_edit'),
            'scheduleHelper' => $this->schedules,
        ]);
    }

    /**
     * CI Schsettings::saveattendancetype — JSON because CI JS posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $validator = Validator::make($request->all(), [
            'attendence_type' => ['required'],
        ], [], [
            'attendence_type' => __('system.attendance_type'),
        ]);

        if ($validator->fails()) {
            $error = [
                'attendence_type' => $validator->errors()->has('attendence_type')
                    ? '<p>'.$validator->errors()->first('attendence_type').'</p>'
                    : '',
            ];

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings/attendancetype')->withErrors($validator)->withInput();
        }

        $this->attendanceType->save([
            'id' => $request->input('sch_id'),
            'attendence_type' => $request->input('attendence_type'),
            'biometric_device' => $request->input('biometric_device'),
            'biometric' => $request->input('biometric'),
            'low_attendance_limit' => $request->input('low_attendance_limit'),
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/attendancetype')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
