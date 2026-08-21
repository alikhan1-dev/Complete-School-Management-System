<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Models\CustomField;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI Report::staff_report + Staff_model::staff_report (superadmin_restriction parity).
 */
class HumanResourceReportService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
    }

    public function currencySymbol(): string
    {
        return $this->school->currencySymbol();
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function formatAmount(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    /**
     * CI Customlib::get_searchtype (includes empty Select).
     *
     * @return array<string, string>
     */
    public function searchDurationTypes(): array
    {
        return [
            '' => (string) __('system.select'),
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
     * CI Customlib::staff_status.
     *
     * @return array<string, string>
     */
    public function staffStatuses(): array
    {
        return [
            'both' => (string) __('system.all'),
            '1' => (string) __('system.active'),
            '2' => (string) __('system.disabled'),
        ];
    }

    /**
     * @return array{from: string, to: string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        return match ($searchType) {
            'today' => ['from' => $now->toDateString(), 'to' => $now->toDateString()],
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
                'from' => $this->normalizeInputDate($dateFrom) ?? $now->toDateString(),
                'to' => $this->normalizeInputDate($dateTo) ?? $now->toDateString(),
            ],
            default => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    /**
     * @return Collection<int, Role>
     */
    public function roles(): Collection
    {
        return Role::query()->orderBy('id')->get(['id', 'name']);
    }

    /**
     * @return list<object{id:int,designation:string}>
     */
    public function designations(): array
    {
        return DB::table('staff_designation')
            ->orderBy('id')
            ->get(['id', 'designation'])
            ->all();
    }

    /**
     * CI Leavetypes_model::getLeaveType — all types (id => type).
     *
     * @return array<int, string>
     */
    public function leaveTypeMap(): array
    {
        return LeaveType::query()
            ->orderBy('id')
            ->pluck('type', 'id')
            ->map(fn ($type) => (string) $type)
            ->all();
    }

    /**
     * CI customfield_model::get_custom_fields('staff', 1).
     *
     * @return Collection<int, CustomField>
     */
    public function staffTableCustomFields(): Collection
    {
        return CustomField::query()
            ->where('belong_to', 'staff')
            ->where('visible_on_table', 1)
            ->orderBy('weight')
            ->orderBy('id')
            ->get();
    }

    /**
     * CI Staff_model::staff_report — query builder rebuild of legacy SQL.
     *
     * @param  array{
     *     search_type?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     staff_status?: string|null,
     *     role?: string|int|null,
     *     designation?: string|int|null,
     *     apply_status?: bool
     * }  $filters
     * @return list<object>
     */
    public function staffReport(array $filters): array
    {
        $customFields = $this->staffTableCustomFields();

        // Rebuild CI Staff_model::staff_report without GROUP BY staff.* (ONLY_FULL_GROUP_BY safe).
        $query = DB::table('staff')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->leftJoin('department', 'department.id', '=', 'staff.department');

        $select = [
            'staff.*',
            'staff_designation.designation as designation',
            'department.department_name as department',
            'roles.name as user_type',
        ];

        $i = 1;
        foreach ($customFields as $field) {
            $alias = 'table_custom_'.$i;
            $valueAlias = 'cf_'.$field->id;
            $query->leftJoin("custom_field_values as {$alias}", function ($join) use ($alias, $field) {
                $join->on('staff.id', '=', "{$alias}.belong_table_id")
                    ->where("{$alias}.custom_field_id", '=', $field->id);
            });
            $select[] = "{$alias}.field_value as {$valueAlias}";
            $i++;
        }

        $query->select($select);

        $searchType = trim((string) ($filters['search_type'] ?? ''));
        if ($searchType !== '') {
            $range = $this->dateRange(
                $searchType,
                isset($filters['date_from']) ? (string) $filters['date_from'] : null,
                isset($filters['date_to']) ? (string) $filters['date_to'] : null
            );
            $query->whereRaw("DATE_FORMAT(staff.date_of_joining,'%Y-%m-%d') BETWEEN ? AND ?", [
                $range['from'],
                $range['to'],
            ]);
        }

        if (! empty($filters['apply_status'])) {
            $status = (string) ($filters['staff_status'] ?? '1');
            $activeValues = match ($status) {
                'both' => [1, 2],
                '2' => [0],
                default => [1],
            };
            $query->whereIn('staff.is_active', $activeValues);
        }

        $roleId = $filters['role'] ?? '';
        if ($roleId !== '' && $roleId !== null) {
            $query->where('staff_roles.role_id', (int) $roleId);
        }

        $designationId = $filters['designation'] ?? '';
        if ($designationId !== '' && $designationId !== null) {
            $query->where('staff_designation.id', (int) $designationId);
        }

        $this->applySuperadminHide($query);

        // Staff may have multiple roles; keep one row per staff like CI GROUP BY staff.id.
        $rows = $query
            ->orderBy('staff.id')
            ->get()
            ->unique('id')
            ->values();

        $staffIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $leaveMap = $this->leaveConcatByStaffIds($staffIds);

        return $rows->map(function ($row) use ($leaveMap) {
            $row->leaves = $leaveMap[(int) $row->id] ?? null;

            return $row;
        })->all();
    }

    /**
     * CI GROUP_CONCAT(leave_type_id,'@',alloted_leave) per staff.
     *
     * @param  list<int>  $staffIds
     * @return array<int, string>
     */
    protected function leaveConcatByStaffIds(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        return DB::table('staff_leave_details')
            ->whereIn('staff_id', $staffIds)
            ->groupBy('staff_id')
            ->select([
                'staff_id',
                DB::raw("GROUP_CONCAT(leave_type_id,'@',alloted_leave) as leaves"),
            ])
            ->pluck('leaves', 'staff_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * Parse GROUP_CONCAT leave allotments into display lines.
     *
     * @param  array<int, string>  $leaveTypeMap
     * @return list<string>
     */
    public function leaveDisplayLines(?string $leavesCsv, array $leaveTypeMap): array
    {
        if ($leavesCsv === null || trim($leavesCsv) === '') {
            return [];
        }

        $lines = [];
        foreach (explode(',', $leavesCsv) as $chunk) {
            $parts = explode('@', $chunk, 2);
            if (count($parts) < 2) {
                continue;
            }
            $typeId = (int) $parts[0];
            if (! array_key_exists($typeId, $leaveTypeMap)) {
                continue;
            }
            $lines[] = $leaveTypeMap[$typeId].' : '.$parts[1];
        }

        return $lines;
    }

    protected function applySuperadminHide($query): void
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return;
        }

        $roleId = (int) ($staff->roles()->value('roles.id') ?? 0);
        if ($roleId === 7) {
            return;
        }

        if ($this->school->superadminRestriction() === 'disabled') {
            $query->where(function ($q) {
                $q->whereNull('roles.id')->orWhere('roles.id', '!=', 7);
            });
        }
    }

    protected function normalizeInputDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
