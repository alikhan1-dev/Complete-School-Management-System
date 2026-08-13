<?php

namespace Tests\Feature\Leave;

use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveAdminFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupRequestIds = [];

    /** @var list<int> */
    private array $cleanupDetailIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRequestIds !== []) {
            DB::table('staff_leave_request')->whereIn('id', $this->cleanupRequestIds)->delete();
        }
        $this->cleanupRequestIds = [];

        if ($this->cleanupDetailIds !== []) {
            DB::table('staff_leave_details')->whereIn('id', $this->cleanupDetailIds)->delete();
        }
        $this->cleanupDetailIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('leave_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

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

        $token = uniqid('leave', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LV-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Leave',
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
            'basic_salary' => 0,
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

        return $staffId;
    }

    private function createTargetStaff(int $roleId): int
    {
        $token = uniqid('lve', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EMP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Leave',
            'surname' => 'Target',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1991-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Female',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'basic_salary' => 0,
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

        return $staffId;
    }

    private function currentSessionId(): int
    {
        $id = (int) (DB::table('sch_settings')->value('session_id') ?? 0);
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function test_leave_type_crud(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/leavetypes')->assertOk()->assertSee('Leave Type List', false);

        $this->post('/admin/leavetypes/createleavetype', [
            'type' => 'Casual '.$suffix,
        ])->assertRedirect('/admin/leavetypes');

        $type = LeaveType::query()->where('type', 'Casual '.$suffix)->firstOrFail();
        $this->cleanupTypeIds[] = (int) $type->id;
        $this->assertSame('yes', $type->is_active);

        $this->get('/admin/leavetypes/leaveedit/'.$type->id)
            ->assertOk()
            ->assertSee('Edit Leave Type', false);

        $this->post('/admin/leavetypes/createleavetype', [
            'type' => 'Casual Updated '.$suffix,
            'leavetypeid' => $type->id,
        ])->assertRedirect('/admin/leavetypes');

        $type->refresh();
        $this->assertSame('Casual Updated '.$suffix, $type->type);

        $this->get('/admin/leavetypes/leavedelete/'.$type->id)->assertRedirect('/admin/leavetypes');
        $this->assertNull(LeaveType::query()->find($type->id));
        $this->cleanupTypeIds = array_values(array_filter(
            $this->cleanupTypeIds,
            fn ($id) => $id !== (int) $type->id
        ));
    }

    public function test_staff_leave_request_create_approve_and_delete(): void
    {
        $adminId = $this->actingAsSuperAdmin();
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $adminId)->value('role_id');
        $staffId = $this->createTargetStaff($roleId);
        $sessionId = $this->currentSessionId();
        $suffix = uniqid();

        $typeId = (int) DB::table('leave_types')->insertGetId([
            'type' => 'Sick '.$suffix,
            'is_active' => 'yes',
        ]);
        $this->cleanupTypeIds[] = $typeId;

        $detailId = (int) DB::table('staff_leave_details')->insertGetId([
            'staff_id' => $staffId,
            'leave_type_id' => $typeId,
            'alloted_leave' => 10,
            'session_id' => $sessionId,
        ]);
        $this->cleanupDetailIds[] = $detailId;

        $this->get('/admin/leaverequest/leaverequest')
            ->assertOk()
            ->assertSee('Approve Leave Request', false);

        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+1 day'));

        $this->post('/admin/leaverequest/addLeave', [
            'role' => $roleId,
            'empname' => $staffId,
            'applieddate' => $from,
            'leave_from_date' => $from,
            'leave_to_date' => $to,
            'leave_type' => $typeId,
            'reason' => 'Fever',
            'remark' => 'OK',
            'addstatus' => 'pending',
        ])->assertRedirect('/admin/leaverequest/leaverequest');

        $req = StaffLeaveRequest::query()
            ->where('staff_id', $staffId)
            ->where('leave_type_id', $typeId)
            ->firstOrFail();
        $this->cleanupRequestIds[] = (int) $req->id;
        $this->assertSame('pending', $req->status);
        $this->assertEquals(2.0, (float) $req->leave_days);

        $this->post('/admin/leaverequest/leaveStatus/'.$req->id, [
            'status' => 'approved',
            'detailremark' => 'Approved by admin',
        ])->assertRedirect('/admin/leaverequest/leaverequest');

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertNotNull($req->approve_date);

        $this->get('/admin/leaverequest/view/'.$req->id)
            ->assertOk()
            ->assertSee('Leave Details', false);

        $this->get('/admin/leaverequest/remove/'.$req->id.'/'.$staffId)->assertRedirect();
        $this->assertNull(StaffLeaveRequest::query()->find($req->id));
        $this->cleanupRequestIds = [];

        $this->get('/migration-status/leave')
            ->assertOk()
            ->assertJsonPath('slices.leave_types', 'done');
    }
}
