<?php

namespace App\Modules\Staff\Services;

use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\Reports\Services\AttendanceReportService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Staff::profile (core read) + disablestaff/enablestaff + ajax_attendance.
 * Deferred: timeline, payroll, leave summary, rating.
 */
class StaffProfileService
{
    public function __construct(
        protected StaffAttendanceService $attendance,
        protected AttendanceReportService $attendanceReports,
    ) {
    }

    /**
     * CI Staff_model::getProfile.
     */
    public function profile(int $staffId): ?object
    {
        if ($staffId <= 0) {
            return null;
        }

        return DB::table('staff')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('department', 'department.id', '=', 'staff.department')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.id', $staffId)
            ->select([
                'staff.*',
                'staff_designation.designation as designation_label',
                'department.department_name as department_label',
                'roles.id as role_id',
                'roles.name as role_name',
            ])
            ->first();
    }

    public function profileAttendanceMatrix(int $staffId, int $year): array
    {
        $monthlist = $this->attendanceReports->monthDropdown();
        $resultlist = [];
        $dateArray = [];
        $attendenceArray = [];

        foreach ($monthlist as $monthKey => $monthValue) {
            $datemonth = (int) date('m', strtotime($monthKey));
            $dateEachMonth = sprintf('%04d-%02d-01', $year, $datemonth);
            $dateEnd = (int) date('t', strtotime($dateEachMonth));

            for ($n = 1; $n <= $dateEnd; $n++) {
                $attendenceArray[] = sprintf('%02d', $n);
                $attDates = sprintf('%04d-%02d-%02d', $year, $datemonth, $n);
                $dateArray[] = $attDates;
                $resultlist[$attDates] = $this->attendance->searchForStaffOnDate($attDates, $staffId);
            }
        }

        return [
            'year' => $year,
            'monthlist' => $monthlist,
            'resultlist' => $resultlist,
            'attendence_array' => $attendenceArray,
            'date_array' => $dateArray,
            'countAttendance' => [
                $year => $this->attendance->yearlyTypeCounts($year, $staffId),
            ],
            'yearlist' => $this->attendance->attendanceYears(),
        ];
    }

    /**
     * @return list<int|string>
     */
    public function attendanceYearOptions(): array
    {
        return collect($this->attendance->attendanceYears())
            ->pluck('year')
            ->filter(fn ($year) => $year !== null && $year !== '')
            ->values()
            ->all();
    }

    public function disable(int $staffId, ?string $disableAt = null): void
    {
        $payload = ['is_active' => 0];
        if ($disableAt !== null && $disableAt !== '') {
            $payload['disable_at'] = $disableAt;
        }

        DB::table('staff')->where('id', $staffId)->update($payload);
    }

    public function enable(int $staffId): void
    {
        DB::table('staff')->where('id', $staffId)->update([
            'is_active' => 1,
            'disable_at' => null,
        ]);
    }

    public function roleId(int $staffId): int
    {
        return (int) DB::table('staff_roles')->where('staff_id', $staffId)->value('role_id');
    }

    public function assertCanManageStatus(Staff $target, Staff $actor): void
    {
        if ($this->roleId((int) $target->id) !== 7) {
            return;
        }

        abort_if($actor->email !== $target->email, 403);
    }
}
