<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Attendance\Models\StaffAttendanceType;
use App\Modules\Payroll\Models\PayslipAllowance;
use App\Modules\Payroll\Models\StaffPayslip;
use App\Modules\Roles\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Payroll_model + Payroll controller helpers — staff payslip generate / pay / report.
 * Deferred: currency format helpers, SMS/mail on pay, payslip PDF header image, superadmin_visible filter.
 */
class PayrollService
{
    /** @var array<string, int> CI config payroll.php staffattendance */
    public const STAFF_ATTENDANCE = [
        'present' => 1,
        'late' => 2,
        'absent' => 3,
        'half_day' => 4,
        'holiday' => 5,
        'half_day_second_shift' => 6,
    ];

    /** @var array<string, string> CI config payroll_status (English labels) */
    public const PAYROLL_STATUS = [
        'generated' => 'Generated',
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'not_generate' => 'Not Generated',
    ];

    /** @var array<string, string> CI config payment_mode */
    public const PAYMENT_MODE = [
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'online' => 'Transfer to Bank Account',
    ];

    /**
     * CI Customlib::getMonthDropdown — keys are English month names (January…).
     *
     * @return array<string, string>
     */
    public function monthDropdown(): array
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $name = date('F', mktime(0, 0, 0, $i, 1));
            $months[$name] = $name;
        }

        return $months;
    }

    /**
     * CI Staff_model::getStaffRole — active roles as id + type (name).
     *
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
     * CI Payroll_model::searchEmployee.
     *
     * @return list<array<string, mixed>>
     */
    public function searchEmployee(string $month, string $year, string $empName = '', string $role = ''): array
    {
        $query = DB::table('staff')
            ->leftJoin('staff_payslip', function ($join) use ($month, $year) {
                $join->on('staff.id', '=', 'staff_payslip.staff_id')
                    ->where('staff_payslip.month', '=', $month)
                    ->where('staff_payslip.year', '=', $year);
            })
            ->leftJoin('department', 'department.id', '=', 'staff.department')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff.is_active', 1)
            ->select([
                'staff.*',
                'staff_payslip.status',
                DB::raw('IFNULL(staff_payslip.id, 0) as payslip_id'),
                'roles.name as user_type',
                'staff_designation.designation as designation',
                'department.department_name as department',
            ]);

        if ($role !== '') {
            $query->where('roles.name', $role);
        }
        if ($empName !== '') {
            $query->where('staff.name', $empName);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /**
     * CI Payroll_model::searchEmployeeById.
     *
     * @return array<string, mixed>|null
     */
    public function searchEmployeeById(int $id): ?array
    {
        $row = DB::table('staff')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('department', 'department.id', '=', 'staff.department')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff.id', $id)
            ->select([
                'staff.*',
                'roles.name as user_type',
                'staff_designation.designation',
                'department.department_name as department',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return Collection<int, StaffAttendanceType>
     */
    public function attendanceTypes(): Collection
    {
        return StaffAttendanceType::query()->active()->get();
    }

    public function allotedLeave(int $staffId): float|int|string
    {
        $sum = DB::table('staff_leave_details')
            ->where('staff_id', $staffId)
            ->sum('alloted_leave');

        return $sum ?: 0;
    }

    /**
     * CI Payroll::monthAttendance — last $months months before $stMonth.
     *
     * @return array<string, array<string, int|string>>
     */
    public function monthAttendance(string $stMonth, int $months, int $staffId): array
    {
        $record = [];
        for ($i = 1; $i <= $months; $i++) {
            $month = date('m', strtotime($stMonth.' -'.$i.' month'));
            $year = date('Y', strtotime($stMonth.' -'.$i.' month'));
            $row = [];
            foreach (self::STAFF_ATTENDANCE as $attKey => $attValue) {
                if ($attKey === 'half_day_second_shift') {
                    continue;
                }
                $row[$attKey] = $this->countAttendanceObj($month, $year, $staffId, $attValue);
            }
            $record['01-'.$month.'-'.$year] = $row;
        }

        return $record;
    }

    /**
     * CI Payroll::monthLeaves.
     *
     * @return array<string, int|string>
     */
    public function monthLeaves(string $stMonth, int $months, int $staffId): array
    {
        $record = [];
        for ($i = 1; $i <= $months; $i++) {
            $month = date('m', strtotime($stMonth.' -'.$i.' month'));
            $year = date('Y', strtotime($stMonth.' -'.$i.' month'));
            $leaveCount = DB::table('staff_leave_request')
                ->whereRaw('month(date) = ?', [$month])
                ->whereRaw('year(date) = ?', [$year])
                ->where('staff_id', $staffId)
                ->where('status', 'approve')
                ->sum('leave_days');
            $record[$month] = $leaveCount ?: '0';
        }

        return $record;
    }

    public function countAttendanceObj(string $month, string $year, int $staffId, int $attendanceType): int
    {
        return (int) DB::table('staff_attendance')
            ->where('staff_id', $staffId)
            ->whereRaw('month(date) = ?', [$month])
            ->whereRaw('year(date) = ?', [$year])
            ->where('staff_attendance_type_id', $attendanceType)
            ->count();
    }

    public function checkPayslipAvailable(string $month, string $year, int $staffId): bool
    {
        return ! StaffPayslip::query()
            ->where('month', $month)
            ->where('year', $year)
            ->where('staff_id', $staffId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPayslip(array $data): int
    {
        if (! empty($data['id'])) {
            $id = (int) $data['id'];
            unset($data['id']);
            StaffPayslip::query()->where('id', $id)->update($data);

            return $id;
        }

        $data['payment_mode'] = $data['payment_mode'] ?? '';
        $data['remark'] = $data['remark'] ?? '';
        $payslip = StaffPayslip::query()->create($data);

        return (int) $payslip->id;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function addAllowance(array $row): void
    {
        if (! empty($row['id'])) {
            $id = (int) $row['id'];
            unset($row['id']);
            PayslipAllowance::query()->where('id', $id)->update($row);

            return;
        }

        PayslipAllowance::query()->create($row);
    }

    /**
     * CI Payroll_model::update_allowance.
     *
     * @param  list<array<string, mixed>>  $insertData
     * @param  list<array<string, mixed>>  $updateData
     * @param  list<int|string>  $keepIds
     */
    public function updateAllowance(array $insertData, array $updateData, array $keepIds, int $payslipId, string $type): void
    {
        DB::transaction(function () use ($insertData, $updateData, $keepIds, $payslipId, $type) {
            $q = PayslipAllowance::query()
                ->where('cal_type', $type)
                ->where('payslip_id', $payslipId);
            $keep = array_values(array_filter(array_map('intval', $keepIds), fn ($id) => $id > 0));
            if ($keep !== []) {
                $q->whereNotIn('id', $keep);
            }
            $q->delete();

            foreach ($insertData as $row) {
                PayslipAllowance::query()->create($row);
            }
            foreach ($updateData as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                unset($row['id']);
                PayslipAllowance::query()->where('id', $id)->update($row);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPayslip(int $id): ?array
    {
        $row = DB::table('staff_payslip')
            ->join('staff', 'staff.id', '=', 'staff_payslip.staff_id')
            ->leftJoin('staff_designation', 'staff.designation', '=', 'staff_designation.id')
            ->leftJoin('department', 'staff.department', '=', 'department.id')
            ->where('staff_payslip.id', $id)
            ->select([
                'staff.name',
                'staff.surname',
                'department.department_name as department',
                'staff_designation.designation',
                'staff.employee_id',
                'staff_payslip.*',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllowance(int $payslipId, ?string $type = null): array
    {
        $q = PayslipAllowance::query()
            ->where('payslip_id', $payslipId)
            ->select(['id', 'allowance_type', 'amount', 'cal_type']);
        if ($type !== null && $type !== '') {
            $q->where('cal_type', $type);
        }

        return $q->get()->map(fn ($r) => $r->toArray())->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchPayment(int $staffId, string $month, string $year): ?array
    {
        $row = DB::table('staff')
            ->join('staff_payslip', 'staff.id', '=', 'staff_payslip.staff_id')
            ->where('staff_payslip.month', $month)
            ->where('staff_payslip.year', $year)
            ->where('staff_payslip.staff_id', $staffId)
            ->select([
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff.basic_salary',
                'staff_payslip.*',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function paymentSuccess(array $data, int $payslipId): void
    {
        StaffPayslip::query()->where('id', $payslipId)->update($data);
    }

    public function deletePayslip(int $payslipId): void
    {
        DB::transaction(function () use ($payslipId) {
            PayslipAllowance::query()->where('payslip_id', $payslipId)->delete();
            StaffPayslip::query()->where('id', $payslipId)->delete();
        });
    }

    public function revertPayslipStatus(int $payslipId): void
    {
        StaffPayslip::query()->where('id', $payslipId)->update(['status' => 'generated']);
    }

    /**
     * @return list<array{year: string}>
     */
    public function payrollYearCount(): array
    {
        return StaffPayslip::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn ($r) => ['year' => (string) $r->year])
            ->all();
    }

    /**
     * CI Payroll_model::getpayrollReport — paid slips only.
     *
     * @return list<array<string, mixed>>
     */
    public function getPayrollReport(?string $month, string $year, string $role): array
    {
        $query = DB::table('staff')
            ->join('staff_payslip', 'staff_payslip.staff_id', '=', 'staff.id')
            ->leftJoin('staff_designation', 'staff.designation', '=', 'staff_designation.id')
            ->leftJoin('department', 'staff.department', '=', 'department.id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->where('staff_payslip.status', 'paid')
            ->where('staff.is_active', 1)
            ->where('staff_payslip.year', $year)
            ->select([
                'staff.id',
                'staff.employee_id',
                'staff.name',
                'roles.name as user_type',
                'staff.surname',
                'staff_designation.designation',
                'department.department_name as department',
                'staff_payslip.*',
            ]);

        if ($month !== null && $month !== '') {
            $query->where('staff_payslip.month', $month);
        }
        if ($role !== '' && $role !== 'select') {
            $query->where('roles.name', $role);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /**
     * CI Staff_model::getStaffPayroll.
     *
     * @return list<array<string, mixed>>
     */
    public function staffPayrollForProfile(int $staffId): array
    {
        return DB::table('staff_payslip')
            ->where('staff_id', $staffId)
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * CI Payroll_model::getSalaryDetails — paid slips only.
     *
     * @return array{net_salary: float, earnings: float, deduction: float, basic_salary: float, tax: float}
     */
    public function paidSalarySummary(int $staffId): array
    {
        $row = DB::table('staff_payslip')
            ->where('staff_id', $staffId)
            ->where('status', 'paid')
            ->select([
                DB::raw('COALESCE(SUM(net_salary), 0) as net_salary'),
                DB::raw('COALESCE(SUM(total_allowance), 0) as earnings'),
                DB::raw('COALESCE(SUM(total_deduction), 0) as deduction'),
                DB::raw('COALESCE(SUM(basic), 0) as basic_salary'),
                DB::raw('COALESCE(SUM(tax), 0) as tax'),
            ])
            ->first();

        return [
            'net_salary' => $this->toAmount($row->net_salary ?? 0),
            'earnings' => $this->toAmount($row->earnings ?? 0),
            'deduction' => $this->toAmount($row->deduction ?? 0),
            'basic_salary' => $this->toAmount($row->basic_salary ?? 0),
            'tax' => $this->toAmount($row->tax ?? 0),
        ];
    }

    public function toAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace([',', ' '], '', (string) $value);
    }

    public function generatedByStaffId(): ?int
    {
        $id = Auth::guard('staff')->id();

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param  list<string|null>|null  $types
     * @param  list<string|null>|null  $amounts
     * @return list<array{type: string, amount: float}>
     */
    public function zipAllowanceLines(?array $types, ?array $amounts): array
    {
        if ($types === null || $types === []) {
            return [];
        }
        $lines = [];
        foreach ($types as $i => $type) {
            $lines[] = [
                'type' => (string) ($type ?? ''),
                'amount' => $this->toAmount($amounts[$i] ?? 0),
            ];
        }

        return $lines;
    }

    /**
     * Build create-form context (staff + attendance window).
     *
     * @return array<string, mixed>
     */
    public function createFormContext(string $month, string $year, int $staffId): array
    {
        $result = $this->searchEmployeeById($staffId);
        if ($result === null) {
            throw new InvalidArgumentException('Staff not found.');
        }

        $date = $year.'-'.$month;
        $newdate = date('Y-m-d', strtotime($date.' +1 month'));

        return [
            'result' => $result,
            'month' => $month,
            'year' => $year,
            'monthAttendance' => $this->monthAttendance($newdate, 3, $staffId),
            'monthLeaves' => $this->monthLeaves($newdate, 3, $staffId),
            'attendanceType' => $this->attendanceTypes(),
            'alloted_leave' => $this->allotedLeave($staffId),
        ];
    }
}
