<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\AttendanceReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Attendencereports: hub + daywise + daily + type + monthly + period + biometric.
 */
class AttendanceReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected AttendanceReportService $reports,
    ) {
    }

    public function attendance(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.attendance_report'),
            'contentView' => 'reports::admin.attendance.hub',
        ], $this->navFlags()));
    }

    public function daywiseattendancereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('attendance_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'date' => $request->input('date', $this->reports->formatDate(now()->toDateString())),
            'attendance_mode' => $request->input('attendance_mode', ''),
        ];
        $rows = collect();
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'date' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'date.required' => 'The Date field is required.',
            ]);
            $date = $this->reports->parseDate($filters['date']);
            abort_unless($date !== null, 422);
            abort_unless(
                $this->reports->canAccessClassSection(
                    (int) $filters['class_id'],
                    (int) $filters['section_id'],
                    'day_wise'
                ),
                403
            );
            $mode = $filters['attendance_mode'] === '' ? null : (int) $filters['attendance_mode'];
            $rows = $this->reports->studentDaywiseRows(
                (int) $filters['class_id'],
                (int) $filters['section_id'],
                $date,
                $mode,
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_day_wise_attendance_report'),
            'contentView' => 'reports::admin.attendance.student_daywise',
            'classes' => $this->reports->classes(true),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function staffdaywiseattendancereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('attendance_report', 'can_view'), 403);

        $filters = [
            'role' => $request->input('role', ''),
            'date' => $request->input('date', $this->reports->formatDate(now()->toDateString())),
            'attendance_mode' => $request->input('attendance_mode', ''),
        ];
        $rows = collect();
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'role' => ['required'],
                'date' => ['required'],
            ], [
                'role.required' => 'The Role field is required.',
                'date.required' => 'The Date field is required.',
            ]);
            $date = $this->reports->parseDate($filters['date']);
            abort_unless($date !== null, 422);
            $mode = $filters['attendance_mode'] === '' ? null : (int) $filters['attendance_mode'];
            $rows = $this->reports->staffDaywiseRows((string) $filters['role'], $date, $mode);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.staff_day_wise_attendance_report'),
            'contentView' => 'reports::admin.attendance.staff_daywise',
            'roles' => $this->reports->staffRoles(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function daily_attendance_report(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_attendance_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        $dateInput = $request->input('date');
        $sqlDate = now()->toDateString();
        $displayDate = $this->reports->formatDate($sqlDate);
        if ($request->isMethod('post')) {
            $request->validate([
                'date' => ['required'],
            ], [
                'date.required' => 'The Date field is required.',
            ]);
            $parsed = $this->reports->parseDate($dateInput);
            abort_unless($parsed !== null, 422);
            $sqlDate = $parsed;
            $displayDate = $this->reports->formatDate($sqlDate);
        } elseif ($dateInput) {
            $parsed = $this->reports->parseDate($dateInput);
            if ($parsed !== null) {
                $sqlDate = $parsed;
                $displayDate = $this->reports->formatDate($sqlDate);
            }
        }

        $report = $this->reports->dailyAttendanceReport($sqlDate);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.daily_attendance_report'),
            'contentView' => 'reports::admin.attendance.daily',
            'date' => $displayDate,
            'report' => $report,
        ], $this->navFlags()));
    }

    public function attendancereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_attendance_type_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'attendance_type' => $request->input('attendance_type', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $rows = collect();
        $filterLabel = '';
        $searched = false;
        if ($request->isMethod('post')) {
            $request->validate([
                'attendance_type' => ['required'],
                'class_id' => ['required'],
            ], [
                'attendance_type.required' => 'The Attendance Type field is required.',
                'class_id.required' => 'The Class field is required.',
            ]);
            $searched = true;
            abort_unless(
                $this->reports->canAccessClassSection(
                    (int) $filters['class_id'],
                    $filters['section_id'] !== '' ? (int) $filters['section_id'] : null,
                    'union'
                ),
                403
            );
            $payload = $this->reports->attendanceTypeReport(
                (int) $filters['class_id'],
                $filters['section_id'] !== '' ? (int) $filters['section_id'] : null,
                (int) $filters['attendance_type'],
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null,
            );
            $rows = $payload['rows'];
            $filterLabel = $payload['filter'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_attendance_type_report'),
            'contentView' => 'reports::admin.attendance.attendance_type',
            'classes' => $this->reports->classes(),
            'searchTypes' => $this->reports->searchTypes(),
            'attendanceTypes' => $this->reports->studentAttendanceTypesForReport(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'filter_label' => $filterLabel,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function classattendencereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('attendance_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'month' => $request->input('month', ''),
            'year' => $request->input('year', ''),
        ];
        $resultlist = null;
        $studentArray = [];
        $attendenceArray = [];
        $monthAttendance = [];
        $yearSelected = $filters['year'];
        $searched = $request->isMethod('post');

        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'month' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'month.required' => 'The Month field is required.',
            ]);

            abort_unless(
                $this->reports->canAccessClassSection(
                    (int) $request->input('class_id'),
                    (int) $request->input('section_id'),
                    'day_wise'
                ),
                403
            );

            $payload = $this->reports->studentMonthlyMatrix(
                (int) $request->input('class_id'),
                (int) $request->input('section_id'),
                (string) $request->input('month'),
                $request->filled('year') ? (string) $request->input('year') : null,
            );
            $resultlist = $payload['resultlist'];
            $studentArray = $payload['student_array'];
            $attendenceArray = $payload['attendence_array'];
            $monthAttendance = $payload['monthAttendance'];
            $yearSelected = (string) $payload['year'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_attendance_report'),
            'contentView' => 'reports::admin.attendance.class_attendance',
            'classes' => $this->reports->classes(true),
            'monthlist' => $this->reports->monthDropdown(),
            'yearlist' => $this->reports->studentAttendanceYears(),
            'attendencetypeslist' => $this->reports->studentAttendanceTypes(),
            'filters' => $filters,
            'year_selected' => $yearSelected,
            'resultlist' => $resultlist,
            'student_array' => $studentArray,
            'attendence_array' => $attendenceArray,
            'monthAttendance' => $monthAttendance,
            'low_attendance_limit' => $this->reports->lowAttendanceLimit(),
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function staffattendancereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_attendance_report', 'can_view'), 403);

        $filters = [
            'role' => $request->input('role', ''),
            'month' => $request->input('month', ''),
            'year' => $request->input('year', ''),
        ];
        $resultlist = null;
        $studentArray = [];
        $attendenceArray = [];
        $monthAttendance = [];
        $searched = $request->isMethod('post');

        if ($searched) {
            $request->validate([
                'month' => ['required'],
                'year' => ['required'],
            ], [
                'month.required' => 'The Month field is required.',
                'year.required' => 'The Year field is required.',
            ]);

            $role = (string) $request->input('role', 'select');
            if ($role === '') {
                $role = 'select';
            }

            $payload = $this->reports->staffMonthlyMatrix(
                $role,
                (string) $request->input('month'),
                (int) $request->input('year'),
            );
            $resultlist = $payload['resultlist'];
            $studentArray = $payload['student_array'];
            $attendenceArray = $payload['attendence_array'];
            $monthAttendance = $payload['monthAttendance'];
            $filters['role'] = $role;
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.staff_attendance_report'),
            'contentView' => 'reports::admin.attendance.staff_attendance',
            'roles' => $this->reports->staffRoles(),
            'monthlist' => $this->reports->monthDropdown(),
            'yearlist' => $this->reports->staffAttendanceYears(),
            'attendencetypeslist' => $this->reports->staffAttendanceTypesActive(),
            'filters' => $filters,
            'resultlist' => $resultlist,
            'student_array' => $studentArray,
            'attendence_array' => $attendenceArray,
            'monthAttendance' => $monthAttendance,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function reportbymonth(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_period_attendance_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'month' => $request->input('month', ''),
            'subject_id' => $request->input('subject_id', ''),
        ];
        $resultlist = null;
        $noOfDays = 0;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'month' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'month.required' => 'The Month field is required.',
            ]);
            $searched = true;
            abort_unless(
                $this->reports->canAccessClassSection(
                    (int) $request->input('class_id'),
                    (int) $request->input('section_id'),
                    'union'
                ),
                403
            );
            $subjectId = $request->filled('subject_id') ? (string) $request->input('subject_id') : null;
            $resultlist = $this->reports->classPeriodMonthlyAttendence(
                (int) $request->input('class_id'),
                (int) $request->input('section_id'),
                (string) $request->input('month'),
                $subjectId
            );
            $noOfDays = (int) ($resultlist['no_of_days'] ?? 0);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.period_attendance_report'),
            'contentView' => 'reports::admin.attendance.report_by_month',
            'classes' => $this->reports->classes(),
            'monthlist' => $this->reports->monthNoDropdown(),
            'attendencetypeslist' => $this->reports->studentAttendanceTypesActive(),
            'filters' => $filters,
            'resultlist' => $resultlist,
            'no_of_days' => $noOfDays,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function reportbymonthstudent(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_period_attendance_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'student_id' => $request->input('student_id', ''),
            'month' => $request->input('month', ''),
            'subject_id' => $request->input('subject_id', ''),
        ];
        $resultlist = null;
        $noOfDays = 0;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'student_id' => ['required'],
                'month' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'student_id.required' => 'The Student field is required.',
                'month.required' => 'The Month field is required.',
            ]);
            $searched = true;
            abort_unless(
                $this->reports->canAccessClassSection(
                    (int) $request->input('class_id'),
                    (int) $request->input('section_id'),
                    'union'
                ),
                403
            );
            $subjectId = $request->filled('subject_id') ? (string) $request->input('subject_id') : null;
            $resultlist = $this->reports->studentPeriodMonthlyAttendence(
                (int) $request->input('class_id'),
                (int) $request->input('section_id'),
                (int) $request->input('student_id'),
                (string) $request->input('month'),
                $subjectId
            );
            $noOfDays = (int) ($resultlist['no_of_days'] ?? 0);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_period_attendance'),
            'contentView' => 'reports::admin.attendance.report_by_month_student',
            'classes' => $this->reports->classes(),
            'monthlist' => $this->reports->monthNoDropdown(),
            'attendencetypeslist' => $this->reports->studentAttendanceTypesActive(),
            'filters' => $filters,
            'resultlist' => $resultlist,
            'no_of_days' => $noOfDays,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function biometric_attlog(Request $request, int $offset = 0): View
    {
        abort_unless(
            $this->reports->isBiometricEnabled()
            && $this->permissions->hasPrivilege('biometric_attendance_log', 'can_view'),
            403
        );

        $payload = $this->reports->biometricAttendanceLog($offset, 100);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.biometric_attendance_log'),
            'contentView' => 'reports::admin.attendance.biometric_attlog',
            'resultlist' => $payload['rows'],
            'total' => $payload['total'],
            'per_page' => $payload['per_page'],
            'offset' => $payload['offset'],
            'adm_auto_insert' => $this->reports->admAutoInsert(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('attendance_report', 'can_view')
            || $this->permissions->hasPrivilege('student_attendance_type_report', 'can_view')
            || $this->permissions->hasPrivilege('daily_attendance_report', 'can_view')
            || $this->permissions->hasPrivilege('staff_attendance_report', 'can_view')
            || $this->permissions->hasPrivilege('student_period_attendance_report', 'can_view')
            || $this->permissions->hasPrivilege('biometric_attendance_log', 'can_view');
    }

    /**
     * @return array<string, mixed>
     */
    protected function navFlags(): array
    {
        $period = $this->reports->isPeriodAttendance();

        return [
            'isPeriodAttendance' => $period,
            'isBiometricEnabled' => $this->reports->isBiometricEnabled(),
            'canAttendanceReport' => ! $period && $this->permissions->hasPrivilege('attendance_report', 'can_view'),
            'canAttendanceTypeReport' => ! $period && $this->permissions->hasPrivilege('student_attendance_type_report', 'can_view'),
            'canDailyAttendanceReport' => ! $period && $this->permissions->hasPrivilege('daily_attendance_report', 'can_view'),
            'canStaffAttendanceReport' => $this->permissions->hasPrivilege('staff_attendance_report', 'can_view'),
            'canPeriodAttendanceReport' => $period && $this->permissions->hasPrivilege('student_period_attendance_report', 'can_view'),
            'canBiometricLog' => $this->reports->isBiometricEnabled()
                && $this->permissions->hasPrivilege('biometric_attendance_log', 'can_view'),
        ];
    }
}
