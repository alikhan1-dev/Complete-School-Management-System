<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Services\SchoolAttendanceScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CI schsettings/savestaffsetting + admin/stuattendence/saveclasstime + savestudentsetting.
 */
class SchoolAttendanceScheduleController extends Controller
{
    public function __construct(protected SchoolAttendanceScheduleService $schedules)
    {
    }

    /**
     * CI Schsettings::savestaffsetting — JSON {status:0|1}.
     */
    public function saveStaff(Request $request): JsonResponse|RedirectResponse
    {
        $row = $request->input('row');
        $incomplete = $this->scheduleRowsIncomplete($request, $row, 'role_id_');

        $validator = Validator::make($request->all(), [
            'row' => ['required'],
        ], [], [
            'row' => __('system.row'),
        ]);

        if ($incomplete) {
            $validator->after(function ($validator) {
                $validator->errors()->add('fields', __('system.fields_values_required'));
            });
        }

        if ($validator->fails()) {
            return $this->fail($request, [
                'row' => $validator->errors()->has('row')
                    ? '<p>'.$validator->errors()->first('row').'</p>'
                    : '',
                'fields' => $validator->errors()->has('fields')
                    ? '<p>'.$validator->errors()->first('fields').'</p>'
                    : '',
            ]);
        }

        $insertArray = [];
        $roleIds = [];
        foreach ($row as $rowValue) {
            $roleId = $request->input('role_id_'.$rowValue);
            $roleIds[] = $roleId;
            $insertArray[] = [
                'staff_attendence_type_id' => $request->input('attendance_type_id_'.$rowValue),
                'role_id' => $roleId,
                'entry_time_from' => $request->input('entry_time_from_'.$rowValue),
                'entry_time_to' => $request->input('entry_time_to_'.$rowValue),
                'total_institute_hour' => $request->input('total_institute_hour_'.$rowValue),
                'is_active' => 1,
            ];
        }

        $this->schedules->replaceStaffSchedules($insertArray, $roleIds);

        return $this->ok($request, __('system.update_message'), 'schsettings/attendancetype');
    }

    /**
     * CI admin/stuattendence/savestudentsetting — JSON {status:0|1}.
     */
    public function saveStudent(Request $request): JsonResponse|RedirectResponse
    {
        $row = $request->input('row');
        $incomplete = $this->scheduleRowsIncomplete($request, $row, 'class_section_id_');

        $validator = Validator::make($request->all(), [
            'row' => ['required'],
        ], [], [
            'row' => __('system.row'),
        ]);

        if ($incomplete) {
            $validator->after(function ($validator) {
                $validator->errors()->add('fields', __('system.fields_values_required'));
            });
        }

        if ($validator->fails()) {
            return $this->fail($request, [
                'row' => $validator->errors()->has('row')
                    ? '<p>'.$validator->errors()->first('row').'</p>'
                    : '',
                'fields' => $validator->errors()->has('fields')
                    ? '<p>'.$validator->errors()->first('fields').'</p>'
                    : '',
            ]);
        }

        $insertArray = [];
        $classSectionIds = [];
        foreach ($row as $rowValue) {
            $classSectionId = $request->input('class_section_id_'.$rowValue);
            $classSectionIds[] = $classSectionId;
            $insertArray[] = [
                'attendence_type_id' => $request->input('attendance_type_id_'.$rowValue),
                'class_section_id' => $classSectionId,
                'entry_time_from' => $request->input('entry_time_from_'.$rowValue),
                'entry_time_to' => $request->input('entry_time_to_'.$rowValue),
                'total_institute_hour' => $request->input('total_institute_hour_'.$rowValue),
                'is_active' => 1,
            ];
        }

        $this->schedules->replaceStudentSchedules($insertArray, $classSectionIds);

        return $this->ok($request, __('system.update_message'), 'schsettings/attendancetype');
    }

    /**
     * CI admin/stuattendence/saveclasstime — JSON {status:0|1}.
     */
    public function saveClassTime(Request $request): JsonResponse|RedirectResponse
    {
        $classSections = $request->input('class_section_id');
        $timeValid = true;
        if (is_array($classSections) && $classSections !== []) {
            foreach ($classSections as $value) {
                if ($value === '' || $value === null) {
                    $timeValid = false;
                    break;
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'row' => ['required'],
        ], [], [
            'row' => __('system.section'),
        ]);

        if (! $timeValid) {
            $validator->after(function ($validator) {
                $validator->errors()->add('time', __('validation.required', ['attribute' => __('system.time')]));
            });
        }

        if ($validator->fails()) {
            $error = [
                'row' => $validator->errors()->has('row')
                    ? '<p>'.$validator->errors()->first('row').'</p>'
                    : '',
            ];
            if (! $timeValid) {
                $error['time'] = $validator->errors()->has('time')
                    ? '<p>'.$validator->errors()->first('time').'</p>'
                    : '';
            }

            return $this->fail($request, $error);
        }

        $insertData = [];
        $updateData = [];
        $prevRecords = $request->input('prev_record_id', []);
        if (is_array($classSections)) {
            foreach ($classSections as $classSectionId => $time) {
                $payload = [
                    'class_section_id' => $classSectionId,
                    'time' => $this->schedules->timeFormat24($time),
                ];
                $prevId = (int) ($prevRecords[$classSectionId] ?? 0);
                if ($prevId > 0) {
                    $payload['id'] = $prevId;
                    $updateData[] = $payload;
                } else {
                    $insertData[] = $payload;
                }
            }
        }

        $this->schedules->saveClassTimes($insertData, $updateData);

        return $this->ok($request, __('system.success_message'), 'schsettings/attendancetype');
    }

    /**
     * @param  mixed  $row
     */
    protected function scheduleRowsIncomplete(Request $request, mixed $row, string $ownerPrefix): bool
    {
        if (! is_array($row) || $row === []) {
            return false;
        }

        foreach ($row as $rowValue) {
            $owner = $request->input($ownerPrefix.$rowValue);
            $attendanceType = $request->input('attendance_type_id_'.$rowValue);
            $from = $request->input('entry_time_from_'.$rowValue);
            $to = $request->input('entry_time_to_'.$rowValue);
            $hours = $request->input('total_institute_hour_'.$rowValue);
            if ($owner === '' || $owner === null
                || $from === '' || $from === null
                || $to === '' || $to === null
                || $hours === '' || $hours === null
                || $attendanceType === '' || $attendanceType === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $error
     */
    protected function fail(Request $request, array $error): JsonResponse|RedirectResponse
    {
        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 0, 'error' => $error, 'message' => '']);
        }

        return redirect('schsettings/attendancetype')->withErrors($error);
    }

    protected function ok(Request $request, string $message, string $redirect): JsonResponse|RedirectResponse
    {
        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 1, 'message' => $message]);
        }

        return redirect($redirect)->with('success', $message);
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
