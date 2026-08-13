<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\StaffIdCard;
use App\Modules\Roles\Models\Role;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Generatestaffidcard — search staff by role & build print payload.
 * Deferred: AJAX JSON print, mPDF, SaaS quota, superadmin visibility filter, custom fields.
 */
class GenerateStaffIdCardService
{
    public function __construct(
        protected StaffIdCardDocumentService $documents,
        protected StaffIdCardScanCodeService $scanCodes
    ) {
    }

    /**
     * @return Collection<int, StaffIdCard>
     */
    public function listTemplates(): Collection
    {
        return StaffIdCard::query()->orderBy('id')->get();
    }

    public function findTemplate(int $id): StaffIdCard
    {
        return StaffIdCard::query()->findOrFail($id);
    }

    /**
     * Roles for search dropdown (CI getStaffRole).
     * Note: some installs store roles.is_active as 0/1 rather than yes/no — list all ordered.
     *
     * @return Collection<int, Role>
     */
    public function listRoles(): Collection
    {
        return Role::query()->orderBy('id')->get(['id', 'name']);
    }

    /**
     * CI Staff_model::getEmployee($role, 1) — active staff, optional role filter.
     *
     * @return Collection<int, object>
     */
    public function searchStaff(?int $roleId): Collection
    {
        $query = DB::table('staff')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->leftJoin('department', 'department.id', '=', 'staff.department')
            ->where('staff.is_active', 1)
            ->select([
                'staff.id',
                'staff.employee_id',
                'staff.name',
                'staff.surname',
                'staff.father_name',
                'staff.mother_name',
                'staff.date_of_joining',
                'staff.contact_no',
                'staff.dob',
                'staff.gender',
                'staff.image',
                'staff.local_address',
                'staff_designation.designation',
                'department.department_name as department',
                'roles.name as user_type',
                'roles.id as role_id',
            ])
            ->orderBy('staff.id');

        if ($roleId) {
            $query->where('roles.id', $roleId);
        }

        return $query->get();
    }

    /**
     * CI Generatestaffidcard_model::getEmployee($ids, 1).
     *
     * @param  list<int>  $staffIds
     * @return Collection<int, object>
     */
    public function staffForPrint(array $staffIds): Collection
    {
        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if ($staffIds === []) {
            return collect();
        }

        return DB::table('staff')
            ->leftJoin('staff_designation', 'staff_designation.id', '=', 'staff.designation')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->leftJoin('department', 'department.id', '=', 'staff.department')
            ->where('staff.is_active', 1)
            ->whereIn('staff.id', $staffIds)
            ->select([
                'staff.id',
                'staff.employee_id',
                'staff.name',
                'staff.surname',
                'staff.father_name',
                'staff.mother_name',
                'staff.date_of_joining',
                'staff.contact_no',
                'staff.dob',
                'staff.gender',
                'staff.image',
                'staff.local_address',
                'staff_designation.designation',
                'department.department_name as department',
                'roles.name as user_type',
            ])
            ->orderBy('staff.id')
            ->get();
    }

    /**
     * @param  list<int>  $staffIds
     * @return array{
     *     idcard: StaffIdCard,
     *     backgroundUrl: ?string,
     *     logoUrl: ?string,
     *     signUrl: ?string,
     *     dateFormat: string,
     *     rows: list<array{staff: object, fullName: string, photoUrl: string, scanUrl: ?string, dobFormatted: string, joiningFormatted: string}>
     * }
     */
    public function buildPrintPayload(StaffIdCard $idcard, array $staffIds): array
    {
        $settings = SchSetting::query()->first();
        $dateFormat = (string) ($settings->date_format ?? 'm/d/Y');
        $scanType = (string) ($settings->scan_code_type ?? 'barcode');

        $staffRows = $this->staffForPrint($staffIds);
        $rows = [];

        foreach ($staffRows as $staff) {
            $scanRelative = null;
            if ((int) $idcard->enable_staff_barcode === 1) {
                $scanRelative = $this->scanCodes->generate(
                    (string) ($staff->employee_id ?? ''),
                    (int) $staff->id,
                    $scanType
                );
            }

            $rows[] = [
                'staff' => $staff,
                'fullName' => trim(($staff->name ?? '').' '.($staff->surname ?? '')),
                'photoUrl' => $this->photoUrl($staff->image ?? null, (string) ($staff->gender ?? '')),
                'scanUrl' => $this->scanCodes->url($scanRelative),
                'dobFormatted' => $this->formatDate((string) ($staff->dob ?? ''), $dateFormat),
                'joiningFormatted' => $this->formatDate((string) ($staff->date_of_joining ?? ''), $dateFormat),
            ];
        }

        return [
            'idcard' => $idcard,
            'backgroundUrl' => $this->documents->url($idcard->background, StaffIdCardDocumentService::FOLDER_BACKGROUND),
            'logoUrl' => $this->documents->url($idcard->logo, StaffIdCardDocumentService::FOLDER_LOGO),
            'signUrl' => $this->documents->url($idcard->sign_image, StaffIdCardDocumentService::FOLDER_SIGNATURE),
            'dateFormat' => $dateFormat,
            'rows' => $rows,
        ];
    }

    protected function formatDate(string $value, string $dateFormat): string
    {
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        $timestamp = strtotime(substr($value, 0, 10));
        if ($timestamp === false) {
            return $value;
        }

        return date($dateFormat, $timestamp);
    }

    protected function photoUrl(mixed $image, string $gender): string
    {
        $path = trim((string) $image);
        if ($path !== '') {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            if (str_starts_with($path, 'uploads/')) {
                return asset(ltrim($path, '/'));
            }

            return asset('uploads/staff_images/'.ltrim($path, '/'));
        }

        $default = strtolower($gender) === 'female'
            ? 'uploads/staff_images/default_female.jpg'
            : 'uploads/staff_images/default_male.jpg';

        return asset($default);
    }
}
