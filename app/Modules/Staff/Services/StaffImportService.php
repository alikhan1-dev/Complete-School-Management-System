<?php

namespace App\Modules\Staff\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SaasValidationService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/Staff::import + handle_csv_upload + exportformat.
 */
class StaffImportService
{
    /** @var list<string> */
    public const IMPORT_FIELDS = [
        'employee_id',
        'qualification',
        'work_exp',
        'name',
        'surname',
        'father_name',
        'mother_name',
        'contact_no',
        'emergency_contact_no',
        'email',
        'dob',
        'marital_status',
        'date_of_joining',
        'date_of_leaving',
        'local_address',
        'permanent_address',
        'note',
        'gender',
        'account_title',
        'bank_account_no',
        'bank_name',
        'ifsc_code',
        'bank_branch',
        'payscale',
        'basic_salary',
        'epf_no',
        'contract_type',
        'shift',
        'location',
        'facebook',
        'twitter',
        'linkedin',
        'instagram',
        'resume',
        'joining_letter',
        'resignation_letter',
    ];

    /** @var list<string> */
    public const DISPLAY_FIELDS = [
        'staff_id',
        'first_name',
        'last_name',
        'father_name',
        'mother_name',
        'email_login_username',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'phone',
        'emergency_contact_number',
        'marital_status',
        'current_address',
        'permanent_address',
        'qualification',
        'work_experience',
        'note',
    ];

    public function __construct(
        protected StaffAdmissionService $admission,
        protected SaasValidationService $saas,
    ) {
    }

    public function sampleCsvPath(): string
    {
        return public_path('backend/import/staff_csvfile.csv');
    }

    /**
     * @return array{imported:int,total:int}
     */
    public function importFromCsv(string $absolutePath, int $roleId, ?int $departmentId, ?int $designationId): array
    {
        if ($roleId <= 0) {
            throw ValidationException::withMessages([
                'role' => (string) __('system.required'),
            ]);
        }

        $rows = $this->parseCsvRows($absolutePath);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => (string) __('system.please_select_file'),
            ]);
        }

        $this->saas->assertCanAddStaff(count($rows));

        $imported = 0;
        foreach ($rows as $row) {
            if (! $this->isImportableRow($row)) {
                continue;
            }

            if ($this->employeeIdExists((string) $row['employee_id']) || $this->emailExists((string) $row['email'])) {
                continue;
            }

            $input = $this->mapCsvRowToInput($row, $roleId, $departmentId, $designationId);
            $this->admission->importRow($input, $roleId);
            $imported++;
        }

        return [
            'imported' => $imported,
            'total' => count($rows),
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function isImportableRow(array $row): bool
    {
        return trim((string) ($row['employee_id'] ?? '')) !== ''
            && trim((string) ($row['name'] ?? '')) !== ''
            && trim((string) ($row['email'] ?? '')) !== ''
            && trim((string) ($row['gender'] ?? '')) !== ''
            && trim((string) ($row['dob'] ?? '')) !== '';
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    protected function mapCsvRowToInput(array $row, int $roleId, ?int $departmentId, ?int $designationId): array
    {
        $leaving = trim((string) ($row['date_of_leaving'] ?? ''));

        return [
            'employee_id' => trim((string) $row['employee_id']),
            'role' => $roleId,
            'department' => $departmentId,
            'designation' => $designationId,
            'qualification' => trim((string) ($row['qualification'] ?? '')),
            'work_exp' => trim((string) ($row['work_exp'] ?? '')),
            'name' => trim((string) $row['name']),
            'surname' => trim((string) ($row['surname'] ?? '')),
            'father_name' => trim((string) ($row['father_name'] ?? '')),
            'mother_name' => trim((string) ($row['mother_name'] ?? '')),
            'contactno' => trim((string) ($row['contact_no'] ?? '')),
            'emergency_no' => trim((string) ($row['emergency_contact_no'] ?? '')),
            'email' => trim((string) $row['email']),
            'dob' => trim((string) $row['dob']),
            'marital_status' => trim((string) ($row['marital_status'] ?? '')),
            'date_of_joining' => trim((string) ($row['date_of_joining'] ?? '')),
            'date_of_leaving' => $leaving !== '' ? $leaving : null,
            'address' => trim((string) ($row['local_address'] ?? '')),
            'permanent_address' => trim((string) ($row['permanent_address'] ?? '')),
            'note' => trim((string) ($row['note'] ?? '')),
            'gender' => trim((string) $row['gender']),
            'account_title' => trim((string) ($row['account_title'] ?? '')),
            'bank_account_no' => trim((string) ($row['bank_account_no'] ?? '')),
            'bank_name' => trim((string) ($row['bank_name'] ?? '')),
            'ifsc_code' => trim((string) ($row['ifsc_code'] ?? '')),
            'bank_branch' => trim((string) ($row['bank_branch'] ?? '')),
            'basic_salary' => trim((string) ($row['basic_salary'] ?? '')),
            'epf_no' => trim((string) ($row['epf_no'] ?? '')),
            'contract_type' => trim((string) ($row['contract_type'] ?? '')),
            'shift' => trim((string) ($row['shift'] ?? '')),
            'location' => trim((string) ($row['location'] ?? '')),
            'facebook' => trim((string) ($row['facebook'] ?? '')),
            'twitter' => trim((string) ($row['twitter'] ?? '')),
            'linkedin' => trim((string) ($row['linkedin'] ?? '')),
            'instagram' => trim((string) ($row['instagram'] ?? '')),
            'resume' => trim((string) ($row['resume'] ?? '')),
            'joining_letter' => trim((string) ($row['joining_letter'] ?? '')),
            'resignation_letter' => trim((string) ($row['resignation_letter'] ?? '')),
        ];
    }

    protected function employeeIdExists(string $employeeId): bool
    {
        return Staff::query()->where('employee_id', $employeeId)->exists();
    }

    protected function emailExists(string $email): bool
    {
        return Staff::query()->where('email', $email)->exists();
    }

    /**
     * @return list<array<string, string>>
     */
    protected function parseCsvRows(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the uploaded CSV file.',
            ]);
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);

            $header = fgetcsv($handle, 0, $delimiter);
            if ($header === false || $header === [null] || $header === []) {
                return [];
            }

            $header = array_map(static fn ($column) => strtolower(trim((string) $column)), $header);

            $rows = [];
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($data === [null] || $data === []) {
                    continue;
                }

                if (count($data) === 1 && is_string($data[0]) && str_contains($data[0], ',')) {
                    $data = str_getcsv($data[0]);
                }

                if (count($data) !== count($header)) {
                    continue;
                }

                $assoc = [];
                foreach ($header as $index => $key) {
                    if ($key === '') {
                        continue;
                    }
                    $assoc[$key] = trim((string) ($data[$index] ?? ''));
                }

                if ($assoc !== []) {
                    $rows[] = $assoc;
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public static function normalizeOptionalId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'select') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
