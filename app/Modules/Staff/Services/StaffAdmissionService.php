<?php

namespace App\Modules\Staff\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Auth\Services\LegacyPasswordVerifier;
use App\Modules\Certificates\Services\StaffIdCardScanCodeService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CI admin/Staff::create + Staff_model::batchInsert core persist.
 * Deferred: SaaS quota, mail/SMS credentials, document uploads.
 */
class StaffAdmissionService
{
    public function __construct(
        protected LegacyPasswordVerifier $passwords,
        protected CustomFieldValueService $customFields,
        protected CurrentSessionResolver $currentSession,
        protected StaffIdCardScanCodeService $scanCodes,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input  Validated request payload
     * @param  list<array{custom_field_id:int,field_value:string}>  $customFieldRows
     * @return array{staff_id:int,employee_id:string,password:string}
     */
    public function create(array $input, array $customFieldRows = []): array
    {
        $settings = SchSetting::query()->orderBy('id')->firstOrFail();
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new \RuntimeException('Current academic session is not configured in sch_settings.');
        }

        $employeeId = $this->resolveEmployeeId($settings, $input);
        if ($this->employeeIdExists($employeeId)) {
            throw new \InvalidArgumentException('Employee id '.$employeeId.' already exists.');
        }

        $plainPassword = $this->randomPassword();
        $staffRow = $this->mapStaffRow($input, $employeeId, $plainPassword, $settings);
        $roleId = (int) ($input['role'] ?? 0);
        if ($roleId <= 0) {
            throw new \InvalidArgumentException('Role is required.');
        }

        $leaveRows = $this->mapLeaveRows($input);

        return DB::transaction(function () use ($staffRow, $roleId, $leaveRows, $settings, $sessionId, $customFieldRows, $employeeId, $plainPassword) {
            $staffId = (int) DB::table('staff')->insertGetId($staffRow);

            DB::table('staff_roles')->insert([
                'staff_id' => $staffId,
                'role_id' => $roleId,
                'is_active' => 1,
            ]);

            if ((int) $settings->staffid_auto_insert === 1 && (int) $settings->staffid_update_status === 0) {
                $settings->staffid_update_status = 1;
                $settings->save();
            }

            if ($leaveRows !== []) {
                foreach ($leaveRows as &$leaveRow) {
                    $leaveRow['staff_id'] = $staffId;
                    $leaveRow['session_id'] = $sessionId;
                }
                unset($leaveRow);
                DB::table('staff_leave_details')->insert($leaveRows);
            }

            if ($customFieldRows !== []) {
                $this->customFields->insertFor($staffId, $customFieldRows);
            }

            $scanType = (string) ($settings->scan_code_type ?? 'barcode');
            $this->scanCodes->generate($employeeId, $staffId, $scanType !== '' ? $scanType : 'barcode');

            return [
                'staff_id' => $staffId,
                'employee_id' => $employeeId,
                'password' => $plainPassword,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{leave_type_id:int,alloted_leave:float|int|string}>
     */
    protected function mapLeaveRows(array $input): array
    {
        $types = $input['leave_type'] ?? [];
        if (! is_array($types) || $types === []) {
            return [];
        }

        $rows = [];
        foreach ($types as $typeId) {
            $typeId = (int) $typeId;
            if ($typeId <= 0) {
                continue;
            }
            $alloted = $input['alloted_leave_'.$typeId] ?? 0;
            $rows[] = [
                'leave_type_id' => $typeId,
                'alloted_leave' => $alloted === '' ? 0 : $alloted,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function mapStaffRow(array $input, string $employeeId, string $plainPassword, SchSetting $settings): array
    {
        $dob = (string) ($input['dob'] ?? '');
        $joining = (string) ($input['date_of_joining'] ?? '');

        return [
            'employee_id' => $employeeId,
            'lang_id' => (int) ($settings->lang_id ?? 1),
            'currency_id' => 0,
            'department' => $this->nullableInt($input['department'] ?? null),
            'designation' => $this->nullableInt($input['designation'] ?? null),
            'qualification' => (string) ($input['qualification'] ?? ''),
            'work_exp' => (string) ($input['work_exp'] ?? ''),
            'name' => (string) ($input['name'] ?? ''),
            'surname' => (string) ($input['surname'] ?? ''),
            'father_name' => (string) ($input['father_name'] ?? ''),
            'mother_name' => (string) ($input['mother_name'] ?? ''),
            'contact_no' => (string) ($input['contactno'] ?? $input['contact_no'] ?? ''),
            'emergency_contact_no' => (string) ($input['emergency_no'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'dob' => $dob !== '' ? $dob : '1990-01-01',
            'marital_status' => (string) ($input['marital_status'] ?? ''),
            'date_of_joining' => $joining !== '' ? $joining : null,
            'date_of_leaving' => null,
            'local_address' => (string) ($input['address'] ?? ''),
            'permanent_address' => (string) ($input['permanent_address'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'image' => '',
            'password' => $this->passwords->hash($plainPassword),
            'gender' => (string) ($input['gender'] ?? ''),
            'account_title' => (string) ($input['account_title'] ?? ''),
            'bank_account_no' => (string) ($input['bank_account_no'] ?? ''),
            'bank_name' => (string) ($input['bank_name'] ?? ''),
            'ifsc_code' => (string) ($input['ifsc_code'] ?? ''),
            'bank_branch' => (string) ($input['bank_branch'] ?? ''),
            'payscale' => '',
            'basic_salary' => isset($input['basic_salary']) && $input['basic_salary'] !== '' ? (int) $input['basic_salary'] : null,
            'epf_no' => (string) ($input['epf_no'] ?? ''),
            'contract_type' => (string) ($input['contract_type'] ?? ''),
            'shift' => (string) ($input['shift'] ?? ''),
            'location' => (string) ($input['location'] ?? ''),
            'facebook' => (string) ($input['facebook'] ?? ''),
            'twitter' => (string) ($input['twitter'] ?? ''),
            'linkedin' => (string) ($input['linkedin'] ?? ''),
            'instagram' => (string) ($input['instagram'] ?? ''),
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function resolveEmployeeId(SchSetting $settings, array $input): string
    {
        if ((int) $settings->staffid_auto_insert !== 1) {
            $manual = trim((string) ($input['employee_id'] ?? ''));
            if ($manual === '') {
                throw new \InvalidArgumentException('Staff id is required.');
            }

            return $manual;
        }

        return $this->nextEmployeeId($settings);
    }

    protected function nextEmployeeId(SchSetting $settings): string
    {
        $prefix = (string) $settings->staffid_prefix;
        $digits = max(1, (int) $settings->staffid_no_digit);

        if ((int) $settings->staffid_update_status === 1) {
            $last = Staff::query()->orderByDesc('id')->value('employee_id');
            if ($last && str_starts_with((string) $last, $prefix)) {
                $numeric = (int) Str::after((string) $last, $prefix);

                return $prefix.sprintf('%0'.$digits.'d', $numeric + 1);
            }
        }

        return $prefix.(string) $settings->staffid_start_from;
    }

    protected function employeeIdExists(string $employeeId): bool
    {
        return Staff::query()->where('employee_id', $employeeId)->exists();
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * CI Role::get_random_password(6, 6, false, true, false).
     */
    protected function randomPassword(): string
    {
        $selection = 'aeuoyibcdfghjklmnpqrstvwxz1234567890';
        $password = '';
        for ($i = 0; $i < 6; $i++) {
            $password .= $selection[random_int(0, strlen($selection) - 1)];
        }

        return $password;
    }
}
