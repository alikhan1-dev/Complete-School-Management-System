<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('stp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Profile',
            'surname' => 'Admin',
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
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');

        return $roleId;
    }

    private function teacherRoleId(): int
    {
        $roleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $roleId);

        return $roleId;
    }

    private function grantDisableStaffPermission(int $roleId): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', 'disable_staff')->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existingId = DB::table('roles_permissions')
            ->where('role_id', $roleId)
            ->where('perm_cat_id', $permCatId)
            ->value('id');

        if ($existingId) {
            DB::table('roles_permissions')->where('id', $existingId)->update(['can_view' => 1]);
        } else {
            DB::table('roles_permissions')->insert([
                'role_id' => $roleId,
                'perm_cat_id' => $permCatId,
                'can_view' => 1,
                'can_add' => 0,
                'can_edit' => 0,
                'can_delete' => 0,
            ]);
        }
    }

    private function actingAsNonSuperAdminWithDisablePermission(): void
    {
        $roleId = $this->teacherRoleId();
        $this->grantDisableStaffPermission($roleId);

        $token = uniqid('std', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Disable',
            'surname' => 'Manager',
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
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    private function createTeacherStaff(string $suffix): Staff
    {
        $teacherRoleId = $this->teacherRoleId();

        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PRF-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Profile',
            'surname' => 'Target',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '03005556677',
            'emergency_contact_no' => '',
            'email' => 'profile'.$suffix.'@example.test',
            'dob' => '1985-06-20',
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
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    public function test_staff_profile_shows_core_details(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());

        $this->get('/admin/staff/profile/'.$target->id)
            ->assertOk()
            ->assertSee('PRF-', false)
            ->assertSee('profile', false)
            ->assertSee('03005556677', false);
    }

    public function test_staff_disable_and_enable_redirect_to_profile(): void
    {
        $this->actingAsNonSuperAdminWithDisablePermission();
        $target = $this->createTeacherStaff(uniqid());

        $this->post('/admin/staff/disablestaff/'.$target->id)
            ->assertRedirect(route('staff.profile', $target->id));

        $target->refresh();
        $this->assertSame(0, (int) $target->is_active);

        $this->get('/admin/staff/enablestaff/'.$target->id)
            ->assertRedirect(route('staff.profile', $target->id));

        $target->refresh();
        $this->assertSame(1, (int) $target->is_active);
    }

    public function test_superadmin_disable_with_date_returns_json(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());

        $this->postJson('/admin/staff/disablestaff/'.$target->id, [
            'date' => '2026-08-20',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $target->refresh();
        $this->assertSame(0, (int) $target->is_active);
        $this->assertSame('2026-08-20', (string) $target->disable_at);
    }
}
