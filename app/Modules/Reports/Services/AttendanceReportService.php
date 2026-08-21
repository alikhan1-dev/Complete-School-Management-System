<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Attendencereports: hub + daywise + daily + type + monthly student/staff calendars.
 * Deferred: period/subject reports, biometric log, class-teacher class dropdown scope.
 */
class AttendanceReportService
{
    /** @var array<string, int> CI mailsms.php $config['attendence'] */
    public const STUDENT_ATTENDANCE_TYPE_MAP = [
        'present' => 1,
        'late_with_excuse' => 2,
        'late' => 3,
        'absent' => 4,
        'holiday' => 5,
        'half_day' => 6,
        'half_day_second_shift' => 8,
    ];

    /** @var array<string, int> CI payroll.php $config['staffattendance'] */
    public const STAFF_ATTENDANCE_TYPE_MAP = [
        'present' => 1,
        'half_day' => 4,
        'late' => 2,
        'absent' => 3,
        'holiday' => 5,
        'half_day_second_shift' => 6,
    ];

    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    public function isPeriodAttendance(): bool
    {
        return (int) $this->school->get('attendence_type', 0) === 1;
    }

    public function isBiometricEnabled(): bool
    {
        return (int) $this->school->get('biometric', 0) === 1;
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function fullName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));
        $name = $this->settingOn('middlename')
            ? ($middle === '' ? $first : $first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name .= ' '.$last;
        }

        return $name;
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function classes(): Collection
    {
        return DB::table('classes')->orderBy('class')->get();
    }

    /**
     * CI Customlib::get_searchtype keys.
     *
     * @return array<string, string>
     */
    public function searchTypes(): array
    {
        return [
            'today' => (string) __('system.today'),
            'this_week' => (string) __('system.this_week'),
            'last_week' => (string) __('system.last_week'),
            'this_month' => (string) __('system.this_month'),
            'last_month' => (string) __('system.last_month'),
            'last_3_month' => (string) __('system.last_3_month'),
            'last_6_month' => (string) __('system.last_6_month'),
            'last_12_month' => (string) __('system.last_12_month'),
            'this_year' => (string) __('system.this_year'),
            'last_year' => (string) __('system.last_year'),
            'period' => (string) __('system.period'),
        ];
    }

    /**
     * @return array{from: string, to: string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        return match ($searchType) {
            'today' => [
                'from' => $now->toDateString(),
                'to' => $now->toDateString(),
            ],
            'this_week' => [
                'from' => $now->copy()->startOfWeek()->toDateString(),
                'to' => $now->copy()->endOfWeek()->toDateString(),
            ],
            'last_week' => [
                'from' => $now->copy()->startOfWeek()->subWeek()->toDateString(),
                'to' => $now->copy()->startOfWeek()->subWeek()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                'from' => $now->copy()->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_3_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_6_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_12_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(11)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
            'last_year' => [
                'from' => $now->copy()->subYear()->startOfYear()->toDateString(),
                'to' => $now->copy()->subYear()->endOfYear()->toDateString(),
            ],
            'period' => [
                'from' => $this->parseDate($dateFrom) ?: $now->toDateString(),
                'to' => $this->parseDate($dateTo) ?: $now->toDateString(),
            ],
            default => [
                'from' => $now->copy()->startOfWeek()->toDateString(),
                'to' => $now->copy()->endOfWeek()->toDateString(),
            ],
        };
    }

    /**
     * @return Collection<int, object>
     */
    public function studentAttendanceTypes(): Collection
    {
        return DB::table('attendence_type')->orderBy('id')->get();
    }

    /**
     * CI Attendencetype_model::getstdAttType('2') — exclude id 2.
     *
     * @return Collection<int, object>
     */
    public function studentAttendanceTypesForReport(): Collection
    {
        return DB::table('attendence_type')->where('id', '!=', 2)->orderBy('id')->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function staffAttendanceTypes(): Collection
    {
        return DB::table('staff_attendance_type')->orderBy('id')->get();
    }

    /**
     * CI Staffattendancemodel::getStaffAttendanceType — active only.
     *
     * @return Collection<int, object>
     */
    public function staffAttendanceTypesActive(): Collection
    {
        return DB::table('staff_attendance_type')->where('is_active', 'yes')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function staffRoles(): Collection
    {
        return DB::table('roles')->where('is_active', 'yes')->orderBy('id')->get(['id', 'name']);
    }

    /**
     * CI Customlib::getMonthDropdown(null) — Jan–Dec, English month name keys.
     *
     * @return array<string, string>
     */
    public function monthDropdown(): array
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $name = date('F', mktime(0, 0, 0, $i, 1));
            $months[$name] = (string) __('system.'.strtolower($name));
        }

        return $months;
    }

    public function lowAttendanceLimit(): int
    {
        return (int) $this->school->get('low_attendance_limit', 0);
    }

    /**
     * CI Stuattendence_model::attendanceYearCount.
     *
     * @return list<object{year: int|string}>
     */
    public function studentAttendanceYears(): array
    {
        return DB::table('student_attendences')
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->orderBy('year')
            ->get()
            ->all();
    }

    /**
     * CI Staffattendancemodel::attendanceYearCount.
     *
     * @return list<object{year: int|string}>
     */
    public function staffAttendanceYears(): array
    {
        return DB::table('staff_attendance')
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->orderBy('year')
            ->get()
            ->all();
    }

    public function currentSessionName(): string
    {
        $sessionId = (int) $this->currentSession->id();
        if ($sessionId <= 0) {
            return '';
        }

        return (string) (DB::table('sessions')->where('id', $sessionId)->value('session') ?? '');
    }

    /**
     * CI Attendencereports::classattendencereport year resolution.
     */
    public function resolveStudentCalendarYear(string $monthName, ?string $postedYear): int
    {
        if ($postedYear !== null && trim($postedYear) !== '') {
            return (int) $postedYear;
        }

        $sessionCurrent = $this->currentSessionName();
        $startMonth = (int) $this->school->get('start_month', 1);
        $centenary = substr($sessionCurrent, 0, 2);
        $yearFirst = substr($sessionCurrent, 2, 2);
        $yearSecond = substr($sessionCurrent, 5, 2);
        $monthNumber = (int) date('m', strtotime($monthName));

        if ($monthNumber >= $startMonth && $monthNumber <= 12) {
            return (int) ($centenary.$yearFirst);
        }

        return (int) ($centenary.$yearSecond);
    }

    /**
     * @return array{d: string, dow_key: string, is_sunday: bool}
     */
    public function dayHeader(string $ymd): array
    {
        $ts = strtotime($ymd);

        return [
            'd' => date('d', $ts),
            'dow_key' => strtolower(date('D', $ts)),
            'is_sunday' => date('D', $ts) === 'Sun',
        ];
    }

    /**
     * @param  array<string, int|string>  $counts
     * @return array{print: string, class: string, percentage: float|int}
     */
    public function studentPresentPercentage(array $counts, int $lowLimit): array
    {
        $totalPresent = (int) ($counts['present'] ?? 0)
            + (int) ($counts['late_with_excuse'] ?? 0)
            + (int) ($counts['half_day'] ?? 0)
            + (int) ($counts['late'] ?? 0);
        $totalSchoolDays = $totalPresent + (int) ($counts['absent'] ?? 0);

        if ($totalSchoolDays === 0) {
            return ['print' => '-', 'class' => 'label label-success', 'percentage' => -1];
        }

        $percentage = ($totalPresent / $totalSchoolDays) * 100;
        $print = (string) round($percentage, 0);
        if ($percentage < $lowLimit && $percentage >= 0) {
            $class = 'label label-danger';
        } else {
            $class = 'label label-success';
        }

        return ['print' => $print, 'class' => $class, 'percentage' => $percentage];
    }

    /**
     * Staff monthly uses hardcoded 75 (CI view), not sch_settings.low_attendance_limit.
     *
     * @param  array<string, int|string>  $counts
     * @return array{print: string, class: string, percentage: float|int}
     */
    public function staffPresentPercentage(array $counts): array
    {
        $totalPresent = (int) ($counts['present'] ?? 0)
            + (int) ($counts['late'] ?? 0)
            + (int) ($counts['half_day'] ?? 0);
        $totalDays = $totalPresent + (int) ($counts['absent'] ?? 0);

        if ($totalDays === 0) {
            return ['print' => '-', 'class' => 'label label-default', 'percentage' => -1];
        }

        $percentage = ($totalPresent / $totalDays) * 100;
        $print = (string) round($percentage, 0);
        if ($percentage < 75 && $percentage >= 0) {
            $class = 'label label-danger';
        } elseif ($percentage > 75) {
            $class = 'label label-success';
        } else {
            $class = 'label label-default';
        }

        return ['print' => $print, 'class' => $class, 'percentage' => $percentage];
    }

    /**
     * CI classattendencereport search success payload.
     *
     * @return array{
     *   year: int,
     *   no_of_days: int,
     *   attendence_array: list<string>,
     *   resultlist: array<string, array<int, object>>,
     *   student_array: list<object>,
     *   monthAttendance: list<array<int, array<string, int>>>
     * }
     */
    public function studentMonthlyMatrix(int $classId, int $sectionId, string $monthName, ?string $postedYear): array
    {
        $year = $this->resolveStudentCalendarYear($monthName, $postedYear);
        $monthNumber = (int) date('m', strtotime($monthName));
        $numOfDays = (int) cal_days_in_month(CAL_GREGORIAN, $monthNumber, $year);
        $attendenceArray = [];
        for ($i = 1; $i <= $numOfDays; $i++) {
            $attendenceArray[] = sprintf('%04d-%02d-%02d', $year, $monthNumber, $i);
        }

        $students = $this->studentDaywiseRows($classId, $sectionId, $attendenceArray[0] ?? sprintf('%04d-%02d-01', $year, $monthNumber), null);
        $sessionIds = $students->pluck('student_session_id')->map(fn ($id) => (int) $id)->all();
        $byDate = $this->loadStudentAttendanceByDates($sessionIds, $attendenceArray);
        $countsBySession = $this->countStudentAttendanceTypes($sessionIds, $monthNumber, $year);

        $resultlist = [];
        foreach ($attendenceArray as $attDate) {
            $indexed = [];
            foreach ($students as $student) {
                $ssid = (int) $student->student_session_id;
                $row = clone $student;
                $hit = $byDate[$attDate][$ssid] ?? null;
                if ($hit !== null) {
                    $row->attendence_id = $hit->attendence_id;
                    $row->attendence_type_id = $hit->attendence_type_id;
                    $row->remark = $hit->remark;
                    $row->att_type = $hit->att_type;
                    $row->key = $hit->att_key ?? null;
                    $row->date = $hit->date;
                } else {
                    $row->attendence_id = 0;
                    $row->attendence_type_id = null;
                    $row->remark = null;
                    $row->att_type = null;
                    $row->key = null;
                    $row->date = 'xxx';
                }
                $indexed[$ssid] = $row;
            }
            $resultlist[$attDate] = $indexed;
        }

        $monthAttendance = [];
        foreach ($students as $student) {
            $ssid = (int) $student->student_session_id;
            $monthAttendance[] = [$ssid => $countsBySession[$ssid] ?? $this->emptyStudentCounts()];
        }

        return [
            'year' => $year,
            'no_of_days' => $numOfDays,
            'attendence_array' => $attendenceArray,
            'resultlist' => $resultlist,
            'student_array' => $students->values()->all(),
            'monthAttendance' => $monthAttendance,
        ];
    }

    /**
     * CI staffattendancereport search success payload. Day columns use POST year only.
     *
     * @return array{
     *   year: int,
     *   no_of_days: int,
     *   attendence_array: list<string>,
     *   resultlist: array<string, array<int, object>>,
     *   student_array: list<object>,
     *   monthAttendance: list<array<int, array<string, int>>>
     * }
     */
    public function staffMonthlyMatrix(string $roleName, string $monthName, int $searchYear): array
    {
        $monthNumber = (int) date('m', strtotime($monthName));
        $numOfDays = (int) cal_days_in_month(CAL_GREGORIAN, $monthNumber, $searchYear);
        $attendenceArray = [];
        for ($i = 1; $i <= $numOfDays; $i++) {
            $attendenceArray[] = sprintf('%04d-%02d-%02d', $searchYear, $monthNumber, $i);
        }

        $staffRows = $this->staffDaywiseRows($roleName === '' ? 'select' : $roleName, $attendenceArray[0] ?? sprintf('%04d-%02d-01', $searchYear, $monthNumber), null);
        $staffRows = $staffRows->map(function ($staff) {
            $staff->id = (int) ($staff->staff_id ?? $staff->id);

            return $staff;
        })->unique('id')->values();
        $staffIds = $staffRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $byDate = $this->loadStaffAttendanceByDates($staffIds, $attendenceArray);
        $countsByStaff = $this->countStaffAttendanceTypes($staffIds, $monthNumber, $searchYear);

        $resultlist = [];
        foreach ($attendenceArray as $attDate) {
            $indexed = [];
            foreach ($staffRows as $staff) {
                $id = (int) $staff->id;
                $row = clone $staff;
                $hit = $byDate[$attDate][$id] ?? null;
                if ($hit !== null) {
                    $row->attendence_id = $hit->attendence_id;
                    $row->staff_attendance_type_id = $hit->staff_attendance_type_id;
                    $row->remark = $hit->remark;
                    $row->att_type = $hit->att_type;
                    $row->key = $hit->att_key ?? null;
                    $row->date = $hit->date;
                } else {
                    $row->attendence_id = 0;
                    $row->staff_attendance_type_id = null;
                    $row->remark = null;
                    $row->att_type = null;
                    $row->key = null;
                    $row->date = 'xxx';
                }
                $indexed[$id] = $row;
            }
            $resultlist[$attDate] = $indexed;
        }

        $monthAttendance = [];
        foreach ($staffRows as $staff) {
            $id = (int) $staff->id;
            $monthAttendance[] = [$id => $countsByStaff[$id] ?? $this->emptyStaffCounts()];
        }

        return [
            'year' => $searchYear,
            'no_of_days' => $numOfDays,
            'attendence_array' => $attendenceArray,
            'resultlist' => $resultlist,
            'student_array' => $staffRows->values()->all(),
            'monthAttendance' => $monthAttendance,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function emptyStudentCounts(): array
    {
        $empty = [];
        foreach (array_keys(self::STUDENT_ATTENDANCE_TYPE_MAP) as $key) {
            $empty[$key] = 0;
        }

        return $empty;
    }

    /**
     * @return array<string, int>
     */
    protected function emptyStaffCounts(): array
    {
        $empty = [];
        foreach (array_keys(self::STAFF_ATTENDANCE_TYPE_MAP) as $key) {
            $empty[$key] = 0;
        }

        return $empty;
    }

    /**
     * @param  list<int>  $sessionIds
     * @param  list<string>  $dates
     * @return array<string, array<int, object>>
     */
    protected function loadStudentAttendanceByDates(array $sessionIds, array $dates): array
    {
        if ($sessionIds === [] || $dates === []) {
            return [];
        }

        $rows = DB::table('student_attendences')
            ->leftJoin('attendence_type', 'attendence_type.id', '=', 'student_attendences.attendence_type_id')
            ->whereIn('student_attendences.student_session_id', $sessionIds)
            ->whereIn('student_attendences.date', $dates)
            ->select([
                'student_attendences.student_session_id',
                'student_attendences.date',
                'student_attendences.remark',
                'student_attendences.attendence_type_id',
                DB::raw('IFNULL(student_attendences.id, 0) as attendence_id'),
                'attendence_type.type as att_type',
                'attendence_type.key_value as att_key',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->date][(int) $row->student_session_id] = $row;
        }

        return $out;
    }

    /**
     * @param  list<int>  $staffIds
     * @param  list<string>  $dates
     * @return array<string, array<int, object>>
     */
    protected function loadStaffAttendanceByDates(array $staffIds, array $dates): array
    {
        if ($staffIds === [] || $dates === []) {
            return [];
        }

        $rows = DB::table('staff_attendance')
            ->leftJoin('staff_attendance_type', 'staff_attendance_type.id', '=', 'staff_attendance.staff_attendance_type_id')
            ->whereIn('staff_attendance.staff_id', $staffIds)
            ->whereIn('staff_attendance.date', $dates)
            ->select([
                'staff_attendance.staff_id',
                'staff_attendance.date',
                'staff_attendance.remark',
                'staff_attendance.staff_attendance_type_id',
                DB::raw('IFNULL(staff_attendance.id, 0) as attendence_id'),
                'staff_attendance_type.type as att_type',
                'staff_attendance_type.key_value as att_key',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->date][(int) $row->staff_id] = $row;
        }

        return $out;
    }

    /**
     * @param  list<int>  $sessionIds
     * @return array<int, array<string, int>>
     */
    protected function countStudentAttendanceTypes(array $sessionIds, int $month, int $year): array
    {
        $result = [];
        foreach ($sessionIds as $ssid) {
            $result[$ssid] = $this->emptyStudentCounts();
        }
        if ($sessionIds === []) {
            return $result;
        }

        $rows = DB::table('student_attendences')
            ->whereIn('student_session_id', $sessionIds)
            ->whereRaw('MONTH(date) = ?', [$month])
            ->whereRaw('YEAR(date) = ?', [$year])
            ->selectRaw('student_session_id, attendence_type_id, COUNT(*) as attendence')
            ->groupBy('student_session_id', 'attendence_type_id')
            ->get();

        $typeToKey = array_flip(self::STUDENT_ATTENDANCE_TYPE_MAP);
        foreach ($rows as $row) {
            $ssid = (int) $row->student_session_id;
            $typeId = (int) $row->attendence_type_id;
            if (! isset($typeToKey[$typeId])) {
                continue;
            }
            $result[$ssid][$typeToKey[$typeId]] = (int) $row->attendence;
        }

        return $result;
    }

    /**
     * @param  list<int>  $staffIds
     * @return array<int, array<string, int>>
     */
    protected function countStaffAttendanceTypes(array $staffIds, int $month, int $year): array
    {
        $result = [];
        foreach ($staffIds as $id) {
            $result[$id] = $this->emptyStaffCounts();
        }
        if ($staffIds === []) {
            return $result;
        }

        $rows = DB::table('staff_attendance')
            ->whereIn('staff_id', $staffIds)
            ->whereRaw('MONTH(date) = ?', [$month])
            ->whereRaw('YEAR(date) = ?', [$year])
            ->selectRaw('staff_id, staff_attendance_type_id, COUNT(*) as attendence')
            ->groupBy('staff_id', 'staff_attendance_type_id')
            ->get();

        $typeToKey = array_flip(self::STAFF_ATTENDANCE_TYPE_MAP);
        foreach ($rows as $row) {
            $id = (int) $row->staff_id;
            $typeId = (int) $row->staff_attendance_type_id;
            if (! isset($typeToKey[$typeId])) {
                continue;
            }
            $result[$id][$typeToKey[$typeId]] = (int) $row->attendence;
        }

        return $result;
    }

    /**
     * CI Stuattendence_model::searchAttendenceClassSectionWithMode.
     *
     * @return Collection<int, object>
     */
    public function studentDaywiseRows(int $classId, int $sectionId, string $date, ?int $mode): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('student_attendences', function ($join) use ($date) {
                $join->on('student_attendences.student_session_id', '=', 'student_session.id')
                    ->where('student_attendences.date', '=', $date);
            })
            ->leftJoin('attendence_type', 'attendence_type.id', '=', 'student_attendences.attendence_type_id')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.admission_no')
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'student_session.id as student_session_id',
                DB::raw('IFNULL(student_attendences.id, 0) as attendence_id'),
                'student_attendences.attendence_type_id',
                'student_attendences.remark',
                'student_attendences.biometric_attendence',
                'student_attendences.qrcode_attendance',
                'student_attendences.user_agent',
                'attendence_type.type as att_type',
                'attendence_type.key_value as `key`',
                'attendence_type.long_lang_name',
            ]);

        $this->applyStudentModeFilter($query, $mode);

        return $query->get();
    }

    /**
     * CI Staffattendancemodel::searchAttendenceUserTypeWithMode (superadmin visibility deferred).
     *
     * @return Collection<int, object>
     */
    public function staffDaywiseRows(string $roleName, string $date, ?int $mode): Collection
    {
        $query = DB::table('staff')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->leftJoin('staff_attendance', function ($join) use ($date) {
                $join->on('staff.id', '=', 'staff_attendance.staff_id')
                    ->where('staff_attendance.date', '=', $date);
            })
            ->leftJoin('staff_attendance_type', 'staff_attendance_type.id', '=', 'staff_attendance.staff_attendance_type_id')
            ->where('staff.is_active', 1)
            ->orderBy('staff_attendance.created_at')
            ->select([
                DB::raw('IFNULL(staff_attendance.id, 0) as id'),
                'staff_attendance.created_at as attendence_dt',
                'staff_attendance.staff_attendance_type_id',
                'staff_attendance.biometric_attendence',
                'staff_attendance.qrcode_attendance',
                'staff_attendance.user_agent',
                'staff_attendance.remark',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff.contact_no',
                'staff.email',
                'roles.name as user_type',
                DB::raw("IFNULL(staff_attendance.date, 'xxx') as date"),
                'staff.id as staff_id',
                'staff_attendance_type.type as att_type',
                'staff_attendance_type.key_value as `key`',
                'staff_attendance_type.long_lang_name',
            ]);

        if ($roleName !== 'select') {
            $query->where('roles.name', $roleName);
        }

        $this->applyStaffModeFilter($query, $mode);

        return $query->get();
    }

    /**
     * CI get_attendancebydate + gender counts + controller percent assembly.
     *
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     all_student: int,
     *     all_present: int,
     *     all_absent: int,
     *     all_present_percent: string,
     *     all_absent_percent: string
     * }
     */
    public function dailyAttendanceReport(string $date): array
    {
        $sessionId = (int) $this->currentSession->id();
        $sections = DB::table('student_attendences')
            ->join('student_session', 'student_attendences.student_session_id', '=', 'student_session.id')
            ->join('class_sections', function ($join) {
                $join->on('student_session.class_id', '=', 'class_sections.class_id')
                    ->on('student_session.section_id', '=', 'class_sections.section_id');
            })
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('student_attendences.date', $date)
            ->groupBy('class_sections.id', 'classes.id', 'classes.class', 'sections.id', 'sections.section')
            ->selectRaw('classes.class as class_name, classes.id as class_id, sections.id as sections_id, sections.section as section_name,
                SUM(CASE WHEN attendence_type_id = 1 THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN attendence_type_id = 2 THEN 1 ELSE 0 END) AS excuse,
                SUM(CASE WHEN attendence_type_id = 4 THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN attendence_type_id = 3 THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN attendence_type_id = 6 THEN 1 ELSE 0 END) AS half_day')
            ->get();

        $rows = [];
        $allStudent = 0;
        $allPresent = 0;
        $allAbsent = 0;
        foreach ($sections as $value) {
            $present = (int) $value->present;
            $excuse = (int) $value->excuse;
            $late = (int) $value->late;
            $halfDay = (int) $value->half_day;
            $absent = (int) $value->absent;
            $totalPresent = $present + $excuse + $late + $halfDay;
            $totalStudent = $totalPresent + $absent;
            $presentPercent = $totalPresent > 0 && $totalStudent > 0
                ? round(($totalPresent / $totalStudent) * 100)
                : 0;
            $absentPercent = $absent > 0 && $totalStudent > 0
                ? round(($absent / $totalStudent) * 100)
                : 0;

            $allStudent += $totalStudent;
            $allPresent += $totalPresent;
            $allAbsent += $absent;

            $rows[] = [
                'class_section' => $value->class_name.' ('.$value->section_name.')',
                'total_present' => $totalPresent,
                'total_absent' => $absent,
                'present_percent' => $presentPercent.'%',
                'absent_persent' => $absentPercent.'%',
                'total_male_present' => $this->genderCount((int) $value->class_id, (int) $value->sections_id, $date, 'Male', [1, 2, 3, 6]),
                'total_female_present' => $this->genderCount((int) $value->class_id, (int) $value->sections_id, $date, 'Female', [1, 2, 3, 6]),
                'total_male_absent' => $this->genderCount((int) $value->class_id, (int) $value->sections_id, $date, 'Male', [4]),
                'total_female_absent' => $this->genderCount((int) $value->class_id, (int) $value->sections_id, $date, 'Female', [4]),
            ];
        }

        return [
            'rows' => $rows,
            'all_student' => $allStudent,
            'all_present' => $allPresent,
            'all_absent' => $allAbsent,
            'all_present_percent' => $allStudent > 0 ? round(($allPresent / $allStudent) * 100).'%' : '0%',
            'all_absent_percent' => $allStudent > 0 ? round(($allAbsent / $allStudent) * 100).'%' : '0%',
        ];
    }

    /**
     * CI attendancereport — show student_attendences type counts (not unused $fdata filter).
     *
     * @return array{rows: Collection<int, object>, filter: string}
     */
    public function attendanceTypeReport(
        int $classId,
        ?int $sectionId,
        int $attendanceTypeId,
        string $searchType,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $range = $this->dateRange($searchType !== '' ? $searchType : 'this_week', $dateFrom, $dateTo);
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('student_attendences')
            ->join('student_session', 'student_session.id', '=', 'student_attendences.student_session_id')
            ->join('students', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('student_attendences.attendence_type_id', $attendanceTypeId)
            ->where('student_session.class_id', $classId)
            ->whereRaw("DATE_FORMAT(student_attendences.date, '%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']])
            ->groupBy([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.id',
                'classes.class',
                'sections.id',
                'sections.section',
            ])
            ->orderBy('students.id')
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                DB::raw('COUNT(student_attendences.id) as total_type'),
            ]);

        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return [
            'rows' => $query->get(),
            'filter' => $this->formatDate($range['from']).' To '.$this->formatDate($range['to']),
        ];
    }

    public function attendanceSourceLabel(?object $row): string
    {
        $biometric = (int) ($row->biometric_attendence ?? 0);
        $qr = (int) ($row->qrcode_attendance ?? 0);
        if ($biometric === 1) {
            return (string) __('system.biometric');
        }
        if ($qr === 1) {
            return (string) __('system.qrcode').' / '.(string) __('system.barcode');
        }
        if ((int) ($row->attendence_id ?? $row->id ?? 0) > 0) {
            return (string) __('system.manual');
        }

        return '';
    }

    /**
     * @param  list<int>  $typeIds
     */
    protected function genderCount(int $classId, int $sectionId, string $date, string $gender, array $typeIds): int
    {
        $sessionId = (int) $this->currentSession->id();

            return (int) DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('student_attendences', function ($join) use ($date, $typeIds) {
                $join->on('student_attendences.student_session_id', '=', 'student_session.id')
                    ->where('student_attendences.date', '=', $date)
                    ->whereIn('student_attendences.attendence_type_id', $typeIds);
            })
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('students.is_active', 'yes')
            ->where('students.gender', $gender)
            ->selectRaw('COUNT(DISTINCT students.id) as aggregate')
            ->value('aggregate');
    }

    protected function applyStudentModeFilter($query, ?int $mode): void
    {
        if ($mode === 1) {
            $query->where('student_attendences.biometric_attendence', 0)
                ->where('student_attendences.qrcode_attendance', 0);
        } elseif ($mode === 2) {
            $query->where('student_attendences.biometric_attendence', 0)
                ->where('student_attendences.qrcode_attendance', 1);
        } elseif ($mode === 3) {
            $query->where('student_attendences.biometric_attendence', 1)
                ->where('student_attendences.qrcode_attendance', 0);
        }
    }

    protected function applyStaffModeFilter($query, ?int $mode): void
    {
        if ($mode === 1) {
            $query->where('staff_attendance.biometric_attendence', 0)
                ->where('staff_attendance.qrcode_attendance', 0);
        } elseif ($mode === 2) {
            $query->where('staff_attendance.biometric_attendence', 0)
                ->where('staff_attendance.qrcode_attendance', 1);
        } elseif ($mode === 3) {
            $query->where('staff_attendance.biometric_attendence', 1)
                ->where('staff_attendance.qrcode_attendance', 0);
        }
    }
}
