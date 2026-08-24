<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Models\StaffDesignation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffDesignationTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupDesignationIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupDesignationIds !== []) {
            DB::table('staff_designation')->whereIn('id', $this->cleanupDesignationIds)->delete();
        }
        $this->cleanupDesignationIds = [];

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

        $token = uniqid('desg', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'DESG-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Designation',
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

    public function test_designation_crud_flow(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $name = 'Senior Teacher '.$suffix;
        $updated = 'Lead Teacher '.$suffix;

        $this->get('/admin/designation/designation')
            ->assertOk()
            ->assertSee(__('system.add_designation'), false);

        $this->post('/admin/designation/designation', [
            'type' => '',
        ])->assertSessionHasErrors('type');

        $this->post('/admin/designation/designation', [
            'type' => $name,
        ])->assertRedirect(route('staff.designations.index'));

        $row = StaffDesignation::query()->where('designation', $name)->firstOrFail();
        $this->cleanupDesignationIds[] = $row->id;

        $this->get('/admin/designation/designationedit/'.$row->id)
            ->assertOk()
            ->assertSee($name, false)
            ->assertSee(__('system.edit_designation'), false);

        $this->post('/admin/designation/designation', [
            'type' => $updated,
            'designationid' => $row->id,
        ])->assertRedirect(route('staff.designations.index'));

        $this->assertSame($updated, StaffDesignation::query()->findOrFail($row->id)->designation);

        $this->get('/admin/designation/designationdelete/'.$row->id)
            ->assertRedirect(route('staff.designations.index'));

        $this->assertNull(StaffDesignation::query()->find($row->id));
        $this->cleanupDesignationIds = [];
    }

    public function test_designation_rejects_duplicate_name(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $name = 'Duplicate Desg '.$suffix;

        $this->post('/admin/designation/designation', [
            'type' => $name,
        ])->assertRedirect(route('staff.designations.index'));

        $row = StaffDesignation::query()->where('designation', $name)->firstOrFail();
        $this->cleanupDesignationIds[] = $row->id;

        $this->post('/admin/designation/designation', [
            'type' => $name,
        ])->assertSessionHasErrors('type');
    }
}
