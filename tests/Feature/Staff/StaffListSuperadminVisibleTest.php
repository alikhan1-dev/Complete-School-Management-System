<?php

namespace Tests\Feature\Staff;

use App\Modules\Roles\Models\PermissionCategory;
use App\Modules\Roles\Models\RolePermission;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Services\StaffListService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffListSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $grantedPermissionIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->grantedPermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->grantedPermissionIds)->delete();
            $this->grantedPermissionIds = [];
        }

        if ($this->createdStaffIds !== []) {
            DB::table('staff_roles')->whereIn('staff_id', $this->createdStaffIds)->delete();
            DB::table('staff')->whereIn('id', $this->createdStaffIds)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function grantStaffView(int $roleId): void
    {
        $permCatId = (int) PermissionCategory::query()->where('short_code', 'staff')->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existingId = RolePermission::query()
            ->where('role_id', $roleId)
            ->where('perm_cat_id', $permCatId)
            ->value('id');

        if ($existingId) {
            DB::table('roles_permissions')->where('id', $existingId)->update(['can_view' => 1]);

            return;
        }

        $this->grantedPermissionIds[] = (int) DB::table('roles_permissions')->insertGetId([
            'role_id' => $roleId,
            'perm_cat_id' => $permCatId,
            'can_view' => 1,
            'can_add' => 0,
            'can_edit' => 0,
            'can_delete' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => ucfirst($prefix),
            'surname' => 'Staff',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1990-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Male',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'epf_no' => '',
            'contract_type' => '',
            'shift' => '',
            'location' => '',
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    /** @return list<string> */
    private function datatableEmployeeIds(): array
    {
        $payload = $this->getJson('/admin/staff/datatable?draw=1')
            ->assertOk()
            ->json();

        $rows = $payload['data'] ?? [];
        $ids = [];
        foreach ($rows as $row) {
            if (isset($row[1])) {
                $ids[] = (string) $row[1];
            }
        }

        return $ids;
    }

    public function test_staff_datatable_excludes_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');
        $this->assertGreaterThan(0, $superRoleId);
        $this->assertGreaterThan(0, $teacherRoleId);

        $superStaff = $this->createStaff($superRoleId, 'hidden');
        $visibleStaff = $this->createStaff($teacherRoleId, 'visible');
        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->grantStaffView($teacherRoleId);
        $this->actingAs($viewer, 'staff');

        $employeeIds = $this->datatableEmployeeIds();

        $this->assertContains((string) $visibleStaff->employee_id, $employeeIds);
        $this->assertNotContains((string) $superStaff->employee_id, $employeeIds);
    }

    public function test_staff_datatable_shows_superadmin_staff_to_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $superStaff = $this->createStaff($superRoleId, 'shown');
        $viewer = $this->createStaff($superRoleId, 'saView');
        $this->actingAs($viewer, 'staff');

        $employeeIds = $this->datatableEmployeeIds();

        $this->assertContains((string) $superStaff->employee_id, $employeeIds);
    }

    public function test_staff_role_dropdown_excludes_superadmin_role_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $viewer = $this->createStaff($teacherRoleId, 'roleView');
        $this->actingAs($viewer, 'staff');

        $roleIds = app(StaffListService::class)
            ->rolesForFilter()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertNotContains($superRoleId, $roleIds);
        $this->assertContains($teacherRoleId, $roleIds);
    }
}
