<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Models\StaffAttendanceType;
use App\Modules\Reports\Services\AttendanceReportService;
use App\Modules\Roles\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Staffattendancemodel — staff day attendance search + addorUpdate + profile month lookup.
 * SMS, biometric auto-mark, and superadmin-visibility filter deferred.
 */
class StaffAttendanceService
{
    public const TYPE_PRESENT = 1;

    public const TYPE_LATE = 2;

    public const TYPE_ABSENT = 3;

    public const TYPE_HALF_DAY = 4;

    public const TYPE_HOLIDAY = 5;

    public const TYPE_HALF_DAY_SECOND_SHIFT = 6;

    /**
     * @return Collection<int, StaffAttendanceType>
     */
    public function activeTypes(): Collection
    {
        return StaffAttendanceType::query()->active()->get();
    }

    /**
     * CI Staff_model::getStaffRole — active roles for the role dropdown.
     *
     * @return Collection<int, Role>
     */
    public function rolesForFilter(): Collection
    {
        return Role::query()
            ->where('is_active', 'yes')
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * CI StaffAttendaceSetting_model::getRoleWiseAttendanceSetting.
     * Used by UI to prefill entry/exit times when "set all" is chosen.
     *
     * @return list<array<string, mixed>>
     */
    public function schedulesForRole(?string $roleName): array
    {
        $query = DB::table('staff_attendence_schedules')
            ->join('roles', 'roles.id', '=', 'staff_attendence_schedules.role_id')
            ->orderBy('roles.id')
            ->select([
                'staff_attendence_schedules.*',
                'roles.name as role_name',
            ]);

        if ($roleName !== null && $roleName !== '' && $roleName !== 'select') {
            $query->where('roles.name', $roleName);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /**
     * CI searchAttendenceUserType — roster by role name or all ("select").
     *
     * @return Collection<int, object>
     */
    public function searchByRole(string $roleName, string $date): Collection
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
            ->select([
                'staff_attendance.out_time',
                'staff_attendance.in_time',
                DB::raw('IFNULL(staff_attendance.id, 0) as id'),
                'staff_attendance.created_at as attendence_dt',
                'staff_attendance.staff_attendance_type_id',
                'staff_attendance.biometric_attendence',
                'staff_attendance.qrcode_attendance',
                'staff_attendance.user_agent',
                'staff_attendance.biometric_device_data',
                'staff_attendance.remark',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff.contact_no',
                'staff.email',
                'roles.name as user_type',
                'roles.id as role_id',
                DB::raw("IFNULL(staff_attendance.date, 'xxx') as date"),
                'staff.id as staff_id',
                'staff_attendance_type.type as att_type',
                'staff_attendance_type.key_value as `key`',
                'staff_attendance_type.long_lang_name',
                'staff_attendance_type.long_name_style',
            ]);

        if ($roleName !== 'select') {
            $query->where('roles.name', $roleName);
        }

        return $query->orderBy('staff.employee_id')->get();
    }

    /**
     * CI addorUpdate — upsert by (staff_id, date).
     *
     * @param  list<array{
     *     staff_id:int,
     *     staff_attendance_type_id:int,
     *     date:string,
     *     remark?:string|null,
     *     in_time?:string|null,
     *     out_time?:string|null
     * }>  $rows
     */
    public function addOrUpdate(array $rows): int
    {
        if ($rows === []) {
            throw new InvalidArgumentException('No attendance rows to save.');
        }

        $activeTypeIds = StaffAttendanceType::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return (int) DB::transaction(function () use ($rows, $activeTypeIds) {
            $saved = 0;

            foreach ($rows as $row) {
                $staffId = (int) ($row['staff_id'] ?? 0);
                $typeId = (int) ($row['staff_attendance_type_id'] ?? 0);
                $date = (string) ($row['date'] ?? '');

                if ($staffId <= 0 || $date === '' || $typeId <= 0) {
                    throw new InvalidArgumentException('Invalid attendance row.');
                }
                if (! in_array($typeId, $activeTypeIds, true)) {
                    throw new InvalidArgumentException('Invalid staff attendance type.');
                }

                $inTime = $row['in_time'] ?? null;
                $outTime = $row['out_time'] ?? null;
                if (in_array($typeId, [self::TYPE_ABSENT, self::TYPE_HOLIDAY], true)) {
                    $inTime = null;
                    $outTime = null;
                }

                $payload = [
                    'staff_id' => $staffId,
                    'date' => $date,
                    'staff_attendance_type_id' => $typeId,
                    'remark' => (string) ($row['remark'] ?? ''),
                    'in_time' => $this->normalizeTime($inTime),
                    'out_time' => $this->normalizeTime($outTime),
                    // Column is numeric in staff_attendance (unlike student_attendences yes/no).
                    'is_active' => 0,
                ];

                $existing = StaffAttendance::query()
                    ->where('staff_id', $staffId)
                    ->where('date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                } else {
                    StaffAttendance::query()->create(array_merge($payload, [
                        'biometric_attendence' => 0,
                        'qrcode_attendance' => 0,
                        'biometric_device_data' => null,
                        'user_agent' => null,
                    ]));
                }
                $saved++;
            }

            return $saved;
        });
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '00:00:00') {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('H:i:s', $ts);
    }

    /**
     * CI Staffattendancemodel::searchStaffattendance.
     */
    public function searchForStaffOnDate(string $date, int $staffId, bool $activeStaffOnly = true): ?object
    {
        if ($staffId <= 0 || $date === '') {
            return null;
        }

        $query = DB::table('staff')
            ->leftJoin('staff_attendance', function ($join) use ($date) {
                $join->on('staff.id', '=', 'staff_attendance.staff_id')
                    ->where('staff_attendance.date', '=', $date);
            })
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->leftJoin('staff_attendance_type', 'staff_attendance_type.id', '=', 'staff_attendance.staff_attendance_type_id')
            ->where('staff.id', $staffId)
            ->select([
                'staff_attendance.staff_attendance_type_id',
                'staff_attendance_type.type as att_type',
                'staff_attendance_type.key_value as att_key',
                'staff_attendance.remark',
                'staff.name',
                'staff.surname',
                'staff.contact_no',
                'staff.email',
                'roles.name as user_type',
                DB::raw("IFNULL(staff_attendance.date, 'xxx') as date"),
                DB::raw('IFNULL(staff_attendance.id, 0) as attendence_id'),
                'staff.id as id',
            ]);

        if ($activeStaffOnly) {
            $query->where('staff.is_active', 1);
        }

        return $query->first();
    }

    /**
     * CI Staffattendancemodel::attendanceYearCount.
     *
     * @return list<object{year: int|string}>
     */
    public function attendanceYears(): array
    {
        return DB::table('staff_attendance')
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->orderBy('year')
            ->get()
            ->all();
    }

    /**
     * CI Staff_model::count_attendance + Staff::countAttendance for one staff/year.
     *
     * @return array<string, int>
     */
    public function yearlyTypeCounts(int $year, int $staffId): array
    {
        $counts = [];
        foreach (AttendanceReportService::STAFF_ATTENDANCE_TYPE_MAP as $key => $typeId) {
            $counts[$key] = (int) DB::table('staff_attendance')
                ->where('staff_id', $staffId)
                ->whereRaw('YEAR(date) = ?', [$year])
                ->where('staff_attendance_type_id', $typeId)
                ->count();
        }

        return $counts;
    }
}
