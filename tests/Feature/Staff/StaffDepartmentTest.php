<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffDepartmentTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupDepartmentIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupDepartmentIds !== []) {
            DB::table('department')->whereIn('id', $this->cleanupDepartmentIds)->delete();
        }
        $this->cleanupDepartmentIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('dept', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'DEPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Department',
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
    }

    public function test_department_crud_flow(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $name = 'Science '.$suffix;
        $updated = 'Science Updated '.$suffix;

        $this->get('/admin/department/department')
            ->assertOk()
            ->assertSee(__('system.add_department'), false);

        $this->post('/admin/department/department', [
            'type' => '',
        ])->assertSessionHasErrors('type');

        $this->post('/admin/department/department', [
            'type' => $name,
        ])->assertRedirect(route('staff.departments.index'));

        $row = Department::query()->where('department_name', $name)->firstOrFail();
        $this->cleanupDepartmentIds[] = $row->id;

        $this->get('/admin/department/departmentedit/'.$row->id)
            ->assertOk()
            ->assertSee($name, false)
            ->assertSee(__('system.edit_department'), false);

        $this->post('/admin/department/department', [
            'type' => $updated,
            'departmenttypeid' => $row->id,
        ])->assertRedirect(route('staff.departments.index'));

        $this->assertSame($updated, Department::query()->findOrFail($row->id)->department_name);

        $this->get('/admin/department/departmentdelete/'.$row->id)
            ->assertRedirect(route('staff.departments.index'));

        $this->assertNull(Department::query()->find($row->id));
        $this->cleanupDepartmentIds = [];
    }

    public function test_department_rejects_duplicate_name(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $name = 'Duplicate Dept '.$suffix;

        $this->post('/admin/department/department', [
            'type' => $name,
        ])->assertRedirect(route('staff.departments.index'));

        $row = Department::query()->where('department_name', $name)->firstOrFail();
        $this->cleanupDepartmentIds[] = $row->id;

        $this->post('/admin/department/department', [
            'type' => $name,
        ])->assertSessionHasErrors('type');
    }
}
