<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffEditTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_leave_details')->where('staff_id', $staffId)->delete();
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

        $token = uniqid('ste', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Super',
            'surname' => 'Editor',
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

    private function createTargetStaff(string $suffix, int $roleId): Staff
    {
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EDT-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Before',
            'surname' => 'Edit',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '03001112233',
            'emergency_contact_no' => '',
            'email' => 'edit'.$suffix.'@example.test',
            'dob' => '1988-03-10',
            'marital_status' => 'Single',
            'local_address' => 'Old address',
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
            'contract_type' => 'probation',
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

    public function test_staff_edit_form_and_update_persist(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $target = $this->createTargetStaff($suffix, $teacherRoleId);

        DB::table('sch_settings')->limit(1)->update(['staffid_auto_insert' => 0]);

        $this->get('/admin/staff/edit/'.$target->id)
            ->assertOk()
            ->assertSee('Before', false)
            ->assertSee('edit'.$suffix.'@example.test', false);

        $leaveTypeId = (int) DB::table('leave_types')->orderBy('id')->value('id');
        $sessionId = (int) DB::table('sch_settings')->value('session_id');
        $leavePayload = [];
        if ($leaveTypeId > 0 && $sessionId > 0) {
            $leavePayload = [
                'leave_type_id' => [$leaveTypeId],
                'alloted_leave' => [12],
                'altid' => [''],
            ];
        }

        $this->post('/admin/staff/edit/'.$target->id, array_merge([
            'employee_id' => 'EDT-UPD-'.$suffix,
            'role' => $teacherRoleId,
            'name' => 'After',
            'surname' => 'Updated',
            'gender' => 'Female',
            'dob' => '1988-03-10',
            'email' => 'updated'.$suffix.'@example.test',
            'contactno' => '03009998877',
            'contract_type' => 'permanent',
            'address' => 'New address',
        ], $leavePayload))
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('After', $target->name);
        $this->assertSame('Updated', $target->surname);
        $this->assertSame('Female', $target->gender);
        $this->assertSame('updated'.$suffix.'@example.test', $target->email);
        $this->assertSame('EDT-UPD-'.$suffix, $target->employee_id);
        $this->assertSame('New address', $target->local_address);
        $this->assertSame('permanent', $target->contract_type);

        if ($leaveTypeId > 0 && $sessionId > 0) {
            $leaveRow = DB::table('staff_leave_details')
                ->where('staff_id', $target->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('session_id', $sessionId)
                ->first();
            $this->assertNotNull($leaveRow);
            $this->assertSame('12.00', number_format((float) $leaveRow->alloted_leave, 2, '.', ''));
        }
    }

    public function test_staff_edit_blocks_other_superadmin_record(): void
    {
        $superRoleId = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $otherSuperId = DB::table('staff')->insertGetId([
            'employee_id' => 'OTH-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Other',
            'surname' => 'Super',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'other'.$suffix.'@example.test',
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
            'staff_id' => $otherSuperId,
            'role_id' => $superRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $otherSuperId;

        $this->get('/admin/staff/edit/'.$otherSuperId)->assertForbidden();
    }
}
